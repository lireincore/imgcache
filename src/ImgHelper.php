<?php

namespace LireinCore\ImgCache;

use LireinCore\Image\ImageInterface;

class ImgHelper
{
    /**
     * @param null|string $driver
     * @return int
     */
    public static function getDriverCode($driver = null)
    {
        switch ($driver) {
            case 'gmagick':
                return ImageInterface::DRIVER_GM;
            case 'imagick':
                return ImageInterface::DRIVER_IM;
            case 'gd':
                return ImageInterface::DRIVER_GD;
            default:
                return ImageInterface::DRIVER_DEFAULT;
        }
    }

    /**
     * @param string $class
     * @param array $params
     * @return object
     */
    public static function createClassArrayAssoc($class, $params = [])
    {
        $realParams = [];

        if (method_exists($class, '__construct')) {
            $refMethod = new \ReflectionMethod($class, '__construct');

            foreach ($refMethod->getParameters() as $i => $param) {
                $pname = $param->getName();
                /*if ($param->isPassedByReference()) {
                    // @todo: shall we raise some warning?
                }*/
                if (array_key_exists($pname, $params)) {
                    $realParams[] = $params[$pname];
                } elseif ($param->isDefaultValueAvailable()) {
                    $realParams[] = $param->getDefaultValue();
                } else {
                    $title = $class . '::__construct';

                    throw new \RuntimeException('Call to ' . $title . ' missing parameter nr. ' . ($i + 1) . ": '{$pname}'");
                }
            }
        }

        $refClass = new \ReflectionClass($class);

        return $refClass->newInstanceArgs($realParams);
    }
}