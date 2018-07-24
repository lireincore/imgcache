<?php

namespace LireinCore\ImgCache;

use LireinCore\Image\ImageInterface;
use LireinCore\Image\ImageHelper;

class ImgHelper
{
    /**
     * @param mixed $hashData
     * @return string
     */
    public static function hash($hashData)
    {
        return md5(json_encode($hashData));
    }

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
     * @return string|null
     */
    public static function getAvailableDriver()
    {
        if (ImageHelper::checkIsImagickAvailable(ImageInterface::MIN_REQUIRED_IM_VER)) {
            return 'imagick';
        } elseif (ImageHelper::checkIsGDAvailable(ImageInterface::MIN_REQUIRED_GD_VER)) {
            return 'gd';
        } elseif (ImageHelper::checkIsGmagickAvailable(ImageInterface::MIN_REQUIRED_GM_VER)) {
            return 'gmagick';
        }

        return null;
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