<?php

return [
    'id'        => 'revolutionize',
    'name'      => __('Revolutionize'),
    'icon'      => '<i class="icon-clipboard"></i>',
    'tab'       => "Common",
    'fields'    => [
        [
            'id'            => 'section_title_variation',
            'type'          => 'select',
            'class'         => '',
            'label_title'   => __('Section title variation'),
            'options'       => [
                'am-section_title_one'      => __('Classic'),
                'am-section_title_two'      => __('Traditional'),
                'am-section_title_three'    => __('Modern'),
            ],
            'default'       => '',
        ],
        [
            'id'            => 'video',
            'type'          => 'file',
            'class'         => '',
            'label_title'   => __('Add Section Video'),
            'label_desc'    => __('Add video'),
            'max_size'      => 4,            
            'ext'    => [
                'mp4',
                'flv',
                'webm',
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
            'id'                => 'revolutionize_repeater',
            'type'              => 'repeater',
            'label_title'       => __('Revolutionize data'),
            'repeater_title'    => __('Add data'),
            'multi'             => true,
            'fields'       => [
                [
                    'id'            => 'revolu_image',
                    'type'          => 'file',
                    'class'         => '',
                    'label_title'   => __('Shape image'),
                    'label_desc'    => __('Add image'),
                    'max_size'      => 4,                  
                    'ext'    => [
                        'jpg',
                        'png',
                        'svg',
                    ]
                ],
                [
                    'id'            => 'revolu_heading',
                    'type'          => 'text',
                    'value'         => '',
                    'class'         => '',
                    'label_title'   => __('Heading'),
                    'placeholder'   => __('Enter heading'),
                ],
                [
                    'id'            => 'revolu_paragraph',
                    'type'          => 'editor',
                    'value'         => '',
                    'class'         => '',
                    'label_title'   => __('Description'),
                    'placeholder'   => __('Enter description'),
                ],
            ]
        ],
        [
            'id'            => 'bg_image',
            'type'          => 'file',
            'class'         => '',
            'label_title'   => __('Background image'),
            'label_desc'    => __('Add image'),
            'max_size'      => 4,                  
            'ext'    => [
                'jpg',
                'png',
                'svg',
            ]
        ],
    ]
];
