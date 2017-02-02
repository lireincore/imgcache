<?php

namespace LireinCore\ImgCache;

class ImgCache
{
    /**
     * @var array
     */
    private $_originalConfig;

    /**
     * @var string
     */
    private $driver;

    /**
     * @var int
     */
    private $driverCode = Image::DRIVER_DEFAULT;

    /**
     * @var string
     */
    private $srcDir;

    /**
     * @var string
     */
    private $destDir;

    /**
     * @var string
     */
    private $webDir;

    /**
     * @var string
     */
    private $realWebDir;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var array
     */
    private $plug = [];

    /**
     * @var array
     */
    private $convert = [];

    /**
     * @var array
     */
    private $convertMap = [];

    /**
     * @var array
     */
    private $_effects = [];

    /**
     * @var array
     */
    private $_effectsRegistry = [
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

    /**
     * @var array
     */
    private $presets = [];

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

        $this->_originalConfig = $config;

        if (isset($config['driver'])) {
            $this->driver = $config['driver'];
            switch ($this->driver) {
                case 'gmagick': $this->driverCode = Image::DRIVER_GM; break;
                case 'imagick': $this->driverCode = Image::DRIVER_IM; break;
                case 'gd': $this->driverCode = Image::DRIVER_GD; break;
            }
        }

        $this->srcDir = $config['srcdir'];

        $this->destDir = $config['destdir'];

        if (isset($config['webdir'])) {
            $this->webDir = $config['webdir'];
            $this->realWebDir = realpath($this->webDir);
        }

        if (isset($config['baseurl'])) {
            $this->baseUrl = $config['baseurl'];
        }

        if (isset($config['plug'])) {
            $this->plug = $config['plug'];
        }

        if (isset($config['convert'])) {
            $this->convert = $config['convert'];
            foreach ($this->convert as $srcstr => $dest) {
                foreach (explode(',', $srcstr) as $src) {
                    $this->convertMap[trim($src)] = trim($dest);
                }
            }
        }

        if (isset($config['effects'])) {
            $this->_effects = $config['effects'];
            foreach ($this->_effects as $name => $class) {
                $this->registerEffect($name, $class);
            }
        }

        if (isset($config['presets'])) {
            $this->presets = $config['presets'];
        }
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->_originalConfig;
    }

    /**
     * @param string $presetName
     * @param string $fileFullName
     * @return string|bool
     */
    public function path($presetName, $fileFullName)
    {
        if (!isset($this->presets[$presetName])) return false;

        $destFullPath = $this->destDir . DIRECTORY_SEPARATOR . $this->getThumbPath($fileFullName, $presetName);

        if (!is_file($destFullPath)) {
            $srcFullPath = $this->srcDir . DIRECTORY_SEPARATOR . $fileFullName;

            if ($state = is_file($srcFullPath)) {
                $state = $this->createThumb($presetName, $srcFullPath, $destFullPath);
            }

            if (!$state) {
                if (!empty($this->presets[$presetName]['plug']['path'])) {
                    $plugSrcFilePath = $this->presets[$presetName]['plug']['path'];
                }
                elseif (!empty($this->plug['path'])) {
                    $plugSrcFilePath = $this->plug['path'];
                }
                else return false;

                $destFullPath = $this->destDir . DIRECTORY_SEPARATOR . $this->getPlugThumbPath($plugSrcFilePath, $presetName);

                if (!is_file($destFullPath)) {
                    if (!$this->createThumb($presetName, $plugSrcFilePath, $destFullPath)) return false;
                }
            }
        }

        return $destFullPath;
    }

    /**
     * @param string $presetName
     * @param string $fileFullName
     * @param bool|false $absolute
     * @return string|bool
     */
    public function url($presetName, $fileFullName, $absolute = false)
    {
        if ($path = $this->path($presetName, $fileFullName)) {
            $realpath = realpath($path);
            if (false !== strpos($realpath, $this->realWebDir)) {
                $url = str_replace('\\', '/', substr($realpath, strlen($this->realWebDir)));

                return $absolute ? $this->baseUrl . $url : $url;
            }
            else return false;
        }
        else return false;
    }

