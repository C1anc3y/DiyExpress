<?php
/**
 * @File    :   SimpleVM.php
 * @Author  :   ClanceyHuang
 * @Refer   :   unknown
 * @Desc    :   ...
 * @Version :   PHP7.x
 * @Contact :   ClanceyHuang@outlook.com
 * @Site    :   http://debug.cool
 */


namespace DiyExpress\VM;
use DiyExpress\AST\NodeType;
use DiyExpress\Formula\IFormulaNode;

class SimpleVM{
    /**
     * 扫描节点列表，并构造匹配对应的表达式
     * @param $nodeList
     * @return array
     */
    public function scanNodeList($nodeList): array
    {
        if (empty($nodeList)) {
            $error_msg[] = "公式扫描错误";
        }

        $result_flag = null;
        $assign_flag = null;
        $nodeList_dirty = $nodeList;
        $astNodeType = new NodeType();
        # 优先if() => 内容替换为ifStmt
        # 其他函数预留，如sum() => 内容替换为 sumStmt
        # *,/
        # +,-
        $dirty_node_list_count = count($nodeList_dirty); # 节点数组长度
        # 处理的result和assign
        if (is_null($result_flag) || is_null($assign_flag)) {
            foreach ($nodeList_dirty as $dirty_result_key => &$dirty_result_value) {
                if ($dirty_result_value->structType == $astNodeType::RESULT_STMT) {
                    if (!$result_flag) {
                        unset($nodeList_dirty[$dirty_result_key]); # 用过就销毁
                        $result_flag = true;
                    } else {
                        $error_msg[] = "公式存在多个结果值";
                    }
                }
            }

            # 处理assign等号问题
            foreach ($nodeList_dirty as $dirty_assign_key => &$dirty_assign_value) {
                if ($dirty_assign_value->structType == $astNodeType::ASSIGNMENT_STMT) {
                    if (!$assign_flag) {
                        $assign_flag = true;
                        unset($nodeList_dirty[$dirty_assign_key]); # 用过就销毁
                    } else {
                        $error_msg[] = "公式存在多个结果等号";
                    }
                }
            }
        }

        # 处理if层级，连带if的括号也处理掉，以免造成括号优先级的干扰 DONE
        foreach ($nodeList_dirty as $dirty_if_key => &$dirty_if_value) {
            if ($dirty_if_value->structType == $astNodeType::IF_STMT) {
                $if_stmt_tmp = []; # 每遇到一个if就初始化一次。
                $if_condition_tmp = $if_then_tmp = $if_else_tmp = [];
                $if_current_key_tmp = $dirty_if_key;
                $if_current_id = $dirty_if_value->structId;
                $if_current_pid = $dirty_if_value->structPid;
                $if_current_level = $dirty_if_value->structLevel;
                $if_index_pre_tag = $if_index_suffix = null; # 初始化标记索引
                # 找到该层级下，if后续的开闭括号，中间的内容用一个变量代替。并销毁nodeList_dirty中的该数据
                for ($if_current_key = $if_current_key_tmp; $if_current_key <= $dirty_node_list_count; $if_current_key++) {
                    $find_if_value = $nodeList_dirty[$if_current_key];
                    $find_if_level = $find_if_value->structLevel;
                    $find_if_type = $find_if_value->structType;
                    $find_if_text = $find_if_value->structText;
                    # 因为if后的括号在下一层,
                    # 因为if和开括号是连结的所以这里可以直接level和key分别+1进行判定，
                    #   其他地方不可这样操作，因为原数组是一直在unset的

                    if ($find_if_level == $if_current_level + 1
                        && $if_current_key == $dirty_if_key + 1
                        && $find_if_type == $astNodeType::SMALL_BRACKET
                        && $find_if_text == '(') {
                        # $if_stmt_tmp[$if_current_key] = $find_if_value; # if的左括号不需要入临时数组
                        # 记录一个需要开始的flag
                        unset($nodeList_dirty[$if_current_key]);
                        $if_index_pre_tag = true;
                    } else if ($find_if_level == $if_current_level + 1
                        && $find_if_type == $astNodeType::SMALL_BRACKET
                        && $find_if_text == ')') {
                        # 记录一个结束的flag，并在break前入临时队列
                        # $if_stmt_tmp[$if_current_key] = $find_if_value; # if 的右括号不需要入临时数组
                        unset($nodeList_dirty[$if_current_key]);
                        $if_index_suffix = $if_current_key;
                        break;
                    } elseif (is_null($if_index_suffix)) {
                        $if_stmt_tmp[] = $find_if_value;
                        unset($nodeList_dirty[$if_current_key]);
                    } else {
                        continue;
                    }
                }
                if (is_null($if_index_pre_tag) || is_null($if_index_suffix)) {
                    $error_msg[] = "if表达式错误";
                }
                # 处理condition、then、else
                $ifExpress_data = $this->dealIfExpress($if_stmt_tmp);
                if ($ifExpress_data['error_msg']){
                    return ['error_msg'=>$ifExpress_data['error_msg']];
                }

                $if_condition_tmp = $ifExpress_data['condition'];
                $if_then_tmp = $ifExpress_data['then'];
                $if_else_tmp = $ifExpress_data['else'];

                $if_condition_res_tmp = $this->scanChildStmt($if_condition_tmp);
                if ($if_condition_res_tmp['error_msg']) {
                    return ['error_msg' => $if_condition_res_tmp['error_msg']];
                }
                $if_then_res_tmp = $this->scanChildStmt($if_then_tmp);
                if ($if_then_res_tmp['error_msg']) {
                    return ['error_msg' => $if_then_res_tmp['error_msg']];
                }
                $if_else_res_tmp = $this->scanChildStmt($if_else_tmp);
                if ($if_else_res_tmp['error_msg']) {
                    return ['error_msg' => $if_else_res_tmp['error_msg']];
                }

                $if_result['condition'] = $if_condition_res_tmp['nodeList'];
                $if_result['then'] = $if_then_res_tmp['nodeList'];
                $if_result['else'] = $if_else_res_tmp['nodeList'];
                $nodeList_dirty[$dirty_if_key] = new IFormulaNode('ifExpress', $if_result,$if_current_id,$if_current_pid,$if_current_level);
                ksort($nodeList_dirty);
            } else {
                continue;
            }
        }
        unset($dirty_if_value);
        // TODO else diy function logic wait... like this, sum(),avg(),...

        # 到这里，自定义的函数都处理完后，现在用两个堆栈，来处理对应值，构造成后缀表达式
        # 处理括号的优先级
        foreach ($nodeList_dirty as $bracket_key => &$bracket_value) {
            $bracket_type = $bracket_value->structType;
            $bracket_text = $bracket_value->structText;
            $bracket_level = $bracket_value->structLevel;
            $bracket_id = $bracket_value->structId;
            $bracket_pid = $bracket_value->structPid;

            $bracket_tmp = [];
            $bracket_close = false;
            # 处理开口括号，并且同级获取闭合括号，中间的内容作为括号表达式
            if ($bracket_type == $astNodeType::SMALL_BRACKET && $bracket_text == "(") {
                for ($bracket_current_key = $bracket_key; $bracket_current_key <= $dirty_node_list_count; $bracket_current_key++) {
                    $find_bracket_value = $nodeList_dirty[$bracket_current_key];
                    $find_bracket_text = $find_bracket_value->structText;
                    $find_bracket_type = $find_bracket_value->structType;
                    $find_bracket_level = $find_bracket_value->structLevel;

                    if (!$bracket_close){
                        # 闭合括号不处理,只记录一个标记
                        if ($find_bracket_level == $bracket_level
                            && $find_bracket_type == $astNodeType::SMALL_BRACKET
                            && $find_bracket_text == ")"
                        ){
                            $bracket_close = true;
                            unset($nodeList_dirty[$bracket_current_key]);
//                            pd($bracket_tmp);
                            $bracket_result_data = $this->scanChildStmt($bracket_tmp);
//                            pd($bracket_result_data);
                            $bracket_result = $bracket_result_data['nodeList'];
                            if ($bracket_result_data['error_msg']) {
                                return ['error_msg' => $bracket_result_data['error_msg']];
                            }
                            $nodeList_dirty[$bracket_key] = new IFormulaNode('bracketExpress', $bracket_result,$bracket_id,$bracket_pid,$bracket_level);
//                            $bracket_value = new IFormulaNode('bracketExpress', $bracket_result,$bracket_id,$bracket_pid,$bracket_level);
                            continue;
                        }
                        elseif ($find_bracket_level == $bracket_level
                            && $find_bracket_type == $astNodeType::SMALL_BRACKET
                            && $find_bracket_text == '('
                        ){
                            unset($nodeList_dirty[$bracket_current_key]);
                            continue;
                        }
                        else{
                            $bracket_tmp[] = $find_bracket_value;
                            unset($nodeList_dirty[$bracket_current_key]);
                        }
                    }
                }
                if (!$bracket_close) {
                    $error_msg[] = "闭合括号缺失";
                }
                $bracket_result = $this->scanChildStmt($bracket_tmp);
                if ($bracket_result['error_msg']) {
                    return ['error_msg' => $bracket_result['error_msg']];
                }
//                $nodeList[$bracket_key] = new IFormulaNode('bracketExpress', $bracket_result['nodeList']);
                $nodeList_dirty[$bracket_key] = new IFormulaNode('bracketExpress', $bracket_result['nodeList']);
                ksort($nodeList_dirty);
            }
        }
        unset($bracket_value);

        # 处理 *,/
        $high_key_list = array_keys($nodeList_dirty);
        foreach ($high_key_list as $high_key_key => $high_key) {
            $high_value = $nodeList_dirty[$high_key];
            $high_type = $high_value->structType;
            if (in_array($high_type, $astNodeType->high_tag)) {
                $high_result_tmp = [];
                # 拿左右两侧的数值a和b以及操作tag，构造成a,b,tag这种后缀式
                $pre_high_key = $high_key_list[$high_key_key - 1] ?? null;
                $pre_high_value = $nodeList_dirty[$pre_high_key] ?: null;
                $next_high_key = $high_key_list[$high_key_key + 1] ?: null;
                $next_high_value = $nodeList_dirty[$next_high_key] ?: null;
                if ($pre_high_value && $next_high_value) {
                    $high_result_tmp[] = $pre_high_value;
                    $high_result_tmp[] = $high_value;
                    $high_result_tmp[] = $next_high_value;
                    unset($nodeList_dirty[$pre_high_key]);
                    unset($nodeList_dirty[$next_high_key]);
                    unset($nodeList_dirty[$high_key]);
                    $nodeList_dirty[$next_high_key] = $high_result_tmp;
                    ksort($nodeList_dirty);
                }
            } else {
                continue;
            }
        }

        # 处理 +,-
        $low_key_list = array_keys($nodeList_dirty);
        foreach ($low_key_list as $low_key_key => $low_key) {
            $low_value = $nodeList_dirty[$low_key];
            $low_type = $low_value->structType;
            if (in_array($low_type, $astNodeType->low_tag)) {
                $low_result_tmp = [];
                # 拿左右两侧的数值a和b以及操作tag，构造成a,b,tag这种后缀式
                $pre_low_key = $low_key_list[$low_key_key - 1] ?? null;
                $pre_low_value = $nodeList_dirty[$pre_low_key] ?: null;
                $next_low_key = $low_key_list[$low_key_key + 1] ?: null;
                $next_low_value = $nodeList_dirty[$next_low_key] ?: null;
                if ($pre_low_value && $next_low_value) {
                    $low_result_tmp[] = $pre_low_value;
                    $low_result_tmp[] = $low_value;
                    $low_result_tmp[] = $next_low_value;
                    unset($nodeList_dirty[$pre_low_key]);
                    unset($nodeList_dirty[$low_key]);
                    unset($nodeList_dirty[$next_low_key]);
                    $nodeList_dirty[$next_low_key] = $low_result_tmp;
                    ksort($nodeList_dirty);
                }
            } else {
                continue;
            }
        }

        # 处理 > >= < <= ==
        $compare_key_list = array_keys($nodeList_dirty);
        foreach ($compare_key_list as $compare_key_key => $compare_key){
            $compare_value = $nodeList_dirty[$compare_key];
            $compare_type = $compare_value->structType;
            if (in_array($compare_type, $astNodeType->compare_tag)) {
                $compare_result_tmp = [];
                $pre_compare_key = $compare_key_list[$compare_key_key - 1] ?? null;
                $pre_compare_value = $nodeList_dirty[$pre_compare_key] ?: null;

                $next_compare_key = $compare_key_list[$compare_key_key + 1] ?: null;
                $next_compare_value = $nodeList_dirty[$next_compare_key] ?: null;

                if ($pre_compare_value && $next_compare_value) {
                    $compare_result_tmp[] = $pre_compare_value;
                    $compare_result_tmp[] = $compare_value;
                    $compare_result_tmp[] = $next_compare_value;
                    unset($nodeList_dirty[$pre_compare_key]);
                    unset($nodeList_dirty[$compare_key]);
                    unset($nodeList_dirty[$next_compare_key]);
                    $nodeList_dirty[$next_compare_key] = $compare_result_tmp;
                    ksort($nodeList_dirty);
                }
            } else {
                continue;
            }
        }

        # 处理 and or
        $logic_key_list = array_keys($nodeList_dirty);
        foreach ($logic_key_list as $logic_key_key => $logic_key) {
            $logic_value = $nodeList_dirty[$logic_key];
            $logic_type = $logic_value->structType;
            if (in_array($logic_type, $astNodeType->logic_tag)) {
                $logic_result_tmp = [];
                $pre_logic_key = $logic_key_list[$logic_key_key - 1] ?? null;
                $pre_logic_value = $nodeList_dirty[$pre_logic_key] ?: null;

                $next_logic_key = $logic_key_list[$logic_key_key + 1] ?: null;
                $next_logic_value = $nodeList_dirty[$next_logic_key] ?: null;

                if ($pre_logic_value && $next_logic_value) {
                    $logic_result_tmp[] = $pre_logic_value;
                    $logic_result_tmp[] = $logic_value;
                    $logic_result_tmp[] = $next_logic_value;
                    unset($nodeList_dirty[$pre_logic_key]);
                    unset($nodeList_dirty[$logic_key]);
                    unset($nodeList_dirty[$next_logic_key]);
                    $nodeList_dirty[$next_logic_key] = $logic_result_tmp;
                    ksort($nodeList_dirty);
                }
            } else {
                continue;
            }
        }
        if ($error_msg) {
            return array('error_msg'=>$error_msg);
        }
        $result = array_values($nodeList_dirty)[0];
        return array('nodeList'=>$result);
    }

