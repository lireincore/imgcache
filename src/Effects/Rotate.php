<?php

namespace LireinCore\ImgCache\Effects;

use LireinCore\ImgCache\IEffect;
use LireinCore\ImgCache\Image;

/**
 * Rotate image
 */
class Rotate implements IEffect
{
    /**
     * @var float
     */
    private $_angle;

    /**
     * @var string
     */
    private $_bgcolor;

    /**
     * Rotate constructor.
     *
     * @param float $angle in degrees
     * @param string $bgcolor for example: #fff or rgb(255,255,255) or rgba(255,255,255,0.5) transparent
     */
    public function __construct($angle, $bgcolor = 'transparent')
    {
        $this->_angle = $angle;
        $this->_bgcolor = $bgcolor;
    }

    /**
     * @inheritdoc
     */
    public function apply(Image $img)
    {
        //TODO
        
        return $this;
    }
}