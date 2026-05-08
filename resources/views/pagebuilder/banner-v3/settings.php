<?php

return [
    'id'        => 'banner-v3',
    'name'      => __('BannerV3'),
    'icon'      => '<i class="icon-credit-card"></i>',
    'tab'       => "Banners",
    'fields'    => [
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
            'label_title'   => __('Primary button URL'),
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
            'label_title'   => __('Secondary button URL'),
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
            'id'                => 'banner_repeater',
            'type'              => 'repeater',
            'label_title'       => __('Companies banner'),
            'repeater_title'    => __('Add image'),
            'multi'             => true,
            'fields'       => [
                [
                    'id'            => 'banner_image',
                    'type'          => 'file',
                    'class'         => '',
                    'label_title'   => __('Companies image'),
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
            'id'            => 'left_image',
            'type'          => 'file',
            'class'         => '',
            'label_title'   => __('Left image'),
            'label_desc'    => __('Add image'),
            'max_size'      => 4,
            'ext'    => [
                'jpg',
                'png',
                'svg',
            ],
        ],
        [
            'id'            => 'video',
            'type'          => 'file',
            'class'         => '',
            'label_title'   => __('Banner video'),
            'label_desc'    => __('Add video'),
            'max_size'      => 15,
            'ext'    => [
                'mp4',
            ],
        ],
        [
            'id'            => 'wright_image',
            'type'          => 'file',
            'class'         => '',
            'label_title'   => __('Right image'),
            'label_desc'    => __('Add image'),
            'max_size'      => 4,
            'ext'    => [
                'jpg',
                'png',
                'svg',
            ],
        ],
        [
            'id'            => 'allen_image',
            'type'          => 'file',
            'class'         => '',
            'label_title'   => __('Image'),
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
