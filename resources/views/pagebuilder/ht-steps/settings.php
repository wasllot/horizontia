<?php

return [
    'id'   => 'ht-steps',
    'name' => 'Pasos numerados',
    'icon' => '<i class="icon-list"></i>',
    'tab'  => 'Academia',
    'fields' => [
        ['id' => 'heading', 'type' => 'text', 'value' => 'Cómo funciona', 'label_title' => 'Título', 'placeholder' => 'Título'],
        [
            'id' => 'items', 'type' => 'repeater', 'label_title' => 'Pasos', 'repeater_title' => 'Paso', 'multi' => true,
            'fields' => [
                ['id' => 'title', 'type' => 'text', 'value' => '', 'label_title' => 'Título', 'placeholder' => 'Título del paso'],
                ['id' => 'text', 'type' => 'text', 'value' => '', 'label_title' => 'Descripción', 'placeholder' => 'Descripción'],
            ],
        ],
    ],
];
