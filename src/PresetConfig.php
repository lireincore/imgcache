<?php

namespace LireinCore\ImgCache;

use LireinCore\Image\ImageHelper;
use LireinCore\Image\Manipulator;
use LireinCore\ImgCache\Exception\ConfigException;

final class PresetConfig
{
    /**
     * @var string graphic library for all presets: `imagick`, `gd`, `gmagick`
     * (by default, tries to use: imagick->gd->gmagick)
     */
    private $driver;

    /**
     * @var string image class for all presets (which implements \LireinCore\Image\Manipulator interface)
     */
    private $imageClass;

    /**
     * @var string original images source directory for all presets
     */
    private $srcDir;

    /**
     * @var string thumbs destination directory for all presets
     * (to access the thumbs from the web they should be in a directory accessible from the web)
     */
    private $destDir;

    /**
     * @var string web directory for all presets
     */
    private $webDir;

    /**
     * @var string base url for all presets
     */
    private $baseUrl;

    /**
     * @var int quality of save jpeg images for all presets: 0-100
     */
    private $jpegQuality;

    /**
     * @var int compression level of save png images for all presets: 0-9
     */
    private $pngCompressionLevel;

    /**
     * @var int compression filter of save png images for all presets: 0-9
     */
    private $pngCompressionFilter;

    /**
     * @var array formats convert map for all presets
     */
    private $convertMap = [];

    /**
     * @var string absolute path to stub for all presets (used if original image is not available)
     */
    private $stubPath;

    /**
     * @var bool apply preset effects and postprocessors to stub?
     */
    private $processStub;

    /**
     * @var string url to get the stub from a third-party service (used if original image is not available)
     */
    private $stubUrl;

    /**
     * @var array
     */
    private $postProcessorsConfig = [];

    /**
     * @var array
     */
    private $effectsConfig = [];

    /**
     * @var Config
     */
    private $config;

    /**
     * @var string
     */
    private $hash;

