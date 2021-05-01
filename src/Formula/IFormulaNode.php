<?php
/**
 * @File    :   IFormulaNode.php
 * @Author  :   ClanceyHuang
 * @Refer   :   unknown
 * @Desc    :   ...
 * @Version :   PHP7.x
 * @Contact :   ClanceyHuang@outlook.com
 * @Site    :   http://debug.cool
 */


namespace DiyExpress\Formula;

class IFormulaNode implements IFormula {

    public $structId = null;
    public $structPid = null;
    public $structType = null;
    public $structText = null;
    public $structLevel = null;
    public $structChild = null;

    public function __construct($structType, $structText, $structId = 0, $structPid = 0, $structLevel = 0)
    {
        $this->structId = $structId;
        $this->structPid = $structPid;
        $this->structType = $structType;
        $this->structText = $structText;
        $this->structLevel = $structLevel;
    }

    /**
     * @inheritDoc
     */
    public function getStructType()
    {
        return $this->structType;
    }

    /**
     * @inheritDoc
     */
    public function setStructType(string $structType)
    {
        $this->structType = $structType;
    }

    /**
     * @inheritDoc
     */
    public function getStructText()
    {
        return $this->structText;
    }

    /**
     * @inheritDoc
     */
    public function setStructText(string $structText)
    {
        $this->structText = $structText;
    }

    /**
     * @inheritDoc
     */
    public function getStructChild()
    {
        return $this->structChild;
    }

    /**
     * @inheritDoc
     */
    public function setStructChild($structChild)
    {
        $this->structChild = $structChild;
    }
}