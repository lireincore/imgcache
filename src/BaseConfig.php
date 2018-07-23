<?php

namespace LireinCore\ImgCache;

use LireinCore\Image\ImageHelper;
use LireinCore\ImgCache\Exception\ConfigException;

abstract class BaseConfig
{
    const DEFAULT_IMAGE_CLASS = '\LireinCore\Image\Image';
    const DEFAULT_JPEG_QUALITY = 75;
    const DEFAULT_PNG_COMPRESSION_LEVEL = 7;
    const DEFAULT_PNG_COMPRESSION_FILTER = 5;
    const DEFAULT_PROCESS_PLUG = false;

    /**
     * @var string graphic library for all presets: `imagick`, `gd`, `gmagick`
     * (by default, tries to use: imagick->gd->gmagick)
     */
    protected $_driver;

    /**
     * @var string image class for all presets (which implements \LireinCore\Image\ImageInterface)
     */
    protected $_imageClass;

    /**
     * @var string original images source directory for all presets
     */
    protected $_srcDir;

    /**
     * @var string thumbs destination directory for all presets
     * (to access the thumbs from the web they should be in a directory accessible from the web)
     */
    protected $_destDir;

    /**
     * @var string web directory for all presets
     */
    protected $_webDir;

    /**
     * @var string base url for all presets
     */
    protected $_baseUrl;

    /**
     * @var int quality of save jpeg images for all presets: 0-100
     */
    protected $_jpegQuality;

    /**
     * @var int compression level of save png images for all presets: 0-9
     */
    protected $_pngCompressionLevel;

    /**
     * @var int compression filter of save png images for all presets: 0-9
     */
    protected $_pngCompressionFilter;

    /**
     * @var array formats convert map for all presets
     */
    protected $_convertMap = [];

    /**
     * @var string absolute path to plug for all presets (used if original image is not available)
     */
    protected $_plugPath;

    /**
     * @var bool apply preset effects and postprocessors to plug?
     */
    protected $_processPlug;

    /**
     * @var string url to get the plug from a third-party service (used if original image is not available)
     */
    protected $_plugUrl;

    /**
     * @var array
     */
    protected $_postProcessorsConfig = [];

    /**
     * @var array
     */
    protected $_defaultConvertMap = [
        'jpeg' => 'jpeg',
        'png'  => 'png',
        'gif'  => 'gif',
        'wbmp' => 'wbmp',
        'xbm'  => 'xbm',
        '*'    => 'png'
    ];

    /**
     * @var array
     */
    protected $_convertDestinationFormats = ['jpeg', 'png', 'gif', 'wbmp', 'xbm'];

    /**
     * BaseConfig constructor.
     *
     * @param array $config
     * @throws ConfigException
     */
    public function __construct($config)
    {
        if (!empty($config['driver'])) {
            $this->setDriver($config['driver']);
        }

        if (!empty($config['image_class'])) {
            $this->setImageClass($config['image_class']);
        }

        if (!empty($config['srcdir'])) {
            $this->setSrcDir($config['srcdir']);
        }

        if (!empty($config['destdir'])) {
            $this->setDestDir($config['destdir']);
        }

        if (!empty($config['webdir'])) {
            $this->setWebDir($config['webdir']);
        };

        if (!empty($config['baseurl'])) {
            $this->setBaseUrl($config['baseurl']);
        }

        if (isset($config['jpeg_quality'])) {
            $this->setJpegQuality($config['jpeg_quality']);
        }

        if (isset($config['png_compression_level'])) {
            $this->setPngCompressionLevel($config['png_compression_level']);
        }

        if (isset($config['png_compression_filter'])) {
            $this->setPngCompressionFilter($config['png_compression_filter']);
        }

        if (isset($config['convert_map'])) {
            $this->setConvertMap($config['convert_map']);
        }

        if (!empty($config['plug']['path'])) {
            $this->setPlugPath($config['plug']['path']);
        }

        if (isset($config['plug']['process'])) {
            $this->setProcessPlug($config['plug']['process']);
        }

        if (!empty($config['plug']['url'])) {
            $this->setPlugUrl($config['plug']['url']);
        }
    }

    /**
     * @param string $driver
     * @throws ConfigException
     */
    public function setDriver($driver)
    {
        if (in_array($driver, ['imagick', 'gd', 'gmagick'], true)) {
            $this->_driver = $driver;
        } else {
            throw new ConfigException("Incorrect driver value. Should be 'gd', 'imagick' or 'gmagick'");
        }
    }

    /**
     * @return string
     */
    public function getDriver()
    {
        return $this->_driver;
    }

    /**
     * @param null|string $srcDir
     * @throws ConfigException
     */
    public function setSrcDir($srcDir)
    {
        if ($srcDir === null) {
            $this->_srcDir = null;
        } else {
            if (is_dir($srcDir)) {
                $this->_srcDir = rtrim($srcDir, "\\/");
            } else {
                throw new ConfigException("Source directory {$srcDir} not found");
            }
        }
    }

    /**
     * @return null|string
     */
    public function getSrcDir()
    {
        return $this->_srcDir;
    }

    /**
     * @param string $destDir
     * @throws ConfigException
     */
    public function setDestDir($destDir)
    {
        $destDir = rtrim($destDir, "\\/");

        if (!is_dir($destDir)) {
            try {
                ImageHelper::rmkdir($destDir);
            } catch (\RuntimeException $e) {
                throw new ConfigException("Destination directory {$destDir} does not exist", $e->getCode(), $e);
            }
        }

        $this->_destDir = $destDir;
    }

