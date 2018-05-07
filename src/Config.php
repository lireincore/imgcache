<?php

namespace LireinCore\ImgCache;

use LireinCore\ImgCache\Exception\ConfigException;

class Config extends BaseConfig
{
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
     * @var PresetConfig[] presets list
     *
     * ['preset name' => 'preset config']
     */
    protected $_presets = [];

    /**
     * @var array presets config
     */
    protected $_presetsConfig = [];

    /**
     * Config constructor.
     *
     * @param array $config
     * @throws ConfigException
     */
    public function __construct($config)
    {
        parent::__construct($config);

        if (empty($config['destdir'])) {
            throw new ConfigException("Destination directory is required");
        }

        if (empty($config['image_class'])) {
            $this->setImageClass(static::DEFAULT_IMAGE_CLASS);
        }

        if (!isset($config['jpeg_quality'])) {
            $this->setJpegQuality(static::DEFAULT_JPEG_QUALITY);
        }

        if (!isset($config['png_compression_level'])) {
            $this->setPngCompressionLevel(static::DEFAULT_PNG_COMPRESSION_LEVEL);
        }

        if (!isset($config['png_compression_filter'])) {
            $this->setPngCompressionFilter(static::DEFAULT_PNG_COMPRESSION_FILTER);
        }

        if (!isset($config['plug']['process'])) {
            $this->setProcessPlug(static::DEFAULT_PROCESS_PLUG);
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
                        throw new ConfigException("Incorrect config format. Unknown postprocessor type `{$postProcessorConfig['type']}`");
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
     * @param string $type
     * @return null|string
     */
    public function getPostProcessorClassName($type)
    {
        return isset($this->_postProcessorsMap[$type]) ? $this->_postProcessorsMap[$type] : null;
    }
}