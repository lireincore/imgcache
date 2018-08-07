<?php

namespace LireinCore\ImgCache;

use LireinCore\Image\ImageHelper;
use LireinCore\ImgCache\Exception\ConfigException;

class PresetConfig
{
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
     * @var array
     */
    protected $effectsConfig = [];

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var string
     */
    protected $hash;

    /**
     * PresetConfig constructor.
     *
     * @param Config $baseConfig
     * @param array $config
     * @throws ConfigException
     */
    public function __construct(Config $baseConfig, $config)
    {
        $this->config = $baseConfig;

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

        if (isset($config['effects'])) {
            if (is_array($config['effects'])) {
                foreach ($config['effects'] as $effectConfig) {
                    if (!isset($effectConfig['type'])) {
                        throw new ConfigException("Incorrect config format. Effect type not specified");
                    }
                    $class = $this->config()->effectClassName($effectConfig['type']);
                    if (null === $class) {
                        throw new ConfigException("Incorrect config format. Unknown effect type `{$effectConfig['type']}`");
                    }
                    $this->effectsConfig[] = $effectConfig;
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
                    $class = $this->config()->postProcessorClassName($postProcessorConfig['type']);
                    if (null === $class) {
                        throw new ConfigException("Incorrect config format. Unknown postprocessor type `{$postProcessorConfig['type']}`");
                    }
                    $this->postProcessorsConfig[] = $postProcessorConfig;
                }
            } else {
                throw new ConfigException('Incorrect config format');
            }
        }

        $this->setHash($this->hashData());
    }

    /**
     * @return array
     */
    protected function hashData()
    {
        $hashData = [];

        $hashData['driver'] = $this->driver();
        $hashData['image_class'] = $this->imageClass();
        $hashData['jpeg_quality'] = $this->jpegQuality();
        $hashData['png_compression_level'] = $this->pngCompressionLevel();
        $hashData['png_compression_filter'] = $this->pngCompressionFilter();
        $hashData['effects_map'] = $this->config()->effectsMap();
        ksort($hashData['effects_map']);
        $hashData['postprocessors_map'] = $this->config()->postProcessorsMap();
        ksort($hashData['postprocessors_map']);

        if ($this->hasEffects()) {
            $hashData['effects_config'] = $this->sortEffectsOrPostProcessorsConfig($this->effectsConfig());
        }

        if ($this->hasPostProcessors()) {
            $hashData['postprocessors_config'] =
                $this->sortEffectsOrPostProcessorsConfig($this->postProcessorsConfig());
        } elseif ($this->config()->hasPostProcessors()) {
            $hashData['postprocessors_config'] =
                $this->sortEffectsOrPostProcessorsConfig($this->config()->postProcessorsConfig());
        }

        return $hashData;
    }

    /**
     * @param array $configItems
     * @return array
     */
    protected function sortEffectsOrPostProcessorsConfig(array $configItems)
    {
        return array_map(function ($item) {
            if (!empty($item['params'])) {
                ksort($item['params']);
            }
            ksort($item);
            return $item;
        }, $configItems);
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
        return $this->driver !== null ? $this->driver : $this->config()->driver();
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
        return $this->srcDir !== null ? $this->srcDir : $this->config()->srcDir();
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
        return $this->destDir !== null ? $this->destDir : $this->config()->destDir();
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
        return $this->webDir !== null ? $this->webDir : $this->config()->webDir();
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
        return $this->baseUrl !== null ? $this->baseUrl : $this->config()->baseUrl();
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
        return $this->jpegQuality !== null ? $this->jpegQuality : $this->config()->jpegQuality();
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
        return $this->pngCompressionLevel !== null ? $this->pngCompressionLevel : $this->config()->pngCompressionLevel();
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
        return $this->pngCompressionFilter !== null ? $this->pngCompressionFilter : $this->config()->pngCompressionFilter();
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
        return $this->convertMap + $this->config()->convertMap();
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
     * @param bool $presetOnly
     * @return null|string
     */
    public function plugPath($presetOnly = false)
    {
        if ($presetOnly) {
            return $this->plugPath;
        } else {
            return $this->plugPath !== null ? $this->plugPath : $this->config()->plugPath();
        }
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
        return $this->processPlug !== null ? $this->processPlug : $this->config()->isPlugProcessed();
    }

    /**
     * @param null|string $plugUrl
     */
    protected function setPlugUrl($plugUrl)
    {
        $this->plugUrl = $plugUrl;
    }

    /**
     * @param bool $presetOnly
     * @return null|string
     */
    public function plugUrl($presetOnly = false)
    {
        if ($presetOnly) {
            return $this->plugUrl;
        } else {
            return $this->plugUrl !== null ? $this->plugUrl : $this->config()->plugUrl();
        }
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
        return $this->imageClass !== null ? $this->imageClass : $this->config()->imageClass();
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
     * @return bool
     */
    public function hasEffects()
    {
        return !empty($this->effectsConfig);
    }

    /**
     * @return array
     */
    public function effectsConfig()
    {
        return $this->effectsConfig;
    }

    /**
     * @param array $hashData
     */
    protected function setHash(array $hashData)
    {
        $this->hash = substr(ImgHelper::hash($hashData), 0, 12);
    }

    /**
     * @return string
     */
    public function hash()
    {
        return $this->hash;
    }

    /**
     * @return Config
     */
    protected function config()
    {
        return $this->config;
    }
}