<?php

namespace LireinCore\ImgCache;

use LireinCore\ImgCache\Exception\ConfigException;

final class PresetConfigRegistry
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var string[]
     */
    private $nameToHashMap = [];

    /**
     * @var array presets definitions (from configuration and your calls)
     */
    private $presetDefinitions = [];

    /**
     * @var PresetConfig[] presets configs
     */
    private $presetConfigs = [];

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
    public function addPresetDefinition(array $presetDefinition) : string
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
    public function presetConfig(string $presetDefinitionHash) : ?PresetConfig
    {
        if (isset($this->presetConfigs[$presetDefinitionHash])) {
            return $this->presetConfigs[$presetDefinitionHash];
        }
        if (isset($this->presetDefinitions[$presetDefinitionHash])) {
            $this->presetConfigs[$presetDefinitionHash] = new PresetConfig($this->config, $this->presetDefinitions[$presetDefinitionHash]);
            return $this->presetConfigs[$presetDefinitionHash];
        }
        return null;
    }

    /**
     * @return string[]
     */
    public function presetDefinitionHashList() : array
    {
        return \array_keys($this->presetDefinitions);
    }

    /**
     * @param string $presetName
     * @return null|string
     */
    public function hashByName(string $presetName) : ?string
    {
        return $this->nameToHashMap[$presetName] ?? null;
    }

    /**
     * @param array $presetDefinition
     * @return string
     */
    private function presetDefinitionHash(array $presetDefinition) : string
    {
        if (!empty($presetDefinition['plug'])) {
            \ksort($presetDefinition['plug']);
        }
        if (!empty($presetDefinition['effects'])) {
            $presetDefinition['effects'] = $this->sortEffectsOrPostProcessorsConfig($presetDefinition['effects']);
        }
        if (!empty($presetDefinition['postprocessors'])) {
            $presetDefinition['postprocessors'] = $this->sortEffectsOrPostProcessorsConfig($presetDefinition['postprocessors']);
        }
        \ksort($presetDefinition);

        return ImgHelper::hash($presetDefinition);
    }

    /**
     * @param array $configItems
     * @return array
     */
    private function sortEffectsOrPostProcessorsConfig(array $configItems) : array
    {
        return \array_map(function ($item) {
            if (!empty($item['params'])) {
                \ksort($item['params']);
            }
            \ksort($item);
            return $item;
        }, $configItems);
    }
}