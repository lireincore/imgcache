# Image effect, thumb and cache

[![Latest Stable Version](https://poser.pugx.org/lireincore/imgcache/v/stable)](https://packagist.org/packages/lireincore/imgcache)
[![Total Downloads](https://poser.pugx.org/lireincore/imgcache/downloads)](https://packagist.org/packages/lireincore/imgcache)
[![License](https://poser.pugx.org/lireincore/imgcache/license)](https://packagist.org/packages/lireincore/imgcache)

## About

Image effect, thumb and cache for PHP.

## Install

Add the `lireincore/imgcache` package to your `require` section in the `composer.json` file

or

``` bash
$ composer require lireincore/imgcache dev-master
```

## Usage

```php
use LireinCore\ImgCache\ImgCache;

$config = [
    //graphic library for all presets: gmagick, imagick, gd (default: gmagick->imagick->gd)
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
    //define image class for all presets (which implements \LireinCore\ImgCache\IImage)
    'image' => '\Foo\Bar\MyImageClass',
    //register custom effects
    //(default effects: crop, resize, scale, rotate, overlay, flip, fit, blur, gamma, grayscale, negative)
    'effects' => [
        //effect name => class (which implements \LireinCore\ImgCache\IEffect) (default: \LireinCore\ImgCache\Image)
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
            //define image class for preset 'origin' (which implements \LireinCore\ImgCache\IImage)
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

//get thumb path for image '/path/to/my/project/backend/uploads/user/image.jpg' (preset 'origin')
$thumbPath = $imgcache->path('origin', 'user/image.jpg');
//$thumbPath: '/path/to/my/project/backend/www/thumbs/presets/origin/user/image.jpg'
//if the source image is not found
//$thumbPath: '/path/to/my/project/backend/www/thumbs/plugs/origin/plug_origin.png'

//get thumb relative url for image '/path/to/my/project/uploads/blog/image.jpg' (preset 'content_preview')
$thumbRelUrl = $imgcache->url('content_preview', 'blog/image.jpg');
//$thumbRelUrl: '/thumbs/presets/content_preview/blog/image.jpg'

//get thumb absolute url for image '/path/to/my/project/uploads/news/image.jpg' (preset 'test')
$thumbAbsUrl = $imgcache->url('test', 'news/image.jpg', true);
//$thumbAbsUrl: 'https://www.mysite.com/thumbs/presets/test/news/image.jpg'
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.