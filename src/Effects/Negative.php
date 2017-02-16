<?php

namespace LireinCore\ImgCache\Effects;

use LireinCore\ImgCache\IEffect;
use LireinCore\ImgCache\IImage;

/**
 * Image negative
 */
class Negative implements IEffect
{
    /**
     * @inheritdoc
     */
    public function apply(IImage $img)
    {
        $img->negative();
        
        return $this;
    }
}