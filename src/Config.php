<?php

namespace LireinCore\ImgCache;

use LireinCore\Image\Effect;
use LireinCore\Image\Effects\Flip;
use LireinCore\Image\Effects\Rotate;
use LireinCore\Image\Effects\Resize;
use LireinCore\Image\Effects\Crop;
use LireinCore\Image\Effects\Scale;
use LireinCore\Image\Effects\ScaleUp;
use LireinCore\Image\Effects\ScaleDown;
use LireinCore\Image\Effects\Fit;
use LireinCore\Image\Effects\Cover;
use LireinCore\Image\Effects\Negative;
use LireinCore\Image\Effects\Grayscale;
use LireinCore\Image\Effects\Gamma;
use LireinCore\Image\Effects\Blur;
use LireinCore\Image\Effects\Overlay;
use LireinCore\Image\Effects\Text;
use LireinCore\Image\PostProcessor;
use LireinCore\Image\PostProcessors\JpegOptim;
use LireinCore\Image\PostProcessors\OptiPng;
//use LireinCore\Image\PostProcessors\MozJpeg;
use LireinCore\Image\PostProcessors\PngQuant;
use LireinCore\Image\ImageHelper;
use LireinCore\Image\Manipulator;
use LireinCore\Image\Manipulators\Imagine;
use LireinCore\ImgCache\Exception\ConfigException;

final class Config
{
    private const DEFAULT_IMAGE_CLASS = Imagine::class;
    private const DEFAULT_JPEG_QUALITY = 75;
    private const DEFAULT_PNG_COMPRESSION_LEVEL = 7;
    private const DEFAULT_PNG_COMPRESSION_FILTER = 5;
    private const DEFAULT_PROCESS_STUB = false;

    /**
     * @var string graphic library for all presets: 'gd', 'imagick', 'gmagick'
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
     * @var array formats convert map for all presets
     */
    private $convertMap = [
        'jpeg' => 'jpeg',
        'png'  => 'png',
        'gif'  => 'gif',
        'wbmp' => 'wbmp',
        'xbm'  => 'xbm',
        '*'    => 'png'
    ];

    /**
     * @var array effects map
     */
    private $effectsMap = [
        'flip'       => Flip::class,
        'rotate'     => Rotate::class,
        'resize'     => Resize::class,
        'crop'       => Crop::class,
        'scale'      => Scale::class,
        'scale_up'   => ScaleUp::class,
        'scale_down' => ScaleDown::class,
        'fit'        => Fit::class,
        'cover'      => Cover::class,
        'negative'   => Negative::class,
        'grayscale'  => Grayscale::class,
        'gamma'      => Gamma::class,
        'blur'       => Blur::class,
        'overlay'    => Overlay::class,
        'text'       => Text::class
    ];

    /**
     * @var array postprocessors map
     */
    private $postProcessorsMap = [
        'jpegoptim' => JpegOptim::class,
        'optipng'   => OptiPng::class,
        //'mozjpeg'   => MozJpeg::class,
        'pngquant'  => PngQuant::class,
    ];

    /**
     * @var array
     */
    private $postProcessorsConfig = [];

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
            throw new ConfigException('Destination directory is required');
        }

        if (isset($config['driver'])) {
            $this->setDriver($config['driver']);
        } else {
            $driver = ImgHelper::availableDriver();
            if ($driver === null) {
                throw new ConfigException("No graphic libraries installed or higher versions is required. Please, install one of the following: '" . \implode("', '", ImageHelper::supportedDrivers()) . "'");
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
        }

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
            if (\is_array($config['convert_map'])) {
                foreach ($config['convert_map'] as $srcFormats => $destFormat) {
                    $this->registerConvert($srcFormats, $destFormat);
                }
            } else {
                throw new ConfigException('Incorrect config format');
            }
        }

        if (isset($config['plug']['path'])) {
            $this->setStubPath($config['plug']['path']);
        }

        if (isset($config['plug']['process'])) {
            $this->setProcessStub($config['plug']['process']);
        } else {
            $this->setProcessStub(static::DEFAULT_PROCESS_STUB);
        }

        if (isset($config['plug']['url'])) {
            $this->setStubUrl($config['plug']['url']);
        }

        if (isset($config['effects_map'])) {
            if (\is_array($config['effects_map'])) {
                foreach ($config['effects_map'] as $type => $class) {
                    $this->registerEffect($type, $class);
                }
            } else {
                throw new ConfigException('Incorrect config format');
            }
        }

        if (isset($config['postprocessors_map'])) {
            if (\is_array($config['postprocessors_map'])) {
                foreach ($config['postprocessors_map'] as $type => $class) {
                    $this->registerPostProcessor($type, $class);
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
    private function setDriver(string $driver) : void
    {
        if (\in_array($driver, ImageHelper::supportedDrivers(), true)) {
            $this->driver = $driver;
        } else {
            throw new ConfigException("Incorrect driver value. Should be one of the following: '" . implode("', '", ImageHelper::supportedDrivers()) . "'");
        }
    }

    /**
     * @return string
     */
    public function driver() : string
    {
        return $this->driver;
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
        return $this->srcDir;
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
        return $this->destDir;
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
        return $this->webDir;
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
        return $this->baseUrl;
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
        return $this->jpegQuality;
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
        return $this->pngCompressionLevel;
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
        return $this->pngCompressionFilter;
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
     * @return null|string
     */
    public function stubPath() : ?string
    {
        return $this->stubPath;
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
        return $this->processStub;
    }

    /**
     * @param null|string $stubUrl
     */
    private function setStubUrl(?string $stubUrl) : void
    {
        $this->stubUrl = $stubUrl;
    }

    /**
     * @return null|string
     */
    public function stubUrl() : ?string
    {
        return $this->stubUrl;
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
        return $this->imageClass;
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
     * Register custom effects or override default effects
     *
     * @param string $type
     * @param string $class class which implements \LireinCore\Image\Effect interface
     * @throws ConfigException
     */
    private function registerEffect(string $type, string $class) : void
    {
        if (\class_exists($class)) {
            $interfaces = \class_implements($class);
            if (\in_array(Effect::class, $interfaces, true)) {
                $this->effectsMap[$type] = $class;
            } else {
                throw new ConfigException("Class {$class} don't implement interface " . Effect::class);
            }
        } else {
            throw new ConfigException("Class {$class} not found");
        }
    }

    /**
     * @return string[]
     */
    public function effectsMap() : array
    {
        return $this->effectsMap;
    }

    /**
     * @param string $type
     * @return null|string
     */
    public function effectClassName(string $type) : ?string
    {
        return $this->effectsMap[$type] ?? null;
    }

    /**
     * Register custom postprocessor or override default postprocessors
     *
     * @param string $type
     * @param string $class class which implements \LireinCore\Image\PostProcessor interface
     * @throws ConfigException
     */
    private function registerPostProcessor(string $type, string $class) : void
    {
        if (\class_exists($class)) {
            $interfaces = \class_implements($class);
            if (\in_array(PostProcessor::class, $interfaces, true)) {
                $this->postProcessorsMap[$type] = $class;
            } else {
                throw new ConfigException("Class {$class} don't implement interface " . PostProcessor::class);
            }
        } else {
            throw new ConfigException("Class {$class} not found");
        }
    }

    /**
     * @return string[]
     */
    public function postProcessorsMap() : array
    {
        return $this->postProcessorsMap;
    }

    /**
     * @param string $type
     * @return null|string
     */
    public function postProcessorClassName(string $type) : ?string
    {
        return $this->postProcessorsMap[$type] ?? null;
    }
}