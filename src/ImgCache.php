<?php

namespace LireinCore\ImgCache;

use LireinCore\Image\ImageInterface;
use LireinCore\Image\EffectInterface;
use LireinCore\Image\PostProcessorInterface;
use LireinCore\Image\ImageHelper;
use LireinCore\ImgCache\Exception\ConfigException;

class ImgCache
{
    /**
     * @var Config
     */
    protected $_config;

    /**
     * @var PathResolver
     */
    protected $_pathResolver;

    /**
     * @var array
     */
    protected $_presetsEffects = [];

    /**
     * @var array
     */
    protected $_presetsPostProcessors = [];

    /**
     * @var PostProcessorInterface[]
     */
    protected $_postProcessors = [];

    /**
     * ImgCache constructor.
     *
     * @param array $config
     * @throws ConfigException
     */
    public function __construct($config)
    {
        if (!is_array($config)) {
            throw new ConfigException('Incorrect config format');
        }
        $this->_config = new Config($config);
        $this->_pathResolver = new PathResolver($this->getConfig());
    }

    /**
     * @param array $config
     * @throws ConfigException
     */
    public function setConfig($config)
    {
        if (!is_array($config)) {
            throw new ConfigException('Incorrect config format');
        }
        $this->_config = new Config($config);
        $this->_pathResolver->setConfig($this->_config);
        $this->_presetsEffects = [];
        $this->_presetsPostProcessors = [];
        $this->_postProcessors = [];
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * @param string $presetName
     * @param string|null $fileRelPath
     * @param bool $usePlug
     * @return null|string
     * @throws ConfigException
     */
    public function path($presetName, $fileRelPath = null, $usePlug = true)
    {
        if (!$this->isPreset($presetName)) {
            return null;
        }

        if ($fileRelPath !== null) {
            $destPath = $this->_pathResolver->getDestPath($presetName, $fileRelPath);
            if ($this->checkThumb($presetName, $fileRelPath, $destPath)) {
                $path = $destPath;
            } else {
                $path = null;
            }
        } else {
            $path = null;
        }

        if ($path === null && $usePlug) {
            $presetConfig = $this->getPresetConfig($presetName);
            $plugPath = $presetConfig->getPlugPath();
            if ($plugPath !== null) {
                $plugDestPath = $this->_pathResolver->getDestPath($presetName, $plugPath, true);
                if ($this->checkPlug($presetName, $plugPath, $plugDestPath)) {
                    $path = $plugDestPath;
                }
            }
        }

        return $path;
    }

    /**
     * @param string|null $presetName
     * @param string|null $fileRelPath
     * @param bool $absolute
     * @param bool $usePlug
     * @return null|string
     * @throws ConfigException
     */
    public function url($presetName = null, $fileRelPath = null, $absolute = false, $usePlug = true)
    {
        if ($presetName === null) {
            if ($usePlug) {
                $plugUrl = $this->getConfig()->getPlugUrl();
                if ($plugUrl !== null) {
                    return $plugUrl;
                }
            }

            return null;
        }

        if (!$this->isPreset($presetName)) {
            return null;
        }

        $presetConfig = $this->getPresetConfig($presetName);
        $webDir = $presetConfig->getWebDir();
        $baseUrl = $presetConfig->getBaseUrl();
        $plugUrl = $presetConfig->getPlugUrl();

        $hasWebPlug = $usePlug && $plugUrl !== null;
        $hasFileUrl = $webDir !== null && !($absolute && $baseUrl === null);

        if (!$hasWebPlug && !$hasFileUrl) {
            return null;
        }

        $path = null;
        if ($hasFileUrl && $fileRelPath !== null) {
            $destPath = $this->_pathResolver->getDestPath($presetName, $fileRelPath);
            if ($this->isWebPath($presetName, $destPath) && $this->checkThumb($presetName, $fileRelPath, $destPath)) {
                $path = $destPath;
            }
        }

        if ($path === null && $usePlug) {
            $presetPlugPath = $presetConfig->getPlugPath(true);
            if ($hasFileUrl && $presetPlugPath !== null) {
                $presetPlugDestPath = $this->_pathResolver->getDestPath($presetName, $presetPlugPath, true);
                if ($this->isWebPath($presetName, $presetPlugDestPath) && $this->checkPlug($presetName, $presetPlugPath, $presetPlugDestPath)) {
                    $path = $presetPlugDestPath;
                }
            }

            if ($path === null) {
                $presetPlugUrl = $presetConfig->getPlugUrl(true);
                if ($presetPlugUrl) {
                    return $presetPlugUrl;
                }

                if ($hasFileUrl && $presetPlugPath === null) {
                    $plugPath = $presetConfig->getPlugPath();
                    if ($plugPath !== null) {
                        $plugDestPath = $this->_pathResolver->getDestPath($presetName, $plugPath, true);
                        if ($this->isWebPath($presetName, $plugDestPath) && $this->checkPlug($presetName, $plugPath, $plugDestPath)) {
                            $path = $plugDestPath;
                        }
                    }
                }

                if ($path === null) {
                    if ($plugUrl) {
                        return $plugUrl;
                    }
                }
            }
        }

        if ($path !== null) {
            $url = str_replace('\\', '/', substr($path, strlen($webDir)));

            return $absolute ? $baseUrl . $url : $url;
        }

        return null;
    }

    /**
     * @param string $fileRelPath
     * @param string|null $presetName
     * @throws ConfigException
     */
    public function clearFileThumbs($fileRelPath, $presetName = null)
    {
        $fileRelPath = ltrim($fileRelPath, "\\/");
        if ($presetName) {
            if ($this->isPreset($presetName)) {
                $this->clearFileThumb($fileRelPath, $presetName);
            }
        } else {
            foreach ($this->getConfig()->getPresetNames() as $presetName) {
                $this->clearFileThumb($fileRelPath, $presetName);
            }
        }
    }

    /**
     * @param string|null $presetName
     * @throws ConfigException
     */
    public function clearPlugsThumbs($presetName = null)
    {
        if ($presetName) {
            $presetConfig = $this->getPresetConfig($presetName);
            ImageHelper::rrmdir($presetConfig->getDestDir() . DIRECTORY_SEPARATOR . 'plugs' . DIRECTORY_SEPARATOR . $presetName);
        }
        else {
            ImageHelper::rrmdir($this->getConfig()->getDestDir() . DIRECTORY_SEPARATOR . 'plugs');
        }
    }

    /**
     * @param string $presetName
     * @throws ConfigException
     */
    public function clearPresetThumbs($presetName)
    {
        $presetConfig = $this->getPresetConfig($presetName);
        ImageHelper::rrmdir($presetConfig->getDestDir() . DIRECTORY_SEPARATOR . 'presets' . DIRECTORY_SEPARATOR . $presetName);
    }

    /**
     * @param string $presetName
     * @return PresetConfig
     * @throws ConfigException
     */
    protected function getPresetConfig($presetName)
    {
        return $this->getConfig()->getPresetConfig($presetName);
    }

    /**
     * @param string $presetName
     * @return bool
     */
    protected function isPreset($presetName)
    {
        return $this->getConfig()->isPreset($presetName);
    }

    /**
     * @param string $fileRelPath
     * @param string $presetName
     * @throws ConfigException
     */
    protected function clearFileThumb($fileRelPath, $presetName)
    {
        $destPath = $this->_pathResolver->getDestPath($presetName, $fileRelPath);
        if (file_exists($destPath)) {
            unlink($destPath);
        }
    }

    /**
     * @param string $presetName
     * @param string $webPath
     * @return bool
     * @throws ConfigException
     */
    protected function isWebPath($presetName, $webPath)
    {
        $presetConfig = $this->getPresetConfig($presetName);
        $webDir = $presetConfig->getWebDir();
        if ($webDir !== null) {
            return false === strpos($webPath, $webDir) ? false : true;
        }

        return false;
    }

    /**
     * @param string $presetName
     * @param string $fileRelPath
     * @param string $destPath
     * @return bool
     * @throws ConfigException
     */
    protected function checkThumb($presetName, $fileRelPath, $destPath)
    {
        if (!is_file($destPath)) {
            $srcDir = $this->getPresetConfig($presetName)->getSrcDir();
            $srcFullPath = $srcDir ? $srcDir . DIRECTORY_SEPARATOR . $fileRelPath : $fileRelPath;
            if (is_file($srcFullPath)) {
                $format = $this->_pathResolver->getDestFormat($presetName, $fileRelPath);
                if (!$this->createThumb($presetName, $srcFullPath, $destPath, $format)) {
                    return false;
                }
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $presetName
     * @param string $plugPath
     * @param string $plugDestPath
     * @return bool
     * @throws ConfigException
     */
    protected function checkPlug($presetName, $plugPath, $plugDestPath)
    {
        if (!is_file($plugDestPath)) {
            $format = $this->_pathResolver->getDestFormat($presetName, $plugPath, true);
            if (!$this->createThumb($presetName, $plugPath, $plugDestPath, $format, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $presetName
     * @param string $srcPath
     * @param string $destPath
     * @param string $format
     * @param bool $isPlug
     * @return bool
     */
    protected function createThumb($presetName, $srcPath, $destPath, $format, $isPlug = false)
    {
        try {
            $presetConfig = $this->getPresetConfig($presetName);
            $class = $presetConfig->getImageClass();
            $driverCode = ImgHelper::getDriverCode($presetConfig->getDriver());

            /** @var ImageInterface $image */
            $image = (new $class($driverCode, false));
            $image->open($srcPath);
            $processPlug = $presetConfig->getProcessPlug();
            if (!$isPlug || $processPlug) {
                $this->applyEffects($presetName, $image);
            }
            $image->save($destPath, [
                'format'                 => $format,
                'jpeg_quality'           => $presetConfig->getJpegQuality(),
                'png_compression_level'  => $presetConfig->getPngCompressionLevel(),
                'png_compression_filter' => $presetConfig->getPngCompressionFilter(),
            ]);
            if (!$isPlug || $processPlug) {
                $this->applyPostProcessors($presetName, $destPath, $format);
            }
        } catch (\Exception $ex) {
            return false;
        }

        return true;
    }

    /**
     * @param string $presetName
     * @param ImageInterface $image
     * @throws ConfigException
     */
    protected function applyEffects($presetName, $image)
    {
        $presetConfig = $this->getPresetConfig($presetName);
        if ($presetConfig->hasEffects()) {
            if (!isset($this->_presetsEffects[$presetName])) {
                $config = $this->getConfig();
                $this->_presetsEffects[$presetName] = [];

                foreach ($presetConfig->getEffectsConfig() as $effectConfig) {
                    $class = $config->getEffectClassName($effectConfig['type']);
                    $params = empty($effectConfig['params']) ? [] : $effectConfig['params'];
                    /** @var EffectInterface $effect */
                    $effect = ImgHelper::createClassArrayAssoc($class, $params);
                    $this->_presetsEffects[$presetName][] = $effect;
                }
            }

            foreach ($this->_presetsEffects[$presetName] as $effect) {
                $image->apply($effect);
            }
        }
    }

    /**
     * @param string $presetName
     * @param string $path
     * @param string $format
     * @throws ConfigException
     */
    protected function applyPostProcessors($presetName, $path, $format)
    {
        $config = $this->getConfig();
        $presetConfig = $this->getPresetConfig($presetName);
        if ($presetConfig->hasPostProcessors()) {
            if (!isset($this->_presetsPostProcessors[$presetName])) {
                $this->_presetsPostProcessors[$presetName] = [];
                foreach ($presetConfig->getPostProcessorsConfig() as $postProcessorConfig) {
                    $class = $config->getPostProcessorClassName($postProcessorConfig['type']);
                    $params = empty($postProcessorConfig['params']) ? [] : $postProcessorConfig['params'];
                    /** @var PostProcessorInterface $postProcessor */
                    $postProcessor = ImgHelper::createClassArrayAssoc($class, $params);
                    $this->_presetsPostProcessors[$presetName][] = $postProcessor;
                }
            }
            foreach ($this->_presetsPostProcessors[$presetName] as $postProcessor) {
                if (in_array($format, $postProcessor->getSupportedFormats())) {
                    $postProcessor->process($path);
                }
            }
        } elseif ($config->hasPostProcessors()) {
            if (empty($this->_postProcessors)) {
                foreach ($config->getPostProcessorsConfig() as $postProcessorConfig) {
                    $class = $config->getPostProcessorClassName($postProcessorConfig['type']);
                    $params = empty($postProcessorConfig['params']) ? [] : $postProcessorConfig['params'];
                    /** @var PostProcessorInterface $postProcessor */
                    $postProcessor = ImgHelper::createClassArrayAssoc($class, $params);
                    $this->_postProcessors[] = $postProcessor;
                }
            }

            foreach ($this->_postProcessors as $postProcessor) {
                if (in_array($format, $postProcessor->getSupportedFormats())) {
                    $postProcessor->process($path);
                }
            }
        }
    }
}