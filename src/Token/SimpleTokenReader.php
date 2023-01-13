<?php

namespace DiyExpress\Token;

class SimpleTokenReader implements ITokenReader
{
    /**
     * @var  null|array $tokens List<IToken>
     */
    public $tokens = null;

    /**
     * @var int $pos
     */
    public int $pos = 0;

    /**
     * 聚合起来的expression
     * @var null $exp
     */
    public $exp = null;

    /**
     * SimpleTokenReader constructor.
     * @param array $tokens List<IToken>
     */
    public function __construct(array $tokens)
    {
        $this->tokens = $tokens;
    }

    /**
     * 读取对应的tokens
     * @Override
     * @return SimpleToken|null
     */
    public function read()
    {
        if ($this->pos < count($this->tokens)) {
            # 指针往后移动
            $this->pos++;
            # 往回当前的的token
            return $this->tokens[$this->pos];
        } else {
            return null;
        }
    }


    /**
     * @Override
     * @return SimpleToken|null
     */
    public function peek()
    {
        if ($this->pos < count($this->tokens)) {
            return $this->tokens[$this->pos];
        } else {
            return null;
        }
    }

    /**
     * 预览上一个节点
     * @return SimpleToken|null
     */
    public function peekPre()
    {
        if ($this->pos <= count($this->tokens) && $this->pos > 0) {
            # 指针大于0且在当前tokens的范围内
            $point = $this->pos - 1;
            return $this->tokens[$point];
        } else {
            return null;
        }
    }

    /**
     * 预览下一个节点
     * @return SimpleToken|null
     */
    public function peekNext()
    {
        if ($this->pos < count($this->tokens) && $this->pos >= 0) {
            $point = $this->pos + 1;
            return $this->tokens[$point];
        } else {
            return null;
        }
    }

    /**
     * @Override
     */
    public function unread()
    {
        if ($this->pos > 0) {
            $this->pos--;
        }
    }

    /**
     * 获取当前位置
     * @Override
     */
    public function getPosition(): int
    {
        return $this->pos;
    }

    /**
     * 设置当前位置
     * @Override
     * @param int $position
     */
    public function setPosition(int $position)
    {
        if ($position >= 0 && $position < count($this->tokens)) {
            $this->pos = $position;
        }
    }

    /**
     * 设置表达式
     * @param $chars
     */
    public function setExp($chars)
    {
        $this->exp[] = $chars;
    }

    /**
     * 获取指定的节点。
     * @param int $pos
     * @return SimpleToken|null
     */
    public function peekByPos($pos = 0)
    {
        if ($pos && $pos < count($this->tokens)) {
            return $this->tokens[$pos];
        } elseif ($this->pos < count($this->tokens)) {
            return $this->tokens[$this->pos];
        } else {
            return null;
        }
    }
}