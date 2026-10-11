<?php

return [
    'id'   => 'ht-cta',
    'name' => 'Llamado a la acción',
    'icon' => '<i class="icon-zap"></i>',
    'tab'  => 'Academia',
    'fields' => [
        ['id' => 'heading', 'type' => 'text', 'value' => '¿Listo para empezar?', 'label_title' => 'Título', 'placeholder' => 'Título'],
        ['id' => 'text', 'type' => 'text', 'value' => 'Elige tu curso y comienza a aprender hoy mismo.', 'label_title' => 'Texto', 'placeholder' => 'Texto'],
        ['id' => 'button_text', 'type' => 'text', 'value' => 'Ver cursos', 'label_title' => 'Texto del botón', 'placeholder' => 'Texto del botón'],
        ['id' => 'button_url', 'type' => 'text', 'value' => '/cursos', 'label_title' => 'URL del botón', 'placeholder' => '/cursos'],
        ['id' => 'style', 'type' => 'select', 'label_title' => 'Estilo', 'options' => ['gold' => 'Dorado', 'navy' => 'Azul oscuro'], 'value' => 'gold'],
    ],
];
