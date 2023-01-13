<?php

namespace DiyExpress\Token;

class SimpleToken implements IToken
{
    /**
     * Token类型
     * @var mixed $type
     */
    var $type = null;

    /**
     * 文本值
     * @var string $text
     */
    public ?string $text = null;

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string|null
     */
    public final function getText()
    {
        return $this->text;
    }
}