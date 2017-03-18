<?php

namespace LireinCore\ImgCache;

use Imagine\Image\AbstractImage;
use Imagine\Image\AbstractImagine;
use Imagine\Image\Point;
use Imagine\Image\Box;
use Imagine\Image\Palette\RGB;

class Image implements IImage
{
    /**
     * @var AbstractImagine
     */
    protected $_imagine;
    
    /**
     * @var AbstractImage
     */
    protected $_img;
    
    /**
     * @var string
     */
    protected $_driver;

    /**
     * Image constructor.
     *
     * @param int $driver
     * @param bool $tryToUseOtherDrivers
     * @throws \RuntimeException
     */
    public function __construct($driver = IImage::DRIVER_DEFAULT, $tryToUseOtherDrivers = true)
    {
        if ($driver == IImage::DRIVER_IM || $driver == IImage::DRIVER_DEFAULT) {
            try {
                $this->_imagine = new \Imagine\Imagick\Imagine();
                $this->_driver = IImage::DRIVER_IM;
            } catch (\RuntimeException $ex1) {
                if ($tryToUseOtherDrivers) {
                    try {
                        $this->_imagine = new \Imagine\Gd\Imagine();
                        $this->_driver = IImage::DRIVER_GD;
                    } catch (\RuntimeException $ex2) {
                        try {
                            $this->_imagine = new \Imagine\Gmagick\Imagine();
                            $this->_driver = IImage::DRIVER_GM;
                        } catch (\RuntimeException $ex3) {
                            throw new \RuntimeException('Graphic library not installed or higher version is required');
                        }
                    }
                } else {
                    throw new \RuntimeException($ex1->getMessage());
                }
            }
        }
        elseif ($driver == IImage::DRIVER_GM) {
            try {
                $this->_imagine = new \Imagine\Gmagick\Imagine();
                $this->_driver = IImage::DRIVER_GM;
            } catch (\RuntimeException $ex1) {
                if ($tryToUseOtherDrivers) {
                    try {
                        $this->_imagine = new \Imagine\Imagick\Imagine();
                        $this->_driver = IImage::DRIVER_IM;
                    } catch (\RuntimeException $ex2) {
                        try {
                            $this->_imagine = new \Imagine\Gd\Imagine();
                            $this->_driver = IImage::DRIVER_GD;
                        } catch (\RuntimeException $ex3) {
                            throw new \RuntimeException('Graphic library not installed or higher version is required');
                        }
                    }
                } else {
                    throw new \RuntimeException($ex1->getMessage());
                }
            }
        }
        elseif ($driver == IImage::DRIVER_GD) {
            try {
                $this->_imagine = new \Imagine\Gd\Imagine();
                $this->_driver = IImage::DRIVER_GD;
            } catch (\RuntimeException $ex1) {
                if ($tryToUseOtherDrivers) {
                    try {
                        $this->_imagine = new \Imagine\Imagick\Imagine();
                        $this->_driver = IImage::DRIVER_IM;
                    } catch (\RuntimeException $ex2) {
                        try {
                            $this->_imagine = new \Imagine\Gmagick\Imagine();
                            $this->_driver = IImage::DRIVER_GM;
                        } catch (\RuntimeException $ex3) {
                            throw new \RuntimeException('Graphic library not installed or higher version is required');
                        }
                    }
                } else {
                    throw new \RuntimeException($ex1->getMessage());
                }
            }
        }
        else {
            throw new \RuntimeException('Unknown graphic library');
        }
    }

    /**
     * @param int $driver
     * @param bool $tryToUseOtherDrivers
     *
     * @return IImage
     */
    public static function newInstance($driver = IImage::DRIVER_DEFAULT, $tryToUseOtherDrivers = true)
    {
        return new static($driver, $tryToUseOtherDrivers);
    }

    /**
     * @return int
     */
    public function getDriver()
    {
        return $this->_driver;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        $size = $this->_img->getSize();

        return $size->getWidth();
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        $size = $this->_img->getSize();

        return $size->getHeight();
    }

    /**
     * @param $filepath
     * @return $this
     * @throws \RuntimeException
     */
    public function open($filepath)
    {
        $this->_img = $this->_imagine->open($filepath);

        return $this;
    }

    /**
     * @param int $width
     * @param int $height
     * @param string|array $color
     * @param int $transparency
     * @return $this
     */
    public function create($width, $height, $color = '#fff', $transparency = 0)
    {
        $size = new Box($width, $height);
        $palette = new RGB();
        if ($this->_driver == IImage::DRIVER_GM) {
            // @todo: transparency not supported
            $color = $palette->color($color);
        } else {
            $color = $palette->color($color, $transparency);
        }
        $this->_img = $this->_imagine->create($size, $color);

        return $this;
    }

    /**
     * @return IImage
     */
    public function copy()
    {
        $image = new static($this->_driver, false);
        $image->_img = $this->_img->copy();

        return $image;
    }

    /**
     * @param IEffect $effect
     * @return $this
     */
    public function apply(IEffect $effect)
    {
        $effect->apply($this);

        return $this;
    }