    # 处理子集表达式，并返回结果 DONE
    function scanChildStmt(&$nodeList)
    {
        if (empty($nodeList)) {
            return $nodeList;
        }

        $node_list_count = count($nodeList);
        $astNodeType = new NodeType();
        # 处理if
        foreach ($nodeList as $if_key => &$if_value) {
            if ($if_value->structType == $astNodeType::IF_STMT) {
                $if_stmt_tmp = []; # 每遇到一个if，就初始化一次。
                $if_condition_tmp = $if_then_tmp = $if_else_tmp = [];
                $if_current_key_tmp = $if_key;
                $if_current_level = $if_value->structLevel;
                $if_current_id = $if_value->structId;
                $if_current_pid = $if_value->structPid;

                $if_index_pre_tag = $if_index_suffix = null; # 初始化标记索引
                # 找到该层级下，if后续的开闭括号，中间的内容用一个变量代替。并销毁nodeList_dirty中的该数据
                for ($if_current_key = $if_current_key_tmp; $if_current_key <= $node_list_count; $if_current_key++) {
                    $find_if_value = $nodeList[$if_current_key];
                    $find_if_level = $find_if_value->structLevel;
                    $find_if_type = $find_if_value->structType;
                    $find_if_text = $find_if_value->structText;
                    # 因为if后的括号在下一层,
                    # 因为if和开括号是连结的所以这里可以直接level和key分别+1进行判定，
                    #   其他地方不可这样操作，因为原数组是一直在unset的

                    if ($find_if_level == $if_current_level + 1
                        && $if_current_key == $if_key + 1
                        && $find_if_type == $astNodeType::SMALL_BRACKET
                        && $find_if_text == '(') {
                        # $if_stmt_tmp[$if_current_key] = $find_if_value; # if的左括号不需要入临时数组
                        # 记录一个需要开始的flag
                        unset($nodeList[$if_current_key]);
                        $if_index_pre_tag = true;
                    } else if ($find_if_level == $if_current_level + 1
                        && $find_if_type == $astNodeType::SMALL_BRACKET
                        && $find_if_text == ')') {
                        # 记录一个结束的flag，并在break前入临时队列
                        # $if_stmt_tmp[$if_current_key] = $find_if_value; # if 的右括号不需要入临时数组
                        unset($nodeList[$if_current_key]);
                        $if_index_suffix = $if_current_key;
                        break;
                    } elseif (is_null($if_index_suffix)) {
                        $if_stmt_tmp[] = $find_if_value;
                        unset($nodeList[$if_current_key]);
                    } else {
                        continue;
                    }
                }
                if (is_null($if_index_pre_tag) || is_null($if_index_suffix)) {
                    $error_msg[] = 'if条件语句匹配错误';
                    return ['error_msg' => $error_msg];
                }

                # 处理condition、then、else
                $ifExpress_data = $this->dealIfExpress($if_stmt_tmp);
                if ($ifExpress_data['error_msg']){
                    return array('error_msg'=>$ifExpress_data['error_msg']);
                }

                $if_condition_tmp = $ifExpress_data['condition'];
                $if_then_tmp = $ifExpress_data['then'];
                $if_else_tmp = $ifExpress_data['else'];

                $if_condition_res_tmp = $this->scanChildStmt($if_condition_tmp);
                if ($if_condition_res_tmp['error_msg']) {
                    return ['error_msg' => $if_condition_res_tmp['error_msg']];
                }

                $if_then_res_tmp = $this->scanChildStmt($if_then_tmp);
                if ($if_then_res_tmp['error_msg']) {
                    return $if_then_res_tmp['error_msg'];
                }

                $if_else_res_tmp = $this->scanChildStmt($if_else_tmp);
                if ($if_else_res_tmp['error_msg']) {
                    return $if_else_res_tmp['error_msg'];
                }

                $if_result['condition'] = $if_condition_res_tmp['nodeList'];
                $if_result['then'] = $if_then_res_tmp['nodeList'];
                $if_result['else'] = $if_else_res_tmp['nodeList'];
                $nodeList[$if_key] = new IFormulaNode('ifExpress', $if_result,$if_current_id,$if_current_pid,$if_current_level);
                ksort($nodeList);
            } else {
                continue;
            }
        }
        unset($if_value);

        // todo else diy func like sum\avg\min\max...  wait next opt version.
        foreach ($nodeList as $bracket_key => &$bracket_value) {
            $bracket_type = $bracket_value->structType;
            $bracket_text = $bracket_value->structText;
            $bracket_level = $bracket_value->structLevel;
            $bracket_id = $bracket_value->structId;
            $bracket_pid = $bracket_value->structPid;


            # 处理开口括号，并且同级获取闭合括号，中间的内容作为括号表达式
            if ($bracket_type == $astNodeType::SMALL_BRACKET && $bracket_text == "(") {

                $bracket_tmp = [];
                $bracket_close = false;
                for ($bracket_current_key = $bracket_key; $bracket_current_key <= $node_list_count; $bracket_current_key++) {
                    $find_bracket_value = $nodeList[$bracket_current_key];
                    $find_bracket_text = $find_bracket_value->structText;
                    $find_bracket_type = $find_bracket_value->structType;
                    $find_bracket_level = $find_bracket_value->structLevel;

                    if (!$bracket_close){
                        # 闭合括号不处理,只记录一个标记
                        if ($find_bracket_level == $bracket_level && $find_bracket_type == $astNodeType::SMALL_BRACKET && $find_bracket_text == ")"){
                            $bracket_close = true;
                            unset($nodeList[$bracket_current_key]);
                            $bracket_result_data = $this->scanChildStmt($bracket_tmp);
                            $bracket_result = $bracket_result_data['nodeList'];
                            if ($bracket_result_data['error_msg']) {
                                return ['error_msg' => $bracket_result_data['error_msg']];
                            }
                            $nodeList[$bracket_key] = new IFormulaNode('bracketExpress', $bracket_result,$bracket_id,$bracket_pid,$bracket_level);
                            continue;
                        }
                        elseif ($find_bracket_level == $bracket_level && $find_bracket_type == $astNodeType::SMALL_BRACKET && $find_bracket_text == '('){
                            unset($nodeList[$bracket_current_key]);
                            continue;
                        }
                        else{
                            $bracket_tmp[] = $find_bracket_value;
                            unset($nodeList[$bracket_current_key]);
                        }
                    }
                }
                if (!$bracket_close) {
                    $error_msg[] = "闭合括号缺失";
                }
            }
        }
        ksort($nodeList);
        unset($bracket_value);

        $high_key_list = array_keys($nodeList);
        foreach ($high_key_list as $high_key_key => $high_key) {
            $high_value = $nodeList[$high_key];
            $high_type = $high_value->structType;
            if (in_array($high_type, $astNodeType->high_tag)) {
                $high_result_tmp = [];
                $pre_high_key = $high_key_list[$high_key - 1] ?? null;
                $pre_high_value = $nodeList[$pre_high_key] ?: null;
                $next_high_key = $high_key_list[$high_key + 1] ?: null;
                $next_high_value = $nodeList[$next_high_key] ?: null;
                if ($pre_high_value && $next_high_value) {
                    $high_result_tmp[] = $pre_high_value;
                    $high_result_tmp[] = $high_value;
                    $high_result_tmp[] = $next_high_value;

                    unset($nodeList[$pre_high_key]);
                    unset($nodeList[$next_high_key]);
                    unset($nodeList[$high_key]);
                    $nodeList[$next_high_key] = $high_result_tmp;
                    ksort($nodeList);
                }
            } else {
                continue;
            }
        }

        # 处理 +,- DONE
        $low_key_list = array_keys($nodeList);
        foreach ($low_key_list as $low_key_key => $low_key) {
            $dirty_low_value = $nodeList[$low_key];
            if (in_array($dirty_low_value->structType, $astNodeType->low_tag)) {
                $result_tmp = [];
                # 拿左右两侧的数值a和b以及操作tag，构造成a,b,tag这种后缀式
                $pre_low_key = $low_key_list[$low_key_key - 1] ?? null;
                $pre_low_value = $nodeList[$pre_low_key] ?: null;

                $next_low_key = $low_key_list[$low_key_key + 1] ?: null;
                $next_low_value = $nodeList[$next_low_key] ?: null;

                if ($pre_low_value && $next_low_value) {
                    $result_tmp[] = $pre_low_value;
                    $result_tmp[] = $dirty_low_value;
                    $result_tmp[] = $next_low_value;

                    unset($nodeList[$pre_low_key]);
                    unset($nodeList[$next_low_key]);
                    unset($nodeList[$low_key]);
                    $nodeList[$next_low_key] = $result_tmp;
                    ksort($nodeList);
                }
            } else {
                continue;
            }
        }

        # 处理 比较符 > >= < <= == DONE
        $compare_key_list = array_keys($nodeList);
        foreach ($compare_key_list as $compare_key_key => $compare_key){
            $compare_value = $nodeList[$compare_key];
            $compare_type = $compare_value->structType;
            if (in_array($compare_type, $astNodeType->compare_tag)) {
                $compare_result_tmp = [];
                $pre_compare_key = $compare_key_list[$compare_key_key - 1] ?? null;
                $pre_compare_value = $nodeList[$pre_compare_key] ?: null;

                $next_compare_key = $compare_key_list[$compare_key_key + 1] ?: null;
                $next_compare_value = $nodeList[$next_compare_key] ?: null;

                if ($pre_compare_value && $next_compare_value) {
                    $compare_result_tmp[] = $pre_compare_value;
                    $compare_result_tmp[] = $compare_value;
                    $compare_result_tmp[] = $next_compare_value;
                    unset($nodeList[$pre_compare_key]);
                    unset($nodeList[$compare_key]);
                    unset($nodeList[$next_compare_key]);
                    $nodeList[$next_compare_key] = $compare_result_tmp;
                    ksort($nodeList);
                }
            } else {
                continue;
            }
        }

        # 处理逻辑符 and or
        $logic_key_list = array_keys($nodeList);
        foreach ($logic_key_list as $logic_key_key => $logic_key) {
            $logic_value = $nodeList[$logic_key];
            $logic_type = $logic_value->structType;
            if (in_array($logic_type, $astNodeType->logic_tag)) {
                $logic_result_tmp = [];
                $pre_logic_key = $logic_key_list[$logic_key_key - 1] ?? null;
                $pre_logic_value = $nodeList[$pre_logic_key] ?: null;
                $next_logic_key = $logic_key_list[$logic_key_key + 1] ?: null;
                $next_logic_value = $nodeList[$next_logic_key] ?: null;
                if ($pre_logic_value && $next_logic_value) {
                    $logic_result_tmp[] = $pre_logic_value;
                    $logic_result_tmp[] = $logic_value;
                    $logic_result_tmp[] = $next_logic_value;
                    unset($nodeList[$pre_logic_key]);
                    unset($nodeList[$next_logic_key]);
                    unset($nodeList[$logic_key]);
                    $nodeList[$next_logic_key] = $logic_result_tmp;
                    ksort($nodeList);
                }
            } else {
                continue;
            }
        }
        return ['nodeList' => $nodeList, 'error_msg' => $error_msg ?: array()];
    }

