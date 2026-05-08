<?php

return [
    'id'        => 'marketplace-stands-out',
    'name'      => __('Marketplace Stands Out'),
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
            'id'            => 'select_verient',
            'type'          => 'select',
            'class'         => '',
            'label_title'   => __('Select style verient'),
            'options'       => [
                'marketplace-stands-out-varient-one'        => 'Marketplace Stands Out Style v1',
                'marketplace-stands-out-varient-two'        => 'Marketplace Stands Out Style v2',
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
        // 1st section
        [
            'id'            => 'section1_heading',
            'type'          => 'text',
            'value'         => '',
            'class'         => '',
            'label_title'   => __('Section one heading'),
            'placeholder'   => __('Enter heading'),
        ],
        [
            'id'            => 'section1_paragraph',
            'type'          => 'editor',
            'value'         => '',
            'class'         => '',
            'label_title'   => __('Section one description'),
            'placeholder'   => __('Enter description'),
        ],
        [
            'id'            => 'section1_video',
            'type'          => 'file',
            'class'         => '',
            'label_title'   => __('Section one video'),
            'label_desc'    => __('Add video'),
            'max_size'      => 4,                  
            'ext'    => [
                'mp4',
            ]
        ],
        // end 1st section

        // 2nd section
        [
            'id'            => 'section2_heading',
            'type'          => 'text',
            'value'         => '',
            'class'         => '',
            'label_title'   => __('Section two heading'),
            'placeholder'   => __('Enter heading'),
        ],
        [
            'id'            => 'section2_paragraph',
            'type'          => 'editor',
            'value'         => '',
            'class'         => '',
            'label_title'   => __('Section two description'),
            'placeholder'   => __('Enter description'),
        ],
        [
            'id'            => 'section2_image',
            'type'          => 'file',
            'class'         => '',
            'label_title'   => __('Section two image'),
            'label_desc'    => __('Add image'),
            'max_size'      => 4,                  
            'ext'    => [
                'jpg',
                'png',
                'svg',
            ]
        ],
        // end 2nd section

        // 3rd section
        [
            'id'            => 'section3_image',
            'type'          => 'file',
            'class'         => '',
            'label_title'   => __('Section three image'),
            'label_desc'    => __('Add image'),
            'max_size'      => 4,                  
            'ext'    => [
                'jpg',
                'png',
                'svg',
            ]
        ],
        [
            'id'            => 'section3_heading',
            'type'          => 'text',
            'value'         => '',
            'class'         => '',
            'label_title'   => __('Section three heading'),
            'placeholder'   => __('Enter heading'),
        ],
        [
            'id'            => 'section3_paragraph',
            'type'          => 'editor',
            'value'         => '',
            'class'         => '',
            'label_title'   => __('Section three description'),
            'placeholder'   => __('Enter description'),
        ],
        // end 3rd section
    ]
];
