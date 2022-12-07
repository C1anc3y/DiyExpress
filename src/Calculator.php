<?php

/**
 * @File    :   Calculator.php
 * @Author  :   ClanceyHuang
 * @Refer   :   unknown
 * @Desc    :   ...
 * @Version :   PHP7.x
 * @Contact :   ClanceyHuang@outlook.com
 * @Site    :   http://debug.cool
 */


namespace DiyExpress;

use DiyExpress\AST\NodeType;
use DiyExpress\Formula\SimpleFormula;
use DiyExpress\Parser\SimpleParser;
use DiyExpress\VM\SimpleVM;

class Calculator
{
    const MAX_LEVEL = 1000; # 最深层级，执行到该层级还未计算出结果，直接 return 0

    /**
     * 验证表达式
     * @param $script_list
     * @return array|false
     * @throws \Exception
     */
    public function verifyFormFormula($script_list)
    {
        if (!$script_list) {
            return false;
        }
        $result = [];
        foreach ($script_list as $index => &$script) { # 任意一条出错，直接返回错误提示
            $script = str_replace('\'', "'", $script);
            $script = str_replace('\"', '"', $script);
            # 必须要进入循环重新实例化，否则原有的数据会对后面的公式对象造成干扰。
            $parser = new SimpleParser();
            $vm = new SimpleVM();
            $nodeList = null;
            $nodeList = $parser->parse($script);
            $res_field = $nodeList['field'];
            if ($res_field) {
                $script_key = array_shift($res_field);
                $result[$script_key] = [
                    'formula' => $script,
                    'child' => $nodeList['field']
                ];
            }
            # 验证用的随机给变量赋值
            $check_data_list = [];
            foreach ($nodeList['data'] as $node_v) {
                if (in_array($node_v->structText, $res_field)) {
                    if (!isset($check_data_list[$node_v->structText])) {
                        $rand = rand(100, 999); # 构造随机验证数据
                        $check_data_list[$node_v->structText] = $rand;
                        $node_v->structText = $rand;
                    } else {
                        $node_v->structText = $check_data_list[$node_v->structText];
                    }
                }
            }
            unset($res_field);
            $vmRes = $vm->scanNodeList($nodeList['data']);
            if ($vmRes['error_msg']) {
                $result =  false;
            }
            $treeList = $vmRes['nodeList'];
            $treeList = $this->dealWithAdapt($treeList);
            if (!$treeList) {
                $result = false;
            }
            if (!$result) {
                break;
            }
            unset($script);
        }
        return $result;
    }

    /**
     * 执行表达式
     * @param string $token 自定义表达式，其中的变量需要用双花阔号包起来
     * @param array $data 已知变量的赋值数组
     * @param string|int $rule_result_filed_id 表达式的结果字段id或者标识
     * @return mixed
     */
    public function executeFormFormula($token, $data, $rule_result_field_id)
    {

        $result = 0;
        if (!$token || !$data || !$rule_result_field_id) {
            return $result;
        }
        $parser = new SimpleParser();
        $vm = new SimpleVM();
        $nodeList = null;
        $res_str = $token;

        # 先把结果标记处理掉
        $res_str = str_replace('{{' . $rule_result_field_id . '}}', '结果', $res_str);
        // breakpoint log，输出替换结果标识后的表达式
        foreach ($data as $key => $val) {
            $res_str = str_replace('{{' . $key . '}}', $val ?: 0, $res_str);
        }
        $nodeList = $parser->parse($res_str);
        $vmRes = $vm->scanNodeList($nodeList['data']);
        if ($vmRes['error_msg']) {
            return $result;
        }

        $treeList = $vmRes['nodeList'];
        $treeList = $this->dealWithAdapt($treeList);
        if (is_numeric($treeList)) {
            $result = $treeList;
        } elseif (!is_null($treeList)) {
            $result = $treeList;
        }
        return $result;
    }

    /**
     * 计算的入口中转器
     * @param $nodeList
     * @return int|string
     */
    public function dealWithAdapt(&$nodeList)
    {
        $max_level = self::MAX_LEVEL;
        for ($i = 0; $i <= $max_level; $i++) {
            if (is_object($nodeList) || (is_array($nodeList) && count($nodeList) > 1)) {
                $nodeList = $this->dealWithOpera($nodeList);
            } else {
                break;
            }
        }
        if (is_numeric($nodeList)) {
            return $nodeList ?: 0;
        } elseif (is_string($nodeList)) {
            return $nodeList ?: "";
        } else {
            return 0;
        }
    }


