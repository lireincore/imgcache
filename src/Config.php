<?php

namespace LireinCore\ImgCache;

use LireinCore\Image\ImageHelper;
use LireinCore\ImgCache\Exception\ConfigException;

class Config
{
    const DEFAULT_IMAGE_CLASS = '\LireinCore\Image\Manipulators\Imagine';
    const DEFAULT_JPEG_QUALITY = 75;
    const DEFAULT_PNG_COMPRESSION_LEVEL = 7;
    const DEFAULT_PNG_COMPRESSION_FILTER = 5;
    const DEFAULT_PROCESS_PLUG = false;

    /**
     * @var array
     */
    protected $defaultConvertMap = [
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
    protected $driver;

    /**
     * @var string image class for all presets (which implements \LireinCore\Image\Manipulator interface)
     */
    protected $imageClass;

    /**
     * @var string original images source directory for all presets
     */
    protected $srcDir;

    /**
     * @var string thumbs destination directory for all presets
     * (to access the thumbs from the web they should be in a directory accessible from the web)
     */
    protected $destDir;

    /**
     * @var string web directory for all presets
     */
    protected $webDir;

    /**
     * @var string base url for all presets
     */
    protected $baseUrl;

    /**
     * @var int quality of save jpeg images for all presets: 0-100
     */
    protected $jpegQuality;

    /**
     * @var int compression level of save png images for all presets: 0-9
     */
    protected $pngCompressionLevel;

    /**
     * @var int compression filter of save png images for all presets: 0-9
     */
    protected $pngCompressionFilter;

    /**
     * @var array formats convert map for all presets
     */
    protected $convertMap = [];

    /**
     * @var string absolute path to plug for all presets (used if original image is not available)
     */
    protected $plugPath;

    /**
     * @var bool apply preset effects and postprocessors to plug?
     */
    protected $processPlug;

    /**
     * @var string url to get the plug from a third-party service (used if original image is not available)
     */
    protected $plugUrl;

    /**
     * @var array
     */
    protected $postProcessorsConfig = [];

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
    protected $effectsMap = [
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
    protected $postProcessorsMap = [
        'jpegoptim'  => '\LireinCore\Image\PostProcessors\JpegOptim',
        'optipng'    => '\LireinCore\Image\PostProcessors\OptiPng',
        //'mozjpeg'    => '\LireinCore\Image\PostProcessors\MozJpeg',
        'pngquant'   => '\LireinCore\Image\PostProcessors\PngQuant',
    ];

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
            $driver = ImgHelper::availableDriver();
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
                    $class = $this->postProcessorClassName($postProcessorConfig['type']);
                    if (null === $class) {
                        throw new ConfigException("Incorrect config format. Unknown postprocessor type '{$postProcessorConfig['type']}'");
                    }
                    $this->postProcessorsConfig[] = $postProcessorConfig;
                }
            } else {
                throw new ConfigException('Incorrect config format');
            }
        }
    }

    /**
     * @param string $driver
     * @throws ConfigException
     */
    protected function setDriver($driver)
    {
        if (in_array($driver, ['imagick', 'gd', 'gmagick'], true)) {
            $this->driver = $driver;
        } else {
            throw new ConfigException("Incorrect driver value. Should be 'gd', 'imagick' or 'gmagick'");
        }
    }

    /**
     * @return string
     */
    public function driver()
    {
        return $this->driver;
    }

    /**
     * @param null|string $srcDir
     * @throws ConfigException
     */
    protected function setSrcDir($srcDir)
    {
        if ($srcDir === null) {
            $this->srcDir = null;
        } else {
            if (is_dir($srcDir)) {
                $this->srcDir = rtrim($srcDir, "\\/");
            } else {
                throw new ConfigException("Source directory {$srcDir} not found");
            }
        }
    }

    /**
     * @return null|string
     */
    public function srcDir()
    {
        return $this->srcDir;
    }

    /**
     * @param string $destDir
     * @throws ConfigException
     */
    protected function setDestDir($destDir)
    {
        $destDir = rtrim($destDir, "\\/");

        if (!is_dir($destDir)) {
            try {
                ImageHelper::rmkdir($destDir);
            } catch (\RuntimeException $e) {
                throw new ConfigException("Destination directory {$destDir} does not exist", $e->getCode(), $e);
            }
        }

        $this->destDir = $destDir;
    }

    /**
     * @return string
     */
    public function destDir()
    {
        return $this->destDir;
    }

    /**
     * @param null|string $webDir
     * @throws ConfigException
     */
    protected function setWebDir($webDir)
    {
        if ($webDir === null) {
            $this->webDir = null;
        } else {
            if (is_dir($webDir)) {
                $this->webDir = $webDir;
            } else {
                throw new ConfigException("Web directory {$webDir} not found");
            }
        }
    }

    /**
     * @return null|string
     */
    public function webDir()
    {
        return $this->webDir;
    }

    /**
     * @param null|string $baseUrl
     */
    protected function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * @return null|string
     */
    public function baseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @param int $jpegQuality
     * @throws ConfigException
     */
    protected function setJpegQuality($jpegQuality)
    {
        $value = (int)$jpegQuality;
        if ($value < 0 || $value > 100) {
            throw new ConfigException("Incorrect jpeg_quality value");
        } else {
            $this->jpegQuality = $value;
        }
    }

    /**
     * @return int
     */
    public function jpegQuality()
    {
        return $this->jpegQuality;
    }

    /**
     * @param int $pngCompressionLevel
     * @throws ConfigException
     */
    protected function setPngCompressionLevel($pngCompressionLevel)
    {
        $value = (int)$pngCompressionLevel;
        if ($value < 0 || $value > 9) {
            throw new ConfigException("Incorrect png_compression_level value");
        } else {
            $this->pngCompressionLevel = $value;
        }
    }

    /**
     * @return int
     */
    public function pngCompressionLevel()
    {
        return $this->pngCompressionLevel;
    }

    /**
     * @param int $pngCompressionFilter
     * @throws ConfigException
     */
    protected function setPngCompressionFilter($pngCompressionFilter)
    {
        $value = (int)$pngCompressionFilter;
        if ($value < 0 || $value > 9) {
            throw new ConfigException("Incorrect png_compression_filter value");
        } else {
            $this->pngCompressionFilter = $value;
        }
    }

    /**
     * @return int
     */
    public function pngCompressionFilter()
    {
        return $this->pngCompressionFilter;
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
    protected function setConvertMap($convertMap)
    {
        $formats = ImageHelper::formats();

        foreach ($convertMap as $srcStr => $dest) {
            if (in_array($dest, ImageHelper::supportedDestinationFormats(), true)) {
                foreach (explode(',', $srcStr) as $src) {
                    if ($src === '*' || isset($formats[$src])) {
                        $this->convertMap[$src] = $dest;
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
    public function convertMap()
    {
        return $this->convertMap + $this->defaultConvertMap;
    }

    /**
     * @param null|string $plugPath
     * @throws ConfigException
     */
    protected function setPlugPath($plugPath)
    {
        if ($plugPath === null) {
            $this->plugPath = null;
        } else {
            if (is_file($plugPath)) {
                $this->plugPath = $plugPath;
            } else {
                throw new ConfigException("Plug file {$plugPath} not found");
            }
        }
    }

    /**
     * @return null|string
     */
    public function plugPath()
    {
        return $this->plugPath;
    }

    /**
     * @param bool $processPlug
     */
    protected function setProcessPlug($processPlug)
    {
        $this->processPlug = (bool)$processPlug;
    }

    /**
     * @return bool
     */
    public function isPlugProcessed()
    {
        return $this->processPlug;
    }

    /**
     * @param null|string $plugUrl
     */
    protected function setPlugUrl($plugUrl)
    {
        $this->plugUrl = $plugUrl;
    }

    /**
     * @return null|string
     */
    public function plugUrl()
    {
        return $this->plugUrl;
    }

    /**
     * @param string $imageClass
     * @throws ConfigException
     */
    protected function setImageClass($imageClass)
    {
        if (class_exists($imageClass)) {
            $interfaces = class_implements($imageClass);
            if (in_array('LireinCore\Image\Manipulator', $interfaces, true)) {
                $this->imageClass = $imageClass;
            } else {
                throw new ConfigException("Class {$imageClass} don't implement interface \\LireinCore\\Image\\Manipulator");
            }
        } else {
            throw new ConfigException("Class {$imageClass} not found");
        }
    }

    /**
     * @return string
     */
    public function imageClass()
    {
        return $this->imageClass;
    }

    /**
     * @return bool
     */
    public function hasPostProcessors()
    {
        return !empty($this->postProcessorsConfig);
    }

    /**
     * @return array
     */
    public function postProcessorsConfig()
    {
        return $this->postProcessorsConfig;
    }

    /**
     * Register custom effects or override default effects
     *
     * @param string $type
     * @param string $class class which implements \LireinCore\Image\Effect interface
     * @throws ConfigException
     */
    protected function registerEffect($type, $class)
    {
        if (class_exists($class)) {
            $interfaces = class_implements($class);
            if (in_array('LireinCore\Image\Effect', $interfaces, true)) {
                $this->effectsMap[$type] = $class;
            } else {
                throw new ConfigException("Class {$class} don't implement interface \\LireinCore\\Image\\Effect");
            }
        } else {
            throw new ConfigException("Class {$class} not found");
        }
    }

    /**
     * @return string[]
     */
    public function effectsMap()
    {
        return $this->effectsMap;
    }

    /**
     * @param string $type
     * @return null|string
     */
    public function effectClassName($type)
    {
        return isset($this->effectsMap[$type]) ? $this->effectsMap[$type] : null;
    }

    /**
     * Register custom postprocessor or override default postprocessors
     *
     * @param string $type
     * @param string $class class which implements \LireinCore\Image\PostProcessor interface
     * @throws ConfigException
     */
    protected function registerPostProcessor($type, $class)
    {
        if (class_exists($class)) {
            $interfaces = class_implements($class);
            if (in_array('LireinCore\Image\PostProcessor', $interfaces, true)) {
                $this->postProcessorsMap[$type] = $class;
            } else {
                throw new ConfigException("Class {$class} don't implement interface \\LireinCore\\Image\\PostProcessor");
            }
        } else {
            throw new ConfigException("Class {$class} not found");
        }
    }

    /**
     * @return string[]
     */
    public function postProcessorsMap()
    {
        return $this->postProcessorsMap;
    }

    /**
     * @param string $type
     * @return null|string
     */
    public function postProcessorClassName($type)
    {
        return isset($this->postProcessorsMap[$type]) ? $this->postProcessorsMap[$type] : null;
    }
}