<?php

namespace LireinCore\ImgCache\Effects;

use LireinCore\ImgCache\IEffect;
use LireinCore\ImgCache\TPixel;
use LireinCore\ImgCache\IImage;

/**
 * Scale image
 */
class Scale implements IEffect
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
    private $_direct;

    /**
     * @var bool
     */
    private $_allow_fit;

    /**
     * Scale constructor.
     *
     * @param string $width for example: 100px | 20% | auto
     * @param string $height for example: 100px | 20% | auto
     * @param string $direct for example: up | down
     * @param bool|false $allow_fit decrease if image is greater or increase if image is less
     */
    public function __construct($width, $height, $direct = 'up', $allow_fit = false)
    {
        $this->_width = $width;
        $this->_height = $height;
        $this->_direct = $direct;
        $this->_allow_fit = $allow_fit;
    }

    /**
     * @inheritdoc
     */
    public function apply(IImage $img)
    {

        if ($this->_width != 'auto' || $this->_height != 'auto') {
            $width = $this->_width != 'auto' ? $this->getPxSize($this->_width, $img->getWidth()) : 'auto';
            $height = $this->_height != 'auto' ? $this->getPxSize($this->_height, $img->getHeight()) : 'auto';

            if ($this->_direct == 'up') {
                $this->scaleUp($img, $width, $height, $this->_allow_fit);
            } else {
                $this->scaleDown($img, $width, $height, $this->_allow_fit);
            }
        }

        return $this;
    }

    /**
     * @param IImage $img
     * @param string $width for example: 100 | auto
     * @param string $height for example: 100 | auto
     * @param bool|false $allow_increase increase if image is less
     *
     * @return $this
     */
    private function scaleUp(IImage $img, $width, $height, $allow_increase = false)
    {
        $origWidth = $img->getWidth();
        $origHeight = $img->getHeight();

        if ((($width != 'auto' && $origWidth > $width) || ($height != 'auto' && $origHeight > $height)) ||
            ($allow_increase && (($width == 'auto' || $origWidth != $width) && ($height == 'auto' || $origHeight != $height)))) {
            $aspect = $origHeight / $origWidth;

            if ($height == 'auto' || ($width != 'auto' && $aspect < $height / $width)) {
                if ($width > $origWidth && $allow_increase) {
                    $new_width = $width;
                } else {
                    $new_width = min($width, $origWidth);
                }
                $new_height = (int)round($new_width * $aspect);
            } else {
                if ($height > $origHeight && $allow_increase) {
                    $new_height = $height;
                } else {
                    $new_height = min($height, $origHeight);
                }
                $new_width = (int)round($new_height / $aspect);
            }

            $img->resize($new_width, $new_height/*, $filter*/);
        }

        return $this;
    }

    /**
     * @param IImage $img
     * @param string $width for example: 100 | auto
     * @param string $height for example: 100 | auto
     * @param bool|false $allow_decrease decrease if image is greater
     *
     * @return $this
     */
    private function scaleDown(IImage $img, $width, $height, $allow_decrease = false)
    {
        $origWidth = $img->getWidth();
        $origHeight = $img->getHeight();

        if ((($width != 'auto' && $origWidth < $width) || ($height != 'auto' && $origHeight < $height)) ||
            ($allow_decrease && (($width == 'auto' || $origWidth != $width) && ($height == 'auto' || $origHeight != $height)))) {
            $aspect = $origHeight / $origWidth;

            if ($aspect < $height / $width) {
                if ($width < $origWidth && $allow_decrease) {
                    $new_width = $width;
                } else {
                    $new_width = max($width, $origWidth);
                }
                $new_height = (int)round($new_width * $aspect);
            } else {
                if ($height < $origHeight && $allow_decrease) {
                    $new_height = $height;
                } else {
                    $new_height = max($height, $origHeight);
                }
                $new_width = (int)round($new_height / $aspect);
            }

            $img->resize($new_width, $new_height/*, $filter*/);
        }

        return $this;
    }
}