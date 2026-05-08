<?php

return [
    'id'        => 'achievements',
    'name'      => __('Achievements'),
    'icon'      => '<i class="icon-award"></i>',
    'tab'       => 'About-us',
    'fields'    => [
        [
            'id'            => 'shape_image',
            'type'          => 'file',
            'class'         => '',
            'label_title'   => __('First shape image'),
            'label_desc'    => __('Add image'),
            'max_size'      => 4,               
            'ext'    => [
                'jpg',
                'png',
                'svg',
            ], 
        ],
        [
            'id'            => 'shape_second_image',
            'type'          => 'file',
            'class'         => '',
            'label_title'   => __('Second shape image'),
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
            'label_title'   => __('Pre Heading'),
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
            'id'                => 'repeater_data',
            'type'              => 'repeater',
            'label_title'       => __('Data'),
            'repeater_title'    => __('Data'),
            'multi'             => true,
            'fields'       => [
                [
                    'id'            => 'icon',
                    'type'          => 'text',
                    'value'         => '',
                    'class'         => '',
                    'label_title'   => __('Add icon'),
                    'placeholder'   => __('<i class="fa-solid fa-arrow-up-right-from-square"></i>'),
                ],
                [
                    'id'            => 'sub_heading',
                    'type'          => 'text',
                    'value'         => '',
                    'class'         => '',
                    'label_title'   => __('Sub heading'),
                    'placeholder'   => __('Enter sub heading'),
                ],
            ],
        ],
    ]
];
