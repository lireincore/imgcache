<?php

namespace LireinCore\ImgCache;

use Psr\Log\LoggerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

interface PathResolverFactoryInterface
{
    /**
     * @param Config $config
     * @param PresetConfigRegistry $presetConfigRegistry
     * @param LoggerInterface|null $logger
     * @param EventDispatcherInterface|null $eventDispatcher
     * @return PathResolverInterface
     */
    public function createPathResolver(
        Config $config,
        PresetConfigRegistry $presetConfigRegistry,
        ?LoggerInterface $logger = null,
        ?EventDispatcherInterface $eventDispatcher = null
    ) : PathResolverInterface;
}