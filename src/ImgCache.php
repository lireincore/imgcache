<?php

namespace LireinCore\ImgCache;

use RuntimeException;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use LireinCore\Image\ImageHelper;
use LireinCore\ImgCache\Event\ThumbCreatedEvent;
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
     * @var PathResolverInterface
     */
    private $pathResolver;

    /**
     * @var ImgProcessorInterface
     */
    private $imgProcessor;

    /**
     * @var null|LoggerInterface
     */
    private $logger;

    /**
     * @var null|EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * ImgCache constructor.
     *
     * @param array $config
     * @param LoggerInterface|null $logger
     * @param EventDispatcherInterface|null $eventDispatcher
     * @param PathResolverFactoryInterface|null $pathResolverFactory
     * @param ImgProcessorFactoryInterface|null $imgProcessorFactory
     * @throws ConfigException
     */
    public function __construct(
        array $config,
        ?LoggerInterface $logger = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        ?PathResolverFactoryInterface $pathResolverFactory = null,
        ?ImgProcessorFactoryInterface $imgProcessorFactory = null
    )
    {
        $this->config = new Config($config);
        $namedPresetDefinitions = isset($config['presets']) && \is_array($config['presets']) ? $config['presets'] : [];
        $this->presetConfigRegistry = new PresetConfigRegistry($this->config(), $namedPresetDefinitions);
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
        if ($pathResolverFactory) {
            $this->pathResolver = $pathResolverFactory->createPathResolver(
                $this->config(),
                $this->presetConfigRegistry(),
                $logger,
                $eventDispatcher
            );
        } else {
            $this->pathResolver = new PathResolver($this->config(), $this->presetConfigRegistry());
        }
        if ($imgProcessorFactory) {
            $this->imgProcessor = $imgProcessorFactory->createImgProcessor(
                $this->config(),
                $this->presetConfigRegistry(),
                $logger,
                $eventDispatcher
            );
        } else {
            $this->imgProcessor = new ImgProcessor($this->config(), $this->presetConfigRegistry());
        }
    }

    /**
     * @param string $srcPath absolute or relative path to source image
     * @param string|array $preset preset name or dynamic preset definition
     * @param bool $absolute return absolute url
     * @param bool $useStub use a stub when the image is unavailable
     * @param bool $createThumbIfNotExists check for a thumbnail and create one if it doesn't exist
     * @return string
     * @throws ConfigException
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function url(string $srcPath, $preset, bool $absolute = false, bool $useStub = true, bool $createThumbIfNotExists = true) : string
    {
        $presetDefinitionHash = $this->presetDefinitionHash($preset);
        $this->checkUrlAvailability($presetDefinitionHash, $absolute);

        try {
            if ($createThumbIfNotExists) {
                $this->createThumbIfNotExists($srcPath, $presetDefinitionHash, false);
            }
            $destPath = $this->pathResolver()->destPath($srcPath, $presetDefinitionHash, false);

            return $this->urlFromPath($destPath, $presetDefinitionHash, $absolute);
        } catch (RuntimeException $ex) {
            $resolvedSrcPath = $this->pathResolver()->srcPath($srcPath, $presetDefinitionHash);
            if (!$useStub) {
                throw new RuntimeException("Error create thumb for image '{$resolvedSrcPath}'", 0, $ex);
            }
        }

        if ($this->logger) {
            $this->logger->error("Error create thumb for image '{$resolvedSrcPath}'", [
                'exception' => $ex,
                'preset' => $preset
            ]);
        }

        return $this->stubUrlByHash($presetDefinitionHash, $absolute, $createThumbIfNotExists);
    }

    /**
     * @param string $srcPath absolute or relative path to source image
     * @param string|array $preset preset name or dynamic preset definition
     * @param bool $useStub use a stub when the image is unavailable
     * @param bool $createThumbIfNotExists check for a thumbnail and create one if it doesn't exist
     * @return string
     * @throws ConfigException
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function path(string $srcPath, $preset, bool $useStub = true, bool $createThumbIfNotExists = true) : string
    {
        $presetDefinitionHash = $this->presetDefinitionHash($preset);
        try {
            if ($createThumbIfNotExists) {
                $this->createThumbIfNotExists($srcPath, $presetDefinitionHash, false);
            }

            return $this->pathResolver()->destPath($srcPath, $presetDefinitionHash, false);
        } catch (RuntimeException $ex) {
            $resolvedSrcPath = $this->pathResolver()->srcPath($srcPath, $presetDefinitionHash);
            if (!$useStub) {
                throw new RuntimeException("Error create thumb for image '{$resolvedSrcPath}'", 0, $ex);
            }
        }

        if ($this->logger) {
            $this->logger->error("Error create thumb for image '{$resolvedSrcPath}'", [
                'exception' => $ex,
                'preset' => $preset
            ]);
        }

        return $this->stubPathByHash($presetDefinitionHash, $createThumbIfNotExists);
    }

    /**
     * @param string|array $preset preset name or dynamic preset definition
     * @param bool $absolute return absolute url
     * @param bool $createThumbIfNotExists check for a thumbnail and create one if it doesn't exist
     * @return string
     * @throws ConfigException
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function stubUrl($preset, bool $absolute = false, bool $createThumbIfNotExists = true) : string
    {
        $presetDefinitionHash = $this->presetDefinitionHash($preset);
        $this->checkUrlAvailability($presetDefinitionHash, $absolute);

        return $this->stubUrlByHash($presetDefinitionHash, $absolute, $createThumbIfNotExists);
    }

    /**
     * @param string|array $preset preset name or dynamic preset definition
     * @param bool $createThumbIfNotExists check for a thumbnail and create one if it doesn't exist
     * @return string
     * @throws ConfigException
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function stubPath($preset, bool $createThumbIfNotExists = true) : string
    {
        $presetDefinitionHash = $this->presetDefinitionHash($preset);

        return $this->stubPathByHash($presetDefinitionHash, $createThumbIfNotExists);
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
            ImageHelper::rrmdir($this->pathResolver()->presetDir($presetDefinitionHash, true));
        } else {
            $presetDefinitionHashList = $this->presetConfigRegistry()->presetDefinitionHashList();
            foreach ($presetDefinitionHashList as $presetDefinitionHash) {
                ImageHelper::rrmdir($this->pathResolver()->presetDir($presetDefinitionHash));
                ImageHelper::rrmdir($this->pathResolver()->presetDir($presetDefinitionHash, true));
            }
        }
    }

    /**
     * @param string $presetDefinitionHash
     * @param bool $createThumbIfNotExists
     * @return string
     * @throws ConfigException
     * @throws RuntimeException
     */
    private function stubPathByHash(string $presetDefinitionHash, bool $createThumbIfNotExists = true) : string
    {
        $presetConfig = $this->presetConfig($presetDefinitionHash);
        $stubSrcPath = $presetConfig->stubPath();
        if ($stubSrcPath !== null) {
            if ($createThumbIfNotExists) {
                $this->createThumbIfNotExists($stubSrcPath, $presetDefinitionHash, true);
            }

            return $this->pathResolver()->destPath($stubSrcPath, $presetDefinitionHash, true);
        }
        throw new RuntimeException('Stub path is not configured');
    }

    /**
     * @param string $presetDefinitionHash
     * @param bool $absolute
     * @param bool $createThumbIfNotExists
     * @return string
     * @throws ConfigException
     * @throws RuntimeException
     */
    private function stubUrlByHash(string $presetDefinitionHash, bool $absolute = false, bool $createThumbIfNotExists = true) : string
    {
        $presetConfig = $this->presetConfig($presetDefinitionHash);
        $presetStubSrcPath = $presetConfig->stubPath(true);
        if ($presetStubSrcPath !== null) {
            if ($createThumbIfNotExists) {
                $this->createThumbIfNotExists($presetStubSrcPath, $presetDefinitionHash, true);
            }
            $presetStubDestPath = $this->pathResolver()->destPath($presetStubSrcPath, $presetDefinitionHash, true);

            return $this->urlFromPath($presetStubDestPath, $presetDefinitionHash, $absolute);
        }

        $presetStubUrl = $presetConfig->stubUrl(true);
        if ($presetStubUrl !== null) {
            return $presetStubUrl;
        }

        $stubSrcPath = $presetConfig->stubPath();
        if ($stubSrcPath !== null) {
            if ($createThumbIfNotExists) {
                $this->createThumbIfNotExists($stubSrcPath, $presetDefinitionHash, true);
            }
            $stubDestPath = $this->pathResolver()->destPath($stubSrcPath, $presetDefinitionHash, true);

            return $this->urlFromPath($stubDestPath, $presetDefinitionHash, $absolute);
        }

        $stubUrl = $presetConfig->stubUrl();
        if ($stubUrl !== null) {
            return $stubUrl;
        }
        throw new RuntimeException('Stub path or url is not configured');
    }

    /**
     * @param string $srcPath
     * @param string $presetDefinitionHash
     * @param bool $isStub
     * @throws ConfigException
     * @throws RuntimeException
     */
    private function createThumbIfNotExists(string $srcPath, string $presetDefinitionHash, bool $isStub = false) : void
    {
        $destPath = $this->pathResolver()->destPath($srcPath, $presetDefinitionHash, $isStub);
        if (!\is_file($destPath)) {
            $resolvedSrcPath = $isStub ? $srcPath : $this->pathResolver()->srcPath($srcPath, $presetDefinitionHash);
            $destExt = $this->pathResolver()->destExtension($srcPath, $presetDefinitionHash, $isStub);
            $destFormat = ImageHelper::formatByExt($destExt);
            if (null === $destFormat) {
                throw new RuntimeException("Destination image extension '{$destExt}' not supported");
            }
            $this->imgProcessor()->createThumb($resolvedSrcPath, $destPath, $destFormat, $presetDefinitionHash, $isStub);
            if ($this->eventDispatcher) {
                $event = new ThumbCreatedEvent($resolvedSrcPath, $destPath, $destFormat, $isStub);
                $this->eventDispatcher->dispatch($event);
            }
        }
    }

    /**
     * @param string|array $preset preset name or dynamic preset definition
     * @return string
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    private function presetDefinitionHash($preset) : string
    {
        if (\is_string($preset)) {
            $presetDefinitionHash = $this->presetConfigRegistry()->hashByName($preset);
            if (null === $presetDefinitionHash) {
                throw new RuntimeException("Preset '{$preset}' not found");
            }
        } elseif (\is_array($preset)) {
            $presetDefinitionHash = $this->presetConfigRegistry()->addPresetDefinition($preset);
        } else {
            throw new InvalidArgumentException("Аrgument 'preset' must be a string or an array");
        }

        return $presetDefinitionHash;
    }

    /**
     * @param string $presetDefinitionHash
     * @param bool $absolute
     * @throws RuntimeException
     * @throws ConfigException
     */
    private function checkUrlAvailability(string $presetDefinitionHash, bool $absolute = false) : void
    {
        $presetConfig = $this->presetConfig($presetDefinitionHash);
        $webDir = $presetConfig->webDir();
        $baseUrl = $presetConfig->baseUrl();
        $destDir = $presetConfig->destDir();

        if ($webDir === null) {
            throw new RuntimeException("'webDir' is not configured");
        }

        if ($absolute && $baseUrl === null) {
            throw new RuntimeException("'baseUrl' is not configured for absolute url");
        }

        if (false === \strpos($destDir, $webDir)) {
            throw new RuntimeException("'{$destDir}' is not web accessible directory");
        }
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
     * @return PathResolverInterface
     */
    private function pathResolver() : PathResolverInterface
    {
        return $this->pathResolver;
    }

    /**
     * @return ImgProcessorInterface
     */
    private function imgProcessor() : ImgProcessorInterface
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