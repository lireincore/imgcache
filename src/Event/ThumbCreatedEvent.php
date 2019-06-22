<?php

namespace LireinCore\ImgCache\Event;

use Psr\EventDispatcher\StoppableEventInterface;

final class ThumbCreatedEvent implements StoppableEventInterface
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
     * @var bool
     */
    private $propagationStopped = false;

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

    /**
     * @return bool
     */
    public function isPropagationStopped() : bool
    {
        return $this->propagationStopped;
    }

    public function stopPropagation() : void
    {
        $this->propagationStopped = true;
    }
}