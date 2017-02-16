<?php

namespace LireinCore\ImgCache;

class ImgCache
{
    /**
     * @var array
     */
    private $_config;

    /**
     * @var array
     */
    private $_effects = [];

    /**
     * @var array
     */
    private $_presets = [];

    /**
     * @var array
     */
    private $_options = [];

    /**
     * @var array
     */
    private $_presetsOptions = [];

    /**
     * @var array
     */
    private $_thumbsOptions = [];

    /**
     * ImgCache constructor.
     *
     * @param $config array|string|null
     */
    public function __construct($config = null)
    {
        if ($config !== null) {
            if (is_string($config)) {
                $config = require($config);
            }

            $this->setConfig($config);
        }
    }

    /**
     * @param $config array|string
     */
    public function setConfig($config)
    {
        if (is_string($config)) {
            $config = require($config);
        }

        $this->_config = $config;

        $this->_options = [];
        $this->_presetsOptions = [];
        $this->_thumbsOptions = [];

        $this->_effects = [
            'crop' => '\LireinCore\ImgCache\Effects\Crop',
            'fit' => '\LireinCore\ImgCache\Effects\Fit',
            'flip' => '\LireinCore\ImgCache\Effects\Flip',
            'overlay' => '\LireinCore\ImgCache\Effects\Overlay',
            'resize' => '\LireinCore\ImgCache\Effects\Resize',
            'rotate' => '\LireinCore\ImgCache\Effects\Rotate',
            'scale' => '\LireinCore\ImgCache\Effects\Scale',
            'negative' => '\LireinCore\ImgCache\Effects\Negative',
            'grayscale' => '\LireinCore\ImgCache\Effects\Grayscale',
            'gamma' => '\LireinCore\ImgCache\Effects\Gamma',
            'blur' => '\LireinCore\ImgCache\Effects\Blur',
        ];

        if (isset($config['effects'])) {
            foreach ($config['effects'] as $name => $class) {
                $this->registerEffect($name, $class);
            }
        }

        if (isset($config['presets'])) {
            $this->_presets = $config['presets'];
        }
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * @param string $name
     * @param string $class
     */
    public function registerEffect($name, $class)
    {
        if (class_exists($class)) {
            $interfaces = class_implements($class);
            if (in_array('LireinCore\ImgCache\IEffect', $interfaces)) {
                $this->_effects[$name] = $class;
            }
        }
    }

    /**
     * @param string $name
     */
    public function unregisterEffect($name)
    {
        unset($this->_effects[$name]);
    }

    /**
     * @return array
     */
    public function getEffects()
    {
        return $this->_effects;
    }

    /**
     * @param string $presetName
     * @param string|null $fileRelPath
     * @param bool $usePlug
     * @return bool|string
     */
    public function path($presetName, $fileRelPath = null, $usePlug = true)
    {
        if (!isset($this->_presets[$presetName])) return false;
        $path = $fileRelPath === null ? false : $path = $this->getThumbPath($presetName, $fileRelPath);

        if (!$path && $usePlug) {
            $path = $this->getPlugPath($presetName);
        }

        return $path;
    }

    /**
     * @param string|null $presetName
     * @param string|null $fileRelPath
     * @param bool $absolute
     * @param bool $usePlug
     * @return bool|string
     */
    public function url($presetName = null, $fileRelPath = null, $absolute = false, $usePlug = true)
    {
        if ($presetName === null) {
            $options = $this->getOptions();
            if ($options['plugUrl']) return $options['plugUrl'];
            else return false;
        } elseif (!isset($this->_presets[$presetName])) return false;

        $preset = $this->_presets[$presetName];
        $presetOptions = $this->getPresetOptions($presetName);

        $path = $fileRelPath === null ? false : $path = $this->getThumbPath($presetName, $fileRelPath);
        if ($path) {
            $url = $this->getUrl($presetName, $path, $absolute);
            if ($url) return $url;
        }

        if ($usePlug) {
            if (!empty($preset['plug']['path']) || empty($preset['plug']['url'])) {
                $path = $this->getPlugPath($presetName);
                if ($path) {
                    $url = $this->getUrl($presetName, $path, $absolute);
                    if ($url) return $url;
                }
            }

            if ($presetOptions['plugUrl']) {
                return $presetOptions['plugUrl'];
            }
        }

        return false;
    }

    /**
     * @param string $presetName
     * @param string $fileRelPath
     * @return bool|string
     */
    private function getThumbPath($presetName, $fileRelPath)
    {
        $state = true;
        $presetOptions = $this->getPresetOptions($presetName);
        $fileRelPath = ltrim($fileRelPath, "\\/");
        $thumbOptions = $this->getThumbOptions($fileRelPath, $presetName);
        $destFullPath = $presetOptions['destdir'] . DIRECTORY_SEPARATOR . $thumbOptions['subpath'];

        if (!is_file($destFullPath)) {
            $srcFullPath = $presetOptions['srcdir'] . DIRECTORY_SEPARATOR . $fileRelPath;
            if ($state = is_file($srcFullPath)) {
                $state = $this->createThumb($presetName, $srcFullPath, $destFullPath, $thumbOptions);
            }
        }

        return $state ? $destFullPath : false;
    }

    /**
     * @param string $presetName
     * @return bool|string
     */
    private function getPlugPath($presetName)
    {
        $presetOptions = $this->getPresetOptions($presetName);

        if ($presetOptions['plugPath']) {
            $srcFullPath = $presetOptions['plugPath'];
        }
        else return false;

        $thumbOptions = $this->getThumbOptions($srcFullPath, $presetName, true);
        $destFullPath = $presetOptions['destdir'] . DIRECTORY_SEPARATOR . $thumbOptions['subpath'];

        if (!is_file($destFullPath)) {
            if (!$this->createThumb($presetName, $srcFullPath, $destFullPath, $thumbOptions)) return false;
        }

        return $destFullPath;
    }

    /**
     * @param string $presetName
     * @param string $path
     * @param bool $absolute
     * @return bool|string
     */
    private function getUrl($presetName, $path, $absolute = false)
    {
        $presetOptions = $this->getPresetOptions($presetName);
        $realPath = realpath($path);
        $isWebPath = false === strpos($realPath, $presetOptions['realWebDir']) ? false : true;

        if ($isWebPath) {
            $url = str_replace('\\', '/', substr($realPath, strlen($presetOptions['realWebDir'])));

            return $absolute ? $presetOptions['baseurl'] . $url : $url;
        }

        return false;
    }

    /**
     * @param string $fileRelPath
     * @param string|null $presetName
     */
    public function clearFileThumbs($fileRelPath, $presetName = null)
    {
        $presetOptions = $this->getPresetOptions($presetName);
        $fileRelPath = ltrim($fileRelPath, "\\/");
        if ($presetName) {
            if (isset($this->_presets[$presetName])) {
                $thumbOptions = $this->getThumbOptions($fileRelPath, $presetName);
                $path = $presetOptions['destdir'] . DIRECTORY_SEPARATOR . $thumbOptions['path'];
                if (file_exists($path)) unlink($path);
            }
        }
        else {
            foreach ($this->_presets as $presetName => $preset) {
                $thumbOptions = $this->getThumbOptions($fileRelPath, $presetName);
                $path = $presetOptions['destdir'] . DIRECTORY_SEPARATOR . $thumbOptions['path'];
                if (file_exists($path)) unlink($path);
            }
        }
    }

    /**
     * @param string $presetName
     */
    public function clearPresetThumbs($presetName)
    {
        $presetOptions = $this->getPresetOptions($presetName);
        $this->rrmdir($presetOptions['destdir'] . DIRECTORY_SEPARATOR . 'presets' . DIRECTORY_SEPARATOR . $presetName);
    }

    /**
     * @param string|null $presetName
     */
    public function clearPlugsThumbs($presetName = null)
    {
        $presetOptions = $this->getPresetOptions($presetName);
        if ($presetName) {
            $this->rrmdir($presetOptions['destdir'] . DIRECTORY_SEPARATOR . 'plugs' . DIRECTORY_SEPARATOR . $presetName);
        }
        else {
            $this->rrmdir($presetOptions['destdir'] . DIRECTORY_SEPARATOR . 'plugs');
        }
    }

    /**
     * @param array $convert
     * @return array
     */
    private function getConvertMap($convert)
    {
        $convertMap = [];

        foreach ($convert as $srcstr => $dest) {
            foreach (explode(',', $srcstr) as $src) {
                $convertMap[trim($src)] = trim($dest);
            }
        }

        return $convertMap;
    }

    /**
     * @param string $driver
     * @return int
     */
    private function getDriverCode($driver)
    {
        $driverCode = null;

        switch ($driver) {
            case 'gmagick': $driverCode = IImage::DRIVER_GM; break;
            case 'imagick': $driverCode = IImage::DRIVER_IM; break;
            case 'gd': $driverCode = IImage::DRIVER_GD; break;
            default: $driverCode = IImage::DRIVER_DEFAULT;
        }

        return $driverCode;
    }

    /**
     * @return array
     */
    private function getOptions()
    {
        if (!$this->_options) {
            $config = $this->_config;

            $defaultConvertMap = [
                'jpeg' => 'jpeg',
                'png' => 'png',
                'gif' => 'gif',
                'wbmp' => 'wbmp',
                'xbm' => 'xbm',
                '*' => 'png',
            ];

            $imageClass = '\LireinCore\ImgCache\Image';
            if (!empty($config['image']) && class_exists($config['image'])) {
                $interfaces = class_implements($config['image']);
                if (in_array('LireinCore\ImgCache\IImage', $interfaces)) {
                    $imageClass = $config['image'];
                }
            }

            $this->_options = [
                'driverCode' => !empty($config['driver']) ? $this->getDriverCode($config['driver']) : IImage::DRIVER_DEFAULT,
                'srcdir' => !empty($config['srcdir']) ? rtrim($config['srcdir'], "\\/") : '',
                'destdir' => !empty($config['destdir']) ? rtrim($config['destdir'], "\\/") : sys_get_temp_dir(),
                'realWebDir' => !empty($config['webdir']) ? realpath($config['webdir']) : '',
                'baseurl' => !empty($config['baseurl']) ? $config['baseurl'] : '',
                'plugPath' => !empty($config['plug']['path']) ? $config['plug']['path'] : '',
                'plugEffects' => isset($config['plug']['effects']) ? $config['plug']['effects'] : true,
                'plugUrl' => !empty($config['plug']['url']) ? $config['plug']['url'] : null,
                'convertMap' => isset($config['convert']) ? $this->getConvertMap($config['convert']) + $defaultConvertMap : $defaultConvertMap,
                'jpeg_quality' => isset($config['jpeg_quality']) ? $config['jpeg_quality'] : 75,
                'png_compression_level' => isset($config['png_compression_level']) ? $config['png_compression_level'] : 7,
                'png_compression_filter' => isset($config['png_compression_filter']) ? $config['png_compression_filter'] : 5,
                'imageClass' => $imageClass,
            ];
        }

        return $this->_options;
    }

    /**
     * @param string $presetName
     * @return array
     */
    private function getPresetOptions($presetName)
    {
        if (!isset($this->_presetsOptions[$presetName])) {
            $options = $this->getOptions();
            $preset = $this->_presets[$presetName];
            $convertMap = [];
            if (!empty($preset['convert'])) {
                $convertMap = $this->getConvertMap($preset['convert']);
            }
            if ($options['convertMap']) {
                $convertMap += $options['convertMap'];
            }

            $imageClass = $options['imageClass'];
            if (!empty($preset['image']) && class_exists($preset['image'])) {
                $interfaces = class_implements($preset['image']);
                if (in_array('LireinCore\ImgCache\IImage', $interfaces)) {
                    $imageClass = $preset['image'];
                }
            }

            $this->_presetsOptions[$presetName] = [
                'driverCode' => !empty($preset['driver']) ? $this->getDriverCode($preset['driver']) : $options['driverCode'],
                'srcdir' => !empty($preset['srcdir']) ? rtrim($preset['srcdir'], "\\/") : $options['srcdir'],
                'destdir' => !empty($preset['destdir']) ? rtrim($preset['destdir'], "\\/") : $options['destdir'],
                'realWebDir' => !empty($preset['webdir']) ? realpath($preset['webdir']) : $options['realWebDir'],
                'baseurl' => !empty($preset['baseurl']) ? $preset['baseurl'] : $options['baseurl'],
                'plugPath' => !empty($preset['plug']['path']) ? $preset['plug']['path'] : $options['plugPath'],
                'plugEffects' => isset($preset['plug']['effects']) ? $preset['plug']['effects'] : $options['plugEffects'],
                'plugUrl' => !empty($preset['plug']['url']) ? $preset['plug']['url'] : $options['plugUrl'],
                'convertMap' => $convertMap,
                'jpeg_quality' => isset($preset['jpeg_quality']) ? $preset['jpeg_quality'] : $options['jpeg_quality'],
                'png_compression_level' => isset($preset['png_compression_level']) ? $preset['png_compression_level'] : $options['png_compression_level'],
                'png_compression_filter' => isset($preset['png_compression_filter']) ? $preset['png_compression_filter'] : $options['png_compression_filter'],
                'imageClass' => $imageClass,
            ];
        }

        return $this->_presetsOptions[$presetName];
    }

    /**
     * @param string $filepath
     * @param string $presetName
     * @param bool $isPlug
     * @return array
     */
    private function getThumbOptions($filepath, $presetName, $isPlug = false)
    {
        if ($isPlug && isset($this->_thumbsOptions[$presetName]['plug'])) {
            return $this->_thumbsOptions[$presetName]['plug'];
        } elseif (isset($this->_thumbsOptions[$presetName]['main'][$filepath])) {
            return $this->_thumbsOptions[$presetName]['main'][$filepath];
        }

        $destInfo = pathinfo($filepath);
        $originalExt = $destInfo['extension'];

        $presetOptions = $this->getPresetOptions($presetName);
        $convertMap = $presetOptions['convertMap'];
        $originalFormat = $this->getFormatByExt($originalExt);

        if (key_exists($originalFormat, $convertMap)) {
            $format = $convertMap[$originalFormat];
        } elseif (key_exists('*', $convertMap)) {
            $format = $convertMap['*'];
        } else {
            $format = $originalFormat;
        }

        $ext = $this->getExtByFormat($format);

        $subPath = "{$destInfo['filename']}.{$ext}";
        if ($isPlug) {
            $subPath = 'plugs' . DIRECTORY_SEPARATOR . $presetName . DIRECTORY_SEPARATOR . $subPath;
        } else {
            if ($destInfo['dirname'] !== '.') $subPath = $destInfo['dirname'] . DIRECTORY_SEPARATOR . $subPath;
            $subPath = 'presets' . DIRECTORY_SEPARATOR . $presetName . DIRECTORY_SEPARATOR . $subPath;
        }

        $options = [
            'format' => $format,
            'ext' => $ext,
            'subpath' => $subPath,
            'isPlug' => $isPlug,
        ];

        if ($isPlug) {
            $this->_thumbsOptions[$presetName]['plug'] = $options;
        } else {
            $this->_thumbsOptions[$presetName]['main'][$filepath] = $options;
        }

        return $options;
    }

    /**
     * @param string $presetName
     * @param string $srcPath
     * @param string $destPath
     * @param array $thumbOptions
     * @return bool
     */
    private function createThumb($presetName, $srcPath, $destPath, $thumbOptions)
    {
        $state = true;
        try {
            $presetOptions = $this->getPresetOptions($presetName);
            $image = (new $presetOptions['imageClass']($presetOptions['driverCode'], false))->open($srcPath);
            if (!$thumbOptions['isPlug'] || $presetOptions['plugEffects']) {
                $this->applyPreset($image, $this->_presets[$presetName]);
            }
            $image->save($destPath, [
                'format' => $thumbOptions['format'],
                'jpeg_quality' => $presetOptions['jpeg_quality'],
                'png_compression_level' => $presetOptions['png_compression_level'],
                //'png_compression_filter' => $presetOptions['png_compression_filter'],
            ]);
        } catch (\Exception $ex) {
            $state = false;
        }

        return $state;
    }

    /**
     * @param IImage $image
     * @param array $preset
     */
    private function applyPreset($image, $preset)
    {
        $effectsList = $this->getEffects();

        if (!empty($preset['effects'])) {
            foreach ($preset['effects'] as $effectData) {
                $params = empty($effectData['params']) ? [] : $effectData['params'];
                $effect = $this->create_class_array_assoc($effectsList[$effectData['type']], $params);
                $image->apply($effect);
            }
        }
    }

    /**
     * @return array
     */
    private function getFormats()
    {
        return [
            'jpeg' => [
                'mime' => ['image/jpeg', 'image/pjpeg'],
                'ext' => ['jpg', 'jpeg', 'jpe', 'pjpeg'],
            ],
            'png' => [
                'mime' => ['image/png', 'image/x-png'],
                'ext' => ['png'],
            ],
            'gif' => [
                'mime' => ['image/gif'],
                'ext' => ['gif'],
            ],
            'wbmp' => [
                'mime' => ['image/vnd.wap.wbmp'],
                'ext' => ['wbmp'],
            ],
            'xbm' => [
                'mime' => ['image/xbm', 'image/x-xbitmap'],
                'ext' => ['xbm'],
            ],
            'bmp' => [
                'mime' => ['image/bmp', 'image/x-windows-bmp', 'image/x-ms-bmp'],
                'ext' => ['bmp'],
            ],
            'ico' => [
                'mime' => ['image/x-icon', 'image/vnd.microsoft.icon', 'image/x-ico'],
                'ext' => ['ico'],
            ],
            'webp' => [
                'mime' => ['image/webp'],
                'ext' => ['webp'],
            ],
            'tiff' => [
                'mime' => ['image/tiff'],
                'ext' => ['tif', 'tiff'],
            ],
            'svg' => [
                'mime' => ['image/svg+xml'],
                'ext' => ['svg'/*, 'svgz'*/],
            ],
            'psd' => [
                'mime' => ['image/psd'],
                'ext' => ['psd'],
            ],
            'aiff' => [
                'mime' => ['image/iff'],
                'ext' => ['aiff'],
            ],
            'swf' => [
                'mime' => ['application/x-shockwave-flash'],
                'ext' => ['swf'],
            ],
            'swc' => [
                'mime' => ['application/x-shockwave-flash'],
                'ext' => ['swc'],
            ],
            'jp2' => [
                'mime' => ['image/jp2'],
                'ext' => ['jp2'],
            ],
            'jb2' => [
                'mime' => ['application/octet-stream'],
                'ext' => ['jb2'],
            ],
            'jpc' => [
                'mime' => ['application/octet-stream'],
                'ext' => ['jpc'],
            ],
            'jpf' => [
                'mime' => ['application/octet-stream'],
                'ext' => ['jpf'],
            ]
        ];
    }

    /**
     * @param string $mime
     * @return null|string
     */
    /*private function getFormatByMime($mime)
    {
        $formats = $this->getFormats();

        foreach ($formats as $name => $format) {
            if (in_array($mime, $format['mime'])) return $name;
        }

        return null;
    }*/

    /**
     * @param string $ext
     * @return null|string
     */
    private function getFormatByExt($ext)
    {
        $formats = $this->getFormats();

        foreach ($formats as $name => $format) {
            if (in_array($ext, $format['ext'])) return $name;
        }

        return null;
    }

    /**
     * @param string $format
     * @return null|string
     */
    private function getExtByFormat($format)
    {
        $formats = $this->getFormats();

        if (isset($formats[$format]['ext'][0])) return $formats[$format]['ext'][0];

        return null;
    }

    /**
     * Создает объект передавая в конструктор ассоциативный массив
     *
     * @param string $class
     * @param array $params [arg => value]
     * @return IEffect
     * @throws \RuntimeException
     */
    private static function create_class_array_assoc($class, $params = [])
    {
        if (!class_exists($class)) {
            throw new \RuntimeException('Call to unexisting class: '. $class);
        }

        $real_params = [];

        if (method_exists($class, '__construct')) {
            $refMethod = new \ReflectionMethod($class, '__construct');

            foreach ($refMethod->getParameters() as $i => $param) {
                $pname = $param->getName();
                /*if ($param->isPassedByReference()) {
                    /// @todo shall we raise some warning?
                }*/
                if (array_key_exists($pname, $params)) {
                    $real_params[] = $params[$pname];
                } elseif ($param->isDefaultValueAvailable()) {
                    $real_params[] = $param->getDefaultValue();
                } else {
                    $title = $class . '::__construct';
                    throw new \RuntimeException('Call to ' . $title . ' missing parameter nr. ' . ($i + 1) . ': ' . $pname);
                }
            }
        }

        $refClass = new \ReflectionClass($class);

        return $refClass->newInstanceArgs($real_params);
    }

    /**
     * Удаляет каталог и все его содержимое
     * @param string $pathname
     * @return bool
     */
    private function rrmdir($pathname)
    {
        if (($dir = @opendir($pathname)) === false) return false;

        while (($file = readdir($dir)) !== false) {
            if (($file != '.') && ($file != '..')) {
                $full = $pathname . DIRECTORY_SEPARATOR . $file;
                if (is_dir($full)) {
                    $this->rrmdir($full);
                } else {
                    unlink($full);
                }
            }
        }

        closedir($dir);

        return @rmdir($pathname);
    }
}