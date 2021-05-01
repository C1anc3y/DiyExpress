<?php
/**
 * @File    :   IToken.php
 * @Author  :   ClanceyHuang
 * @Refer   :   unknown
 * @Desc    :   ...
 * @Version :   PHP7.x
 * @Contact :   ClanceyHuang@outlook.com
 * @Site    :   http://debug.cool
 */


namespace DiyExpress\Token;

interface IToken{
    /**
     * Token的类型
     * @return mixed|TokenType
     */
    public function getType();

    /**
     * Token的文本值
     * @return mixed|string
     */
    public function getText();
}
