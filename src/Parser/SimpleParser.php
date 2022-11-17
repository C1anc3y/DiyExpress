<?php
/**
 * @File    :   SimpleParser.php
 * @Author  :   ClanceyHuang
 * @Refer   :   unknown
 * @Desc    :   ...
 * @Version :   PHP7.x
 * @Contact :   ClanceyHuang@outlook.com
 * @Site    :   http://debug.cool
 */


namespace DiyExpress\Parser;
use DiyExpress\AST\NodeType;
use DiyExpress\Formula\IFormulaNode;
use DiyExpress\Lexer\SimpleLexer;
use DiyExpress\Token\SimpleTokenReader;
use DiyExpress\Token\TokenType;

class SimpleParser{

    /**
     * [
     *  ['type'=>'','text'=>'','path'=>'','complete'=>0], ...
     * ]
     * @var null |array
     */
    public $store_list = null;

    /**
     * @var mixed $struct_node_list array|null
     */
    public $struct_node_list = [];

    public $parse_error_tip = null;

    public $field_list = null;

    /**
     * 安全处理，必须强制clean，否则存在高危安全隐患，涉及服务器命令执行。
     * @param $script
     * @return string
     */
    public function secCleanCode($script): string
    {
        $result = "";
        $new_script = "";
        # 先过滤空格
        $split_array = str_split(trim($script));
        foreach ($split_array as $split_val) {
            if (!$this->isBlankCharacter($split_val)) {
                $new_script .= $split_val;
            }
        }
        if (empty($new_script)) {
            return $result;
        }

        # 校验违禁命令词
        global $_G;
        $ban_list = $_G["ban_list"];
        if (in_array($new_script, $ban_list)) {
            return $result;
        } else {
            $result = $new_script;
        }
        return $result;
    }

    /**
     * 过滤无效空格
     * @param $ch
     * @return bool
     */
    protected function isBlankCharacter($ch): bool
    {
        static $blanks = [' ', "\t", "\n", "\r", ''];
        return in_array($ch, $blanks);
    }

    /**
     * 中文版的split
     * @param $str
     * @param int $split_len
     * @return array|bool|mixed
     */
    protected function utf8_str_split($str, $split_len = 1)
    {
        if (!preg_match('/^[0-9]+$/', $split_len) || $split_len < 1)
            return FALSE;

        $len = mb_strlen($str, 'UTF-8');
        if ($len <= $split_len)
            return array($str);
        preg_match_all('/.{' . $split_len . '}|[^\x00]{1,' . $split_len . '}$/us', $str, $ar);
        return $ar[0];
    }

    /**
     * 解析脚本
     * @param $script
     * @return array
     * @throws \Exception
     */
    public function parse($script): array
    {
        # 安全过滤，防止恶意输入违禁命令 opt：采用结构树运算，不需要再验证安全关键词。其中空格过滤容易把and or误伤，因此关闭该验证。
//        $script = $this->secCleanCode($script);
        $lexer = new SimpleLexer();
        $tokens = $lexer->tokenize($script);
        # 处理成ast节点树
        $result = $this->DiyFormulaStmtParse($tokens) ?: array();
        if ($result){
            $result['tokens'] = $tokens;
        }
        return $result;
    }

    /**
     * AST的根节点，解析的入口。
     * @param SimpleTokenReader $tokens
     * @return array
     * @throws \Exception
     */
    public function DiyFormulaStmtParse(SimpleTokenReader $tokens): array
    {
        # 最顶级的节点
        $result = [];
        # 结果值已被解析的标记
        $result_flag = false;
        # 结果等号已被解析的标记
        $assign_flag = false;

        # 这里传递过来的是多个公式解析的词组（目前业务是单个公式传递过来的，但这里做了兼容）。
        while ($tokens->peek() != null) {
            $child = null;
            # 获取一个结果取值
            if (!$result_flag) {
                $child = $this->resultStatement($tokens);
                if ($child != null) {
                    $result_flag = true;
                }
            }
            # 获取赋值token节点
            if (!$assign_flag && $child == null) {
                $child = $this->assignStatement($tokens);
                if ($child != null) {
                    $assign_flag = true;
                }
            }

            if ($child == null) {
                $child = $this->scanTokens($tokens);
            }

            if ($this->parse_error_tip) {
                $result['error'] = $this->parse_error_tip;
                return $result;
            } elseif (is_array($child)) {
                $result['data'] = array_merge($result['data'] ?: array(), $child ?: array()) ?: array();
                $result['field'] = array_values($this->field_list) ?: array();
            }
        }
        return $result;
    }

