<?php

namespace LireinCore\ImgCache\Effects;

use LireinCore\ImgCache\IEffect;
use LireinCore\ImgCache\TPixel;
use LireinCore\ImgCache\Image;

/**
 * Overlay image
 */
class Overlay implements IEffect
{
    use TPixel;

    /**
     * @var string
     */
    private $_watermark;

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
     * @param string $watermark path to file with watermark image
     * @param int $opacity for example: 100
     * @param string $offset_x for example: 100px | 20% | center | left | right
     * @param string $offset_y for example: 100px | 20% | center | top | bottom
     * @param string $width for example: 100px | 20% | origin - original watermark width (% - relative to the background)
     * @param string $height for example: 100px | 20% | origin - original watermark height (% - relative to the background)
     */
    public function __construct($watermark, $opacity = 100, $offset_x = 'right', $offset_y = 'bottom', $width = 'origin', $height = 'origin')
    {
        $this->_watermark = $watermark;
        $this->_opacity = $opacity;
        $this->_offset_x = $offset_x;
        $this->_offset_y = $offset_y;
        $this->_width = $width;
        $this->_height = $height;
    }

    /**
     * @inheritdoc
     */
    public function apply(Image $img)
    {
        $origWidth = $img->getWidth();
        $origHeight = $img->getHeight();

        $driver = $img->getDriver();

        $wt = (new Image($driver, false))->open($this->_watermark);

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

        if ($this->_opacity != 100) {
            $wt_res = $wt->getDriverResObject();
            if ($driver == Image::DRIVER_GM) {
                //todo!!!!
            } elseif ($driver == Image::DRIVER_IM) {
                //todo!!!!
                $wt_res->setImageOpacity($this->_opacity / 100);
                //$wt_res->evaluateImage($wt_res::EVALUATE_MULTIPLY, 0.0, $wt_res::CHANNEL_ALPHA);
            } elseif ($driver == Image::DRIVER_GD) {
                $res = $img->getDriverResObject();

                $cut = imagecreatetruecolor($wt_new_width, $wt_new_height);
                imagecopy($cut, $res, 0, 0, $offset_x, $offset_y, $wt_new_width, $wt_new_height);
                imagecopy($cut, $wt_res, 0, 0, 0, 0, $wt_new_width, $wt_new_height);
                imagecopymerge($res, $cut, $offset_x, $offset_y, 0, 0, $wt_new_width, $wt_new_height, $this->_opacity);
                imagedestroy($cut);
            }
        }

        if ($this->_opacity == 100 || $driver == Image::DRIVER_GM || $driver == Image::DRIVER_IM) {
            $img->paste($wt, $offset_x, $offset_y);
        }

        return $this;
    }
}