<?php

namespace LireinCore\ImgCache;

interface IEffect
{
    /**
     * @param IImage $img
     *
     * @return $this
     */
    public function apply(IImage $img);
}