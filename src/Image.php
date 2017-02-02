<?php

namespace LireinCore\ImgCache;

use Imagine\Image\ImageInterface;
use Imagine\Image\AbstractImage;
use Imagine\Image\AbstractImagine;
use Imagine\Image\Point;
use Imagine\Image\Box;
use Imagine\Image\Palette\RGB;

class Image
{
    /**
     * default graphic driver
     */
    const DRIVER_DEFAULT = 1;

    /**
     * gmagick graphic driver
     */
    const DRIVER_GM = 2;

    /**
     * imagick graphic driver
     */
    const DRIVER_IM = 3;

    /**
     * gd2 graphic driver
     */
    const DRIVER_GD = 4;

    /**
     * @var AbstractImagine
     */
    private $_imagine;
    
    /**
     * @var AbstractImage
     */
    private $_img;
    
    /**
     * @var string
     */
    private $_driver;

    /**
     * @var int
     */
    private $_width;

    /**
     * @var int
     */
    private $_height;

    /**
     * Image constructor.
     *
     * @param int $driver
     * @param bool $tryToUseOtherDrivers
     * @throws \RuntimeException
     */
    public function __construct($driver = self::DRIVER_DEFAULT, $tryToUseOtherDrivers = true)
    {
        if ($driver == self::DRIVER_GM || $driver == self::DRIVER_DEFAULT) {
            try {
                $this->_imagine = new \Imagine\Gmagick\Imagine();
                $this->_driver = self::DRIVER_GM;
            } catch (\RuntimeException $ex1) {
                if ($tryToUseOtherDrivers) {
                    try {
                        $this->_imagine = new \Imagine\Imagick\Imagine();
                        $this->_driver = self::DRIVER_IM;
                    } catch (\RuntimeException $ex2) {
                        try {
                            $this->_imagine = new \Imagine\Gd\Imagine();
                            $this->_driver = self::DRIVER_GD;
                        } catch (\RuntimeException $ex3) {
                            throw new \RuntimeException('Graphic library not installed or higher version is required');
                        }
                    }
                } else {
                    throw new \RuntimeException($ex1->getMessage());
                }
            }
        }
        elseif ($driver == self::DRIVER_IM) {
            try {
                $this->_imagine = new \Imagine\Imagick\Imagine();
                $this->_driver = self::DRIVER_IM;
            } catch (\RuntimeException $ex1) {
                if ($tryToUseOtherDrivers) {
                    try {
                        $this->_imagine = new \Imagine\Gmagick\Imagine();
                        $this->_driver = self::DRIVER_GM;
                    } catch (\RuntimeException $ex2) {
                        try {
                            $this->_imagine = new \Imagine\Gd\Imagine();
                            $this->_driver = self::DRIVER_GD;
                        } catch (\RuntimeException $ex3) {
                            throw new \RuntimeException('Graphic library not installed or higher version is required');
                        }
                    }
                } else {
                    throw new \RuntimeException($ex1->getMessage());
                }
            }
        }
        elseif ($driver == self::DRIVER_GD) {
            try {
                $this->_imagine = new \Imagine\Gd\Imagine();
                $this->_driver = self::DRIVER_GD;
            } catch (\RuntimeException $ex1) {
                if ($tryToUseOtherDrivers) {
                    try {
                        $this->_imagine = new \Imagine\Gmagick\Imagine();
                        $this->_driver = self::DRIVER_GM;
                    } catch (\RuntimeException $ex2) {
                        try {
                            $this->_imagine = new \Imagine\Imagick\Imagine();
                            $this->_driver = self::DRIVER_IM;
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

    public function getDriver()
    {
        return $this->_driver;
    }

    /**
     * @return null|\Imagick|\Gmagick|resource
     */
    public function getDriverResObject()
    {
        $object = null;
        $driver = $this->getDriver();

        if ($driver == static::DRIVER_GM) {
            $object = $this->getImg()->getGmagick();
        } elseif ($driver == static::DRIVER_IM) {
            $object = $this->getImg()->getImagick();
        } elseif ($driver == static::DRIVER_GD) {
            $object = $this->getImg()->getGdResource();
        }

        return $object;
    }

    public function getImagine()
    {
        return $this->_imagine;
    }

    public function getImg()
    {
        return $this->_img;
    }

    public function getWidth()
    {
        return $this->_width;
    }

    public function getHeight()
    {
        return $this->_height;
    }

    public function setWidth($width)
    {
        $this->_width = $width;
    }

    public function setHeight($height)
    {
        return $this->_height = $height;
    }

    /**
     * @param $filepath
     * @return $this
     * @throws \RuntimeException
     */
    public function open($filepath)
    {
        $this->_img = $this->_imagine->open($filepath);
        $size = $this->_img->getSize();
        $this->_width = $size->getWidth();
        $this->_height = $size->getHeight();

        return $this;
    }

    /**
     * @param int $width
     * @param int $height
     * @param string $color
     * @param int $transparency
     * @return $this
     */
    public function create($width, $height, $color = '#fff', $transparency = 0)
    {
        $size = new Box($width, $height);
        $palette = new RGB();
        $color = $palette->color($color, $transparency);
        $this->_img = $this->_imagine->create($size, $color);
        $size = $this->_img->getSize();
        $this->_width = $size->getWidth();
        $this->_height = $size->getHeight();

        return $this;
    }

    /**
     * @param Image $img
     * @param int $offsetX
     * @param int $offsetY
     * @return $this
     */
    public function paste(Image $img, $offsetX, $offsetY)
    {
        $this->getImg()->paste($img->getImg(), new Point($offsetX, $offsetY));

        return $this;
    }

    /**
     * @param int $width
     * @param int $height
     * @param string $filter
     * @return $this
     */
    public function resize($width, $height, $filter = ImageInterface::FILTER_UNDEFINED)
    {
        if ($this->getWidth() != $width || $this->getHeight() != $height) {
            $this->getImg()->resize(new Box($width, $height), $filter);
            $this->setWidth($width);
            $this->setHeight($height);
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
            $this->getImg()->crop(new Point($offsetX, $offsetY), new Box($width, $height));
            $this->setWidth($width);
            $this->setHeight($height);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function flipHorizontally()
    {
        $this->getImg()->flipHorizontally();

        return $this;
    }

    /**
     * @return $this
     */
    public function flipVertically()
    {
        $this->getImg()->flipVertically();

        return $this;
    }

    /**
     * @param float $angle
     * @param string $bgcolor
     * @param int $bgtransparency
     * @return $this
     */
    public function rotate($angle, $bgcolor = '#fff', $bgtransparency = 0)
    {
        $palette = new RGB();
        $color = $palette->color($bgcolor, $bgtransparency);
        $this->getImg()->rotate($angle, $color);

        return $this;
    }

    /**
     * @return $this
     */
    public function negative()
    {
        $this->getImg()->effects()->negative();

        return $this;
    }

    /**
     * @return $this
     */
    public function grayscale()
    {
        $this->getImg()->effects()->grayscale();

        return $this;
    }

    /**
     * @param float $correction
     * @return $this
     */
    public function gamma($correction)
    {
        $this->getImg()->effects()->gamma($correction);

        return $this;
    }

    /**
     * @param float $sigma
     * @return $this
     */
    public function blur($sigma)
    {
        $this->getImg()->effects()->blur($sigma);

        return $this;
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
     * @param string $destPath
     * @return $this
     * @throws \RuntimeException
     */
    public function save($destPath)
    {
        $destPathInfo = pathinfo($destPath);
        if (!is_dir($destPathInfo['dirname'])) {
            $this->rmkdir($destPathInfo['dirname'], 0775);
        }
        $this->_img->save($destPath);

        /*if ($result) {
            chmod($dest_file, 0664);
        }*/

        return $this;
    }

    /**
     * Создает каталоги рекурсивно и пытается назначить им указанные права, хозяина и группу
     * @param string $pathname
     * @param int $mode
     * @param mixed $user
     * @param mixed $group
     * @throws \RuntimeException
     */
    private function rmkdir($pathname, $mode = 0775, $user = null, $group = null)
    {
        $dirs = array_filter(explode(DIRECTORY_SEPARATOR, $pathname));
        $path = '';

        foreach ($dirs as $dir) {
            $path .= DIRECTORY_SEPARATOR . $dir;
            if (!is_dir($path)) {
                if (!@mkdir($path, $mode)) {
                    throw new \RuntimeException("Failed to make dir '{$path}'");
                }
                else {
                    @chmod($path, $mode);
                    if (!is_null($user)) @chown($path, $user);
                    if (!is_null($group)) @chgrp($path, $group);
                }
            }
        }
    }
}