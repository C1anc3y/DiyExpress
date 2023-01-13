<?php

namespace DiyExpress\Token;

interface IToken
{
    /**
     * Token的类型
     * @return mixed Type
     */
    public function getType();

    /**
     * Token的文本值
     * @return mixed Text
     */
    public function getText();
}
