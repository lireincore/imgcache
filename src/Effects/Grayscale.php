<?php

namespace LireinCore\ImgCache\Effects;

use LireinCore\ImgCache\IEffect;
use LireinCore\ImgCache\Image;

/**
 * Image grayscale
 */
class Grayscale implements IEffect
{
    /**
     * @inheritdoc
     */
    public function apply(Image $img)
    {
        $img->grayscale();

        return $this;
    }
}