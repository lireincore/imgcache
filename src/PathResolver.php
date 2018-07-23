<?php

namespace LireinCore\ImgCache;

use LireinCore\Image\ImageHelper;
use LireinCore\ImgCache\Exception\ConfigException;

class PathResolver
{
    /**
     * @var Config
     */
    protected $_config;

    /**
     * @var array
     */
    protected $_data = [];

    /**
     * PathResolver constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->setConfig($config);
    }

    /**
     * @param Config $config
     */
    public function setConfig(Config $config)
    {
        $this->_config = $config;
        $this->_data = [];
    }

    /**
     * @param string $presetName
     * @param string $fileRelPath
     * @param bool $isPlug
     * @return string
     * @throws ConfigException
     */
    public function getDestPath($presetName, $fileRelPath, $isPlug = false)
    {
        $presetConfig = $this->_config->getPresetConfig($presetName);
        $data = $this->getData($presetName, $fileRelPath, $isPlug);

        return $presetConfig->getDestDir() . DIRECTORY_SEPARATOR . $data['subpath'];
    }

    /**
     * @param string $presetName
     * @param string $fileRelPath
     * @param bool $isPlug
     * @return string
     * @throws ConfigException
     */
    public function getDestFormat($presetName, $fileRelPath, $isPlug = false)
    {
        $data = $this->getData($presetName, $fileRelPath, $isPlug);

        return $data['format'];
    }

    /**
     * @param string $presetName
     * @param string $fileRelPath
     * @param bool $isPlug
     * @return array
     * @throws ConfigException
     */
    protected function getData($presetName, $fileRelPath, $isPlug = false)
    {
        if (!$isPlug) {
            $fileRelPath = ltrim($fileRelPath, "\\/");
        }

        if ($isPlug && isset($this->_data[$presetName]['plug'])) {
            return $this->_data[$presetName]['plug'];
        } elseif (isset($this->_data[$presetName]['main'][$fileRelPath])) {
            return $this->_data[$presetName]['main'][$fileRelPath];
        }

        $destInfo = pathinfo($fileRelPath);
        $originalExt = $destInfo['extension'];
        $originalFormat = ImageHelper::getFormatByExt($originalExt);
        $format = $this->getConvertedFormat($presetName, $originalFormat);
        $ext = ImageHelper::getExtByFormat($format);
        $subPath = "{$destInfo['filename']}.{$ext}";
        $presetConfig = $this->_config->getPresetConfig($presetName);
        $hash = $presetConfig->getHash();
        $hashedPresetName = "{$presetName}_{$hash}";

        if ($isPlug) {
            $subPath = implode(DIRECTORY_SEPARATOR, ['plugs', $hashedPresetName, $subPath]);
        } else {
            if ($destInfo['dirname'] !== '.') {
                $subPath = $destInfo['dirname'] . DIRECTORY_SEPARATOR . $subPath;
            }
            $subPath = implode(DIRECTORY_SEPARATOR, ['presets', $hashedPresetName, $subPath]);
        }

        $data = [
            'format' => $format,
            'ext' => $ext,
            'subpath' => $subPath,
            'isPlug' => $isPlug,
        ];

        if ($isPlug) {
            $this->_data[$presetName]['plug'] = $data;
        } else {
            $this->_data[$presetName]['main'][$fileRelPath] = $data;
        }

        return $data;
    }

    /**
     * @param string $presetName
     * @param string $originalFormat
     * @return string
     * @throws ConfigException
     */
    protected function getConvertedFormat($presetName, $originalFormat)
    {
        $convertMap = $this->_config->getPresetConfig($presetName)->getConvertMap();
        if (key_exists($originalFormat, $convertMap)) {
            return $convertMap[$originalFormat];
        } elseif (key_exists('*', $convertMap)) {
            return $convertMap['*'];
        } else {
            return $originalFormat;
        }
    }
}