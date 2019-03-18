<?php

namespace LireinCore\ImgCache;

use LireinCore\Image\ImageHelper;
use LireinCore\ImgCache\Exception\ConfigException;

final class PathResolver
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
     * @param string $srcPath
     * @param string $presetDefinitionHash
     * @param bool $isPlug
     * @return string
     * @throws ConfigException
     */
    public function destPath(string $srcPath, string $presetDefinitionHash, bool $isPlug = false) : string
    {
        $data = $this->data($srcPath, $presetDefinitionHash, $isPlug);
        $presetConfig = $this->presetConfig($presetDefinitionHash);

        return $presetConfig->destDir() . DIRECTORY_SEPARATOR . $data['subpath'];
    }

    /**
     * @param string $srcPath
     * @param string $presetDefinitionHash
     * @param bool $isPlug
     * @return string
     * @throws ConfigException
     */
    public function destFormat(string $srcPath, string $presetDefinitionHash, bool $isPlug = false) : string
    {
        $data = $this->data($srcPath, $presetDefinitionHash, $isPlug);

        return $data['format'];
    }

    /**
     * @param string $presetDefinitionHash
     * @return string
     * @throws ConfigException
     */
    public function presetDir(string $presetDefinitionHash) : string
    {
        $presetConfig = $this->presetConfig($presetDefinitionHash);

        return \implode(DIRECTORY_SEPARATOR, [$presetConfig->destDir(), 'presets', $presetDefinitionHash]);
    }

    /**
     * @param string $presetDefinitionHash
     * @return string
     * @throws ConfigException
     */
    public function stubDir(string $presetDefinitionHash) : string
    {
        $presetConfig = $this->presetConfig($presetDefinitionHash);

        return \implode(DIRECTORY_SEPARATOR, [$presetConfig->destDir(), 'stubs', $presetDefinitionHash]);
    }

    /**
     * @param string $srcPath
     * @param string $presetDefinitionHash
     * @param bool $isPlug
     * @return array
     * @throws ConfigException
     */
    private function data(string $srcPath, string $presetDefinitionHash, bool $isPlug = false) : array
    {
        if (!$isPlug) {
            $srcPath = \ltrim($srcPath, "\\/");
        }
        if ($isPlug && isset($this->data['stubs'][$presetDefinitionHash][$srcPath])) {
            return $this->data['stubs'][$presetDefinitionHash][$srcPath];
        }
        if (isset($this->data['presets'][$presetDefinitionHash][$srcPath])) {
            return $this->data['presets'][$presetDefinitionHash][$srcPath];
        }

        $srcInfo = \pathinfo($srcPath);
        $srcExt = $srcInfo['extension'];
        $srcFormat = ImageHelper::formatByExt($srcExt);

        $format = $this->convertedFormat($srcFormat, $presetDefinitionHash);
        $ext = ImageHelper::extensionByFormat($format);
        $subPath = "{$srcInfo['filename']}.{$ext}";
        $presetConfig = $this->presetConfig($presetDefinitionHash);
        $presetHash = $presetConfig->hash();

        if ($isPlug) {
            $subPath = \implode(DIRECTORY_SEPARATOR, ['stubs', $presetHash, $subPath]);
        } else {
            if ($srcInfo['dirname'] !== '.') {
                $subPath = $srcInfo['dirname'] . DIRECTORY_SEPARATOR . $subPath;
            }
            $subPath = \implode(DIRECTORY_SEPARATOR, ['presets', $presetHash, $subPath]);
        }

        $data = [
            'format' => $format,
            'ext' => $ext,
            'subpath' => $subPath
        ];

        if ($isPlug) {
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