<?php

namespace LireinCore\ImgCache;

use LireinCore\ImgCache\Exception\ConfigException;

class PresetConfig extends BaseConfig
{
    /**
     * @var Config
     */
    protected $_config;

    /**
     * @var array
     */
    protected $_effectsConfig = [];

    /**
     * @var array
     */
    protected $_postProcessorsConfig = [];

    /**
     * PresetConfig constructor.

     * @param Config $config
     * @param array $presetConfig
     * @throws ConfigException
     */
    public function __construct(Config $config, $presetConfig)
    {
        parent::__construct($presetConfig);

        $this->_config = $config;

        if (isset($presetConfig['effects'])) {
            if (is_array($presetConfig['effects'])) {
                foreach ($presetConfig['effects'] as $effectConfig) {
                    if (!isset($effectConfig['type'])) {
                        throw new ConfigException("Incorrect config format. Effect type not specified");
                    }
                    $class = $this->_config->getEffectClassName($effectConfig['type']);
                    if (null === $class) {
                        throw new ConfigException("Incorrect config format. Unknown effect type `{$effectConfig['type']}`");
                    }
                    $this->_effectsConfig[] = $effectConfig;
                }
            } else {
                throw new ConfigException('Incorrect config format');
            }
        }

        if (isset($presetConfig['postprocessors'])) {
            if (is_array($presetConfig['postprocessors'])) {
                foreach ($presetConfig['postprocessors'] as $postProcessorConfig) {
                    if (!isset($postProcessorConfig['type'])) {
                        throw new ConfigException("Incorrect config format. Postprocessor type not specified");
                    }
                    $class = $this->_config->getPostProcessorClassName($postProcessorConfig['type']);
                    if (null === $class) {
                        throw new ConfigException("Incorrect config format. Unknown postprocessor type `{$postProcessorConfig['type']}`");
                    }
                    $this->_postProcessorsConfig[] = $postProcessorConfig;
                }
            } else {
                throw new ConfigException('Incorrect config format');
            }
        }
    }

    /**
     * @return bool
     */
    public function hasEffects()
    {
        return !empty($this->_effectsConfig);
    }

    /**
     * @return array
     */
    public function getEffectsConfig()
    {
        return $this->_effectsConfig;
    }

    /**
     * @return null|string
     */
    public function getDriver()
    {
        return $this->_driver !== null ? $this->_driver : $this->_config->getDriver();
    }

    /**
     * @return null|string
     */
    public function getSrcDir()
    {
        return $this->_srcDir !== null ? $this->_srcDir : $this->_config->getSrcDir();
    }

    /**
     * @return string
     */
    public function getDestDir()
    {
        return $this->_destDir !== null ? $this->_destDir : $this->_config->getDestDir();
    }

    /**
     * @return null|string
     */
    public function getWebDir()
    {
        return $this->_webDir !== null ? $this->_webDir : $this->_config->getWebDir();
    }

    /**
     * @return null|string
     */
    public function getBaseUrl()
    {
        return $this->_baseUrl !== null ? $this->_baseUrl : $this->_config->getBaseUrl();
    }

    /**
     * @return int
     */
    public function getJpegQuality()
    {
        return $this->_jpegQuality !== null ? $this->_jpegQuality : $this->_config->getJpegQuality();
    }

    /**
     * @return int
     */
    public function getPngCompressionLevel()
    {
        return $this->_pngCompressionLevel !== null ? $this->_pngCompressionLevel : $this->_config->getPngCompressionLevel();
    }

    /**
     * @return int
     */
    public function getPngCompressionFilter()
    {
        return $this->_pngCompressionFilter !== null ? $this->_pngCompressionFilter : $this->_config->getPngCompressionFilter();
    }

    /**
     * @return array
     */
    public function getConvertMap()
    {
        return $this->_convertMap + $this->_config->getConvertMap() + static::DEFAULT_CONVERT_MAP;
    }

    /**
     * @param bool $presetOnly
     * @return null|string
     */
    public function getPlugPath($presetOnly = false)
    {
        if ($presetOnly) {
            return $this->_plugPath;
        } else {
            return $this->_plugPath !== null ? $this->_plugPath : $this->_config->getPlugPath();
        }
    }

    /**
     * @return bool
     */
    public function getProcessPlug()
    {
        return $this->_processPlug !== null ? $this->_processPlug : $this->_config->getProcessPlug();
    }

    /**
     * @param bool $presetOnly
     * @return null|string
     */
    public function getPlugUrl($presetOnly = false)
    {
        if ($presetOnly) {
            return $this->_plugUrl;
        } else {
            return $this->_plugUrl !== null ? $this->_plugUrl : $this->_config->getPlugUrl();
        }
    }

    /**
     * @return string
     */
    public function getImageClass()
    {
        return $this->_imageClass !== null ? $this->_imageClass : $this->_config->getImageClass();
    }
}