    /**
     * 状态机处理赋值的类型
     * 在自定义公式中，应该只会出现一次作为结果值，所以需要判定次数，大于1就throw掉
     * @param SimpleTokenReader $tokens
     * @return bool|null
     */
    private function assignStatement(SimpleTokenReader $tokens)
    {
        $astNodeType = new NodeType();
        $token = $tokens->peek();
        if ($token != null && $token->type == TokenType::ASSIGNMENT) {
            # 获取当前堆栈最后一个节点,id追加，pid相同
            $last_keys = array_keys($this->struct_node_list);
            $last_obj = end($this->struct_node_list);
            # id一直追加
            $current_id = end($last_keys) + 1;
            # 不涉及层级变更，父级id与末节点保持一致
            $current_pid = $last_obj->structPid;
            # 不涉及层级变更，层级与末节点保持一致
            $current_level = $last_obj->structLevel;
            $structNode = new IFormulaNode($astNodeType::ASSIGNMENT_STMT, $token->text, $current_id, $current_pid, $current_level);
            $this->struct_node_list[] = $structNode;
            $tokens->read();
            if ($tokens->peek() == null) {
                $node = null;
                $tokens->unread();
                is_array($this->store_list) && array_pop($this->store_list);
                $this->parse_error_tip = '赋值等号右侧缺失参数';
                return null;
            }
        } else {
            return null;
        }
        return true;
    }

    /**
     * 结果标识符解析
     * @param $tokens
     * @return bool|null
     */
    private function resultStatement($tokens)
    {
        $astNodeType = new NodeType();
        $token = $tokens->peek();
        if ($token != null && $token->type == TokenType::IDENTIFIER) {
            # 获取当前堆栈最后一个节点,id追加，pid相同
            $last_keys = array_keys($this->struct_node_list);
            $last_obj = end($this->struct_node_list);
            # id一直追加
            $current_id = end($last_keys) + 1;
            # 不涉及层级变更，父级id与末节点保持一致
            $current_pid = $last_obj->structPid ?: 0;
            # 不涉及层级变更，层级与末节点保持一致
            $current_level = $last_obj->structLevel ?: 1;
            $structNode = new IFormulaNode($astNodeType::RESULT_STMT, $token->text, $current_id, $current_pid, $current_level);
            $this->struct_node_list[] = $structNode;
            $tokens->read();
            if ($tokens->peek() == null || $tokens->peek()->type != TokenType::ASSIGNMENT) {
                $tokens->unread();
                is_array($this->store_list) && array_pop($this->store_list);
                return null;
            }
        } else {
            return null;
        }
        return true;
    }

    /**
     * 扫描需要解析的tokens
     * @param SimpleTokenReader $tokens
     * @return array|null
     * @throws \Exception
     */
    private function scanTokens(SimpleTokenReader $tokens)
    {
        $result = [];
        if ($tokens == null) {
            return null;
        }

        $tokenType = new TokenType();
        if ($this->struct_node_list[0]->structType != NodeType::RESULT_STMT) {
            $this->parse_error_tip = '公式缺失结果值';
            return null;
        }
        if ($this->struct_node_list[1]->structType != NodeType::ASSIGNMENT_STMT) {
            $this->parse_error_tip = '公式缺失等号';
            return null;
        }

        # 循环处理所有的tokens
        while ($tokens->peek() != null) {
            $token = $tokens->peek();
            if ($token != null) {
                # 处理层级对象数组的逻辑
                if (in_array($token->type, $tokenType->tag_array)) {
                    # 处理运算符
                    $this->structComputeTag($tokens);
                } elseif ($token->type == $tokenType::SMALL_BRACKET_LEFT) {
                    # 处理开口小括号的条件
                    $this->structSmallBracketOpen($tokens);
                } elseif ($token->type == $tokenType::ID_IF) {
                    # 处理if的判定
                    $this->structIfStmt($tokens);
                } elseif ($token->type == $tokenType::SMALL_BRACKET_RIGHT) {
                    # 处理闭合小括号的条件
                    $this->structSmallBracketClose($tokens);
                } else {
                    # 处理表达式
                    $this->structExpression($tokens);
                }
            }
            $tokens->read();
        }
        if ($this->parse_error_tip) {
            return null;
        } elseif ($this->struct_node_list) {
            current($this->struct_node_list);
            $endNode = end($this->struct_node_list);
            if ($endNode->structLevel < 0) {
                $this->parse_error_tip = '公式语法有误';
                return null;
            }
            $result = $this->struct_node_list;
            $this->getIdentifierField($result);
        }
        return $result;
    }

