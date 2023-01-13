<?php

namespace DiyExpress\AST;

class SimpleNode implements INode
{
    /**
     * 父节点
     * @var mixed $parent SimpleINode|null
     */
    var $parent = null;

    /**
     * 子节点
     * @var INode|array $children
     */
    var $children;

    /**
     * @var INode|array $readonlyChildren
     */
    var $readonlyChildren;

    /**
     * 类型
     * @var NodeType|null
     */
    var $nodeType = null;

    /**
     * 文本值
     * @var null|string
     */
    public ?string $text = null;

    public int $level = 0;

    /**
     * 初始化节点
     * SimpleINode constructor.
     * @param string $nodeType NodeType
     * @param $text
     * @param int $level
     */
    public function __construct(string $nodeType, $text, $level = 0)
    {
        $this->nodeType = $nodeType;
        $this->text = $text;
        $this->level = $level;
    }


    /**
     * 获取父节点
     * @Override
     */
    function getParent()
    {
        return $this->parent;
    }


    /**
     * 获取子节点
     * @Override
     * @return INode|array
     */
    function getChildren()
    {
        return $this->readonlyChildren;
    }


    /**
     * 获取类型
     * @Override
     * @return NodeType
     */
    function getType()
    {
        return $this->nodeType;
    }


    /**
     * 获取文本
     * @Override
     * @return string
     */
    function getText()
    {
        return $this->text;
    }

    /**
     * 添加子节点
     * @param mixed $child SimpleNode
     * @return void
     */
    function addChild($child)
    {
        $this->children[] = $child;
    }

    function addParent($child)
    {
        $this->parent[] = $child;
    }

    /**
     * @return int
     */
    function getLevel()
    {
        return $this->level;
    }

    /**
     * @param $level
     * @return void
     */
    function setLevel($level)
    {
        $this->level = $level;
    }
}
