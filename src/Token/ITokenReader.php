<?php

namespace DiyExpress\Token;
interface ITokenReader
{
    /**
     * 返回Token流中下一个Token，并从流中取出。 如果流已经为空，返回null;
     * @return mixed
     */
    public function read();

    /**
     * 返回Token流中下一个Token，但不从流中取出。 如果流已经为空，返回null;
     * @return mixed
     */
    public function peek();

    /**
     * Token流回退一步。恢复原来的Token。
     */
    public function unread();

    /**
     * 获取Token流当前的读取位置。
     * @return int
     */
    public function getPosition(): int;

    /**
     * 设置Token流当前的读取位置
     * @param int $position
     */
    public function setPosition(int $position);
}