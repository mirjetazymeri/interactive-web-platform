<?php

return [
    // List available widgets in the builder palette
    'widgets' => [
        // Simple text block
        [
            'type'   => 'text',
            'name'   => 'Text',
            'config' => [
                'align' => ['left','center','right'],
            ],
            'i18n'   => ['content' => true], // translatable field keys
        ],
        [
            'type'   => 'image',
            'name'   => 'Image',
            'config' => ['path' => null, 'alt' => null, 'link' => null, 'ratio' => null],
            'i18n'   => ['caption' => true],
        ],
        [
            'type'   => 'video',
            'name'   => 'Video',
            'config' => ['provider' => 'youtube', 'url' => null],
            'i18n'   => [],
        ],
        [
            'type'   => 'product_grid',
            'name'   => 'Product Grid',
            'config' => ['category_id' => null, 'limit' => 8, 'sort' => 'newest', 'layout' => 'grid'],
            'i18n'   => ['title' => true],
        ],
        [
            'type'   => 'banner',
            'name'   => 'Banner',
            'config' => ['bg_image' => null, 'link' => null, 'overlay' => 0.3],
            'i18n'   => ['headline'=> true, 'subheadline'=> true, 'cta'=> true],
        ],
        // Add more widgets as needed
    ],
];
