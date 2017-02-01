<?php

namespace LireinCore\ImgCache;

interface IEffect
{
    /**
     * @param Image $img
     * @return $this
     */
    public function apply(Image $img);
}