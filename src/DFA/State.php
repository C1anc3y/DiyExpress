<?php
/**
 * @File    :   State.php
 * @Author  :   ClanceyHuang
 * @Refer   :   unknown
 * @Desc    :   ...
 * @Version :   PHP7.x
 * @Contact :   ClanceyHuang@outlook.com
 * @Site    :   http://debug.cool
 */

namespace DiyExpress\DFA;
class State{
    const INITIAL = 'Initial';              # 初始状态
    const INT_LITERAL = 'IntLiteral';       # 数值
    const VALUE_STRING = 'Value_string';    # 字符串
    const IDENTIFIER = 'Identifier';        # 标识符的id状态 未特殊定义的词

    const ID_IF = 'Id_If';              # 关键词 if
    const ID_IF1 = 'Id_if1';
    const ID_IF2 = 'Id_if2';

    const ID_IN = 'Id_In';              # 关键词 in
    const ID_IN1 = 'Id_in1';
    const ID_IN2 = 'Id_in2';

    const ID_ELSE = 'Id_Else';          # 关键词 ELSE
    const ID_ELSE1 = 'Id_else1';        # ELSE 中的 E e
    const ID_ELSE2 = 'Id_else2';        # ELSE 中的 L l
    const ID_ELSE3 = 'Id_else3';        # ELSE 中的 S s
    const ID_ELSE4 = 'Id_else4';        # ELSE 中的 E e

    const ID_INT = 'Id_Int';            # 关键词 int
    const ID_INT1 = 'Id_int1';
    const ID_INT2 = 'Id_int2';
    const ID_INT3 = 'Id_int3';

    # 逻辑AND操作符
    const ID_AND = 'Id_And';            # 关键词 AND
    const ID_AND1 = 'Id_And1';          # AND 中的 A a
    const ID_AND2 = 'Id_And2';          # AND 中的 N n
    const ID_AND3 = 'Id_And3';          # AND 中的 D d

    # 逻辑OR操作符
    const ID_OR = 'Id_Or';              # 关键词 OR
    const ID_OR1 = 'Id_Or1';            # OR 中的 O o
    const ID_OR2 = 'Id_Or2';            # OR 中的 R r

    const ID_MAX = 'Id_Max';            # 关键词Max
    const ID_MAX1 = 'Id_Max1';          # M m
    const ID_MAX2 = 'Id_Max2';          # A a
    const ID_MAX3 = 'Id_Max3';          # X x

    const ID_MIN = 'Id_Min';            # 关键词Min
    const ID_MIN1 = 'Id_Min1';          # M m
    const ID_MIN2 = 'Id_Min2';          # I i
    const ID_MIN3 = 'Id_Min3';          # N n

    const ID_AVG = 'Id_Avg';            # 关键词Avg
    const ID_AVG1 = 'Id_Avg1';          # A a
    const ID_AVG2 = 'Id_Avg2';          # V v
    const ID_AVG3 = 'Id_Avg3';          # G g

    const ID_SUM = 'Id_Sum';            # 关键词Sum
    const ID_SUM1 = 'Id_Sum1';          # S s
    const ID_SUM2 = 'Id_Sum2';          # U u
    const ID_SUM3 = 'Id_Sum3';          # M m


    const ASSIGNMENT = 'Assignment';    # = 赋值
    const GT = 'GT';                    # >
    const GE = 'GE';                    # >=
    const LT = 'LT';                    # <
    const LE = 'LE';                    # <=
    const EQ = 'EQ';                    # == 比较的等号
    const NE = 'NE';                    # !=

    const ADDITION = 'Addition';                # 加
    const SUBTRACTION = 'Subtraction';          # 减
    const MULTIPLICATION = 'Multiplication';    # 乘
    const DIVISION = 'Division';                # 除

    const SMALL_BRACKET_LEFT = 'LeftSmallBracket';          # 左小括号 (
    const SMALL_BRACKET_RIGHT = 'RightSmallBracket';        # 右小括号 )
    const MEDIUM_BRACKET_LEFT = 'LeftMediumBracket';        # 左中括号 [
    const MEDIUM_BRACKET_RIGHT = 'RightMediumBracket';      # 右中括号 ]
    const LARGE_BRACKET_LEFT = 'LeftLargeBracket';          # 左大括号 {
    const LARGE_BRACKET_RIGHT = 'RightLargeBracket';        # 右大括号 }

    const COMMA = 'Comma';                  # 逗号
    const SEMI_COLON = 'SemiColon';         # 分号    ;
    const DOUBLE_QUOTES = 'DoubleQuotes';   # 双引号   “
    const APOSTROPHE = "Apostrophe";        # 单引号   ‘
    const NUM_DOT = 'NumDot';               # 小数点   .
}