    /**
     * 运算符相关的结构体定义 及 比较符的结构体定义
     * 结构规则 找到左右两个的变量或者表达式，缺失任何一个都抛错
     * @param SimpleTokenReader $tokens
     * @return void
     * @throws \Exception
     */
    private function structComputeTag(SimpleTokenReader $tokens)
    {
        $token = $tokens->peek();
        $tokenType = new TokenType();
        if ($token != null && in_array($token->type, $tokenType->tag_array)) {
            # 校验操作符前后是否右变量或者表达式
            $preToken = $tokens->peekPre();
            if ($preToken == null || in_array($preToken->type, $tokenType->tag_array)) {
                $this->parse_error_tip = '左侧变量缺失';
                return;
            }
            $nextToken = $tokens->peekNext();
            if ($nextToken == null || in_array($nextToken->type, $tokenType->tag_array)) {
                $this->parse_error_tip = '右侧变量缺失';
                return;
            }

            $astNodeType = new NodeType();
            list($current_id, $current_pid, $current_level) = $this->dealLevel($token, $preToken);

            # 具体处理运算操作
            $can_assign = true;
            $current_type = "";
            switch ($token->type) {
                case $tokenType::ADDITION:
                    # 加法操作
                    $current_type = $astNodeType::ADDITION;
                    break;
                case $tokenType::SUBTRACTION:
                    # 减法操作
                    $current_type = $astNodeType::SUBTRACTION;
                    break;
                case $tokenType::MULTIPLICATION:
                    # 乘法操作
                    $current_type = $astNodeType::MULTIPLICATION;
                    break;
                case $tokenType::DIVISION:
                    # 除法操作
                    $current_type = $astNodeType::DIVISION;
                    break;
                case $tokenType::LT:
                    # < 比较符
                    $current_type = $astNodeType::COMPARE_STMT_LT;
                    break;
                case $tokenType::LE:
                    # <= 比较符
                    $current_type = $astNodeType::COMPARE_STMT_LE;
                    break;
                case $tokenType::GT:
                    # > 比较符
                    $current_type = $astNodeType::COMPARE_STMT_GT;
                    break;
                case $tokenType::GE:
                    # >= 比较符
                    $current_type = $astNodeType::COMPARE_STMT_GE;
                    break;
                case $tokenType::EQ:
                    # == 比较符
                    $current_type = $astNodeType::COMPARE_STMT_EQ;
                    break;
                case $tokenType::ID_AND:
                    # 逻辑与的判定
                    $current_type = $astNodeType::ID_AND;
                    break;
                case $tokenType::ID_OR:
                    # 逻辑或的判定
                    $current_type = $astNodeType::ID_OR;
                    break;
                default:
                    $can_assign = false;
                    break;
            }
            if ($can_assign && $current_type) {
                $structNode = new IFormulaNode($current_type, $token->text, $current_id, $current_pid, $current_level);
                $this->struct_node_list[] = $structNode;
            }
        } else {
            return;
        }
    }

    /**
     * 小括号开启的结构体定义
     * 结构规则：
     * 左括号左侧的一般是操作符或者if
     * 遇到最近的一个右括号就闭合 SMALL_BRACKET_RIGHT
     * 遇到左括号或者if条件，就重置开始 SMALL_BRACKET_LEFT、ID_IF
     * @param SimpleTokenReader $tokens
     * @return void
     * @throws \Exception
     */
    private function structSmallBracketOpen(SimpleTokenReader $tokens)
    {
        $token = $tokens->peek();
        $tokenType = new TokenType();

        if ($token != null && $token->type == $tokenType::SMALL_BRACKET_LEFT) {
            # 校验操作符前后是否右变量或者表达式
            $preToken = $tokens->peekPre();
            if ($preToken == null || !in_array($preToken->type, $tokenType->openSmallBracket_inLeft)) {
//                echo "左侧缺失操作符";
                $this->parse_error_tip = '左侧缺失操作符';
                return;
            }
            $nextToken = $tokens->peekNext();
            if ($nextToken == null || !in_array($nextToken->type, $tokenType->openSmallBracket_inRight)) {
                $this->parse_error_tip = '右侧变量缺失';
                return;
            }

            $astNodeType = new NodeType();
            list($current_id, $current_pid, $current_level) = $this->dealLevel($token, $preToken);
            $structNode = new IFormulaNode($astNodeType::SMALL_BRACKET, $token->text, $current_id, $current_pid, $current_level);
            $this->struct_node_list[] = $structNode;
        } else {
            return;
        }
    }

