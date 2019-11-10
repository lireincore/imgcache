<?php

namespace LireinCore\ImgCache;

use RuntimeException;
use LireinCore\Image\ImageHelper;
use LireinCore\ImgCache\Exception\ConfigException;

final class PathResolver implements PathResolverInterface
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
    private $data = [];

    /**
     * PathResolver constructor.
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
     * @param string $relSrcPath
     * @param string $presetDefinitionHash
     * @return string
     * @throws ConfigException
     */
    public function srcPath(string $relSrcPath, string $presetDefinitionHash) : string
    {
        $presetConfig = $this->presetConfig($presetDefinitionHash);
        $srcDir = $presetConfig->srcDir();

        if (null !== $srcDir) {
            return $srcDir . DIRECTORY_SEPARATOR . \ltrim($relSrcPath, "\\/");
        }

        return $relSrcPath;
    }

    /**
     * @param string $srcPath
     * @param string $presetDefinitionHash
     * @param bool $isStub
     * @return string
     * @throws ConfigException
     * @throws RuntimeException
     */
    public function destPath(string $srcPath, string $presetDefinitionHash, bool $isStub = false) : string
    {
        $data = $this->data($srcPath, $presetDefinitionHash, $isStub);
        $presetConfig = $this->presetConfig($presetDefinitionHash);

        return $presetConfig->destDir() . DIRECTORY_SEPARATOR . $data['relpath'];
    }

    /**
     * @param string $srcPath
     * @param string $presetDefinitionHash
     * @param bool $isStub
     * @return string
     * @throws ConfigException
     * @throws RuntimeException
     */
    public function destExtension(string $srcPath, string $presetDefinitionHash, bool $isStub = false) : string
    {
        $data = $this->data($srcPath, $presetDefinitionHash, $isStub);

        return $data['ext'];
    }

    /**
     * @param string $presetDefinitionHash
     * @param bool $isStub
     * @return string
     * @throws ConfigException
     */
    public function presetDir(string $presetDefinitionHash, bool $isStub = false) : string
    {
        $presetConfig = $this->presetConfig($presetDefinitionHash);

        return \implode(DIRECTORY_SEPARATOR, [$presetConfig->destDir(), $isStub ? 'stubs' : 'presets', $presetDefinitionHash]);
    }

    /**
     * @param string $srcPath
     * @param string $presetDefinitionHash
     * @param bool $isStub
     * @return array
     * @throws ConfigException
     * @throws RuntimeException
     */
    private function data(string $srcPath, string $presetDefinitionHash, bool $isStub = false) : array
    {
        if (!$isStub) {
            $srcPath = \ltrim($srcPath, "\\/");
        }
        if ($isStub && isset($this->data['stubs'][$presetDefinitionHash][$srcPath])) {
            return $this->data['stubs'][$presetDefinitionHash][$srcPath];
        }
        if (isset($this->data['presets'][$presetDefinitionHash][$srcPath])) {
            return $this->data['presets'][$presetDefinitionHash][$srcPath];
        }

        $srcInfo = \pathinfo($srcPath);
        $srcExt = $srcInfo['extension'];
        $srcFormat = ImageHelper::formatByExt($srcExt);
        if (null === $srcFormat) {
            throw new RuntimeException("Source image extension '{$srcExt}' not supported");
        }
        $format = $this->convertedFormat($srcFormat, $presetDefinitionHash);
        $ext = ImageHelper::extensionByFormat($format);
        if (null === $ext) {
            throw new RuntimeException("Destination image format '{$format}' not supported");
        }
        $relPath = "{$srcInfo['filename']}.{$ext}";
        $presetConfig = $this->presetConfig($presetDefinitionHash);
        $presetHash = $presetConfig->hash();

        if ($isStub) {
            $relPath = \implode(DIRECTORY_SEPARATOR, ['stubs', $presetHash, $relPath]);
        } else {
            if ($srcInfo['dirname'] !== '.') {
                $relPath = $srcInfo['dirname'] . DIRECTORY_SEPARATOR . $relPath;
            }
            $relPath = \implode(DIRECTORY_SEPARATOR, ['presets', $presetHash, $relPath]);
        }

        $data = [
            'ext' => $ext,
            'relpath' => $relPath
        ];

        if ($isStub) {
            $this->data['stubs'][$presetDefinitionHash][$srcPath] = $data;
        } else {
            $this->data['presets'][$presetDefinitionHash][$srcPath] = $data;
        }

        return $data;
    }

    /**
     * @param string $srcFormat
     * @param string $presetDefinitionHash
     * @return string
     * @throws ConfigException
     */
    private function convertedFormat(string $srcFormat, string $presetDefinitionHash) : string
    {
        $presetConfig = $this->presetConfig($presetDefinitionHash);
        $convertMap = $presetConfig->convertMap();

        if (\array_key_exists($srcFormat, $convertMap)) {
            return $convertMap[$srcFormat];
        }
        if (\array_key_exists('*', $convertMap)) {
            return $convertMap['*'];
        }

        return $srcFormat;
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