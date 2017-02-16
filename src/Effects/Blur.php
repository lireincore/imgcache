<?php

namespace LireinCore\ImgCache\Effects;

use LireinCore\ImgCache\IEffect;
use LireinCore\ImgCache\IImage;

/**
 * Image gaussian blur
 */
class Blur implements IEffect
{
    /**
     * @var float
     */
    private $_sigma;

    /**
     * Blur constructor.
     *
     * @param float $sigma for example: 2
     */
    public function __construct($sigma = 1.0)
    {
        $this->_sigma = $sigma;
    }

    /**
     * @inheritdoc
     */
    public function apply(IImage $img)
    {
        $img->blur($this->_sigma);

        return $this;
    }
}