<?php

namespace LireinCore\ImgCache\Effects;

use LireinCore\ImgCache\IEffect;
use LireinCore\ImgCache\Image;

/**
 * Image negative
 */
class Negative implements IEffect
{
    /**
     * @inheritdoc
     */
    public function apply(Image $img)
    {
        $img->negative();
        
        return $this;
    }
}