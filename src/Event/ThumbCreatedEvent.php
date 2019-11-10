<?php

namespace LireinCore\ImgCache\Event;

use Psr\EventDispatcher\StoppableEventInterface;

final class ThumbCreatedEvent implements StoppableEventInterface
{
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
     * Thumb format
     *
     * @var string
     */
    private $destFormat;

    /**
     * Is stub
     *
     * @var bool
     */
    private $isStub;

    /**
     * @var bool
     */
    private $propagationStopped = false;

    /**
     * ThumbCreatedEvent constructor.
     *
     * @param string $srcPath
     * @param string $destPath
     * @param string $destFormat
     * @param bool $isStub
     */
    public function __construct(string $srcPath, string $destPath, string $destFormat, bool $isStub)
    {
        $this->srcPath = $srcPath;
        $this->destPath = $destPath;
        $this->destFormat = $destFormat;
        $this->isStub = $isStub;
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
     * @return string
     */
    public function destFormat() : string
    {
        return $this->destFormat;
    }

    /**
     * @return bool
     */
    public function isStub() : bool
    {
        return $this->isStub;
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