    /**
     * PresetConfig constructor.
     *
     * @param Config $baseConfig
     * @param array $config
     * @throws ConfigException
     */
    public function __construct(Config $baseConfig, array $config)
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
        }

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
            if (\is_array($config['convert_map'])) {
                $this->convertMap = $this->config()->convertMap();
                foreach ($config['convert_map'] as $srcFormats => $destFormat) {
                    $this->registerConvert($srcFormats, $destFormat);
                }
            } else {
                throw new ConfigException('Incorrect config format');
            }
        }

        if (!empty($config['plug']['path'])) {
            $this->setStubPath($config['plug']['path']);
        }

        if (isset($config['plug']['process'])) {
            $this->setProcessStub($config['plug']['process']);
        }

        if (!empty($config['plug']['url'])) {
            $this->setStubUrl($config['plug']['url']);
        }

        if (isset($config['effects'])) {
            if (\is_array($config['effects'])) {
                foreach ($config['effects'] as $effectConfig) {
                    if (!isset($effectConfig['type'])) {
                        throw new ConfigException('Incorrect config format. Effect type not specified');
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
            if (\is_array($config['postprocessors'])) {
                foreach ($config['postprocessors'] as $postProcessorConfig) {
                    if (!isset($postProcessorConfig['type'])) {
                        throw new ConfigException('Incorrect config format. Postprocessor type not specified');
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
    private function hashData() : array
    {
        $hashData = [];
        $hashData['driver'] = $this->driver();
        $hashData['image_class'] = $this->imageClass();
        $hashData['jpeg_quality'] = $this->jpegQuality();
        $hashData['png_compression_level'] = $this->pngCompressionLevel();
        $hashData['png_compression_filter'] = $this->pngCompressionFilter();
        $hashData['effects_map'] = $this->config()->effectsMap();
        \ksort($hashData['effects_map']);
        $hashData['postprocessors_map'] = $this->config()->postProcessorsMap();
        \ksort($hashData['postprocessors_map']);
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
    private function sortEffectsOrPostProcessorsConfig(array $configItems) : array
    {
        return \array_map(static function ($item) {
            if (!empty($item['params'])) {
                \ksort($item['params']);
            }
            \ksort($item);
            return $item;
        }, $configItems);
    }

    /**
     * @param string $driver
     * @throws ConfigException
     */
    private function setDriver(string $driver) : void
    {
        if (\in_array($driver, ImageHelper::supportedDrivers(), true)) {
            $this->driver = $driver;
        } else {
            throw new ConfigException("Incorrect driver value. Should be one of the following: '" . \implode("', '", ImageHelper::supportedDrivers()) . "'");
        }
    }

    /**
     * @return string
     */
    public function driver() : string
    {
        return $this->driver ?? $this->config()->driver();
    }

    /**
     * @param null|string $srcDir
     * @throws ConfigException
     */
    private function setSrcDir(?string $srcDir) : void
    {
        if ($srcDir === null) {
            $this->srcDir = null;
        } elseif (\is_dir($srcDir)) {
            $this->srcDir = \rtrim($srcDir, "\\/");
        } else {
            throw new ConfigException("Source directory {$srcDir} not found");
        }
    }

    /**
     * @return null|string
     */
    public function srcDir() : ?string
    {
        return $this->srcDir ?? $this->config()->srcDir();
    }

    /**
     * @param string $destDir
     * @throws ConfigException
     */
    private function setDestDir(string $destDir) : void
    {
        $destDir = \rtrim($destDir, "\\/");
        if (!\is_dir($destDir)) {
            try {
                ImageHelper::rmkdir($destDir);
            } catch (\RuntimeException $e) {
                throw new ConfigException("Destination directory {$destDir} does not exist", 0, $e);
            }
        }

        $this->destDir = $destDir;
    }

    /**
     * @return string
     */
    public function destDir() : string
    {
        return $this->destDir ?? $this->config()->destDir();
    }

    /**
     * @param null|string $webDir
     * @throws ConfigException
     */
    private function setWebDir(?string $webDir) : void
    {
        if ($webDir === null || \is_dir($webDir)) {
            $this->webDir = $webDir;
        } else {
            throw new ConfigException("Web directory {$webDir} not found");
        }
    }

    /**
     * @return null|string
     */
    public function webDir() : ?string
    {
        return $this->webDir ?? $this->config()->webDir();
    }

    /**
     * @param null|string $baseUrl
     */
    private function setBaseUrl(?string $baseUrl) : void
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * @return null|string
     */
    public function baseUrl() : ?string
    {
        return $this->baseUrl ?? $this->config()->baseUrl();
    }

    /**
     * @param int $jpegQuality
     * @throws ConfigException
     */
    private function setJpegQuality(int $jpegQuality) : void
    {
        if ($jpegQuality < 0 || $jpegQuality > 100) {
            throw new ConfigException('Incorrect jpeg_quality value');
        }
        $this->jpegQuality = $jpegQuality;
    }

    /**
     * @return int
     */
    public function jpegQuality() : int
    {
        return $this->jpegQuality ?? $this->config()->jpegQuality();
    }

    /**
     * @param int $pngCompressionLevel
     * @throws ConfigException
     */
    private function setPngCompressionLevel(int $pngCompressionLevel) : void
    {
        if ($pngCompressionLevel < 0 || $pngCompressionLevel > 9) {
            throw new ConfigException('Incorrect png_compression_level value');
        }
        $this->pngCompressionLevel = $pngCompressionLevel;
    }

    /**
     * @return int
     */
    public function pngCompressionLevel() : int
    {
        return $this->pngCompressionLevel ?? $this->config()->pngCompressionLevel();
    }

    /**
     * @param int $pngCompressionFilter
     * @throws ConfigException
     */
    private function setPngCompressionFilter(int $pngCompressionFilter) : void
    {
        if ($pngCompressionFilter < 0 || $pngCompressionFilter > 9) {
            throw new ConfigException('Incorrect png_compression_filter value');
        }
        $this->pngCompressionFilter = $pngCompressionFilter;
    }

    /**
     * @return int
     */
    public function pngCompressionFilter() : int
    {
        return $this->pngCompressionFilter ?? $this->config()->pngCompressionFilter();
    }

    /**
     * @param string $srcFormats
     * @param string $destFormat
     * @throws ConfigException
     */
    private function registerConvert(string $srcFormats, string $destFormat) : void
    {
        if (\in_array($destFormat, ImageHelper::supportedDestinationFormats(), true)) {
            $formats = ImageHelper::formats();
            foreach (\explode(',', $srcFormats) as $srcFormat) {
                if ($srcFormat === '*' || isset($formats[$srcFormat])) {
                    $this->convertMap[$srcFormat] = $destFormat;
                } else {
                    throw new ConfigException("Incorrect convert value. Unsupported source image format {$srcFormat}");
                }
            }
        } else {
            throw new ConfigException("Incorrect convert value. Unsupported destination image format {$destFormat}");
        }
    }

    /**
     * @return array
     */
    public function convertMap() : array
    {
        return $this->convertMap;
    }

    /**
     * @param null|string $stubPath
     * @throws ConfigException
     */
    private function setStubPath(?string $stubPath) : void
    {
        if ($stubPath === null || \is_file($stubPath)) {
            $this->stubPath = $stubPath;
        } else {
            throw new ConfigException("Stub file {$stubPath} not found");
        }
    }

    /**
     * @param bool $presetOnly
     * @return null|string
     */
    public function stubPath(bool $presetOnly = false) : ?string
    {
        if ($presetOnly) {
            return $this->stubPath;
        }
        return $this->stubPath ?? $this->config()->stubPath();
    }

    /**
     * @param bool $processStub
     */
    private function setProcessStub(bool $processStub) : void
    {
        $this->processStub = $processStub;
    }

    /**
     * @return bool
     */
    public function isStubProcessed() : bool
    {
        return $this->processStub ?? $this->config()->isStubProcessed();
    }

    /**
     * @param null|string $stubUrl
     */
    private function setStubUrl(?string $stubUrl) : void
    {
        $this->stubUrl = $stubUrl;
    }

    /**
     * @param bool $presetOnly
     * @return null|string
     */
    public function stubUrl(bool $presetOnly = false) : ?string
    {
        if ($presetOnly) {
            return $this->stubUrl;
        }
        return $this->stubUrl ?? $this->config()->stubUrl();
    }

    /**
     * @param string $imageClass
     * @throws ConfigException
     */
    private function setImageClass(string $imageClass) : void
    {
        if (\class_exists($imageClass)) {
            $interfaces = \class_implements($imageClass);
            if (\in_array(Manipulator::class, $interfaces, true)) {
                $this->imageClass = $imageClass;
            } else {
                throw new ConfigException("Class {$imageClass} don't implement interface " . Manipulator::class);
            }
        } else {
            throw new ConfigException("Class {$imageClass} not found");
        }
    }

    /**
     * @return string
     */
    public function imageClass() : string
    {
        return $this->imageClass ?? $this->config()->imageClass();
    }

    /**
     * @return bool
     */
    public function hasPostProcessors() : bool
    {
        return !empty($this->postProcessorsConfig);
    }

    /**
     * @return array
     */
    public function postProcessorsConfig() : array
    {
        return $this->postProcessorsConfig;
    }

    /**
     * @return bool
     */
    public function hasEffects() : bool
    {
        return !empty($this->effectsConfig);
    }

    /**
     * @return array
     */
    public function effectsConfig() : array
    {
        return $this->effectsConfig;
    }

    /**
     * @param array $hashData
     */
    private function setHash(array $hashData) : void
    {
        $this->hash = \substr(ImgHelper::hash($hashData), 0, 12);
    }

    /**
     * @return string
     */
    public function hash() : string
    {
        return $this->hash;
    }

    /**
     * @return Config
     */
    private function config() : Config
    {
        return $this->config;
    }
}