    /**
     * 小括号闭合的结构体定义
     * 结构规则：
     * 闭合括号后还有未处理的tokens，需要往上回溯一层节点再继续执行scan。如果没有了，需要回溯上层并验证节点是否完成。
     * @param SimpleTokenReader $tokens
     * @return void
     * @throws \Exception
     */
    private function structSmallBracketClose(SimpleTokenReader $tokens)
    {
        $token = $tokens->peek();
        $tokenType = new TokenType();
        if ($token != null && $token->type == $tokenType::SMALL_BRACKET_RIGHT) {
            # 校验右括号的左侧是否为数值、表达式、变量、字符串等，即不能是操作符
            $preToken = $tokens->peekPre();
            if ($preToken == null || !in_array($preToken->type, $tokenType->closeSmallBracket_inLeft)) {
                $this->parse_error_tip = '闭合括号左侧参数有误';
                return;
            }
            $nextToken = $tokens->peekNext();
            // 右闭合括号后可以直接结束，所以可以为null
            if (!in_array($nextToken->type, $tokenType->closeSmallBracket_inRight)) {
                $this->parse_error_tip = '闭合括号右侧参数有误';
                return;
            }

            $astNodeType = new NodeType();
            list($current_id, $current_pid, $current_level) = $this->dealLevel($token, $preToken);

            if ($current_level < 0) {
                $this->parse_error_tip = '括号匹配错误';
                return;
            } else {
                $structNode = new IFormulaNode($astNodeType::SMALL_BRACKET, $token->text, $current_id, $current_pid, $current_level);
                $this->struct_node_list[] = $structNode;
            }
        } else {
            return;
        }
    }

    /**
     * if statement 的结构体定义
     * 结构规则：
     * 遇到逗号分段，且逗号必须有两个，多于或者少于都不行 Comma
     * 遇到最近的一个右括号就闭合 SMALL_BRACKET_RIGHT
     * 遇到左括号或者自身的if就重置开始 SMALL_BRACKET_LEFT、ID_IF
     * @param SimpleTokenReader $tokens
     * @return void
     * @throws \Exception
     */
    private function structIfStmt(SimpleTokenReader $tokens)
    {
        $token = $tokens->peek();
        $tokenType = new TokenType();
        if ($token != null && $token->type == $tokenType::ID_IF) {
            $preToken = $tokens->peekPre();
            if ($preToken == null || !in_array($preToken->type, array_merge($tokenType->tag_array, [$tokenType::ASSIGNMENT, $tokenType::ID_IF, $tokenType::SMALL_BRACKET_LEFT,$tokenType::COMMA]))) {
                $this->parse_error_tip = 'if符号左侧参数错误';
                return;
            }
            $nextToken = $tokens->peekNext();
            if ($nextToken == null || $nextToken->type != $tokenType::SMALL_BRACKET_LEFT) {
                $this->parse_error_tip = 'if符号右侧不是括号';
                return;
            }
            $astNodeType = new NodeType();
            list($current_id, $current_pid, $current_level) = $this->dealLevel($token, $preToken);
            $structNode = new IFormulaNode($astNodeType::IF_STMT, $token->text, $current_id, $current_pid, $current_level);
            $this->struct_node_list[] = $structNode;
        } else {
            return;
        }
    }

