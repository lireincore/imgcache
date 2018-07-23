<?php

namespace LireinCore\ImgCache;

use LireinCore\Image\ImageHelper;
use LireinCore\Image\ImageInterface;
use LireinCore\ImgCache\Exception\ConfigException;

class Config
{
    const DEFAULT_IMAGE_CLASS = '\LireinCore\Image\Image';
    const DEFAULT_JPEG_QUALITY = 75;
    const DEFAULT_PNG_COMPRESSION_LEVEL = 7;
    const DEFAULT_PNG_COMPRESSION_FILTER = 5;
    const DEFAULT_PROCESS_PLUG = false;
    const DEFAULT_CONVERT_MAP = [
        'jpeg' => 'jpeg',
        'png'  => 'png',
        'gif'  => 'gif',
        'wbmp' => 'wbmp',
        'xbm'  => 'xbm',
        '*'    => 'png'
    ];

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
     * @var string[] effects map
     *
     * ['effect' => 'class']
     *
     * For example,
     * ```php
     * [
     *     'my_effect1' => '\Foo\Bar\MyEffect1',
     *     'my_effect2' => '\Foo\Bar\MyEffect2'
     * ]
     * ```
     */
    protected $_effectsMap = [
        'flip'       => '\LireinCore\Image\Effects\Flip',
        'rotate'     => '\LireinCore\Image\Effects\Rotate',
        'resize'     => '\LireinCore\Image\Effects\Resize',
        'crop'       => '\LireinCore\Image\Effects\Crop',
        'scale'      => '\LireinCore\Image\Effects\Scale',
        'scale_up'   => '\LireinCore\Image\Effects\ScaleUp',
        'scale_down' => '\LireinCore\Image\Effects\ScaleDown',
        'fit'        => '\LireinCore\Image\Effects\Fit',
        'cover'      => '\LireinCore\Image\Effects\Cover',
        'negative'   => '\LireinCore\Image\Effects\Negative',
        'grayscale'  => '\LireinCore\Image\Effects\Grayscale',
        'gamma'      => '\LireinCore\Image\Effects\Gamma',
        'blur'       => '\LireinCore\Image\Effects\Blur',
        'overlay'    => '\LireinCore\Image\Effects\Overlay',
        'text'       => '\LireinCore\Image\Effects\Text'
    ];

    /**
     * @var string[] postprocessors map
     *
     * ['postprocessor' => 'class']
     *
     * For example,
     * ```php
     * [
     *     'my_postprocessor1' => '\Foo\Bar\MyPostProcessor1',
     *     'my_postprocessor2' => '\Foo\Bar\MyPostProcessor2'
     * ]
     * ```
     */
    protected $_postProcessorsMap = [
        'jpegoptim'  => '\LireinCore\Image\PostProcessors\JpegOptim',
        'optipng'    => '\LireinCore\Image\PostProcessors\OptiPng',
        //'mozjpeg'    => '\LireinCore\Image\PostProcessors\MozJpeg',
        'pngquant'   => '\LireinCore\Image\PostProcessors\PngQuant',
    ];

    /**
     * @var array presets config
     */
    protected $_presetsConfig = [];

    /**
     * @var PresetConfig[] presets list
     *
     * ['preset name' => 'preset config']
     */
    protected $_presets = [];