    # 处理if表达式，切割condition、then、else
    function dealIfExpress($ifExpress): array
    {
        $ifSign = $ifExpress[0];
        $ifSignLevel = $ifSign->structLevel;

        $condition_list = [];
        $then_list = [];
        $else_list = [];
        $comma = 0;
        $list_tmp = [];
        foreach ($ifExpress as $if_key => $if_value) {
            if ($if_key > 0) {
                if ($if_value->structLevel == $ifSignLevel + 1 && $if_value->structType == NodeType::COMMA) {
                    if ($comma == 0) {
                        # 遇到第一个逗号前，都是condition
                        $condition_list = $list_tmp;
                        $comma = $comma + 1;
                        $list_tmp = [];
                    } elseif ($comma == 1) {
                        # 遇到第二个逗号前，都是then
                        $then_list = $list_tmp;
                        $comma = $comma + 1;
                        $list_tmp = [];

                    } else {
//                        # 之后的都是else
                        $comma = $comma + 1;
                        $list_tmp = [];
                    }
                } else {
                    $list_tmp[] = $if_value;
                    if ($comma == 2) {
                        $else_list = $list_tmp;
                    }
                }
            } else {
                continue;
            }
        }
        $condition_data = $this->scanChildStmt($condition_list);
        if ($condition_data['error_msg']){
            return ['error_msg'=>$condition_data['error_msg']];
        }
        $then_data = $this->scanChildStmt($then_list);
        if ($then_data['error_msg']){
            return ['error_msg'=>$then_data['error_msg']];
        }
        $else_data =  $this->scanChildStmt($else_list);
        if ($else_data['error_msg']){
            return ['error_msg'=>$else_data['error_msg']];
        }
        return ['condition'=>$condition_data['nodeList'],'then'=>$then_data['nodeList'] ,'else'=>$else_data['nodeList']];
    }


}