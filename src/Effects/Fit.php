<?php

namespace LireinCore\ImgCache\Effects;

use LireinCore\ImgCache\IEffect;
use LireinCore\ImgCache\TPixel;
use LireinCore\ImgCache\Image;

/**
 * Fit image in box
 */
class Fit implements IEffect
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
     * @var string
     */
    private $_bgcolor;

    /**
     * @var int
     */
    private $_bgtransparency;

    /**
     * @var bool
     */
    private $_allow_increase;

    /**
     * Fit constructor.
     *
     * @param string $offset_x for example: 100px | 20% | center | left | right
     * @param string $offset_y for example: 100px | 20% | center | top | bottom
     * @param string $width for example: 100px | 20%
     * @param string $height for example: 100px | 20%
     * @param string $bgcolor for example: #fff or #ffffff or rgb(255,255,255) //todo!!!! rgb формат не понимает
     * @param int $bgtransparency for example: 0
     * @param bool|false $allow_increase увеличивать изображение до максимального, если оно меньше
     */
    public function __construct($offset_x, $offset_y, $width, $height, $bgcolor = '#fff', $bgtransparency = 0, $allow_increase = false)
    {
        $this->_offset_x = $offset_x;
        $this->_offset_y = $offset_y;
        $this->_width = $width;
        $this->_height = $height;
        $this->_bgcolor = $bgcolor;
        $this->_bgtransparency = $bgtransparency;
        $this->_allow_increase = $allow_increase;
    }

    /**
     * @inheritdoc
     */
    public function apply(Image $img)
    {
        $origWidth = $img->getWidth();
        $origHeight = $img->getHeight();
        $width = $this->getPxSize($this->_width, $origWidth);
        $height = $this->getPxSize($this->_height, $origHeight);

        if ($origWidth != $width && $origHeight != $height) {
            $oldImage = clone $img; //todo!!!! в imagine есть своя операция клонирования
            $oldImage->apply(new Scale($width, $height, 'up', $this->_allow_increase));
            $img->create($width, $height, $this->_bgcolor, $this->_bgtransparency);

            $offset_x = $this->getWtOffset($this->_offset_x, $img->getWidth(), $oldImage->getWidth());
            $offset_y = $this->getWtOffset($this->_offset_y, $img->getHeight(), $oldImage->getHeight());

            $img->paste($oldImage, $offset_x, $offset_y);
        }

        return $this;
    }
}