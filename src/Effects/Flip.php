<?php

namespace LireinCore\ImgCache\Effects;

use LireinCore\ImgCache\IEffect;
use LireinCore\ImgCache\Image;

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
    public function apply(Image $img)
    {
        //TODO
        
        return $this;
    }
}