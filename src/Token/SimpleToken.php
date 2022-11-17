<?php
/**
 * @File    :   SimpleToken.php
 * @Author  :   ClanceyHuang
 * @Refer   :   unknown
 * @Desc    :   ...
 * @Version :   PHP7.x
 * @Contact :   ClanceyHuang@outlook.com
 * @Site    :   http://debug.cool
 */


namespace DiyExpress\Token;

class SimpleToken implements IToken{
    /**
     * Token类型
     * @var mixed $type
     */
    var $type = null;

    /**
     * 文本值
     * @var string $text
     */
    var $text = null;

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