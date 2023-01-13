<?php

namespace DiyExpress\Formula;

use DiyExpress\AST\NodeType;
use DiyExpress\Exception\RuntimeException;

class SimpleFormula
{

    /**
     * @var array 构造一个初始化的执行器
     */
    public array $opera_struct_list = [
        NodeType::ADDITION, # 加法
        NodeType::SUBTRACTION, # 减法
        NodeType::MULTIPLICATION, # 乘法
        NodeType::DIVISION, # 除法
        NodeType::COMPARE_STMT_EQ, # ==
        NodeType::COMPARE_STMT_GE, # >=
        NodeType::COMPARE_STMT_GT, # >
        NodeType::COMPARE_STMT_LT, # <
        NodeType::COMPARE_STMT_LE, # <=
        NodeType::ID_AND, # AND
        NodeType::ID_OR,  # OR
    ];


    public array $func_struct_list = [
        NodeType::IF_STMT,
//        NodeType::SUM_STMT,
//        NodeType::AVG_STMT,
//        NodeType::MIN_STMT,
//        NodeType::MAX_STMT,
    ];


    public function calculator($structType, $args)
    {
        if (!in_array($structType, array_merge($this->opera_struct_list, $this->func_struct_list))) {
            # 验证是否属于定义的运算结构体
            return 0;
        }
        if (in_array($structType, $this->opera_struct_list)) {
            $left = $args['left'] ?: 0;
            $right = $args['right'] ?: 0;
            $method_name = $structType . 'Struct';
            if ($method_name && method_exists($this, $method_name)) {
                return $this->$method_name($left, $right);
            } else {
                return 0;
            }
        } elseif ($structType == NodeType::IF_STMT) {
            $condition = $args['condition'];
            $then = $args['then'];
            $else = $args['else'];
            $method_name = $structType . 'Struct';
            if ($method_name && method_exists($this, $method_name)) {
                return $this->$method_name($condition, $then, $else);
            } else {
                return 0;
            }
        } else {
            # 其他函数暂不支持
            return 0;
        }
    }

    /**
     * 定义if方法
     * 截取下一层级中的两个逗号","，直到该层级的括号闭合。
     * if(condition,then,else)
     * @param $condition
     * @param $then
     * @param $else
     * @return mixed
     */
    public function IfStmtStruct($condition, $then, $else)
    {
        if ($condition) {
            return $then;
        } else {
            return $else;
        }
    }

    /**
     * 定义sum函数方法
     * 截取下一层级中的n个逗号，直到该层级的括号闭合。
     * sum(1,2,3,4,5,...args)
     * @param $args
     * @return mixed
     */
    public function SumStmtStruct($args)
    {
        return array_sum($args);
    }

    /**
     * 定义求均的结构体方法
     * @param $args
     * @return float
     * @throws RuntimeException
     */
    public function AvgStmtStruct($args)
    {
        $num = count($args) ?: 0;
        if (!$num) {
            throw new RuntimeException('Error in avg: args count num error.');
        }
        $sum = array_sum($args);
        return (float)($sum / $num);
    }

    /**
     * 定义获取最大值的结构体
     * @param $args
     * @return mixed
     */
    public function MaxStmtStruct($args)
    {
        return max($args);
    }

    /**
     * 定义获取最小值的结构体
     * @param $args
     * @return mixed
     */
    public function MinStmtStruct($args)
    {
        return min($args);
    }

    /**
     * 定义 less than < 小于 结构体
     * @param $left
     * @param $right
     * @return bool
     */
    public function LTStruct($left, $right)
    {
        if (!is_numeric($left) || !is_numeric($right)) {
            return 0;
        }
        return ($left < $right) ? 1 : 0;
    }

    /**
     * 定义 less than or equal <= 小于等于结构体
     * @param $left
     * @param $right
     * @return bool
     */
    public function LEStruct($left, $right)
    {
        if (!is_numeric($left) || !is_numeric($right)) {
            return 0;
        }
        return ($left <= $right) ? 1 : 0;
    }

    /**
     * 定义 great than > 大于结构体
     * @param $left
     * @param $right
     * @return mixed
     */
    public function GTStruct($left, $right)
    {
        if (!is_numeric($left) || !is_numeric($right)) {
            return 0;
        }
        return ($left > $right) ? 1 : 0;
    }

    /**
     * 定义 great and equal >=
     * @param $left
     * @param $right
     * @return bool
     */
    public function GEStruct($left, $right)
    {
        if (!is_numeric($left) || !is_numeric($right)) {
            return 0;
        }
        return ($left >= $right) ? 1 : 0;
    }

    /**
     * 定义 equal == 等号表达式结构体
     * @param $left
     * @param $right
     * @return bool
     */
    public function EQStruct($left, $right)
    {
        if (is_numeric($left) && is_numeric($right)) {
            return ($left == $right) ? 1 : 0;
        } elseif (is_string($left) && is_string($right)) {
            # 过滤掉字符的引号
            $left = trim($left, '\'"');
            $right = trim($right, '\'"');
            return ($left == $right) ? 1 : 0;
        } else {
            return 0;
        }
    }

    /**
     * 加法
     * @param $left
     * @param $right
     * @return int
     */
    public function AdditionStruct($left, $right)
    {
        if (!is_numeric($left) || !is_numeric($right)) {
            return 0;
        }
        return ($left + $right) ?: 0;
    }

    /**
     * 减法
     * @param $left
     * @param $right
     * @return int|string
     */
    public function SubtractionStruct($left, $right)
    {
        if (!is_numeric($left) || !is_numeric($right)) {
            return 0;
        }
        return ($left - $right) ?: 0;
    }

    /**
     * 乘法
     * @param $left
     * @param $right
     * @return float|int
     */
    public function MultiplicationStruct($left, $right)
    {
        if (!is_numeric($left) || !is_numeric($right)) {
            return 0;
        }
        return ($left * $right) ?: 0;
    }

    /**
     * 除法
     * @param $left
     * @param $right
     * @return float|int
     */
    public function DivisionStruct($left, $right)
    {
        if (!is_numeric($left) || !is_numeric($right)) {
            return 0;
        }
        if ($right == 0) {
            return 0;
        }
        return ($left / $right) ?: 0;
    }

    /**
     * 处理 and 逻辑
     * @param $left
     * @param $right
     * @return int
     */
    public function Id_AndStruct($left, $right): int
    {
        $res = ($left && $right);
        return $res ? 1 : 0;
    }

    /**
     * 处理 or 逻辑
     * @param $left
     * @param $right
     * @return int
     */
    public function Id_OrStruct($left, $right): int
    {
        $res = ($left || $right);
        return $res ? 1 : 0;
    }

}
