<?php
/**
 * @File    :   SimpleFormula.php
 * @Author  :   ClanceyHuang
 * @Refer   :   unknown
 * @Desc    :   ...
 * @Version :   PHP7.x
 * @Contact :   ClanceyHuang@outlook.com
 * @Site    :   http://debug.cool
 */


namespace DiyExpress\Formula;
interface IFormula{
    /**
     * 结构的类型
     * @return string|null $structType
     */
    public function getStructType();

    /**
     * @param string $structType
     * @return void
     */
    public function setStructType(string $structType);

    /**
     * 结构的文本值，可能是变量，可能是表达式
     * @return string|null $structText
     */
    public function getStructText();

    /**
     * @param string $structText
     * @return void
     */
    public function setStructText(string $structText);

    /**
     * 子节点
     * @return mixed
     */
    public function getStructChild();

    /**
     * @param $structChild
     * @return void
     */
    public function setStructChild($structChild);
}