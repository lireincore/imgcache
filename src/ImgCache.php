<?php

namespace LireinCore\ImgCache;

use Psr\Log\LoggerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use LireinCore\Image\ImageHelper;
use LireinCore\ImgCache\Exception\ConfigException;

final class ImgCache
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
     * @var PathResolver
     */
    private $pathResolver;

    /**
     * @var ImgProcessor
     */
    private $imgProcessor;

    /**
     * @var null|LoggerInterface
     */
    private $logger;

    /**
     * ImgCache constructor.
     *
     * @param array $config
     * @param null|LoggerInterface $logger
     * @param null|EventDispatcherInterface $eventDispatcher
     * @throws ConfigException
     */
    public function __construct(
        array $config,
        ?LoggerInterface $logger = null,
        ?EventDispatcherInterface $eventDispatcher = null
    )
    {
        $this->config = new Config($config);
        $this->logger = $logger;
        $namedPresetDefinitions = isset($config['presets']) && \is_array($config['presets']) ? $config['presets'] : [];
        $this->presetConfigRegistry = new PresetConfigRegistry($this->config(), $namedPresetDefinitions);
        $this->pathResolver = new PathResolver($this->config(), $this->presetConfigRegistry());
        $this->imgProcessor = new ImgProcessor(
            $this->config(),
            $this->presetConfigRegistry(),
            $logger,
            $eventDispatcher
        );
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
    public function url(string $srcPath, $preset, bool $absolute = false, bool $useStub = true) : string
    {
        $presetDefinitionHash = $this->presetDefinitionHash($preset);
        $this->checkUrlAvailability($presetDefinitionHash, $absolute);

        $destPath = $this->pathResolver()->destPath($srcPath, $presetDefinitionHash);
        if ($this->isWebPath($destPath, $presetDefinitionHash)) {
            if ($this->checkThumb($srcPath, $presetDefinitionHash)) {
                return $this->urlFromPath($destPath, $presetDefinitionHash, $absolute);
            }
            if ($useStub) {
                return $this->stubUrlByHash($presetDefinitionHash, $absolute);
            }
            $resolvedSrcPath = $this->resolveSrcPath($srcPath, $presetDefinitionHash);
            if ($this->logger) {
                $this->logger->warning("Source image '{$resolvedSrcPath}' not found and stub path or url is not configured");
            }
            throw new \RuntimeException("Source image '{$resolvedSrcPath}' not found and stub path or url is not configured");
        }
        throw new \RuntimeException("'{$destPath}' is not web accessible image");
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
    public function path(string $srcPath, $preset, bool $useStub = true) : string
    {
        $presetDefinitionHash = $this->presetDefinitionHash($preset);
        if ($this->checkThumb($srcPath, $presetDefinitionHash)) {
            return $this->pathResolver()->destPath($srcPath, $presetDefinitionHash);
        }
        if ($useStub) {
            return $this->stubPathByHash($presetDefinitionHash);
        }
        $resolvedSrcPath = $this->resolveSrcPath($srcPath, $presetDefinitionHash);
        if ($this->logger) {
            $this->logger->warning("Source image '{$resolvedSrcPath}' not found and stub path or url is not configured");
        }
        throw new \RuntimeException("Source image '{$resolvedSrcPath}' not found and stub path or url is not configured");
    }

    /**
     * @param string|array $preset preset name or dynamic preset definition
     * @param bool $absolute
     * @return string
     * @throws ConfigException
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function stubUrl($preset, bool $absolute = false) : string
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
    public function stubPath($preset) : string
    {
        $presetDefinitionHash = $this->presetDefinitionHash($preset);

        return $this->stubPathByHash($presetDefinitionHash);
    }

    /**
     * @param string|array|null $preset preset name or dynamic preset definition
     * @throws ConfigException
     */
    public function clearCache($preset = null) : void
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
    private function checkUrlAvailability(string $presetDefinitionHash, bool $absolute = false) : void
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
    private function stubUrlByHash(string $presetDefinitionHash, bool $absolute = false) : string
    {
        $presetConfig = $this->presetConfig($presetDefinitionHash);
        $presetPlugPath = $presetConfig->plugPath(true);
        if ($presetPlugPath !== null) {
            $presetPlugDestPath = $this->pathResolver()->destPath($presetPlugPath, $presetDefinitionHash, true);
            if ($this->isWebPath($presetPlugDestPath, $presetDefinitionHash)) {
                $this->checkPlug($presetPlugPath, $presetDefinitionHash);
                return $this->urlFromPath($presetPlugDestPath, $presetDefinitionHash, $absolute);
            }
            throw new \RuntimeException("'{$presetPlugDestPath}' is not web accessible image");
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
            }
            throw new \RuntimeException("'{$plugDestPath}' is not web accessible image");
        }

        $plugUrl = $presetConfig->plugUrl();
        if ($plugUrl) {
            return $plugUrl;
        }
        throw new ConfigException('Stub path or url is not configured');
    }

    /**
     * @param string $presetDefinitionHash
     * @return string
     * @throws ConfigException
     * @throws \RuntimeException
     */
    private function stubPathByHash(string $presetDefinitionHash) : string
    {
        $presetConfig = $this->presetConfig($presetDefinitionHash);
        $plugPath = $presetConfig->plugPath();
        if ($plugPath !== null) {
            $this->checkPlug($plugPath, $presetDefinitionHash);
            return $this->pathResolver()->destPath($plugPath, $presetDefinitionHash, true);
        }
        throw new \RuntimeException('Stub path or url is not configured');
    }

    /**
     * @param string $path
     * @param string $presetDefinitionHash
     * @param bool $absolute
     * @return string
     * @throws ConfigException
     */
    private function urlFromPath(string $path, string $presetDefinitionHash, bool $absolute = false) : string
    {
        $presetConfig = $this->presetConfig($presetDefinitionHash);
        $webDir = $presetConfig->webDir();
        $baseUrl = $presetConfig->baseUrl();
        $url = \str_replace('\\', '/', \substr($path, \strlen($webDir)));

        return $absolute ? $baseUrl . $url : $url;
    }

    /**
     * @param string|array $preset preset name or dynamic preset definition
     * @return string
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    private function presetDefinitionHash($preset) : string
    {
        if (\is_string($preset)) {
            $presetDefinitionHash = $this->presetConfigRegistry()->hashByName($preset);
            if (null === $presetDefinitionHash) {
                throw new \RuntimeException("Preset '{$preset}' not found");
            }
        } elseif (\is_array($preset)) {
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
    private function isWebPath(string $webPath, string $presetDefinitionHash) : bool
    {
        $presetConfig = $this->presetConfig($presetDefinitionHash);
        $webDir = $presetConfig->webDir();
        if ($webDir !== null) {
            return false !== \strpos($webPath, $webDir) ?: false;
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
    private function checkThumb(string $srcPath, string $presetDefinitionHash) : bool
    {
        $destPath = $this->pathResolver()->destPath($srcPath, $presetDefinitionHash);
        if (!\is_file($destPath)) {
            $resolvedSrcPath = $this->resolveSrcPath($srcPath, $presetDefinitionHash);
            if (\is_file($resolvedSrcPath)) {
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
    private function checkPlug(string $plugPath, string $presetDefinitionHash) : void
    {
        $plugDestPath = $this->pathResolver()->destPath($plugPath, $presetDefinitionHash, true);
        if (!\is_file($plugDestPath)) {
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
    private function resolveSrcPath(string $srcPath, string $presetDefinitionHash) : string
    {
        $presetConfig = $this->presetConfig($presetDefinitionHash);
        $srcDir = $presetConfig->srcDir();

        if (null !== $srcDir) {
            $srcPath = \ltrim($srcPath, "\\/");
            $resolvedSrcPath = $srcDir . DIRECTORY_SEPARATOR . $srcPath;
        } else {
            $resolvedSrcPath = $srcPath;
        }

        return $resolvedSrcPath;
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
     * @return PathResolver
     */
    private function pathResolver() : PathResolver
    {
        return $this->pathResolver;
    }

    /**
     * @return ImgProcessor
     */
    private function imgProcessor() : ImgProcessor
    {
        return $this->imgProcessor;
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