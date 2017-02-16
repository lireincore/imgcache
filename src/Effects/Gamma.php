<?php

namespace LireinCore\ImgCache\Effects;

use LireinCore\ImgCache\IEffect;
use LireinCore\ImgCache\IImage;

/**
 * Image gamma correction
 */
class Gamma implements IEffect
{
    /**
     * @var float
     */
    private $_correction;

    /**
     * Gamma constructor.
     *
     * @param float $correction for example: 0.7
     */
    public function __construct($correction)
    {
        $this->_correction = $correction;
    }

    /**
     * @inheritdoc
     */
    public function apply(IImage $img)
    {
        $img->gamma($this->_correction);

        return $this;
    }
}