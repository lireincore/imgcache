<?php

namespace LireinCore\ImgCache;

use LireinCore\Image\Effect;
use LireinCore\Image\PostProcessor;
use LireinCore\Image\Manipulator;
use LireinCore\ImgCache\Exception\ConfigException;

class ImgProcessor
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var PresetConfigRegistry
     */
    protected $presetConfigRegistry;

    /**
     * @var array
     */
    protected $presetsEffects = [];

    /**
     * @var array
     */
    protected $presetsPostProcessors = [];

    /**
     * @var PostProcessor[]
     */
    protected $postProcessors = [];

    /**
     * ImgProcessor constructor.
     *
     * @param Config $config
     * @param PresetConfigRegistry $presetConfigRegistry
     */
    public function __construct(Config $config, PresetConfigRegistry $presetConfigRegistry)
    {
        $this->config = $config;
        $this->presetConfigRegistry = $presetConfigRegistry;
    }

    /**
     * @param string $srcPath
     * @param string $destPath
     * @param string $format
     * @param string $presetDefinitionHash
     * @param bool $isPlug
     * @throws ConfigException
     * @throws \RuntimeException
     */
    public function createThumb($srcPath, $destPath, $format, $presetDefinitionHash, $isPlug = false)
    {
        $presetConfig = $this->presetConfig($presetDefinitionHash);
        $class = $presetConfig->imageClass();
        $driverCode = ImgHelper::driverCode($presetConfig->driver());
        try {
            /** @var Manipulator $manipulator */
            $manipulator = (new $class($driverCode, false));
        } catch (\Exception $ex) {
            throw new \RuntimeException("Graphic library error", 0, $ex);
        }
        try {
            $manipulator->open($srcPath);
        } catch (\Exception $ex) {
            throw new \RuntimeException("Error opening original image", 0, $ex);
        }
        $processPlug = $presetConfig->isPlugProcessed();
        try {
            if (!$isPlug || $processPlug) {
                $this->applyEffects($manipulator, $presetDefinitionHash);
            }
        } catch (\Exception $ex) {
            throw new \RuntimeException("Error applying effects", 0, $ex);
        }
        try {
            $manipulator->save($destPath, [
                'format'                 => $format,
                'jpeg_quality'           => $presetConfig->jpegQuality(),
                'png_compression_level'  => $presetConfig->pngCompressionLevel(),
                'png_compression_filter' => $presetConfig->pngCompressionFilter(),
            ]);
        } catch (\Exception $ex) {
            throw new \RuntimeException("Image saving error", 0, $ex);
        }
        try {
            if (!$isPlug || $processPlug) {
                $this->applyPostProcessors($destPath, $format, $presetDefinitionHash);
            }
        } catch (\Exception $ex) {
            throw new \RuntimeException("Error applying postprocessors", 0, $ex);
        }
    }

    /**
     * @param Manipulator $manipulator
     * @param string $presetDefinitionHash
     * @throws ConfigException
     * @throws \InvalidArgumentException
     * @throws \OutOfBoundsException
     * @throws \RuntimeException
     */
    protected function applyEffects($manipulator, $presetDefinitionHash)
    {
        $presetConfig = $this->presetConfig($presetDefinitionHash);
        if ($presetConfig->hasEffects()) {
            if (!isset($this->presetsEffects[$presetDefinitionHash])) {
                $config = $this->config();
                $this->presetsEffects[$presetDefinitionHash] = [];

                foreach ($presetConfig->effectsConfig() as $effectConfig) {
                    $class = $config->effectClassName($effectConfig['type']);
                    $params = empty($effectConfig['params']) ? [] : $effectConfig['params'];
                    /** @var Effect $effect */
                    $effect = ImgHelper::createClassArrayAssoc($class, $params);
                    $this->presetsEffects[$presetDefinitionHash][] = $effect;
                }
            }

            foreach ($this->presetsEffects[$presetDefinitionHash] as $effect) {
                $manipulator->apply($effect);
            }
        }
    }

    /**
     * @param string $path
     * @param string $format
     * @param string $presetDefinitionHash
     * @throws ConfigException
     * @throws \RuntimeException
     */
    protected function applyPostProcessors($path, $format, $presetDefinitionHash)
    {
        $config = $this->config();
        $presetConfig = $this->presetConfig($presetDefinitionHash);
        if ($presetConfig->hasPostProcessors()) {
            if (!isset($this->presetsPostProcessors[$presetDefinitionHash])) {
                $this->presetsPostProcessors[$presetDefinitionHash] = [];
                foreach ($presetConfig->postProcessorsConfig() as $postProcessorConfig) {
                    $class = $config->postProcessorClassName($postProcessorConfig['type']);
                    $params = empty($postProcessorConfig['params']) ? [] : $postProcessorConfig['params'];
                    /** @var PostProcessor $postProcessor */
                    $postProcessor = ImgHelper::createClassArrayAssoc($class, $params);
                    $this->presetsPostProcessors[$presetDefinitionHash][] = $postProcessor;
                }
            }
            foreach ($this->presetsPostProcessors[$presetDefinitionHash] as $postProcessor) {
                if (in_array($format, $postProcessor->supportedFormats())) {
                    $postProcessor->process($path);
                }
            }
        } elseif ($config->hasPostProcessors()) {
            if (empty($this->postProcessors)) {
                foreach ($config->postProcessorsConfig() as $postProcessorConfig) {
                    $class = $config->postProcessorClassName($postProcessorConfig['type']);
                    $params = empty($postProcessorConfig['params']) ? [] : $postProcessorConfig['params'];
                    /** @var PostProcessor $postProcessor */
                    $postProcessor = ImgHelper::createClassArrayAssoc($class, $params);
                    $this->postProcessors[] = $postProcessor;
                }
            }

            foreach ($this->postProcessors as $postProcessor) {
                if (in_array($format, $postProcessor->supportedFormats())) {
                    $postProcessor->process($path);
                }
            }
        }
    }

    /**
     * @return Config
     */
    protected function config()
    {
        return $this->config;
    }

    /**
     * @return PresetConfigRegistry
     */
    protected function presetConfigRegistry()
    {
        return $this->presetConfigRegistry;
    }

    /**
     * @param string $presetDefinitionHash
     * @return PresetConfig
     * @throws ConfigException
     */
    protected function presetConfig($presetDefinitionHash)
    {
        return $this->presetConfigRegistry()->presetConfig($presetDefinitionHash);
    }
}