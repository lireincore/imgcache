<?php

namespace LireinCore\ImgCache\Effects;

use LireinCore\ImgCache\IEffect;
use LireinCore\ImgCache\TPixel;
use LireinCore\ImgCache\IImage;

/**
 * Resize image
 */
class Resize implements IEffect
{
    use TPixel;

    /**
     * @var string
     */
    private $_width;

    /**
     * @var string
     */
    private $_height;

    /**
     * @var string
     */
    private $_filter;

    /**
     * Resize constructor.
     * 
     * @param string $width for example: 100px | 20%
     * @param string $height for example: 100px | 20%
     * @param string $filter
     */
    public function __construct($width, $height, $filter = null)
    {
        $this->_width = $width;
        $this->_height = $height;
        $this->_filter = $filter;
    }

    /**
     * @inheritdoc
     */
    public function apply(IImage $img)
    {
        $origWidth = $img->getWidth();
        $origHeight = $img->getHeight();
        $width = $this->getPxSize($this->_width, $origWidth);
        $height = $this->getPxSize($this->_height, $origHeight);

        $img->resize($width, $height/*, $this->_filter*/);

        return $this;
    }
}