    /**
     * calculator tree
     * @param $nodeList
     * @param int $level
     * @return array|int
     */
    public function dealWithOpera(&$nodeList, $level = 20)
    {
        $astNodeType = new NodeType();
        $simpleScript = new SimpleFormula();
        $first_cal_tag = 1;
        $first_op_list  = [];

        if (is_array($nodeList)) {
            foreach ($nodeList as $first_k => &$first_v) {
                if (is_numeric($first_k)) {
                    if (is_array($first_v)) {
                        $first_cal_tag = 0;
                        if (count($first_v) == 1) {
                            $first_v = array_pop($first_v);
                        } else {
                            $first_v = $this->dealWithOpera($first_v);
                        }
                    } elseif (is_object($first_v)) {
                        $first_v_text = $first_v->structText;
                        if (is_array($first_v_text)) {
                            $first_cal_tag = 0;
                            if (count($first_v_text) == 1) {
                                $first_v->structText = array_pop($first_v_text);
                            }
                            $first_v->structText = $this->dealWithOpera($first_v->structText);
                        } else {
                            if ($first_cal_tag) {
                                $first_op_list[] = $first_v;
                            } else {
                                $first_op_list = []; # 清空
                            }
                        }
                    } elseif (is_numeric($first_v)) {
                        $first_op_list[] = (object)['structType' => 'RESULT', 'structText' => $first_v];
                    } else {
                        continue;
                    }
                } else {
                    continue;
                }
            }
            if ($first_cal_tag) {
                # 没有下级，直接计算当前的字面量
                $first_op_tag_data = $first_op_list[1];
                $first_op_left_data = $first_op_list[0];
                $first_op_right_data = $first_op_list[2];
                $first_op_tag_type = $first_op_tag_data->structType;
                if (in_array($first_op_tag_type, array_merge($astNodeType->high_tag, $astNodeType->low_tag, $astNodeType->compare_tag, $astNodeType->logic_tag))) {
                    $first_op_left_text = $first_op_left_data->structText;
                    $first_op_right_text = $first_op_right_data->structText;
                    $first_res = $simpleScript->calculator(
                        $first_op_tag_type,
                        ['left' => $first_op_left_text, 'right' => $first_op_right_text]
                    );
                    $nodeList = $first_res;
                }
            }

            // # 循环处理if的结果
            $if_cal_tag = 1;
            $if_op_list  = [];
            foreach ($nodeList as $if_k => &$if_v) {
                if (in_array($if_k, ['condition', 'then', 'else'])) {
                    if (is_array($if_v)) {
                        $if_cal_tag = 0;
                        if (count($if_v) == 1) {
                            $if_v = array_pop($if_v);
                        }
                        $if_v = $this->dealWithOpera($if_v);
                    } elseif (is_object($if_v)) {
                        $if_v_text = $if_v->structText;
                        if (is_array($if_v_text)) {
                            $if_cal_tag = 0;
                            $if_v->structText = $this->dealWithOpera($if_v->structText);
                        } else {
                            if (!$if_cal_tag) {
                                $if_op_list = [];
                            } else {
                                $if_op_list[$if_k] = $if_v;
                            }
                        }
                    } elseif (is_numeric($if_v)) {
                        $if_op_list[$if_k] = (object)['structType' => 'RESULT', 'structText' => $if_v];
                    } else {
                        continue;
                    }
                }
            }

            if (
                is_array($nodeList)
                && count($nodeList) == 3
                && isset($nodeList['condition'])
                && !isset($nodeList['condition'][0])
                && isset($nodeList['then'])
                && isset($nodeList['else'])
            ) {
                if ($nodeList['condition']) {
                    $nodeList = $nodeList['then'];
                } else {
                    $nodeList = $nodeList['else'];
                }
            }
            if ($if_cal_tag) {
                # 没有下级，直接计算当前的字面量
                $if_condition = $if_op_list['condition'] ?: null;
                $if_then = $if_op_list['then'] ?: null;
                $if_else = $if_op_list['else'] ?: null;
                if (!is_null($if_condition) && !is_null($if_then) && !is_null($if_else)) {
                    $condition_text = $if_condition->structText;
                    $then_text = $if_then->structText;
                    $else_text = $if_else->structText;
                    $if_res = $simpleScript->calculator($astNodeType::IF_STMT, [
                        'condition' => $condition_text,
                        'then' => $then_text,
                        'else' => $else_text,
                    ]);
                    $nodeList = $if_res;
                }
            }
        } elseif (is_object($nodeList)) {
            $first_v_text = $nodeList->structText;
            if (is_array($first_v_text)) {
                if (count($first_v_text) == 1) {
                    $nodeList = array_pop($first_v_text);
                }
                $nodeList = $this->dealWithOpera($first_v_text);
            } else {
                $nodeList = $nodeList->structText;
            }
        }
        return $nodeList;
    }
}
