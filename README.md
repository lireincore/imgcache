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
$ composer require lireincore/imgcache
```

## Usage

```php
use LireinCore\ImgCache\ImgCache;

$config = [
    //graphic library for all presets: gmagick, imagick, gd (default gmagick->imagick->gd)
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
    //plug for all presets
    'plug' => [
        'path' => '/path/to/my/project/assets/plug.png',
    ],
    //formats convert map for all presets
    //supported formats for source images: gif, png, jpeg, bmp, ico, webp, wbmp, tiff, svg, xbm, * (all others)
    //supported formats for destination images: gif, png, jpeg, bmp, ico, webp, wbmp, tiff, svg, xbm
    'convert' => [
        //source format => destination format
        'gif,webp' => 'png', //gif and webp to png
        '*' => 'jpeg', //all others to jpeg
    ],
    //quality of save jpeg images: 0-100 (default 75)
    'jpeg_quality' => 80,
    //compression level of save png images: 0-9 (default 7)
    'png_compression_level' => 8,
    //compression filter of save png images: 0-9 (default 5)
    'png_compression_filter' => 6,
    //register custom effects
    'effects' => [
        //effect name => class (which implements \LireinCore\ImgCache\IEffect)
        'myeffect' => '\Foo\Bar\MyEffect',
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
                        'watermark' => '/path/to/my/project/assets/watermark.png',
                        'opacity' => 80,
                        'offset_x' => 'right',
                        'offset_y' => 'bottom',
                        'width' => '50%',
                        'height' => '50%'
                    ]
                ],
            ],
            //plug for preset 'origin'
            'plug' => [
                'path' => '/path/to/my/project/assets/plug_origin.png'
            ],
        ],
        //preset 'content'
        'content' => [
            'effects' => [
                //first effect
                0 => [
                    'type' => 'overlay',
                    'params' => [
                        'watermark' => '/path/to/my/project/assets/watermark.png',
                        'opacity' => 70,
                        'offset_x' => 'right',
                        'offset_y' => 'bottom',
                        'width' => '50%',
                        'height' => '50%'
                    ]
                ],
                //second effect
                1 => [
                    'type' => 'scale',
                    'params' => [
                        'width' => '500px',
                        'height' => '500px',
                        'direct' => 'up',
                        'allow_fit' => true,
                    ]
                ],
                //third effect
                2 => [
                    'type' => 'crop',
                    'params' => [
                        'offset_x' => '50%',
                        'offset_y' => '50%',
                        'width' => '50%',
                        'height' => '50%'
                    ]
                ]
            ],
            //formats convert map for preset 'content'
            'convert' => [
                'bmp,tiff' => 'png', //bmp and tiff to png
                'gif' => 'jpeg',
            ]
        ],
        'fit' => [
            //effects list
            'effects' => [
                0 => [
                    //effect type
                    'type' => 'fit',
                    //effect params
                    'params' => [
                        'offset_x' => 'center',
                        'offset_y' => 'center',
                        'width' => '200',
                        'height' => '90',
                        'bgcolor' => '#f00',
                        'bgtransparency' => 50,
                        'allow_increase' => true,
                    ]
                ],
            ],
        ],
    ],
];

$imgcache = new ImgCache($config);

//get thumb path for image '/path/to/my/project/uploads/user/image1.jpg' (preset 'avatar')
$thumbPath = $imgcache->path('avatar', 'user/image1.jpg');
//$thumbPath: '/path/to/my/project/www/thumbs/presets/avatar/user/image1.jpg'
//if the source image is not found
//$thumbPath: '/path/to/my/project/www/thumbs/presets/avatar/user/image1.jpg'

//get thumb relative url for image '/path/to/my/project/uploads/blog/image2.jpg' (preset 'content')
$thumbRelUrl = $imgcache->url('content', 'blog/image2.jpg');
//$thumbRelUrl: '/thumbs/presets/content/blog/image2.jpg'

//get thumb absolute url for image '/path/to/my/project/uploads/news/image3.jpg' (preset 'logo')
$thumbAbsUrl = $imgcache->url('logo', 'news/image3.jpg', true);
//$thumbAbsUrl: 'https://www.mysite.com/thumbs/presets/logo/news/image3.jpg'
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.