<?php

return [
    'id'        => 'get-app',
    'name'      => __('Get App'),
    'icon'      => '<i class="icon-play"></i>',
    'tab'       => "Common",
    'fields'    => [
        [
            'id'            => 'select_verient',
            'type'          => 'select',
            'class'         => '',
            'label_title'   => __('Select style verient'),
            'options'       => [
                'get-app-varient-one'        => 'Get app Style v1',
                'get-app-varient-two'        => 'Get app Style v2',
                'get-app-varient-three'      => 'Get app Style v3',
                'get-app-varient-four'       => 'Get app Style v4',
                'get-app-varient-five'       => 'Get app Style v5',
            ],
            'default'       => '',  
            'placeholder'   => __('settings.select_from_list'),  
        ],
        [
            'id'            => 'pre_heading',
            'type'          => 'text',
            'value'         => '',
            'class'         => '',
            'label_title'   => __('Pre heading'),
            'placeholder'   => __('Enter pre heading'),
        ],
        [
            'id'            => 'heading',
            'type'          => 'text',
            'value'         => '',
            'class'         => '',
            'label_title'   => __('Heading'),
            'placeholder'   => __('Enter heading'),
        ],
        [
            'id'            => 'paragraph',
            'type'          => 'editor',
            'value'         => '',
            'class'         => '',
            'label_title'   => __('Description'),
            'placeholder'   => __('Enter description'),
        ],
        [
            'id'            => 'app_store_image',
            'type'          => 'file',
            'class'         => '',
            'label_title'   => __('Apple store image'),
            'label_desc'    => __('Add image'),
            'max_size'      => 4,            
            'ext'    => [
                'jpg',
                'png',
                'svg',
            ], 
        ],
        [
            'id'            => 'google_image',
            'type'          => 'file',
            'class'         => '',
            'label_title'   => __('Play store image'),
            'label_desc'    => __('Add image'),
            'max_size'      => 4,            
            'ext'    => [
                'jpg',
                'png',
                'svg',
            ], 
        ],
        [
            'id'            => 'mobile_image',
            'type'          => 'file',
            'class'         => '',
            'label_title'   => __('Main image'),
            'label_desc'    => __('Add image'),
            'max_size'      => 4,            
            'ext'    => [
                'jpg',
                'png',
                'svg',
            ], 
        ],
    ]
];