    /**
     * Config constructor.
     *
     * @param array $config
     * @throws ConfigException
     */
    public function __construct($config)
    {
        if (isset($config['destdir'])) {
            $this->setDestDir($config['destdir']);
        } else {
            throw new ConfigException("Destination directory is required");
        }

        if (isset($config['driver'])) {
            $this->setDriver($config['driver']);
        } else {
            $driver = ImgHelper::getAvailableDriver();
            if ($driver === null) {
                throw new ConfigException("No graphic libraries installed or higher versions is required. Please, install 'gd', 'imagick' or 'gmagick'");
            }
            $this->setDriver($driver);
        }

        if (isset($config['image_class'])) {
            $this->setImageClass($config['image_class']);
        } else {
            $this->setImageClass(static::DEFAULT_IMAGE_CLASS);
        }

        if (isset($config['srcdir'])) {
            $this->setSrcDir($config['srcdir']);
        }

        if (isset($config['webdir'])) {
            $this->setWebDir($config['webdir']);
        };

        if (isset($config['baseurl'])) {
            $this->setBaseUrl($config['baseurl']);
        }

        if (isset($config['jpeg_quality'])) {
            $this->setJpegQuality($config['jpeg_quality']);
        } else {
            $this->setJpegQuality(static::DEFAULT_JPEG_QUALITY);
        }

        if (isset($config['png_compression_level'])) {
            $this->setPngCompressionLevel($config['png_compression_level']);
        } else {
            $this->setPngCompressionLevel(static::DEFAULT_PNG_COMPRESSION_LEVEL);
        }

        if (isset($config['png_compression_filter'])) {
            $this->setPngCompressionFilter($config['png_compression_filter']);
        } else {
            $this->setPngCompressionFilter(static::DEFAULT_PNG_COMPRESSION_FILTER);
        }

        if (isset($config['convert_map'])) {
            $this->setConvertMap($config['convert_map']);
        }

        if (isset($config['plug']['path'])) {
            $this->setPlugPath($config['plug']['path']);
        }

        if (isset($config['plug']['process'])) {
            $this->setProcessPlug($config['plug']['process']);
        } else {
            $this->setProcessPlug(static::DEFAULT_PROCESS_PLUG);
        }

        if (isset($config['plug']['url'])) {
            $this->setPlugUrl($config['plug']['url']);
        }

        if (isset($config['effects_map'])) {
            if (is_array($config['effects_map'])) {
                foreach ($config['effects_map'] as $type => $class) {
                    $this->registerEffect($type, $class);
                }
            } else {
                throw new ConfigException('Incorrect config format');
            }
        }

        if (isset($config['postprocessors_map'])) {
            if (is_array($config['postprocessors_map'])) {
                foreach ($config['postprocessors_map'] as $type => $class) {
                    $this->registerPostProcessor($type, $class);
                }
            } else {
                throw new ConfigException('Incorrect config format');
            }
        }

        if (isset($config['postprocessors'])) {
            if (is_array($config['postprocessors'])) {
                foreach ($config['postprocessors'] as $postProcessorConfig) {
                    if (!isset($postProcessorConfig['type'])) {
                        throw new ConfigException("Incorrect config format. Postprocessor type not specified");
                    }
                    $class = $this->getPostProcessorClassName($postProcessorConfig['type']);
                    if (null === $class) {
                        throw new ConfigException("Incorrect config format. Unknown postprocessor type '{$postProcessorConfig['type']}'");
                    }
                    $this->_postProcessorsConfig[] = $postProcessorConfig;
                }
            } else {
                throw new ConfigException('Incorrect config format');
            }
        }

        if (isset($config['presets'])) {
            if (is_array($config['presets'])) {
                $this->_presetsConfig = $config['presets'];
            } else {
                throw new ConfigException('Incorrect config format');
            }
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
            if (in_array($dest, ImageInterface::SUPPORTED_DESTINATION_FORMATS, true)) {
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
        return $this->_convertMap + static::DEFAULT_CONVERT_MAP;
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

    /**
     * @param string $name
     * @return PresetConfig
     * @throws ConfigException
     */
    public function getPresetConfig($name)
    {
        if (!isset($this->_presets[$name])) {
            if (isset($this->_presetsConfig[$name])) {
                $this->_presets[$name] = new PresetConfig($this, $this->_presetsConfig[$name]);
            } else {
                return null;
            }
        }

        return $this->_presets[$name];
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isPreset($name)
    {
        return isset($this->_presetsConfig[$name]);
    }

    /**
     * @return string[]
     */
    public function getPresetNames()
    {
        return array_keys($this->_presetsConfig);
    }

    /**
     * Register custom effects or override default effects
     *
     * @param string $type
     * @param string $class class which implements \LireinCore\Image\EffectInterface
     * @throws ConfigException
     */
    public function registerEffect($type, $class)
    {
        if (class_exists($class)) {
            $interfaces = class_implements($class);
            if (in_array('LireinCore\Image\EffectInterface', $interfaces, true)) {
                $this->_effectsMap[$type] = $class;
            } else {
                throw new ConfigException("Class {$class} don't implement interface \\LireinCore\\Image\\EffectInterface");
            }
        } else {
            throw new ConfigException("Class {$class} not found");
        }
    }

    /**
     * @return string[]
     */
    public function getEffectsMap()
    {
        return $this->_effectsMap;
    }

    /**
     * @param string $type
     * @return null|string
     */
    public function getEffectClassName($type)
    {
        return isset($this->_effectsMap[$type]) ? $this->_effectsMap[$type] : null;
    }

    /**
     * Register custom postprocessor or override default postprocessors
     *
     * @param string $type
     * @param string $class class which implements \LireinCore\Image\PostProcessorInterface
     * @throws ConfigException
     */
    public function registerPostProcessor($type, $class)
    {
        if (class_exists($class)) {
            $interfaces = class_implements($class);
            if (in_array('LireinCore\Image\PostProcessorInterface', $interfaces, true)) {
                $this->_postProcessorsMap[$type] = $class;
            } else {
                throw new ConfigException("Class {$class} don't implement interface \\LireinCore\\Image\\PostProcessorInterface");
            }
        } else {
            throw new ConfigException("Class {$class} not found");
        }
    }

    /**
     * @return string[]
     */
    public function getPostProcessorsMap()
    {
        return $this->_postProcessorsMap;
    }

    /**
     * @param string $type
     * @return null|string
     */
    public function getPostProcessorClassName($type)
    {
        return isset($this->_postProcessorsMap[$type]) ? $this->_postProcessorsMap[$type] : null;
    }
}