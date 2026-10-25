<?php

use App\Models\ContentPage;
use App\Models\Course;
use App\Models\Master;
use App\Models\Masterclass;
use App\Models\Product;

return [

    'disk' => env('MEDIA_DISK', 'public'),

    'managed_directories' => [
        'masterclasses',
        'masters',
        'products',
        'courses',
        'content',
    ],

    'protected_prefixes' => [
        'images',
        'assets',
        'css',
        'js',
        'fonts',
        'vendor',
    ],

    'models' => [
        Masterclass::class => ['image'],
        Product::class => ['image', 'gallery'],
        Master::class => ['photo'],
        Course::class => ['image'],
        ContentPage::class => ['image'],
    ],

];
