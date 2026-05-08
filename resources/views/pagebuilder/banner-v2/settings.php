<?php

return [
    'id'        => 'banner-v2',
    'name'      => __('BannerV2'),
    'icon'      => '<i class="icon-credit-card"></i>',
    'tab'       => "Banners",
    'fields'    => [
        [
            'id'            => 'pre_heading_image',
            'type'          => 'file',
            'class'         => '',
            'label_title'   => __('Pre heading image'),
            'label_desc'    => __('Add image'),
            'max_size'      => 4,
            'ext'    => [
                'jpg',
                'png',
                'svg',
            ],
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
            'id'            => 'all_tutor_btn_url',
            'type'          => 'text',
            'value'         => '',
            'class'         => '',
            'label_title'   => __('Primary button url'),
            'placeholder'   => __('Enter url'),
        ],
        [
            'id'            => 'all_tutor_btn_txt',
            'type'          => 'text',
            'value'         => '',
            'class'         => '',
            'label_title'   => __('Primary button text'),
            'placeholder'   => __('Enter button text'),
        ],
        [
            'id'            => 'see_demo_btn_url',
            'type'          => 'text',
            'value'         => '',
            'class'         => '',
            'label_title'   => __('Secondary button url'),
            'placeholder'   => __('Enter url'),
        ],
        [
            'id'            => 'see_demo_btn_txt',
            'type'          => 'text',
            'value'         => '',
            'class'         => '',
            'label_title'   => __('Secondary button text'),
            'placeholder'   => __('Enter button text'),
        ],
        [
            'id'            => 'image',
            'type'          => 'file',
            'class'         => '',
            'label_title'   => __('Banner image'),
            'label_desc'    => __('Add image'),
            'max_size'      => 4,
            'ext'    => [
                'jpg',
                'png',
                'svg',
            ],
        ],
        [
            'id'                => 'banner_repeater',
            'type'              => 'repeater',
            'label_title'       => __('Companies images'),
            'repeater_title'    => __('Add image'),
            'multi'             => true,
            'fields'       => [
                [
                    'id'            => 'banner_image',
                    'type'          => 'file',
                    'class'         => '',
                    'label_title'   => __('Company image'),
                    'label_desc'    => __('Add image'),
                    'max_size'      => 4,
                    'ext'    => [
                        'jpg',
                        'png',
                        'svg',
                    ]
                ]
            ]
        ],
        [
            'id'            => 'select_variation',
            'type'          => 'radio',
            'class'         => '',
            'label_title'   => __('Select banner variation'),
            'options'       => [
                'v1'   => __('v1'),
                'v2'   => __('v2'),
            ],
            'default'       => 'v2',
        ],
    ]
];
