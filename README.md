# Image cache

[![Latest Stable Version](https://poser.pugx.org/lireincore/imgcache/v/stable)](https://packagist.org/packages/lireincore/imgcache)
[![Total Downloads](https://poser.pugx.org/lireincore/imgcache/downloads)](https://packagist.org/packages/lireincore/imgcache)
[![License](https://poser.pugx.org/lireincore/imgcache/license)](https://packagist.org/packages/lireincore/imgcache)

## About

Adds caching capabilities to the package [lireincore/image](https://github.com/lireincore/image)

Also, you can use a special extension [lireincore/yii2-imgcache](https://github.com/lireincore/yii2-imgcache) that integrates this package with Yii2 framework.

## Install

Add the `"lireincore/imgcache": "~0.2.0"` package to your `require` section in the `composer.json` file

or

``` bash
$ php composer.phar require lireincore/imgcache
```

## Usage

```php
use LireinCore\ImgCache\ImgCache;

$config = [
    //graphic library for all presets: imagick, gd, gmagick (by default, tries to use: imagick->gd->gmagick)
    'driver' => 'gmagick',
    
    //original images source directory for all presets
    'srcdir' => '/path/to/my/project/uploads',
    
    //thumbs destination directory for all presets (required)
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
    'convert_map' => [
        //source format => destination format
        'gif,wbmp' => 'png', //gif and wbmp to png
        '*' => 'jpeg' //all others to jpeg
    ],
    
    //plug for all presets (used if original image is not available)
    'plug' => [
        //absolute path to plug
        'path' => '/path/to/my/project/assets/plug.png',
        
        //apply preset effects and postprocessors to plug? (default: false)
        'process' => true,
    ],
    
    //define custom image class for all presets (which implements \LireinCore\Image\ImageInterface)
    //(default: \LireinCore\Image\Image)
    'image_class' => '\Foo\Bar\MyImageClass',

    //register custom effects or override default effects
    //(default effects: crop, cover, resize, scale_up, scale_down, scale, rotate, overlay, flip, fit, blur, gamma, grayscale, negative, text)
    'effects_map' => [
        //effect => class (which implements \LireinCore\Image\EffectInterface)
        'myeffect1' => '\Foo\Bar\MyEffect1',
        'myeffect2' => '\Foo\Bar\MyEffect2'
    ],

    //register custom postprocessors or override default postprocessors
    //(default postprocessors: jpegoptim, optipng, pngquant)
    'postprocessors_map' => [
        //postprocessor => class (which implements \LireinCore\Image\PostProcessorInterface)
        'my_postprocessor1' => '\Foo\Bar\MyPostProcessor1',
        'my_postprocessor2' => '\Foo\Bar\MyPostProcessor2'
    ],

    //postprocessors list
    'postprocessors' => [
        [
            //postprocessor type
            'type' => 'jpegoptim',
            //postprocessor params
            'params' => [
                'path' => '/path/to/jpegoptim', // custom path to postprocessor binary (default: '/usr/bin/jpegoptim')
                'quality' => 75, // for example: 0-100, 0 - worst | 100 - best (default: 85)
                'strip_all' => false, // remove all metadata (Comments, Exif, IPTC, ICC, XMP) (default: true)
                'progressive' => false // convert to progressive jpeg (default: true)
            ]
        ],
        [
            //postprocessor type
            'type' => 'optipng',
            //postprocessor params
            'params' => [
                'path' => '/path/to/optipng', // custom path to postprocessor binary (default: '/usr/bin/optipng')
                'level' => 5, // for example: 0-7, 0 - maximum compression speed | 7 - maximum compression size (default: 2)
                'strip_all' => false // remove all metadata (default: true)
            ]
        ]
    ],
    
    //presets list
    'presets' => [
        //preset 'origin'
        'origin' => [
            //effects list
            'effects' => [
                [
                    //effect type
                    'type' => 'overlay',
                    //effect params
                    'params' => [
                        'path' => '/path/to/my/project/assets/watermark.png', // path to overlay
                        'opacity' => 80, // overlay opacity, for example: 0-100, 0 - fully transparent | 100 - not transparent (default: 100) (not supported in gmagick)
                        'offset_x' => 'right', // overlay horizontal offset, for example: 100 | 20% | center | left | right  (default: right)
                        'offset_y' => 'bottom', // overlay vertical offset, for example: 100 | 20% | center | top | bottom  (default: bottom)
                        'width' => '50%', // overlay width, for example: 100 | 20% - change overlay image width (% - relative to the background image) (default: original size)
                        'height' => '50%' // overlay height, for example: 100 | 20% - change overlay image height (% - relative to the background image) (default: original size)
                    ]
                ],
            ],
            
            //you can override certain options for this preset
            
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
            
            //plug for preset 'origin' (used if original image is not available)
            'plug' => [
                'path' => '/path/to/my/project/backend/assets/plug_origin.png',
            ],
            
            //define custom image class for preset 'origin' (which implements \LireinCore\Image\ImageInterface)
            'image_class' => '\Foo\Bar\MyOriginImage',

            //postprocessors list for preset 'origin'
            'postprocessors' => [
                [
                    //postprocessor type
                    'type' => 'pngquant',
                    //postprocessor params
                    'params' => [
                        'path' => '/path/to/pngquant', // custom path to postprocessor binary (default: '/usr/bin/pngquant')
                        'quality' => 75, // for example: 0-100, 0 - worst | 100 - best (default: 85)
                    ]
                ]
            ],
        ],
        
        //preset 'content_preview'
        'content_preview' => [
            'effects' => [
                //first effect
                [
                    'type' => 'scale_up',
                    'params' => [
                        'max_width' => '500', // for example: 100 | 20% (default: auto)
                        'max_height' => '500', // for example: 100 | 20% (default: auto)
                        'allow_increase' => true // increase if image is less (default: false)
                    ]
                ],
                //second effect
                [
                    'type' => 'crop',
                    'params' => [
                        'offset_x' => '50%', // for example: 100 | 20% | center | left | right (default: left)
                        'offset_y' => '50%', // for example: 100 | 20% | center | top | bottom (default: top)
                        'width' => '50%', // for example: 100 | 20% (default: auto)
                        'height' => '50%' // for example: 100 | 20% (default: auto)
                    ]
                ],
                //third effect
                [
                    'type' => 'gamma',
                    'params' => [
                        'correction' => 0.8 // gamma correction (0.0-1.0)
                    ]
                ],
                //fourth effect
                [
                    'type' => 'blur',
                    'params' => [
                        'sigma' => 3 // standard deviation
                    ]
                ]
            ],
            //formats convert map for preset 'content', extend convert map for all presets
            'convert_map' => [
                'xbm' => 'png', //xbm to png
                'gif' => 'jpeg'
            ],
            'plug' => [
                //url to get the plug from a third-party service
                'url' => 'http://placehold.it/100x100'
            ],
        ],
        
        //preset 'test'
        'test' => [
            'effects' => [
                [
                    'type' => 'grayscale',
                ],
                [
                    'type' => 'fit',
                    'params' => [
                        'offset_x' => 'center', // for example: 100 | 20% | center | left | right (default: center)
                        'offset_y' => 'center', // for example: 100 | 20% | center | top | bottom (default: center)
                        'width' => '200', // for example: 100 | 20% (default: auto)
                        'height' => '90', // for example: 100 | 20% (default: auto)
                        'bgcolor' => '#f00', // background color, for example: '#fff' or '#ffffff' - hex | '50,50,50' - rgb | '50,50,50,50' - cmyk (default: #fff)
                        'bgtransparency' => 50, // background transparency, for example: 0-100, 0 - not transparent | 100 - fully transparent (default: 0) (not supported in gmagick)
                        'allow_increase' => true // increase if image is less (default: false)
                    ]
                ]
                [
                    'type' => 'text',
                    'params' => [
                        'text' => 'Hello word!', // text for writing
                        'font' => '/path/to/font', // font name or absolute path to the font file, for example: Verdana (default: Times New Roman)
                        'offset_x' => '5%', // for example: 100 | 20% (default: 0)
                        'offset_y' => '10', // for example: 100 | 20% (default: 0)
                        'size' => 14, // font size, for example: 14 (default: 12)
                        'color' => '#000', // font color, for example: '#fff' or '#ffffff' - hex | '50,50,50' - rgb | '50,50,50,50' - cmyk (default: #fff)
                        'opacity' => 50, // font opacity, for example: 0-100, 0 - fully transparent | 100 - not transparent (default: 100)
                        'angle' => 30, // in degrees, for example: 90 (default: 0)
                        'width' => '90%', // for example: 100 | 20% - text box width (% - relative to the background image) (default: none)
                    ]
                ]
            ],
        ],
        
        //preset 'test2'
        'test2' => [
            'effects' => [
                [
                    'type' => 'negative'
                ],
                [
                    'type' => 'flip',
                    'params' => [
                        'mode' => 'horizontal' // for example: vertical | horizontal | full
                    ]
                ],
                [
                    'type' => 'resize',
                    'params' => [
                        'width' => '100', // 100 | 20% (default: auto)
                        'height' => '100' // 100 | 20% (default: auto)
                    ]
                ],
                [
                    'type' => 'rotate',
                    'params' => [
                        'angle' => 90, // angle in degrees
                        'bgcolor' => '#f00', // background color, for example: '#fff' or '#ffffff' - hex | '50,50,50' - rgb | '50,50,50,50' - cmyk (default: #fff)
                        'bgtransparency' => 70 // background transparency, for example: 0-100, 0 - not transparent | 100 - fully transparent (default: 0) (not supported in gmagick)
                    ]
                ],
                [
                    'type' => 'scale',
                    'params' => [
                        'ratio' => '200%', // (for example: 0.5 | 50%)
                    ]
                ],
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

//clear all file thumbs ($path should be specified as path() and url())
$imgcache->clearFileThumbs($path);

//clear all preset thumbs
$imgcache->clearPresetThumbs('presetName');
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.