    /**
     * @param IImage $img
     * @param int $offsetX
     * @param int $offsetY
     * @param int $opacity
     * @return $this
     */
    public function paste(IImage $img, $offsetX, $offsetY, $opacity = 100)
    {
        if (!$img instanceof static) {
            throw new \InvalidArgumentException(sprintf('LireinCore\ImgCache\Image can only paste() LireinCore\ImgCache\Image instances, %s given', get_class($img)));
        }

        if ($opacity != 100) {
            $img_res = $img->getDriverResObject();
            if ($this->_driver == IImage::DRIVER_GM) {
                // @todo: opacity not supported
            } elseif ($this->_driver == IImage::DRIVER_IM) {
                $img_res->setImageOpacity($opacity / 100);
            } elseif ($this->_driver == IImage::DRIVER_GD) {
                $img_width = $img->getWidth();
                $img_height = $img->getHeight();
                $res = $this->getDriverResObject();

                $cut = imagecreatetruecolor($img_width, $img_height);
                imagecopy($cut, $res, 0, 0, $offsetX, $offsetY, $img_width, $img_height);
                imagecopy($cut, $img_res, 0, 0, 0, 0, $img_width, $img_height);
                imagecopymerge($res, $cut, $offsetX, $offsetY, 0, 0, $img_width, $img_height, $opacity);
                imagedestroy($cut);
            }
        }

        if ($opacity == 100 || $this->_driver == IImage::DRIVER_GM || $this->_driver == IImage::DRIVER_IM) {
            $this->_img->paste($img->_img, new Point($offsetX, $offsetY));
        }

        return $this;
    }

    /**
     * @param int $width
     * @param int $height
     * @param string $filter
     * @return $this
     */
    public function resize($width, $height, $filter = IImage::FILTER_UNDEFINED)
    {
        if ($this->getWidth() != $width || $this->getHeight() != $height) {
            $this->_img->resize(new Box($width, $height), $filter);
        }

        return $this;
    }

    /**
     * @param int $offsetX
     * @param int $offsetY
     * @param int $width
     * @param int $height
     * @return $this
     */
    public function crop($offsetX, $offsetY, $width, $height)
    {
        if ($offsetX != 0 || $offsetY != 0 || $this->getWidth() != $width || $this->getHeight() != $height) {
            $this->_img->crop(new Point($offsetX, $offsetY), new Box($width, $height));
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function flipHorizontally()
    {
        $this->_img->flipHorizontally();

        return $this;
    }

    /**
     * @return $this
     */
    public function flipVertically()
    {
        $this->_img->flipVertically();

        return $this;
    }

    /**
     * @param float $angle
     * @param string|array $bgcolor
     * @param int $bgtransparency
     * @return $this
     */
    public function rotate($angle, $bgcolor = '#fff', $bgtransparency = 0)
    {
        $palette = new RGB();
        if ($this->_driver == IImage::DRIVER_GM) {
            // @todo: transparency not supported
            $color = $palette->color($bgcolor);
        } else {
            $color = $palette->color($bgcolor, $bgtransparency);
        }
        $this->_img->rotate($angle, $color);

        return $this;
    }

    /**
     * @return $this
     */
    public function negative()
    {
        $this->_img->effects()->negative();

        return $this;
    }

    /**
     * @return $this
     */
    public function grayscale()
    {
        $this->_img->effects()->grayscale();

        return $this;
    }

    /**
     * @param float $correction
     * @return $this
     */
    public function gamma($correction)
    {
        $this->_img->effects()->gamma($correction);

        return $this;
    }

    /**
     * @param float $sigma
     * @return $this
     */
    public function blur($sigma)
    {
        $this->_img->effects()->blur($sigma);

        return $this;
    }

    /**
     * @param string $destPath
     * @param array $options
     * @return $this
     * @throws \RuntimeException
     */
    public function save($destPath, $options = [])
    {
        $destPathInfo = pathinfo($destPath);
        if (!is_dir($destPathInfo['dirname'])) {
            $this->rmkdir($destPathInfo['dirname']);
        }

        // @todo: png_compression_filter with GD have another format
        if ($this->_driver == IImage::DRIVER_GD) unset($options['png_compression_filter']);

        $this->_img->save($destPath, $options);

        return $this;
    }

    /**
     * @return null|\Imagick|\Gmagick|resource
     */
    private function getDriverResObject()
    {
        $object = null;

        if ($this->_driver == static::DRIVER_GM) {
            $object = $this->_img->getGmagick();
        } elseif ($this->_driver == static::DRIVER_IM) {
            $object = $this->_img->getImagick();
        } elseif ($this->_driver == static::DRIVER_GD) {
            $object = $this->_img->getGdResource();
        }

        return $object;
    }

    /**
     * @param string $pathname
     * @throws \RuntimeException
     */
    private function rmkdir($pathname)
    {
        $dirs = array_filter(explode(DIRECTORY_SEPARATOR, $pathname));
        $path = '';

        foreach ($dirs as $dir) {
            $path .= DIRECTORY_SEPARATOR . $dir;
            if (!is_dir($path)) {
                if (!@mkdir($path, 0755)) {
                    throw new \RuntimeException("Failed to make dir '{$path}'");
                }
            }
        }
    }
}