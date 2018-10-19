<?php

namespace LireinCore\ImgCache;

use LireinCore\Image\ImageHelper;
use LireinCore\ImgCache\Exception\ConfigException;

class ImgCache
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
     * @var PathResolver
     */
    protected $pathResolver;

    /**
     * @var ImgProcessor
     */
    protected $imgProcessor;

    /**
     * ImgCache constructor.
     *
     * @param array $config
     * @throws ConfigException
     */
    public function __construct(array $config)
    {
        $this->config = new Config($config);

        $namedPresetDefinitions = isset($config['presets']) && is_array($config['presets']) ? $config['presets'] : [];

        $this->presetConfigRegistry = new PresetConfigRegistry($this->config(), $namedPresetDefinitions);
        $this->pathResolver = new PathResolver($this->config(), $this->presetConfigRegistry());
        $this->imgProcessor = new ImgProcessor($this->config(), $this->presetConfigRegistry());
    }

    /**
     * @param string $srcPath absolute or relative path to source image
     * @param string|array $preset preset name or dynamic preset definition
     * @param bool $absolute
     * @param bool $useStub
     * @return string
     * @throws ConfigException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function url($srcPath, $preset, $absolute = false, $useStub = true)
    {
        if (!is_string($srcPath)) {
            throw new \InvalidArgumentException("Аrgument 'srcPath' must be a string");
        }

        $presetDefinitionHash = $this->presetDefinitionHash($preset);
        $this->checkUrlAvailability($presetDefinitionHash, $absolute);

        $destPath = $this->pathResolver()->destPath($srcPath, $presetDefinitionHash);
        if ($this->isWebPath($destPath, $presetDefinitionHash)) {
            if ($this->checkThumb($srcPath, $presetDefinitionHash)) {
                return $this->urlFromPath($destPath, $presetDefinitionHash, $absolute);
            } else {
                if ($useStub) {
                    return $this->stubUrlByHash($presetDefinitionHash, $absolute);
                } else {
                    $resolvedSrcPath = $this->resolveSrcPath($srcPath, $presetDefinitionHash);
                    throw new \RuntimeException("Source image '{$resolvedSrcPath}' not found");
                }
            }
        } else {
            throw new \RuntimeException("'{$destPath}' is not web accessible image");
        }
    }

    /**
     * @param string $srcPath absolute or relative path to source image
     * @param string|array $preset preset name or dynamic preset definition
     * @param bool $useStub
     * @return string
     * @throws ConfigException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function path($srcPath, $preset, $useStub = true)
    {
        if (!is_string($srcPath)) {
            throw new \InvalidArgumentException("Аrgument 'srcPath' must be a string");
        }

        $presetDefinitionHash = $this->presetDefinitionHash($preset);
        if ($this->checkThumb($srcPath, $presetDefinitionHash)) {
            return $this->pathResolver()->destPath($srcPath, $presetDefinitionHash);
        } else {
            if ($useStub) {
                return $this->stubPathByHash($presetDefinitionHash);
            } else {
                $resolvedSrcPath = $this->resolveSrcPath($srcPath, $presetDefinitionHash);
                throw new \RuntimeException("Source image '{$resolvedSrcPath}' not found");
            }
        }
    }

    /**
     * @param string|array $preset preset name or dynamic preset definition
     * @param bool $absolute
     * @return string
     * @throws ConfigException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function stubUrl($preset, $absolute = false)
    {
        $presetDefinitionHash = $this->presetDefinitionHash($preset);
        $this->checkUrlAvailability($presetDefinitionHash, $absolute);

        return $this->stubUrlByHash($presetDefinitionHash, $absolute);
    }

    /**
     * @param string|array $preset preset name or dynamic preset definition
     * @return string
     * @throws ConfigException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function stubPath($preset)
    {
        $presetDefinitionHash = $this->presetDefinitionHash($preset);

        return $this->stubPathByHash($presetDefinitionHash);
    }

    /**
     * @param string|array|null $preset preset name or dynamic preset definition
     * @throws ConfigException
     */
    public function clearCache($preset = null)
    {
        if ($preset !== null) {
            $presetDefinitionHash = $this->presetDefinitionHash($preset);
            ImageHelper::rrmdir($this->pathResolver()->presetDir($presetDefinitionHash));
        } else {
            $presetDefinitionHashList = $this->presetConfigRegistry()->presetDefinitionHashList();
            foreach ($presetDefinitionHashList as $presetDefinitionHash) {
                ImageHelper::rrmdir($this->pathResolver()->presetDir($presetDefinitionHash));
            }
        }
    }

    /**
     * @param string $presetDefinitionHash
     * @param bool $absolute
     * @throws ConfigException
     */
    protected function checkUrlAvailability($presetDefinitionHash, $absolute = false)
    {
        $presetConfig = $this->presetConfig($presetDefinitionHash);
        $webDir = $presetConfig->webDir();
        $baseUrl = $presetConfig->baseUrl();

        if ($webDir === null) {
            throw new ConfigException("'webDir' is not configured");
        }

        if ($absolute && $baseUrl === null) {
            throw new ConfigException("'baseUrl' is not configured for absolute url");
        }
    }

    /**
     * @param string $presetDefinitionHash
     * @param bool $absolute
     * @return string
     * @throws ConfigException
     * @throws \RuntimeException
     */
    protected function stubUrlByHash($presetDefinitionHash, $absolute = false)
    {
        $presetConfig = $this->presetConfig($presetDefinitionHash);
        $presetPlugPath = $presetConfig->plugPath(true);
        if ($presetPlugPath !== null) {
            $presetPlugDestPath = $this->pathResolver()->destPath($presetPlugPath, $presetDefinitionHash, true);
            if ($this->isWebPath($presetPlugDestPath, $presetDefinitionHash)) {
                $this->checkPlug($presetPlugPath, $presetDefinitionHash);
                return $this->urlFromPath($presetPlugDestPath, $presetDefinitionHash, $absolute);
            } else {
                throw new \RuntimeException("'{$presetPlugDestPath}' is not web accessible image");
            }
        }

        $presetPlugUrl = $presetConfig->plugUrl(true);
        if ($presetPlugUrl) {
            return $presetPlugUrl;
        }

        $plugPath = $presetConfig->plugPath();
        if ($plugPath !== null) {
            $plugDestPath = $this->pathResolver()->destPath($plugPath, $presetDefinitionHash, true);
            if ($this->isWebPath($plugDestPath, $presetDefinitionHash)) {
                $this->checkPlug($plugPath, $presetDefinitionHash);
                return $this->urlFromPath($plugDestPath, $presetDefinitionHash, $absolute);
            } else {
                throw new \RuntimeException("'{$plugDestPath}' is not web accessible image");
            }
        }

        $plugUrl = $presetConfig->plugUrl();
        if ($plugUrl) {
            return $plugUrl;
        } else {
            throw new ConfigException("Path or url to image stub is not configured");
        }
    }

    /**
     * @param string $presetDefinitionHash
     * @return string
     * @throws ConfigException
     * @throws \RuntimeException
     */
    protected function stubPathByHash($presetDefinitionHash)
    {
        $presetConfig = $this->presetConfig($presetDefinitionHash);
        $plugPath = $presetConfig->plugPath();
        if ($plugPath !== null) {
            $this->checkPlug($plugPath, $presetDefinitionHash);
            return $this->pathResolver()->destPath($plugPath, $presetDefinitionHash, true);
        } else {
            throw new \RuntimeException("Path to image stub is not configured");
        }
    }

    /**
     * @param string $path
     * @param string $presetDefinitionHash
     * @param bool $absolute
     * @return string
     * @throws ConfigException
     */
    protected function urlFromPath($path, $presetDefinitionHash, $absolute = false)
    {
        $presetConfig = $this->presetConfig($presetDefinitionHash);
        $webDir = $presetConfig->webDir();
        $baseUrl = $presetConfig->baseUrl();

        $url = str_replace('\\', '/', substr($path, strlen($webDir)));

        return $absolute ? $baseUrl . $url : $url;
    }

    /**
     * @param string|array $preset preset name or dynamic preset definition
     * @return string
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    protected function presetDefinitionHash($preset)
    {
        if (is_string($preset)) {
            $presetDefinitionHash = $this->presetConfigRegistry()->hashByName($preset);
            if (null === $presetDefinitionHash) {
                throw new \RuntimeException("Preset '{$preset}' not found");
            }
        } elseif (is_array($preset)) {
            $presetDefinitionHash = $this->presetConfigRegistry()->addPresetDefinition($preset);
        } else {
            throw new \InvalidArgumentException("Аrgument 'preset' must be a string or an array");
        }

        return $presetDefinitionHash;
    }

    /**
     * @param string $webPath
     * @param string $presetDefinitionHash
     * @return bool
     * @throws ConfigException
     */
    protected function isWebPath($webPath, $presetDefinitionHash)
    {
        $presetConfig = $this->presetConfig($presetDefinitionHash);
        $webDir = $presetConfig->webDir();
        if ($webDir !== null) {
            return false === strpos($webPath, $webDir) ? false : true;
        }

        return false;
    }

    /**
     * @param string $srcPath
     * @param string $presetDefinitionHash
     * @return bool
     * @throws ConfigException
     * @throws \RuntimeException
     */
    protected function checkThumb($srcPath, $presetDefinitionHash)
    {
        $destPath = $this->pathResolver()->destPath($srcPath, $presetDefinitionHash);
        if (!is_file($destPath)) {
            $resolvedSrcPath = $this->resolveSrcPath($srcPath, $presetDefinitionHash);
            if (is_file($resolvedSrcPath)) {
                $format = $this->pathResolver()->destFormat($srcPath, $presetDefinitionHash);
                $this->imgProcessor()->createThumb($resolvedSrcPath, $destPath, $format, $presetDefinitionHash);
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $plugPath
     * @param string $presetDefinitionHash
     * @throws ConfigException
     * @throws \RuntimeException
     */
    protected function checkPlug($plugPath, $presetDefinitionHash)
    {
        $plugDestPath = $this->pathResolver()->destPath($plugPath, $presetDefinitionHash, true);
        if (!is_file($plugDestPath)) {
            $format = $this->pathResolver()->destFormat($plugPath, $presetDefinitionHash, true);
            $this->imgProcessor()->createThumb($plugPath, $plugDestPath, $format, $presetDefinitionHash, true);
        }
    }

    /**
     * @param string $srcPath
     * @param string $presetDefinitionHash
     * @return string
     * @throws ConfigException
     */
    protected function resolveSrcPath($srcPath, $presetDefinitionHash)
    {
        $presetConfig = $this->presetConfig($presetDefinitionHash);
        $srcDir = $presetConfig->srcDir();

        if (null !== $srcDir) {
            $srcPath = ltrim($srcPath, "\\/");
            $resolvedSrcPath = $srcDir . DIRECTORY_SEPARATOR . $srcPath;
        } else {
            $resolvedSrcPath = $srcPath;
        }

        return $resolvedSrcPath;
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
     * @return PathResolver
     */
    protected function pathResolver()
    {
        return $this->pathResolver;
    }

    /**
     * @return ImgProcessor
     */
    protected function imgProcessor()
    {
        return $this->imgProcessor;
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