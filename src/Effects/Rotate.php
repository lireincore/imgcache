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
     * @var int
     */
    private $_bgtransparency;

    /**
     * Rotate constructor.
     *
     * @param float $angle in degrees
     * @param string $bgcolor for example: #fff or #ffffff or rgb(255,255,255) //todo!!!! rgb формат не понимает
     * @param int $bgtransparency for example: 0
     */
    public function __construct($angle, $bgcolor = '#fff', $bgtransparency = 0)
    {
        $this->_angle = $angle;
        $this->_bgcolor = $bgcolor;
        $this->_bgtransparency = $bgtransparency;
    }

    /**
     * @inheritdoc
     */
    public function apply(Image $img)
    {
        $img->rotate($this->_angle, $this->_bgcolor, $this->_bgtransparency);
        
        return $this;
    }
}