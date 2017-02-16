<?php

namespace LireinCore\ImgCache\Effects;

use LireinCore\ImgCache\IEffect;
use LireinCore\ImgCache\IImage;

/**
 * Flip image
 */
class Flip implements IEffect
{
    /**
     * @var string
     */
    private $_mode;

    /**
     * Flip constructor.
     *
     * @param string $mode for example: vertical, horizontal, full
     */
    public function __construct($mode)
    {
        $this->_mode = $mode;
    }

    /**
     * @inheritdoc
     */
    public function apply(IImage $img)
    {
        if ($this->_mode === 'vertical') {
            $img->flipVertically();
        } elseif ($this->_mode === 'horizontal') {
            $img->flipHorizontally();
        } elseif ($this->_mode === 'full') {
            $img->flipHorizontally();
            $img->flipVertically();
        }
        
        return $this;
    }
}