<?php

namespace LireinCore\ImgCache;

use LireinCore\ImgCache\Exception\ConfigException;

class PresetConfigRegistry
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var string[]
     */
    protected $nameToHashMap = [];

    /**
     * @var array presets definitions (from configuration and your calls)
     */
    protected $presetDefinitions = [];

    /**
     * @var PresetConfig[] presets configs
     */
    protected $presetConfigs = [];

    /**
     * PresetConfigStorage constructor.
     *
     * @param Config $config
     * @param array $namedPresetDefinitions
     */
    public function __construct(Config $config, array $namedPresetDefinitions)
    {
        $this->config = $config;
        foreach ($namedPresetDefinitions as $presetName => $presetDefinition) {
            $this->nameToHashMap[$presetName] = $this->addPresetDefinition($presetDefinition);
        }
    }

    /**
     * @param array $presetDefinition
     * @return string
     */
    public function addPresetDefinition(array $presetDefinition)
    {
        $presetDefinitionHash = $this->presetDefinitionHash($presetDefinition);

        $this->presetDefinitions[$presetDefinitionHash] = $presetDefinition;

        return $presetDefinitionHash;
    }

    /**
     * @param string $presetDefinitionHash
     * @return PresetConfig|null
     * @throws ConfigException
     */
    public function presetConfig($presetDefinitionHash)
    {
        if (isset($this->presetConfigs[$presetDefinitionHash])) {
            return $this->presetConfigs[$presetDefinitionHash];
        } elseif (isset($this->presetDefinitions[$presetDefinitionHash])) {
            $this->presetConfigs[$presetDefinitionHash] = new PresetConfig($this->config, $this->presetDefinitions[$presetDefinitionHash]);
            return $this->presetConfigs[$presetDefinitionHash];
        } else {
            return null;
        }
    }

    /**
     * @return string[]
     */
    public function presetDefinitionHashList()
    {
        return array_keys($this->presetDefinitions);
    }

    /**
     * @param string $presetName
     * @return null|string
     */
    public function hashByName($presetName)
    {
        return isset($this->nameToHashMap[$presetName]) ? $this->nameToHashMap[$presetName] : null;
    }

    /**
     * @param array $presetDefinition
     * @return string
     */
    protected function presetDefinitionHash(array $presetDefinition)
    {
        if (!empty($presetDefinition['plug'])) {
            ksort($presetDefinition['plug']);
        }

        if (!empty($presetDefinition['effects'])) {
            $presetDefinition['effects'] = $this->sortEffectsOrPostProcessorsConfig($presetDefinition['effects']);
        }

        if (!empty($presetDefinition['postprocessors'])) {
            $presetDefinition['postprocessors'] = $this->sortEffectsOrPostProcessorsConfig($presetDefinition['postprocessors']);
        }

        ksort($presetDefinition);

        return ImgHelper::hash($presetDefinition);
    }

    /**
     * @param array $configItems
     * @return array
     */
    protected function sortEffectsOrPostProcessorsConfig(array $configItems)
    {
        return array_map(function ($item) {
            if (!empty($item['params'])) {
                ksort($item['params']);
            }
            ksort($item);
            return $item;
        }, $configItems);
    }
}