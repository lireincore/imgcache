<?php

namespace LireinCore\ImgCache\Effects;

use LireinCore\ImgCache\IEffect;
use LireinCore\ImgCache\IImage;

/**
 * Image grayscale
 */
class Grayscale implements IEffect
{
    /**
     * @inheritdoc
     */
    public function apply(IImage $img)
    {
        $img->grayscale();

        return $this;
    }
}