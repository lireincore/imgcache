<?php

namespace LireinCore\ImgCache\Effects;

use LireinCore\ImgCache\IEffect;
use LireinCore\ImgCache\TPixel;
use LireinCore\ImgCache\IImage;

/**
 * Overlay image
 */
class Overlay implements IEffect
{
    use TPixel;

    /**
     * @var string
     */
    private $_path;

    /**
     * @var int
     */
    private $_opacity;

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
     * Overlay constructor.
     *
     * @param string $path path to the overlay image
     * @param int $opacity for example: 100
     * @param string $offset_x for example: 100px | 20% | center | left | right
     * @param string $offset_y for example: 100px | 20% | center | top | bottom
     * @param string $width for example: 100px | 20% | origin - original overlay image width (% - relative to the background image)
     * @param string $height for example: 100px | 20% | origin - original overlay image height (% - relative to the background image)
     */
    public function __construct($path, $opacity = 100, $offset_x = 'right', $offset_y = 'bottom', $width = 'origin', $height = 'origin')
    {
        $this->_path = $path;
        $this->_opacity = $opacity;
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

        $wt = $img::newInstance($img->getDriver(), false)->open($this->_path);

        $wt_origWidth = $wt->getWidth();
        $wt_origHeight = $wt->getHeight();

        $wt_width = $this->_width == 'origin' ? $wt_origWidth : $this->getPxSize($this->_width, $origWidth);
        $wt_height = $this->_height == 'origin' ? $wt_origHeight : $this->getPxSize($this->_height, $origHeight);

        $wt->resize($wt_width, $wt_height);

        if ($wt_width > $origWidth || $wt_height > $origHeight) {
            $wt->apply(new Scale($origWidth, $origHeight, 'up'));
        }

        $wt_new_width = $wt->getWidth();
        $wt_new_height = $wt->getHeight();

        $offset_x = $this->getWtOffset($this->_offset_x, $origWidth, $wt_new_width);
        $offset_y = $this->getWtOffset($this->_offset_y, $origHeight, $wt_new_height);

        $img->paste($wt, $offset_x, $offset_y, $this->_opacity);

        return $this;
    }
}