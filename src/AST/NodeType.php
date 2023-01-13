<?php

namespace DiyExpress\AST;

class NodeType
{
    const PROGRAM = "Program";                # 程序入口，根节点
    const INT_DECLARATION = "IntDeclaration";   # 整型变量声明
    const EXPRESSION_STMT = 'ExpressionStmt';   # 表达式语句，即表达式后面跟个分号
    const PRIMARY = 'Primary';                  # 基础表达式

    const IDENTIFIER = 'Identifier';            # 标识符
    const INT_LITERAL = 'IntLiteral';           # 整型字面量

    # 自定义公式处理
    const DIY_FORMULA = 'DiyFormula';           # 自定义公式

    const RESULT_STMT = 'ResultStmt';           # 结果语句，用来区分哪个节点是结果值
    const ASSIGNMENT_STMT = 'AssignmentStmt';   # 赋值语句

    # 定义的几种公式
    const SUM_STMT = 'SumStmt';                 # 求和表达式
    const AVG_STMT = 'AvgStmt';                 # 求均表达式
    const MIN_STMT = 'MinStmt';                 # 求最小值表达式
    const MAX_STMT = 'MaxStmt';                 # 求最大值表达式

    const IF_STMT = 'IfStmt';                   # 条件表达式
    const IF_CONDITION_STMT = 'IfSwitchStmt';   # 条件表达式的判定语句
    const IF_THEN_STMT = 'IfTrueStmt';          # 条件表达式的真值语句
    const IF_ELSE_STMT = 'IfFalseStmt';         # 条件表达式的假值语句

    const SMALL_BRACKET = 'SmallBracket';       # 小括号表达式
    const MEDIUM_BRACKET = 'MediumBracket';     # 中括号表达式
    const LARGE_BRACKET = 'LargeBracket';       # 大括号表达式

    const COMMA = 'Comma';                      # 逗号 // 这里的逗号表达式是否需要进行切割，处理成条件表达式的 判定条件、真值、假值？

    const COMPARE_STMT_LT = 'LT';
    const COMPARE_STMT_LE = 'LE';
    const COMPARE_STMT_GT = 'GT';
    const COMPARE_STMT_GE = 'GE';
    const COMPARE_STMT_EQ = 'EQ';

    const STRING_LITERAL = 'StringLiteral';     # 字符串

    const ADDITION = 'Addition';                # 加
    const SUBTRACTION = 'Subtraction';          # 减
    const MULTIPLICATION = 'Multiplication';    # 乘
    const DIVISION = 'Division';                # 除

    const ID_NOT = 'Id_NOT';
    const ID_AND = 'Id_And';
    const ID_OR = 'Id_Or';


    const IF_EXPRESS = 'ifExpress';
    const BRACKET_EXPRESS = 'bracketExpress';

    /**
     * 数值或者字符字面量
     * @var string[]
     */
    public array $num_char_list = [
        self::INT_LITERAL,
        self::IDENTIFIER,
    ];

    /**
     * 高级运算符
     * @var string[]
     */
    public array $high_tag = [
        self::MULTIPLICATION,
        self::DIVISION
    ];
    /**
     * 低级运算符
     * @var string[]
     */
    public array $low_tag = [
        self::ADDITION,
        self::SUBTRACTION
    ];
    /**
     * 比较符
     * @var string[]
     */
    public array $compare_tag = [
        self::COMPARE_STMT_EQ,
        self::COMPARE_STMT_GE,
        self::COMPARE_STMT_GT,
        self::COMPARE_STMT_LE,
        self::COMPARE_STMT_LT,
    ];
    /**
     * 逻辑符
     * @var string[]
     */
    public array $logic_tag = [
        self::ID_AND,
        self::ID_OR,
    ];
}
