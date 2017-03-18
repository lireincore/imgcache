# Image effect, thumb and cache

[![Latest Stable Version](https://poser.pugx.org/lireincore/imgcache/v/stable)](https://packagist.org/packages/lireincore/imgcache)
[![Total Downloads](https://poser.pugx.org/lireincore/imgcache/downloads)](https://packagist.org/packages/lireincore/imgcache)
[![License](https://poser.pugx.org/lireincore/imgcache/license)](https://packagist.org/packages/lireincore/imgcache)

## About

Image effect, thumb and cache. Similar to imgcache in Drupal. Supports GD, Imagick and Gmagick.

## Install

Add the `"lireincore/imgcache": "dev-master"` package to your `require` section in the `composer.json` file

or

``` bash
$ composer require lireincore/imgcache dev-master
```

## Usage

### ImgCache

```php
use LireinCore\ImgCache\ImgCache;

$config = [
    //graphic library for all presets: imagick, gd, gmagick (by default, tries to use: imagick->gd->gmagick)
    'driver' => 'gmagick',
    
    //original images source directory for all presets
    'srcdir' => '/path/to/my/project/uploads',
    
    //thumbs destination directory for all presets
    //(to access the thumbs from the web they should be in a directory accessible from the web)
    'destdir' => '/path/to/my/project/www/thumbs',
    
    //web directory for all presets
    'webdir' => '/path/to/my/project/www',
    
    //base url for all presets
    'baseurl' => 'https://www.mysite.com',
    
    //quality of save jpeg images for all presets: 0-100 (default: 75)
    'jpeg_quality' => 80,
    
    //compression level of save png images for all presets: 0-9 (default: 7)
    'png_compression_level' => 8,
    
    //compression filter of save png images for all presets: 0-9 (default: 5)
    'png_compression_filter' => 6,
    
    //formats convert map for all presets
    //supported formats for destination images: jpeg, png, gif, wbmp, xbm
    //(default: ['jpeg' => 'jpeg', 'png' => 'png', 'gif' => 'gif', 'wbmp' => 'wbmp', 'xbm' => 'xbm', '*' => 'png'])
    'convert' => [
        //source format => destination format
        'gif,wbmp' => 'png', //gif and wbmp to png
        '*' => 'jpeg' //all others to jpeg
    ],
    
    //plug for all presets
    'plug' => [
        //absolute path to plug
        'path' => '/path/to/my/project/assets/plug.png',
        
        //apply preset effects? (default: true)
        'effects' => true,
    ],
    
    //define custom image class for all presets (which implements \LireinCore\ImgCache\IImage)
    //(default: \LireinCore\ImgCache\Image)
    'image' => '\Foo\Bar\MyImageClass',
    
    //register custom effects
    //(default effects: crop, resize, scale, rotate, overlay, flip, fit, blur, gamma, grayscale, negative)
    'effects' => [
        //effect name => class (which implements \LireinCore\ImgCache\IEffect)
        'myeffect' => '\Foo\Bar\MyEffect'
    ],
    
    //presets list
    'presets' => [
        //preset 'origin'
        'origin' => [
            //effects list
            'effects' => [
                0 => [
                    //effect type
                    'type' => 'overlay',
                    //effect params
                    'params' => [
                        'path' => '/path/to/my/project/assets/watermark.png',
                        'opacity' => 80,
                        'offset_x' => 'right',
                        'offset_y' => 'bottom',
                        'width' => '50%',
                        'height' => '50%'
                    ]
                ],
            ],
            
            //you can override some of the options for this preset
            
            //graphic library for preset 'origin'
            'driver' => 'gd',
            
            //original images source directory for preset 'origin'
            'srcdir' => '/path/to/my/project/backend/uploads',
            
            //thumbs destination directory for preset 'origin'
            //(to access the thumbs from the web they should be in a directory accessible from the web)
            'destdir' => '/path/to/my/project/backend/www/thumbs',
            
            //web directory for preset 'origin'
            'webdir' => '/path/to/my/project/backend/www',
            
            //base url for preset 'origin'
            'baseurl' => 'https://admin.mysite.com',
            
            //quality of save jpeg images for preset 'origin'
            'jpeg_quality' => 100,
            
            //compression level of save png images for preset 'origin'
            'png_compression_level' => 9,
            
            //compression filter of save png images for preset 'origin'
            'png_compression_filter' => 9,
            
            //plug for preset 'origin'
            'plug' => [
                'path' => '/path/to/my/project/backend/assets/plug_origin.png',
                'effects' => false
            ],
            
            //define custom image class for preset 'origin' (which implements \LireinCore\ImgCache\IImage)
            'image' => '\Foo\Bar\MyOriginImage',
        ],
        
        //preset 'content_preview'
        'content_preview' => [
            'effects' => [
                //first effect
                0 => [
                    'type' => 'scale',
                    'params' => [
                        'width' => '500px',
                        'height' => '500px',
                        'direct' => 'up',
                        'allow_fit' => true
                    ]
                ],
                //second effect
                1 => [
                    'type' => 'crop',
                    'params' => [
                        'offset_x' => '50%',
                        'offset_y' => '50%',
                        'width' => '50%',
                        'height' => '50%'
                    ]
                ],
                //third effect
                2 => [
                    'type' => 'gamma',
                    'params' => [
                        'correction' => 0.8
                    ]
                ],
                //fourth effect
                3 => [
                    'type' => 'blur',
                    'params' => [
                        'sigma' => 3
                    ]
                ]
            ],
            //formats convert map for preset 'content', extend convert map for all presets
            'convert' => [
                'xbm' => 'png', //xbm to png
                'gif' => 'jpeg'
            ],
            'plug' => [
                //url to get the plug from a third-party service (works only for function url())
                'url' => 'http://placehold.it/100x100'
            ],
        ],
        
        //preset 'test'
        'test' => [
            'effects' => [
                0 => [
                    'type' => 'grayscale',
                ],
                1 => [
                    'type' => 'fit',
                    'params' => [
                        'offset_x' => 'center',
                        'offset_y' => 'center',
                        'width' => '200',
                        'height' => '90',
                        'bgcolor' => '#f00',
                        'bgtransparency' => 50,
                        'allow_increase' => true
                    ]
                ]
            ],
        ],
        
        //preset 'test2'
        'test2' => [
            'effects' => [
                0 => [
                    'type' => 'negative'
                ],
                1 => [
                    'type' => 'flip',
                    'params' => [
                        'mode' => 'horizontal'
                    ]
                ],
                2 => [
                    'type' => 'resize',
                    'params' => [
                        'width' => '100',
                        'height' => '100'
                    ]
                ],
                3 => [
                    'type' => 'rotate',
                    'params' => [
                        'angle' => 90,
                        'bgcolor' => '#fff',
                        'bgtransparency' => 70
                    ]
                ]
            ],
        ],
    ],
];

$imgcache = new ImgCache($config);

//get thumb path for image '{srcdir}/user/image.jpg' (preset 'origin')
$thumbPath = $imgcache->path('origin', 'user/image.jpg');
//$thumbPath: '{destdir}/presets/origin/user/image.jpg'
//if the source image is not found
//$thumbPath: '{destdir}/plugs/origin/plug_origin.png'

//get thumb relative url for image '{srcdir}/blog/image.jpg' (preset 'content_preview')
$thumbRelUrl = $imgcache->url('content_preview', 'blog/image.jpg');
//$thumbRelUrl: '/thumbs/presets/content_preview/blog/image.jpg'

//get thumb absolute url for image '{srcdir}/news/image.jpg' (preset 'test')
$thumbAbsUrl = $imgcache->url('test', 'news/image.jpg', true);
//$thumbAbsUrl: '{baseurl}/thumbs/presets/test/news/image.jpg'

//register custom effect
$imgcache->registerEffect('effectName', '\My\Effects\EffectClass');

//unregister effect
$imgcache->unregisterEffect('effectName');

//clear all file thumbs ($path should be specified as path() and url())
$imgcache->clearFileThumbs($path);

//clear all preset thumbs
$imgcache->clearPresetThumbs('presetName');
```

### Image

```php
//Use basic effects
use LireinCore\ImgCache\Image;

$image = (new Image())
    ->open('/path/to/image.jpg')
    ->resize(1000, 500)
    ->grayscale()
    ->blur(2)
    ->save('/path/to/new_image.png', ['format' => 'png', 'png_compression_level' => 7]);

//Also you can add extended effects
use LireinCore\ImgCache\Image;
use LireinCore\ImgCache\Effects\Overlay;
use LireinCore\ImgCache\Effects\Scale;
use LireinCore\ImgCache\Effects\Fit;

$image = (new Image(IImage::DRIVER_GD))
    ->open('/path/to/image.jpg')
    ->apply(new Overlay('/path/to/watermark.png', 70, 'right', 'bottom', '50%', '50%'))
    ->grayscale()
    ->apply(new Scale('50%', '50%', 'up', true))
    ->apply(new Fit('center', 'center', '200', '90', '#f00', 20, true))
    ->save('/path/to/new_image.jpg');
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.