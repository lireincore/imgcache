<?php

namespace LireinCore\ImgCache;

use Psr\Log\LoggerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

interface ImgProcessorFactoryInterface
{
    /**
     * @param Config $config
     * @param PresetConfigRegistry $presetConfigRegistry
     * @param LoggerInterface|null $logger
     * @param EventDispatcherInterface|null $eventDispatcher
     * @return ImgProcessorInterface
     */
    public function createImgProcessor(
        Config $config,
        PresetConfigRegistry $presetConfigRegistry,
        ?LoggerInterface $logger = null,
        ?EventDispatcherInterface $eventDispatcher = null
    ) : ImgProcessorInterface;
}