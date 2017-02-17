<?php

namespace LireinCore\ImgCache\Effects;

use LireinCore\ImgCache\IEffect;
use LireinCore\ImgCache\TPixel;
use LireinCore\ImgCache\IImage;

/**
 * Rotate image
 */
class Rotate implements IEffect
{
    use TPixel;

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
     * @param string $bgcolor for example: '#fff' or '#ffffff' - hex | '50,50,50' - rgb | '50,50,50,50' - cmyk
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
    public function apply(IImage $img)
    {
        $bgcolor = $this->parseColor($this->_bgcolor);
        $img->rotate($this->_angle, $bgcolor, $this->_bgtransparency);
        
        return $this;
    }
}