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
     * @var array named preset definitions (from configuration)
     */
    protected $namedPresetDefinitions = [];

    /**
     * @var PresetConfig[] named preset configs
     */
    protected $namedPresetConfigs = [];

    /**
     * @var array dynamic preset definitions (from your calls)
     */
    protected $dynamicPresetDefinitions = [];

    /**
     * @var PresetConfig[] dynamic preset configs
     */
    protected $dynamicPresetConfigs = [];

    /**
     * PresetConfigStorage constructor.
     *
     * @param Config $config
     * @param array $namedPresetDefinitions
     * @throws ConfigException
     */
    public function __construct(Config $config, $namedPresetDefinitions)
    {
        $this->setConfig($config, $namedPresetDefinitions);
    }

    /**
     * @param Config $config
     * @param array $namedPresetDefinitions
     * @throws ConfigException
     */
    public function setConfig(Config $config, $namedPresetDefinitions)
    {
        if (is_array($namedPresetDefinitions)) {
            $this->namedPresetDefinitions = $namedPresetDefinitions;
        } else {
            throw new ConfigException('Incorrect config format');
        }

        $this->config = $config;

        $this->namedPresetConfigs = [];
        $this->dynamicPresetConfigs = [];
    }

    /**
     * @param array $presetDefinition
     * @return string
     */
    public function addDynamicPresetDefinition(array $presetDefinition)
    {
        $hash = $this->presetDefinitionHash($presetDefinition);

        $this->dynamicPresetDefinitions[$hash] = $presetDefinition;

        return $hash;
    }

    /**
     * @param string $name
     * @return PresetConfig|null
     * @throws ConfigException
     */
    public function getPresetConfig($name)
    {
        if (isset($this->namedPresetConfigs[$name])) {
            return $this->namedPresetConfigs[$name];
        } elseif (isset($this->dynamicPresetConfigs[$name])) {
            return $this->dynamicPresetConfigs[$name];
        } elseif (isset($this->namedPresetDefinitions[$name])) {
            $this->namedPresetConfigs[$name] = new PresetConfig($this->config, $this->namedPresetDefinitions[$name]);
            return $this->namedPresetConfigs[$name];
        } elseif (isset($this->dynamicPresetDefinitions[$name])) {
            $this->dynamicPresetConfigs[$name] = new PresetConfig($this->config, $this->dynamicPresetDefinitions[$name]);
            return $this->dynamicPresetConfigs[$name];
        } else {
            return null;
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isNamedPreset($name)
    {
        return isset($this->namedPresetDefinitions[$name]);
    }

    /**
     * @param array $presetDefinition
     * @return string
     */
    protected function presetDefinitionHash(array $presetDefinition)
    {
        return '_' . substr(ImgHelper::hash($presetDefinition), 0, 8);
    }
}