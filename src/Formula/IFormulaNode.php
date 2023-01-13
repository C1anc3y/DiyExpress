<?php

namespace DiyExpress\Formula;

class IFormulaNode implements IFormula
{

    public ?int $structId = null;
    public ?int $structPid = null;
    public ?string $structType = null;
    public ?string $structText = null;
    public ?int $structLevel = null;
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