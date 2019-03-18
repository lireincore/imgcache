<?php

namespace LireinCore\ImgCache\Event;

use Symfony\Component\EventDispatcher\Event;

final class ThumbCreatedEvent extends Event
{
    public const NAME = 'lireincore_imgcache.thumb_created';

    /**
     * Source image path
     *
     * @var string
     */
    private $srcPath;

    /**
     * Thumb path
     *
     * @var string
     */
    private $destPath;

    /**
     * ThumbCreatedEvent constructor.
     *
     * @param string $srcPath
     * @param string $destPath
     */
    public function __construct(string $srcPath, string $destPath)
    {
        $this->srcPath = $srcPath;
        $this->destPath = $destPath;
    }

    /**
     * @return string
     */
    public function srcPath() : string
    {
        return $this->srcPath;
    }

    /**
     * @return string
     */
    public function destPath() : string
    {
        return $this->destPath;
    }
}