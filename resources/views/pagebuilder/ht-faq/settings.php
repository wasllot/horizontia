<?php

return [
    'id'   => 'ht-faq',
    'name' => 'Preguntas frecuentes',
    'icon' => '<i class="icon-help-circle"></i>',
    'tab'  => 'Academia',
    'fields' => [
        ['id' => 'heading', 'type' => 'text', 'value' => 'Preguntas frecuentes', 'label_title' => 'Título', 'placeholder' => 'Título'],
        [
            'id' => 'items', 'type' => 'repeater', 'label_title' => 'Preguntas', 'repeater_title' => 'Pregunta', 'multi' => true,
            'fields' => [
                ['id' => 'question', 'type' => 'text', 'value' => '', 'label_title' => 'Pregunta', 'placeholder' => 'Escribe la pregunta'],
                ['id' => 'answer', 'type' => 'text', 'value' => '', 'label_title' => 'Respuesta', 'placeholder' => 'Escribe la respuesta (admite enlaces y negritas)'],
            ],
        ],
    ],
];
