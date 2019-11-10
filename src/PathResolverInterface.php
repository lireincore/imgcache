<?php

namespace LireinCore\ImgCache;

use RuntimeException;
use LireinCore\ImgCache\Exception\ConfigException;

interface PathResolverInterface
{
    /**
     * @param string $relSrcPath
     * @param string $presetDefinitionHash
     * @return string
     * @throws ConfigException
     */
    public function srcPath(string $relSrcPath, string $presetDefinitionHash) : string;

    /**
     * @param string $srcPath
     * @param string $presetDefinitionHash
     * @param bool $isStub
     * @return string
     * @throws ConfigException
     * @throws RuntimeException
     */
    public function destPath(string $srcPath, string $presetDefinitionHash, bool $isStub = false) : string;

    /**
     * @param string $srcPath
     * @param string $presetDefinitionHash
     * @param bool $isStub
     * @return string
     * @throws ConfigException
     * @throws RuntimeException
     */
    public function destExtension(string $srcPath, string $presetDefinitionHash, bool $isStub = false) : string;

    /**
     * @param string $presetDefinitionHash
     * @param bool $isStub
     * @return string
     * @throws ConfigException
     */
    public function presetDir(string $presetDefinitionHash, bool $isStub = false) : string;
}