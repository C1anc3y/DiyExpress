<?php
/**
 * @File    :   SimpleLexer.php
 * @Author  :   ClanceyHuang
 * @Refer   :   unknown
 * @Desc    :   ...
 * @Version :   PHP7.x
 * @Contact :   ClanceyHuang@outlook.com
 * @Site    :   http://debug.cool
 */


namespace DiyExpress\Lexer;
use DiyExpress\Dfa\State;
use DiyExpress\Token\TokenType;
use DiyExpress\Token\SimpleToken;
use DiyExpress\Token\SimpleTokenReader;
use DiyExpress\Exception\ParseException;
class SimpleLexer
{

    /** 
     * 临时保存token的文本
     * @var string $tokenText
     */
    public $tokenText = null;

    /**
     * 保存解析出来的Token
     * @var array $tokens
     */
    public $tokens = null;

    /**
     * 当前正在解析的Token
     * @var SimpleToken $token
     */
    public $token = null;

    /**
     * 记录 token 和 type 的关系
     * @var null|array $tokenList
     */
    public $tokenList = null;

    /**
     * 是否是字母
     * @param int|string|null $ch
     * @return bool
     */
    private function isAlpha($ch): bool
    {
        return ($ch >= 'a' && $ch <= 'z') || ($ch >= 'A' && $ch <= 'Z');
    }

