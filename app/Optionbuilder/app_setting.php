<?php


return [
    'section' => [
        'id'     => '_app',
        'label'  => __('settings.app_settings'),
        'icon'   => '',
    ],
    'fields' => [

        [
            'id'            => 'app_logo',
            'type'          => 'file',
            'class'         => '',
            'label_title'   => __('settings.app_logo'),
            'field_desc'    => __('settings.add_app_logo'),
            'max_size'   => 3,
            'ext'    => [
                'jpg',
                'png',
                'svg',
            ],
        ],
        [
            'id'            => 'app_splash',
            'type'          => 'file',
            'class'         => '',
            'label_title'   => __('settings.app_splash'),
            'field_desc'    => __('settings.add_app_splash'),
            'max_size'   => 3,                  // size in MB
            'ext'    => [
                'jpg',
                'png',
                'svg',
            ],
        ],
        [
            'id'            => 'app_bg_color',
            'type'          => 'colorpicker',
            'value'         => '#FBF9F4',
            'class'         => '',
            'label_title'   => __('settings.app_bg_color'),
            'field_desc'    => __('settings.app_bg_color_desc'),
        ],
        [
            'id'            => 'app_pri_color',
            'type'          => 'colorpicker',
            'value'         => '#FCCF14',
            'class'         => '',
            'label_title'   => __('settings.app_primary_color'),
        ],
        [
            'id'            => 'app_sec_color',
            'type'          => 'colorpicker',
            'value'         => '#585858',
            'class'         => '',
            'label_title'   => __('settings.app_secondary_color'),
        ],
        [
            'id'            => 'app_card_bg_color',
            'type'          => 'colorpicker',
            'value'         => '#FFFFFF',
            'class'         => '',
            'label_title'   => __('settings.card_bg_color'),
        ],
    ]
];
