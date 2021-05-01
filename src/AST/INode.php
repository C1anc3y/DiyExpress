<?php

/**
 * @File    :   INode.php
 * @Author  :   ClanceyHuang
 * @Refer   :   unknown
 * @Desc    :   ...
 * @Version :   PHP7.x
 * @Contact :   ClanceyHuang@outlook.com
 * @Site    :   http://debug.cool
 */

namespace DiyExpress\AST;

interface INode
{
    /**
     * 父节点
     * @return INode
     */
    public function getParent();

    /**
     * 子节点
     * @return INode
     */
    public function getChildren();

    //

    /**
     * AST类型
     * @return NodeType
     */
    public function getType();

    /**
     * 文本值
     * @return string
     */
    public function getText();
}
