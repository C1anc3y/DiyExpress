<?php

namespace DiyExpress\Token;
class TokenType
{
    const NULL_TAG = null;
    const INT_LITERAL = 'IntLiteral';     //整型字面量
    const STRING_LITERAL = 'StringLiteral';   //字符串
    const IDENTIFIER = 'Identifier';     //标识符 未特殊定义的词

    # 标识符 特殊定义的关键词
    const ID_IF = 'Id_If';
    const ID_ELSE = 'Id_Else';
    const ID_IN = 'Id_IN';
    const ID_INT = 'Id_Int';
    const ID_NOT = 'Id_NOT';
    const ID_AND = 'Id_And';
    const ID_OR = 'Id_Or';
    const ID_MAX = 'Id_Max';
    const ID_MIN = 'Id_Min';
    const ID_SUM = 'Id_Sum';
    const ID_AVG = 'Id_Avg';

    # 运算符
    const ADDITION = 'Addition';                # 加
    const SUBTRACTION = 'Subtraction';          # 减
    const MULTIPLICATION = 'Multiplication';    # 乘
    const DIVISION = 'Division';                # 除

    # 逻辑符
    const ASSIGNMENT = 'Assignment'; // =
    const GE = 'GE';     // >=
    const GT = 'GT';     // >
    const EQ = 'EQ';     // ==
    const LE = 'LE';     // <=
    const LT = 'LT';     // <

    # 标点符号
    const COMMA = 'Comma'; # 逗号
    const SEMI_COLON = 'SemiColon'; // ;
    const APOSTROPHE = "Apostrophe"; // ' 单引号
    const DOUBLE_QUOTES = "DoubleQuotes"; // " 双引号
    const NUM_DOT = 'NumDot'; // . 小数点

    # 括号
    const SMALL_BRACKET_LEFT = 'LeftSmallBracket';       # 左小括号 (
    const SMALL_BRACKET_RIGHT = 'RightSmallBracket';       # 右小括号 )
    const MEDIUM_BRACKET_LEFT = 'LeftMediumBracket';     # 左中括号 [
    const MEDIUM_BRACKET_RIGHT = 'RightMediumBracket';     # 右中括号 ]
    const LARGE_BRACKET_LEFT = 'LeftLargeBracket';       # 左大括号 {
    const LARGE_BRACKET_RIGHT = 'RightLargeBracket';       # 右大括号 }

    /**
     * 闭合小括号的左侧能存在的字符
     * @var string[]
     */
    public array $closeSmallBracket_inLeft = [
        self::IDENTIFIER,           # 标识符
        self::INT_LITERAL,          # 数值
        self::STRING_LITERAL,       # 字符串
        self::SMALL_BRACKET_RIGHT,  # 闭合右括号
        self::SMALL_BRACKET_LEFT,  # 闭合右括号
        self::COMMA,
        #   小于等于，小于，大于等于，大于，双等于
        self::LE, self::LT, self::GE, self::GT, self::EQ,
        # 加减乘除
        self::ADDITION, self::MULTIPLICATION, self::SUBTRACTION, self::DIVISION,
        # 逻辑与，逻辑或
        self::ID_AND, self::ID_OR,
        # 可能空
        self::NULL_TAG,
    ];

    /**
     * 闭合小括号的右侧能存在的字符
     * @var string[]
     */
    public array $closeSmallBracket_inRight = [
        self::IDENTIFIER,           # 标识符
        self::INT_LITERAL,          # 数值
        self::STRING_LITERAL,       # 字符串
        self::SMALL_BRACKET_RIGHT,  # 闭合右括号
        self::SMALL_BRACKET_LEFT,  # 闭合右括号
        self::COMMA,
        # 小于等于，小于，大于等于，大于，双等于
        self::LE, self::LT, self::GE, self::GT, self::EQ,
        # 加减乘除
        self::ADDITION, self::MULTIPLICATION, self::SUBTRACTION, self::DIVISION,
        # 逻辑与，逻辑或
        self::ID_AND, self::ID_OR,
        # 可能空
        self::NULL_TAG,
    ];

    /**
     * 开口小括号左侧能存在的字符
     * @var string[]
     */
    public array $openSmallBracket_inLeft = [
        # 小于等于，小于，大于等于，大于，双等于
        self::LE, self::LT, self::GE, self::GT, self::EQ,
        # 加减乘除
        self::ADDITION, self::MULTIPLICATION, self::SUBTRACTION, self::DIVISION,
        # 逻辑与，逻辑或
        self::ID_AND, self::ID_OR,
        self::ID_IF,                # if标记
        self::COMMA,                # 逗号标记
        self::IDENTIFIER,           # 标识符
        self::INT_LITERAL,          # 数值
        self::STRING_LITERAL,       # 字符串
        self::SMALL_BRACKET_RIGHT,  # 闭合右括号
        self::SMALL_BRACKET_LEFT,   # 开口左括号
        self::NULL_TAG,             # 可能空
        self::ASSIGNMENT,
    ];

    /**
     * 开口小括号右侧能存在的字符
     * @var string[]
     */
    public array $openSmallBracket_inRight = [
        self::IDENTIFIER,           # 标识符
        self::INT_LITERAL,          # 数值
        self::STRING_LITERAL,       # 字符串
        self::SMALL_BRACKET_LEFT,   # 开口左括号
        self::ID_IF,                # if标记
    ];

    /**
     * 加减操作符
     * @var string[]
     */
    public array $plus_minus_op = [
        self::ADDITION,
        self::SUBTRACTION
    ];

    /**
     * 乘除操作符
     * @var string[]
     */
    public array $star_slash_op = [
        self::MULTIPLICATION,
        self::DIVISION
    ];

    /**
     * 比较操作符
     * @var string[]
     */
    public array $compare_op = [
        self::LT, self::LE,
        self::GT, self::GE,
        self::EQ
    ];

    /**
     * 逻辑操作符
     * @var string[]
     */
    public array $logic_op = [
        self::ID_AND,
        self::ID_OR,
    ];

    /**
     * 操作符的汇总
     * @var array
     */
    public array $tag_array = [
        self::ADDITION, self::SUBTRACTION, self::MULTIPLICATION, self::DIVISION,
        self::LT, self::LE, self::GT, self::GE, self::EQ,
        self::ID_AND, self::ID_OR,
    ];
}