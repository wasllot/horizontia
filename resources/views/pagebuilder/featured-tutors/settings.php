<?php

return [
    'id'        => 'featured-tutors',
    'name'      => __('Feature tutors'),
    'icon'      => '<i class="icon-gift"></i>',
    'tab'       => "Common",
    'fields'    => [
        [
            'id'            => 'shape_image',
            'type'          => 'file',
            'class'         => '',
            'label_title'   => __('Background shape image'),
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
            'id'            => 'view_tutor_btn_url',
            'type'          => 'text',
            'value'         => '',
            'class'         => '',
            'label_title'   => __('Primary button url'),
            'placeholder'   => __('Enter url'),
        ],
        [
            'id'            => 'view_tutor_btn_text',
            'type'          => 'text',
            'value'         => '',
            'class'         => '',
            'label_title'   => __('Primary button text'),
            'placeholder'   => __('Enter button text'),
        ],
    ]
];
