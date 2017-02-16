<?php

namespace LireinCore\ImgCache\Effects;

use LireinCore\ImgCache\IEffect;
use LireinCore\ImgCache\TPixel;
use LireinCore\ImgCache\IImage;

/**
 * Crop image
 */
class Crop implements IEffect
{
    use TPixel;

    /**
     * @var string
     */
    private $_offset_x;

    /**
     * @var string
     */
    private $_offset_y;

    /**
     * @var string
     */
    private $_width;

    /**
     * @var string
     */
    private $_height;

    /**
     * Crop constructor.
     *
     * @param string $offset_x for example: 100px | 20% | center
     * @param string $offset_y for example: 100px | 20% | center
     * @param string $width for example: 100px | 20%
     * @param string $height for example: 100px | 20%
     */
    public function __construct($offset_x, $offset_y, $width, $height)
    {
        $this->_offset_x = $offset_x;
        $this->_offset_y = $offset_y;
        $this->_width = $width;
        $this->_height = $height;
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
        $offset_x = $this->getPxOffset($this->_offset_x, $origWidth, $width);
        $offset_y = $this->getPxOffset($this->_offset_y, $origHeight, $height);

        $img->crop($offset_x, $offset_y, $width, $height);

        return $this;
    }
}