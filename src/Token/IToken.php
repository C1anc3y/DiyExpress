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
     * @return mixed Type
     */
    public function getType();

    /**
     * Token的文本值
     * @return mixed Text
     */
    public function getText();
}