    /**
     * 判定是否为中文
     * @param $ch
     * @return bool
     */
    private function isChinese($ch): bool
    {
        if (preg_match("/[\x7f-\xff]/", $ch)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 是否是数字
     * @param int|string|null $ch
     * @return bool
     */
    private function isDigit($ch): bool
    {
        return $ch >= '0' && $ch <= '9';
    }

    /**
     * 是否是空白字符
     * @param int|string|null $ch
     * @return bool
     */
    private function isBlank($ch): bool
    {
        return $ch == ' ' || $ch == '\t' || $ch == '\n';
    }

    /**
     * 解析字符串，形成 IToken 。
     * 这是一个有限状态自动机，在不同的状态中迁移。
     * @param string $code
     * @return SimpleTokenReader
     */
    public function tokenize(string $code)
    {
        # 初始化tokens
        $this->tokens = [];
        $reader = str_split($code) ?: array();

        # 初始化tokenText
        $this->tokenText = "";
        $SimpleToken = new SimpleToken();
        # 初始状态
        $state = State::INITIAL;
        try {
            foreach ($reader as $value) {
                # -1 表示终止
                if ($value != -1) {
                    $ch = $ich = $value;
                    switch ($state) {
                        case State::INITIAL:
                            # 重新确定后续状态
                            $state = $this->initToken($SimpleToken, $ch);
                            break;
                        case State::IDENTIFIER:
                            if ($this->isAlpha($ch) || $this->isDigit($ch) || $this->isChinese($ch)) {
                                # 保持标识符状态
                                $this->tokenText .= $ch;
                            }
                            else {
                                # 退出标识符状态，并保存Token
                                $state = $this->initToken($SimpleToken, $ch);
                            }
                            break;
                        case State::GT:
                            if ($ch == '=') {
                                # 转换成GE
                                $SimpleToken->type = TokenType::GE;
                                $state = State::GE;
                                $this->tokenText .= $ch;
                            }
                            else {
                                # 退出GT状态，并保存Token
                                $state = $this->initToken($SimpleToken, $ch);
                            }
                            break;
                        case State::GE:
                        case State::LT:
                            if ($ch == '='){
                                # 转换成LE
                                $SimpleToken->type = TokenType::LE;
                                $state = State::LE;
                                $this->tokenText .= $ch;
                            }else{
                                $state = $this->initToken($SimpleToken,$ch);
                            }
                            break;
                        case State::LE:
                        case State::ASSIGNMENT:
                            if ($ch == '=') {
                                $SimpleToken->type = TokenType::EQ;
                                $state = State::EQ;
                                $this->tokenText .= $ch;
                            } else {
                                $state = $this->initToken($SimpleToken, $ch);
                            }
                            break;
                        case State::COMMA:
                        case State::EQ:
                        case State::NUM_DOT:
                        case State::ADDITION:
                        case State::SUBTRACTION:
                        case State::MULTIPLICATION:
                        case State::DIVISION:
                        case State::SEMI_COLON:
                        case State::SMALL_BRACKET_LEFT:
                        case State::LARGE_BRACKET_LEFT:
                        case State::LARGE_BRACKET_RIGHT:
                        case State::SMALL_BRACKET_RIGHT:
                            # 退出当前状态，并保存Token
                            $state = $this->initToken($SimpleToken, $ch);
                            break;
                        case State::INT_LITERAL:
                            if ($this->isDigit($ch)) {
                                # 继续保持在数字字面量状态
                                $this->tokenText .= $ch;
                            } elseif ($ch == '.') {
                                # 继续保持在数字字面量状态
                                $this->tokenText .= $ch;
                            } else {
                                # 退出当前状态，并保存Token
                                $state = $this->initToken($SimpleToken, $ch);
                            }
                            break;
                        case State::DOUBLE_QUOTES:
                            # 双引号的处理
                            if ($ch == '"') {
                                $state = State::VALUE_STRING;
                            } else {
                                $state = State::DOUBLE_QUOTES;
                            }
                            $this->tokenText .= $ch;
                            break;
                        case State::APOSTROPHE:
                            # 单引号的处理
                            if ($ch == "'") {
                                $state = State::VALUE_STRING;
                            } else {
                                $state = State::APOSTROPHE;
                            }
                            $this->tokenText .= $ch;
                            break;
                        case State::VALUE_STRING:
                        case State::ID_AND1:
                            if ($ch == 'N' || $ch == 'n') {
                                $state = State::ID_AND2;
                                $this->tokenText .= $ch;
                            }
                            elseif($ch == 'V' || $ch == 'v'){
                                $state = State::ID_AVG2;
                                $this->tokenText .= $ch;
                            }
                            elseif ($this->isDigit($ch) || $this->isAlpha($ch) || $this->isChinese($ch)) {
                                $state = State::IDENTIFIER;
                                $this->tokenText .= $ch;
                            } else {
                                $state = $this->initToken($SimpleToken, $ch);
                            }
                            break;
                        case State::ID_AND2:
                            if ($ch == 'D' || $ch == 'd') {
                                $state = State::ID_AND3;
                                $this->tokenText .= $ch;
                            }
                            elseif ($this->isDigit($ch) || $this->isAlpha($ch) || $this->isChinese($ch)) {
                                $state = State::IDENTIFIER;
                                $this->tokenText .= $ch;
                            }
                            else {
                                $state = $this->initToken($SimpleToken, $ch);
                            }
                            break;
                        case State::ID_AND3:
                            if ($this->isBlank($ch)) {
                                $SimpleToken->type = TokenType::ID_AND;
                                $state = $this->initToken($SimpleToken, $ch);
                            }
                            else {
                                $state = State::IDENTIFIER;
                                $this->tokenText .= $ch;
                            }
                            break;
                        case State::ID_AVG2:
                            if ($ch == 'G' || $ch == 'g'){
                                $SimpleToken->type = TokenType::ID_AVG;
                                $state = $this->initToken($SimpleToken,$ch);
                            }
                            else{
                                $state = State::IDENTIFIER;
                                $this->tokenText .=$ch;
                            }
                            break;
                        case State::ID_OR1:
                            if ($ch == 'R' || $ch == 'r') {
                                $state = State::ID_OR2;
                                $this->tokenText .= $ch;
                            }
                            elseif ($this->isDigit($ch) || $this->isAlpha($ch) || $this->isChinese($ch)) {
                                $state = State::IDENTIFIER;
                                $this->tokenText .= $ch;
                            }
                            else {
                                $state = $this->initToken($SimpleToken, $ch);
                            }
                            break;
                        case State::ID_OR2:
                            if ($this->isBlank($ch)) {
                                $SimpleToken->type = TokenType::ID_OR;
                                $state = $this->initToken($SimpleToken, $ch);
                            }
                            else {
                                $state = State::IDENTIFIER;
                                $this->tokenText .= $ch;
                            }
                            break;
                        case State::ID_IF1:
                            if ($ch == 'f' || $ch == 'F') {
                                $state = State::ID_IF2;
                                $this->tokenText .= $ch;
                            }
                            elseif ($ch == 'n' || $ch == 'N') {
                                $state = State::ID_IN2;
                                $this->tokenText .= $ch;
                            }
                            elseif ($this->isDigit($ch) || $this->isAlpha($ch) || $this->isChinese($ch)) {
                                $state = State::IDENTIFIER;
                                $this->tokenText .= $ch;
                            }
                            else {
                                $state = $this->initToken($SimpleToken, $ch);
                            }
                            break;
                        case State::ID_IF2:
                            if ($this->isBlank($ch) || $ch == '(') {
                                $SimpleToken->type = TokenType::ID_IF;
                                $state = $this->initToken($SimpleToken, $ch);
                            }
                            else {
                                $state = State::IDENTIFIER;
                                $this->tokenText .= $ch;
                            }
                            break;
                        case State::ID_ELSE1:
                            if ($ch == 'L' || $ch == 'l') {
                                $state = State::ID_ELSE2;
                                $this->tokenText .= $ch;
                            }
                            elseif ($this->isDigit($ch) || $this->isAlpha($ch) || $this->isChinese($ch)) {
                                $state = State::IDENTIFIER;
                                $this->tokenText .= $ch;
                            }
                            else {
                                $state = $this->initToken($SimpleToken, $ch);
                            }
                            break;
                        case State::ID_ELSE2:
                            if ($ch == 'S' || $ch == 's') {
                                $state = State::ID_ELSE3;
                                $this->tokenText .= $ch;
                            }
                            elseif ($this->isDigit($ch) || $this->isAlpha($ch) || $this->isChinese($ch)) {
                                $state = State::IDENTIFIER;
                                $this->tokenText .= $ch;
                            }
                            else {
                                $state = $this->initToken($SimpleToken, $ch);
                            }
                            break;
                        case State::ID_ELSE3:
                            if ($ch == 'E' || $ch == 'e') {
                                $state = State::ID_ELSE4;
                                $this->tokenText .= $ch;
                            }
                            elseif ($this->isDigit($ch) || $this->isAlpha($ch) || $this->isChinese($ch)) {
                                $state = State::IDENTIFIER;
                                $this->tokenText .= $ch;
                            }
                            else {
                                $state = $this->initToken($SimpleToken, $ch);
                            }
                            break;
                        case State::ID_ELSE4:
                            if ($this->isBlank($ch)) {
                                $SimpleToken->type = TokenType::ID_ELSE;
                                $state = $this->initToken($SimpleToken, $ch);
                            }
                            else {
                                $state = State::IDENTIFIER;
                                $this->tokenText .= $ch;
                            }
                            break;
                        case State::ID_IN2:
                            if ($this->isBlank($ch)) {
                                $SimpleToken->type = TokenType::ID_IN;
                                $state = $this->initToken($SimpleToken, $ch);
                            }
                            else {
                                # 切换回Id状态
                                $state = State::IDENTIFIER;
                                $this->tokenText .= $ch;
                            }
                            break;
                        case State::ID_INT1:
                            if ($ch == 'n') {
                                $state = State::ID_INT2;
                                $this->tokenText .= $ch;
                            }
                            elseif ($this->isDigit($ch) || $this->isAlpha($ch) || $this->isChinese($ch)) {
                                # 切换回Id状态
                                $state = State::IDENTIFIER;
                                $this->tokenText .= $ch;
                            }
                            else {
                                $state = $this->initToken($SimpleToken, $ch);
                            }
                            break;
                        case State::ID_INT2:
                            if ($ch == 't') {
                                $state = State::ID_INT3;
                                $this->tokenText .= $ch;
                            }
                            elseif ($this->isDigit($ch) || $this->isAlpha($ch) || $this->isChinese($ch)) {
                                # 切换回id状态
                                $state = State::IDENTIFIER;
                                $this->tokenText .= $ch;
                            }
                            else {
                                $state = $this->initToken($SimpleToken, $ch);
                            }
                            break;
                        case State::ID_INT3:
                            if ($this->isBlank($ch)) {
                                $SimpleToken->type = TokenType::ID_INT;
                                $state = $this->initToken($SimpleToken, $ch);
                            }
                            else {
                                # 切换回Id状态
                                $state = State::IDENTIFIER;
                                $this->tokenText .= $ch;
                            }
                            break;
                        case State::ID_MAX1:
                            if ($ch == 'A' || $ch == 'a'){
                                $state = State::ID_MAX2;
                                $this->tokenText .= $ch;
                            }
                            elseif($ch == 'I' || $ch == 'i'){
                                $state = State::ID_MIN2;
                                $this->tokenText .= $ch;
                            }
                            elseif ($this->isDigit($ch) || $this->isAlpha($ch) || $this->isChinese($ch)) {
                                # 切换回id状态
                                $state = State::IDENTIFIER;
                                $this->tokenText .= $ch;
                            } else{
                                $this->initToken($SimpleToken, $ch);
                            }
                            break;
                        case State::ID_MAX2:
                            if ($ch == 'X' || $ch == 'x'){
                                $state = State::ID_MAX3;
                                $this->tokenText .= $ch;
                            }
                            elseif ($this->isDigit($ch) || $this->isAlpha($ch) || $this->isChinese($ch)) {
                                # 切换回id状态
                                $state = State::IDENTIFIER;
                                $this->tokenText .= $ch;
                            }
                            else{
                                $this->initToken($SimpleToken, $ch);
                            }
                            break;
                        case State::ID_MAX3:
                            if ($this->isBlank($ch)){
                                $SimpleToken->type = TokenType::ID_MAX;
                                $state = $this->initToken($SimpleToken, $ch);
                            }
                            else{
                                $state = State::IDENTIFIER;
                                $this->tokenText .= $ch;
                            }
                            break;
                        case State::ID_MIN2:
                            if ($ch == 'N' || $ch == 'n'){
                                $state = State::ID_MIN3;
                            }
                            elseif ($this->isDigit($ch) || $this->isAlpha($ch) || $this->isChinese($ch)) {
                                # 切换回id状态
                                $state = State::IDENTIFIER;
                            }
                            else{
                                $state = State::IDENTIFIER;
                            }
                            $this->tokenText .= $ch;
                            break;
                        case State::ID_MIN3:
                            if ($this->isBlank($ch)){
                                $SimpleToken->type = TokenType::ID_MIN;
                                $state = $this->initToken($SimpleToken,$ch);
                            }
                            else{
                                $state = State::IDENTIFIER;
                                $this->tokenText .= $ch;
                            }
                            break;
                        case State::ID_SUM1:
                            if ($ch == 'U' || $ch == 'u'){
                                $state = State::ID_SUM2;
                            }
                            elseif ($this->isDigit($ch) || $this->isAlpha($ch) || $this->isChinese($ch)) {
                                # 切换回id状态
                                $state = State::IDENTIFIER;
                            }
                            else{
                                $state = State::IDENTIFIER;
                            }
                            $this->tokenText .= $ch;
                            break;
                        case State::ID_SUM2:
                            if ($ch == 'M' || $ch == 'm'){
                                $state = State::ID_SUM3;
                                $this->tokenText .= $ch;
                            }
                            elseif ($this->isDigit($ch) || $this->isAlpha($ch) || $this->isChinese($ch)) {
                                # 切换回id状态
                                $state = State::IDENTIFIER;
                                $this->tokenText .= $ch;
                            }
                            else{
                                $state = $this->initToken($SimpleToken,$ch);
                            }
                            break;
                        case State::ID_SUM3:
                            if ($this->isBlank($ch)){
                                $SimpleToken->type = TokenType::ID_SUM;
                                $this->tokenText .= $ch;
                            }
                            elseif ($this->isDigit($ch) || $this->isAlpha($ch) || $this->isChinese($ch)) {
                                # 切换回id状态
                                $state = State::IDENTIFIER;
                                $this->tokenText .= $ch;
                            }
                            else{
                                $state = $this->initToken($SimpleToken,$ch);
                                $state = State::IDENTIFIER;

                            }
                            break;
                        default:
                            break;
                    }
                }
            }

            if (strlen($this->tokenText) > 0) {
                $this->initToken($SimpleToken, $ch);
            }
        } catch (ParseException $e) {
            echo $e;
        }
        return $this->tokens ? new SimpleTokenReader($this->tokens) : null;
    }

    /**
     * 有限状态机进入初始状态。
     * 这个初始状态其实并不做停留，它马上进入其他状态。
     * 开始解析的时候，进入初始状态；某个Token解析完毕，也进入初始状态，在这里把Token记下来，然后建立一个新的Token。
     * @param $ch
     * @param $SimpleToken
     * @return  string $newState
     */
    private function initToken(&$SimpleToken, $ch): string
    {
        if (strlen($this->tokenText) > 0) {
            $SimpleToken->text = $this->tokenText;
            $this->tokens[] = $SimpleToken;

            $this->tokenText = "";
            $SimpleToken = new SimpleToken();
        }

        # 默认的初始状态
        $newState = State::INITIAL;

        if ($this->isAlpha($ch) || $this->isChinese($ch)) { # 第一个字符是字母
            if ($ch == 'I' || $ch == 'i') {         # I 开头，暂时用 if 标记
                $newState = State::ID_IF1;
            } elseif ($ch == 'E' || $ch == 'e') {   # e开头，暂时用else标记
                $newState = State::ID_ELSE1;
            } elseif ($ch == 'A' || $ch == 'a') {   # A 开头暂时用 AND 标记，包含 and、avg
                $newState = State::ID_AND1;
            } elseif ($ch == 'O' || $ch == 'o') {   # O 开头暂时用 or 标记
                $newState = State::ID_OR1;
            } elseif ($ch == 'M' || $ch == 'm'){    # m 开头暂时用Max表示 ，包含 max、min
                $newState = State::ID_MAX1;
            } elseif ($ch == 'S' || $ch == 's'){    # S 开头暂时用sum表示
                $newState = State::ID_SUM1;
            }
            else {
                # 进入Id状态
                $newState = State::IDENTIFIER;
            }
            $SimpleToken->type = TokenType::IDENTIFIER;
            $this->tokenText .= $ch;
        }
        elseif ($this->isDigit($ch)) {       # 第一个字符是数字
            $newState = State::INT_LITERAL;
            $SimpleToken->type = TokenType::INT_LITERAL;
            $this->tokenText .= $ch;
        }
        elseif ($ch == ',') { # 第一个字符是逗号
            $newState = State::COMMA;
            $SimpleToken->type = TokenType::COMMA;
            $this->tokenText .= $ch;
        }
        elseif ($ch == '>') {         # 第一个字符是>
            $newState = State::GT;
            $SimpleToken->type = TokenType::GT;
            $this->tokenText .= $ch;
        }
        elseif($ch == '<'){
            $newState = State::LT;
            $SimpleToken->type = TokenType::LT;
            $this->tokenText .= $ch;
        }
        elseif ($ch == '+') {
            $newState = State::ADDITION;
            $SimpleToken->type = TokenType::ADDITION;
            $this->tokenText .= $ch;
        }
        elseif ($ch == '-') {
            $newState = State::SUBTRACTION;
            $SimpleToken->type = TokenType::SUBTRACTION;
            $this->tokenText .= $ch;
        }
        elseif ($ch == '*') {
            $newState = State::MULTIPLICATION;
            $SimpleToken->type = TokenType::MULTIPLICATION;
            $this->tokenText .= $ch;
        }
        elseif ($ch == '/') {
            $newState = State::DIVISION;
            $SimpleToken->type = TokenType::DIVISION;
            $this->tokenText .= $ch;
        }
        elseif ($ch == ';') {
            $newState = State::SEMI_COLON;
            $SimpleToken->type = TokenType::SEMI_COLON;
            $this->tokenText .= $ch;
        }
        elseif ($ch == '{') {
            $newState = State::LARGE_BRACKET_LEFT;
            $SimpleToken->type = TokenType::LARGE_BRACKET_LEFT;
            $this->tokenText .= $ch;
        }
        elseif ($ch == '}') {
            $newState = State::LARGE_BRACKET_RIGHT;
            $SimpleToken->type = TokenType::LARGE_BRACKET_RIGHT;
            $this->tokenText .= $ch;
        }
        elseif ($ch == '(') {
            $newState = State::SMALL_BRACKET_LEFT;
            $SimpleToken->type = TokenType::SMALL_BRACKET_LEFT;
            $this->tokenText .= $ch;
        }
        elseif ($ch == ')') {
            $newState = State::SMALL_BRACKET_RIGHT;
            $SimpleToken->type = TokenType::SMALL_BRACKET_RIGHT;
            $this->tokenText .= $ch;
        }
        elseif ($ch == '=') {
            $newState = State::ASSIGNMENT;
            $SimpleToken->type = TokenType::ASSIGNMENT;
            $this->tokenText .= $ch;
        }
        elseif ($ch == '"') {
            $newState = State::DOUBLE_QUOTES;
            $SimpleToken->type = TokenType::STRING_LITERAL;
            $this->tokenText .= $ch;
        }
        elseif ($ch == "'") {
            $newState = State::APOSTROPHE;
            $SimpleToken->type = TokenType::STRING_LITERAL;
            $this->tokenText .= $ch;
        }
        else {
            # 跳过所有未知的范式
            $newState = State::INITIAL;
        }
        return $newState;
    }
}