    /**
     * @return array
     */
    public function getEffects()
    {
        return $this->_effectsRegistry;
    }

    /**
     * @param string $name
     * @param string $class
     */
    public function registerEffect($name, $class)
    {
        if (class_exists($class)) {
            $parents = class_parents($class);
            if (in_array('LireinCore\ImgCache\Effect', $parents)) {
                $this->_effectsRegistry = array_merge($this->_effectsRegistry, [$name => $class]);
            }
        }
    }

    /**
     * @param string $name
     */
    public function unregisterEffect($name)
    {
        unset($this->_effectsRegistry[$name]);
    }

    /**
     * @param string $presetName
     * @param string $srcPath
     * @param string $destPath
     * @return bool
     */
    private function createThumb($presetName, $srcPath, $destPath)
    {
        $state = true;
        try {
            $image = (new Image($this->driverCode, false))->open($srcPath);
            $this->applyPreset($image, $this->presets[$presetName]);
            $image->save($destPath);
        } catch (\RuntimeException $ex) {
            $state = false;
        }

        return $state;
    }

    /**
     * @param Image $image
     * @param array $preset
     */
    private function applyPreset($image, $preset)
    {
        $effectsList = $this->getEffects();

        if (!empty($preset['effects'])) {
            foreach ($preset['effects'] as $effectData) {
                if (class_exists($effectsList[$effectData['type']])) {
                    $params = empty($effectData['params']) ? [] : $effectData['params'];
                    $effect = $this->create_class_array_assoc($effectsList[$effectData['type']], $params);
                    $image->apply($effect);
                }
            }
        }
    }

    /**
     * @param string $filepath
     * @param string $presetName
     * @return string
     */
    public function getThumbPath($filepath, $presetName)
    {
        static $paths;

        if (!isset($paths[$presetName][$filepath])) {
            $destInfo = pathinfo($filepath);

            $thumbExt = $this->getThumbExt($destInfo['extension'], $presetName);

            $subPath = $destInfo['filename'] . $thumbExt;
            if ($destInfo['dirname'] !== '.') $subPath = $destInfo['dirname'] . DIRECTORY_SEPARATOR . $subPath;
            $paths[$presetName][$filepath] = 'presets' . DIRECTORY_SEPARATOR . $presetName . DIRECTORY_SEPARATOR . $subPath;
        }

        return $paths[$presetName][$filepath];
    }

    /**
     * @param string $filepath
     * @param string $presetName
     * @return string
     */
    private function getPlugThumbPath($filepath, $presetName)
    {
        static $paths;

        if (!isset($paths[$presetName])) {
            $destInfo = pathinfo($filepath);

            $thumbExt = $this->getThumbExt($destInfo['extension'], $presetName);

            $paths[$presetName] = 'plugs' . DIRECTORY_SEPARATOR . $presetName . DIRECTORY_SEPARATOR . 'empty' . $thumbExt;
        }

        return $paths[$presetName];
    }

    /**
     * @param string $originalExt
     * @param string $presetName
     * @return string
     */
    private function getThumbExt($originalExt, $presetName)
    {
        $convertMap = $this->getPresetConvertMap($presetName);
        $originalFormat = $this->getFormatByExt($originalExt);
        $formats = $this->getFormats();

        if (key_exists($originalFormat, $convertMap)) {
            $format = $convertMap[$originalFormat];
            $thumbExt = '.' . $formats[$format]['ext'][0];
        } elseif (key_exists('*', $convertMap)) {
            $format = $convertMap['*'];
            $thumbExt = '.' . $formats[$format]['ext'][0];
        } else {
            $thumbExt = '.' . $originalExt;
        }

        return $thumbExt;
    }