    /**
     * 普通表达式结构的处理，可能是变量，可能是数值，可能是字符串等
     * @param $tokens
     * @return void
     * @throws \Exception
     */
    private function structExpression($tokens)
    {
        $token = $tokens->peek();
        if ($token == null) {
            return;
        }
        $tokenType = new TokenType();
        $astNodeType = new NodeType();

        # 获取当前堆栈最后一个节点,id追加，pid相同
        $last_keys = array_keys($this->struct_node_list);
        $last_obj = end($this->struct_node_list);
        # id一直追加
        $current_id = end($last_keys) + 1;

        $preToken = $tokens->peekPre();
        if ($preToken != null && $preToken->type == $tokenType::SMALL_BRACKET_RIGHT) {
            # 左侧是括号，层级需要往下处理一层
            $current_pid = $last_obj->structId;
            # 不涉及层级变更，层级与末节点保持一致
            $current_level = $last_obj->structLevel - 1;
        } else {
            # 不涉及层级变更，父级id与末节点保持一致
            $current_pid = $last_obj->structPid;
            # 不涉及层级变更，层级与末节点保持一致
            $current_level = $last_obj->structLevel;
        }
        # 操作标记
        $can_assign = true;
        if ($token->type == $tokenType::INT_LITERAL) {
            $current_type = $astNodeType::INT_LITERAL;
        } elseif ($token->type == $tokenType::IDENTIFIER) {
            $current_type = $astNodeType::IDENTIFIER;
        } elseif ($token->type == $tokenType::STRING_LITERAL) {
            $current_type = $astNodeType::STRING_LITERAL;
        } elseif ($token->type == $tokenType::COMMA) {
            $current_type = $astNodeType::COMMA;
        } else {
            $can_assign = false;
            $current_type = $token->type;
        }

        if ($can_assign && $current_type) {
            $structNode = new IFormulaNode($current_type, $token->text, $current_id, $current_pid, $current_level);
            $this->struct_node_list[] = $structNode;
        }
    }

    /**
     * 收集需要的字符变量。
     * @param $data
     * @return bool
     */
    public function getIdentifierField($data)
    {
        $astType = new NodeType();
        $field_type = [$astType::RESULT_STMT => 0, $astType::IDENTIFIER => 1];
        foreach ($data as $v) {
            if (isset($field_type[$v->structType]) && !isset($this->field_list[$v->structText])) {
                $this->field_list[$v->structText] = $v->structText;
            }
        }
        return true;
    }

    /**
     * 定义一个通用的层级处理方法
     * @param $token
     * @param $preToken
     * @return array
     */
    private function dealLevel($token, $preToken): array
    {
        $tokenType = $token->type;
        $preTokenType = $preToken->type;

        # 获取当前堆栈最后一个节点
        $last_keys = array_keys($this->struct_node_list);
        $last_obj = end($this->struct_node_list);
        # id一直追加
        $pre_id = end($last_keys);
        $current_id = $pre_id + 1;
        # 后续字符类型是什么不用管，只需处理前置字符类型。
        switch ($tokenType) {
            case TokenType::SMALL_BRACKET_LEFT:
                if ($preTokenType == TokenType::SMALL_BRACKET_RIGHT) {
                    # 当前左括号遇到前置右括号，层级保持不变，父级结构保持不变
                    $current_pid = $last_obj->structPid;
                    $current_level = $last_obj->structLevel;
                } else {
                    # 当前左括号遇到前置非右括号，层级一律+1，父级继承前置
                    $current_pid = $pre_id;
                    $current_level = $last_obj->structLevel + 1;
                }
                break;
            case TokenType::SMALL_BRACKET_RIGHT:
                if ($preTokenType == TokenType::SMALL_BRACKET_RIGHT) {
                    # 当前右括号，遇到前置右括号，层级-1，父级继承父级。
                    $current_pid = $this->struct_node_list[$pre_id]->structPid;
                    $current_level = $last_obj->structLevel - 1;
                } else {
                    # 当前右括号，遇到前置类型非右括号，保持层级不变
                    $current_pid = $last_obj->structPid;
                    $current_level = $last_obj->structLevel;
                }
                break;
            default:
                if ($preTokenType == TokenType::SMALL_BRACKET_RIGHT) {
                    # 当前字面量遇到前置右括号，层级-1，继承当前父级的父级。
                    $current_pid = $this->struct_node_list[$pre_id]->structPid;
                    $current_level = $last_obj->structLevel - 1;
                } else {
                    # pid也需要往上走一层
                    $current_pid = $last_obj->structPid;
                    $current_level = $last_obj->structLevel;
                }
                break;
        }
        return array($current_id, $current_pid, $current_level);
    }
}