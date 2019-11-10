<?php

namespace LireinCore\ImgCache;

use RuntimeException;
use LireinCore\ImgCache\Exception\ConfigException;

interface ImgProcessorInterface
{
    /**
     * @param string $srcPath
     * @param string $destPath
     * @param string $format
     * @param string $presetDefinitionHash
     * @param bool $isStub
     * @throws ConfigException
     * @throws RuntimeException
     */
    public function createThumb(
        string $srcPath,
        string $destPath,
        string $format,
        string $presetDefinitionHash,
        bool $isStub = false
    ) : void;
}