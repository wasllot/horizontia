<?php

return [
    'id'        => 'user-guide',
    'name'      => __('User guide'),
    'icon'      => '<i class="icon-user"></i>',
    'tab'       => "Common",
    'fields'    => [
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
            'value'         => 'Comprehensive Support at Every Step',
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
            'id'            => 'sub_heading',
            'type'          => 'text',
            'value'         => '',
            'class'         => '',
            'label_title'   => __('Pre Heading'),
            'placeholder'   => __('Enter pre heading'),
        ],
        [
            'id'            => 'second_heading',
            'type'          => 'text',
            'value'         => 'Our Experts Will Guide You to Mastery',
            'class'         => '',
            'label_title'   => __('Heading'),
            'placeholder'   => __('Enter heading'),
        ],
        [
            'id'            => 'second_paragraph',
            'type'          => 'editor',
            'value'         => '',
            'class'         => '',
            'label_title'   => __('Description'),
            'placeholder'   => __('Enter description'),
        ],
        [
            'id'            => 'right_image',
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
    ]
];