    /**
     * @param string $presetName
     * @return array
     */
    private function getPresetConvertMap($presetName)
    {
        $convertMap = [];

        if (!empty($this->presets[$presetName]['convert'])) {
            foreach ($this->presets[$presetName]['convert'] as $srcstr => $dest) {
                foreach (explode(',', $srcstr) as $src) {
                    $convertMap[trim($src)] = trim($dest);
                }
            }
        }

        if ($this->convertMap) {
            $convertMap += $this->convertMap;
        }

        return $convertMap;
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
            'bmp' => [
                'mime' => ['image/bmp', 'image/x-windows-bmp', 'image/x-ms-bmp'],
                'ext' => ['bmp'],
            ],
            'ico' => [
                'mime' => ['image/x-icon', 'image/vnd.microsoft.icon', 'image/x-ico'],
                'ext' => ['ico'],
            ],
            'wbmp' => [
                'mime' => ['image/vnd.wap.wbmp'],
                'ext' => ['wbmp'],
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
            /*'xbm' => [
                'mime' => ['image/xbm', 'image/x-xbitmap'],
                'ext' => ['xbm'],
            ],*/
            /*'psd' => [
                'mime' => ['image/psd'],
                'ext' => ['psd'],
            ],*/
            /*'aiff' => [
                'mime' => ['image/iff'],
                'ext' => ['aiff'],
            ],*/
            /*'swf' => [
                'mime' => ['application/x-shockwave-flash'],
                'ext' => ['swf'],
            ],*/
            /*'swc' => [
                'mime' => ['application/x-shockwave-flash'],
                'ext' => ['swc'],
            ],*/
            /*'jp2' => [
                'mime' => ['image/jp2'],
                'ext' => ['jp2'],
            ],*/
            /*'jb2' => [
                'mime' => ['application/octet-stream'],
                'ext' => ['jb2'],
            ],*/
            /*'jpc' => [
                'mime' => ['application/octet-stream'],
                'ext' => ['jpc'],
            ],*/
            /*'jpf' => [
                'mime' => ['application/octet-stream'],
                'ext' => ['jpf'],
            ],*/
        ];
    }

    /**
     * @param string $mime
     * @return null|string
     */
    private function getFormatByMime($mime)
    {
        $formats = $this->getFormats();

        foreach ($formats as $name => $format) {
            if (in_array($mime, $format['mime'])) return $name;
        }

        return null;
    }

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
     * @param string $filepath
     * @param string|null $presetName
     */
    public function clearFileThumbs($filepath, $presetName = null)
    {
        if ($presetName) {
            if (isset($this->presets[$presetName])) {
                $path = $this->destDir . DIRECTORY_SEPARATOR . $this->getThumbPath($filepath, $presetName);
                if (file_exists($path)) unlink($path);
            }
        }
        else {
            foreach ($this->presets as $presetName => $preset) {
                $path = $this->destDir . DIRECTORY_SEPARATOR . $this->getThumbPath($filepath, $presetName);
                if (file_exists($path)) unlink($path);
            }
        }
    }

    /**
     * @param string $presetName
     */
    public function clearPresetThumbs($presetName)
    {
        $this->rrmdir($this->destDir . DIRECTORY_SEPARATOR . 'presets' . DIRECTORY_SEPARATOR . $presetName);
    }

    /**
     * @param string|null $presetName
     */
    public function clearPlugsThumbs($presetName = null)
    {
        if ($presetName) {
            $this->rrmdir($this->destDir . DIRECTORY_SEPARATOR . 'plugs' . DIRECTORY_SEPARATOR . $presetName);
        }
        else {
            $this->rrmdir($this->destDir . DIRECTORY_SEPARATOR . 'plugs');
        }
    }

    /**
     * @param $class
     * @param $params
     * @return IEffect
     * @throws \RuntimeException
     */
    private function create_class_array_assoc($class, $params)
    {
        $title = $class . '::__construct';

        if (!method_exists($class, '__construct') && !empty($params)) {
            throw new \RuntimeException('Call to unexisting class method: '. $title);
        }

        $refMethod = new \ReflectionMethod($class, '__construct');

        $real_params = [];

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
                throw new \RuntimeException('Call to ' . $title . ' missing parameter nr. '. ($i+1) . ': ' . $pname);
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