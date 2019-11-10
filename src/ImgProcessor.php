<?php

namespace LireinCore\ImgCache;

use Exception;
use RuntimeException;
use InvalidArgumentException;
use LireinCore\Image\Effect;
use LireinCore\Image\PostProcessor;
use LireinCore\Image\Manipulator;
use LireinCore\ImgCache\Exception\ConfigException;

final class ImgProcessor implements ImgProcessorInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var PresetConfigRegistry
     */
    private $presetConfigRegistry;

    /**
     * @var array
     */
    private $presetsEffects = [];

    /**
     * @var array
     */
    private $presetsPostProcessors = [];

    /**
     * @var PostProcessor[]
     */
    private $postProcessors = [];

    /**
     * ImgProcessor constructor.
     *
     * @param Config $config
     * @param PresetConfigRegistry $presetConfigRegistry
     */
    public function __construct(
        Config $config,
        PresetConfigRegistry $presetConfigRegistry
    )
    {
        $this->config = $config;
        $this->presetConfigRegistry = $presetConfigRegistry;
    }

    /**
     * @param string $srcPath
     * @param string $destPath
     * @param string $format
     * @param string $presetDefinitionHash
     * @param bool $isStub
     * @throws ConfigException
     * @throws RuntimeException
     */
    public function createThumb(
        string $srcPath,
        string $destPath,
        string $format,
        string $presetDefinitionHash,
        bool $isStub = false
    ) : void
    {
        $presetConfig = $this->presetConfig($presetDefinitionHash);
        $class = $presetConfig->imageClass();
        $driverCode = ImgHelper::driverCode($presetConfig->driver());
        try {
            /** @var Manipulator $manipulator */
            $manipulator = new $class($driverCode, false);
        } catch (Exception $ex) {
            throw new RuntimeException('Graphic library error', 0, $ex);
        }
        try {
            $manipulator->open($srcPath);
        } catch (Exception $ex) {
            throw new RuntimeException("Error opening source image '{$srcPath}'", 0, $ex);
        }
        $processStub = $presetConfig->isStubProcessed();
        if (!$isStub || $processStub) {
            $this->applyEffects($manipulator, $presetDefinitionHash);
        }
        try {
            $manipulator->save($destPath, [
                'format'                 => $format,
                'jpeg_quality'           => $presetConfig->jpegQuality(),
                'png_compression_level'  => $presetConfig->pngCompressionLevel(),
                'png_compression_filter' => $presetConfig->pngCompressionFilter(),
            ]);
        } catch (Exception $ex) {
            throw new RuntimeException("Error saving image '{$destPath}'", 0, $ex);
        }
        if (!$isStub || $processStub) {
            $this->applyPostProcessors($destPath, $format, $presetDefinitionHash);
        }
    }

    /**
     * @param Manipulator $manipulator
     * @param string $presetDefinitionHash
     * @throws ConfigException
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    private function applyEffects(Manipulator $manipulator, string $presetDefinitionHash) : void
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
            try {
                foreach ($this->presetsEffects[$presetDefinitionHash] as $effect) {
                    $manipulator->apply($effect);
                }
            } catch (Exception $ex) {
                throw new RuntimeException('Error applying effects', 0, $ex);
            }
        }
    }

    /**
     * @param string $path
     * @param string $format
     * @param string $presetDefinitionHash
     * @throws ConfigException
     * @throws RuntimeException
     */
    private function applyPostProcessors(string $path, string $format, string $presetDefinitionHash) : void
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
            try {
                foreach ($this->presetsPostProcessors[$presetDefinitionHash] as $postProcessor) {
                    if (\in_array($format, $postProcessor->supportedFormats(), true)) {
                        $postProcessor->process($path);
                    }
                }
            } catch (Exception $ex) {
                throw new RuntimeException('Error applying postprocessors', 0, $ex);
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
            try {
                foreach ($this->postProcessors as $postProcessor) {
                    if (\in_array($format, $postProcessor->supportedFormats(), true)) {
                        $postProcessor->process($path);
                    }
                }
            } catch (Exception $ex) {
                throw new RuntimeException('Error applying postprocessors', 0, $ex);
            }
        }
    }

    /**
     * @return Config
     */
    private function config() : Config
    {
        return $this->config;
    }

    /**
     * @return PresetConfigRegistry
     */
    private function presetConfigRegistry() : PresetConfigRegistry
    {
        return $this->presetConfigRegistry;
    }

    /**
     * @param string $presetDefinitionHash
     * @return PresetConfig
     * @throws ConfigException
     */
    private function presetConfig(string $presetDefinitionHash) : PresetConfig
    {
        return $this->presetConfigRegistry()->presetConfig($presetDefinitionHash);
    }
}