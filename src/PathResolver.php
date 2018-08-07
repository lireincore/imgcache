<?php

namespace LireinCore\ImgCache;

use LireinCore\Image\ImageHelper;
use LireinCore\ImgCache\Exception\ConfigException;

class PathResolver
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
    protected $data = [];

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
    public function destPath($srcPath, $presetDefinitionHash, $isPlug = false)
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
    public function destFormat($srcPath, $presetDefinitionHash, $isPlug = false)
    {
        $data = $this->data($srcPath, $presetDefinitionHash, $isPlug);

        return $data['format'];
    }

    /**
     * @param string $presetDefinitionHash
     * @return string
     * @throws ConfigException
     */
    public function presetDir($presetDefinitionHash)
    {
        $presetConfig = $this->presetConfig($presetDefinitionHash);

        return implode(DIRECTORY_SEPARATOR, [$presetConfig->destDir(), 'presets', $presetDefinitionHash]);
    }

    /**
     * @param string $presetDefinitionHash
     * @return string
     * @throws ConfigException
     */
    public function stubDir($presetDefinitionHash)
    {
        $presetConfig = $this->presetConfig($presetDefinitionHash);

        return implode(DIRECTORY_SEPARATOR, [$presetConfig->destDir(), 'stubs', $presetDefinitionHash]);
    }

    /**
     * @param string $srcPath
     * @param string $presetDefinitionHash
     * @param bool $isPlug
     * @return array
     * @throws ConfigException
     */
    protected function data($srcPath, $presetDefinitionHash, $isPlug = false)
    {
        if (!$isPlug) {
            $srcPath = ltrim($srcPath, "\\/");
        }

        if ($isPlug && isset($this->data['stubs'][$presetDefinitionHash][$srcPath])) {
            return $this->data['stubs'][$presetDefinitionHash][$srcPath];
        } elseif (isset($this->data['presets'][$presetDefinitionHash][$srcPath])) {
            return $this->data['presets'][$presetDefinitionHash][$srcPath];
        }

        $destInfo = pathinfo($srcPath);
        $srcExt = $destInfo['extension'];
        $srcFormat = ImageHelper::formatByExt($srcExt);
        $format = $this->convertedFormat($srcFormat, $presetDefinitionHash);
        $ext = ImageHelper::extensionByFormat($format);
        $subPath = "{$destInfo['filename']}.{$ext}";
        $presetConfig = $this->presetConfig($presetDefinitionHash);
        $presetHash = $presetConfig->hash();

        if ($isPlug) {
            $subPath = implode(DIRECTORY_SEPARATOR, ['stubs', $presetHash, $subPath]);
        } else {
            if ($destInfo['dirname'] !== '.') {
                $subPath = $destInfo['dirname'] . DIRECTORY_SEPARATOR . $subPath;
            }
            $subPath = implode(DIRECTORY_SEPARATOR, ['presets', $presetHash, $subPath]);
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
    protected function convertedFormat($srcFormat, $presetDefinitionHash)
    {
        $presetConfig = $this->presetConfig($presetDefinitionHash);
        $convertMap = $presetConfig->convertMap();

        if (key_exists($srcFormat, $convertMap)) {
            return $convertMap[$srcFormat];
        } elseif (key_exists('*', $convertMap)) {
            return $convertMap['*'];
        } else {
            return $srcFormat;
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