    /**
     * @return string
     */
    public function getDestDir()
    {
        return $this->_destDir;
    }

    /**
     * @param null|string $webDir
     * @throws ConfigException
     */
    public function setWebDir($webDir)
    {
        if ($webDir === null) {
            $this->_webDir = null;
        } else {
            if (is_dir($webDir)) {
                $this->_webDir = $webDir;
            } else {
                throw new ConfigException("Web directory {$webDir} not found");
            }
        }
    }

    /**
     * @return null|string
     */
    public function getWebDir()
    {
        return $this->_webDir;
    }

    /**
     * @param null|string $baseUrl
     */
    public function setBaseUrl($baseUrl)
    {
        $this->_baseUrl = $baseUrl;
    }

    /**
     * @return null|string
     */
    public function getBaseUrl()
    {
        return $this->_baseUrl;
    }

    /**
     * @param int $jpegQuality
     * @throws ConfigException
     */
    public function setJpegQuality($jpegQuality)
    {
        $value = (int)$jpegQuality;
        if ($value < 0 || $value > 100) {
            throw new ConfigException("Incorrect jpeg_quality value");
        } else {
            $this->_jpegQuality = $value;
        }
    }

    /**
     * @return int
     */
    public function getJpegQuality()
    {
        return $this->_jpegQuality;
    }

    /**
     * @param int $pngCompressionLevel
     * @throws ConfigException
     */
    public function setPngCompressionLevel($pngCompressionLevel)
    {
        $value = (int)$pngCompressionLevel;
        if ($value < 0 || $value > 9) {
            throw new ConfigException("Incorrect png_compression_level value");
        } else {
            $this->_pngCompressionLevel = $value;
        }
    }

    /**
     * @return int
     */
    public function getPngCompressionLevel()
    {
        return $this->_pngCompressionLevel;
    }

    /**
     * @param int $pngCompressionFilter
     * @throws ConfigException
     */
    public function setPngCompressionFilter($pngCompressionFilter)
    {
        $value = (int)$pngCompressionFilter;
        if ($value < 0 || $value > 9) {
            throw new ConfigException("Incorrect png_compression_filter value");
        } else {
            $this->_pngCompressionFilter = $value;
        }
    }

    /**
     * @return int
     */
    public function getPngCompressionFilter()
    {
        return $this->_pngCompressionFilter;
    }

    /**
     * @param array $convertMap
     *
     * ['source formats' => 'destination format']
     *
     * For example,
     * ```php
     * [
     *     'gif,wbmp' => 'png', //gif and wbmp to png
     *     '*' => 'jpeg' //all others to jpeg
     * ]
     * ```
     *
     * supported formats for destination images: jpeg, png, gif, wbmp, xbm
     *
     * @throws ConfigException
     */
    public function setConvertMap($convertMap)
    {
        $formats = ImageHelper::getFormats();

        foreach ($convertMap as $srcStr => $dest) {
            if (in_array($dest, $this->_convertDestinationFormats, true)) {
                foreach (explode(',', $srcStr) as $src) {
                    if ($src === '*' || isset($formats[$src])) {
                        $this->_convertMap[$src] = $dest;
                    } else {
                        throw new ConfigException("Incorrect convert value. Unsupported source image format {$src}");
                    }
                }
            } else {
                throw new ConfigException("Incorrect convert value. Unsupported destination image format {$dest}");
            }
        }
    }

    /**
     * @return array
     */
    public function getConvertMap()
    {
        return $this->_convertMap;
    }

    /**
     * @param null|string $plugPath
     * @throws ConfigException
     */
    public function setPlugPath($plugPath)
    {
        if ($plugPath === null) {
            $this->_plugPath = null;
        } else {
            if (is_file($plugPath)) {
                $this->_plugPath = $plugPath;
            } else {
                throw new ConfigException("Plug file {$plugPath} not found");
            }
        }
    }

    /**
     * @return null|string
     */
    public function getPlugPath()
    {
        return $this->_plugPath;
    }

    /**
     * @param bool $processPlug
     */
    public function setProcessPlug($processPlug)
    {
        $this->_processPlug = (bool)$processPlug;
    }

    /**
     * @return bool
     */
    public function getProcessPlug()
    {
        return $this->_processPlug;
    }

    /**
     * @param null|string $plugUrl
     */
    public function setPlugUrl($plugUrl)
    {
        $this->_plugUrl = $plugUrl;
    }

    /**
     * @return null|string
     */
    public function getPlugUrl()
    {
        return $this->_plugUrl;
    }

    /**
     * @param string $imageClass
     * @throws ConfigException
     */
    public function setImageClass($imageClass)
    {
        if (class_exists($imageClass)) {
            $interfaces = class_implements($imageClass);
            if (in_array('LireinCore\Image\ImageInterface', $interfaces, true)) {
                $this->_imageClass = $imageClass;
            } else {
                throw new ConfigException("Class {$imageClass} don't implement interface \\LireinCore\\Image\\ImageInterface");
            }
        } else {
            throw new ConfigException("Class {$imageClass} not found");
        }
    }

    /**
     * @return string
     */
    public function getImageClass()
    {
        return $this->_imageClass;
    }

    /**
     * @return bool
     */
    public function hasPostProcessors()
    {
        return !empty($this->_postProcessorsConfig);
    }

    /**
     * @return array
     */
    public function getPostProcessorsConfig()
    {
        return $this->_postProcessorsConfig;
    }
}