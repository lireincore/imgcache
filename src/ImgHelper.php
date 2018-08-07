<?php

namespace LireinCore\ImgCache;

use LireinCore\Image\Manipulator;
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
     * @param string $driver
     * @return int
     */
    public static function driverCode($driver)
    {
        switch ($driver) {
            case 'gmagick':
                return Manipulator::DRIVER_GM;
            case 'imagick':
                return Manipulator::DRIVER_IM;
            case 'gd':
                return Manipulator::DRIVER_GD;
            default:
                return Manipulator::DRIVER_DEFAULT;
        }
    }

    /**
     * @return null|string
     */
    public static function availableDriver()
    {
        if (ImageHelper::isImagickAvailable(Manipulator::MIN_REQUIRED_IM_VER)) {
            return 'imagick';
        } elseif (ImageHelper::isGDAvailable(Manipulator::MIN_REQUIRED_GD_VER)) {
            return 'gd';
        } elseif (ImageHelper::isGmagickAvailable(Manipulator::MIN_REQUIRED_GM_VER)) {
            return 'gmagick';
        }

        return null;
    }

    /**
     * @param string $class
     * @param array $params
     * @return object
     * @throws \RuntimeException
     */
    public static function createClassArrayAssoc($class, array $params = [])
    {
        try {
            $refClass = new \ReflectionClass($class);
        } catch (\ReflectionException $ex) {
            throw new \RuntimeException("Class {$class} does not exist", 0, $ex);
        }

        $realParams = [];
        if (method_exists($class, '__construct')) {
            try {
                $refMethod = new \ReflectionMethod($class, '__construct');
            } catch (\ReflectionException $ex) {
                throw new \RuntimeException("Method '__construct' does not exist", 0, $ex);
            }

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
                    throw new \RuntimeException("Call to {$class}::__construct missing parameter nr. " . ($i + 1) . ": '{$pname}'");
                }
            }
        }

        return $refClass->newInstanceArgs($realParams);
    }
}