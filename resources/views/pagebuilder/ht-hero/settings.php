<?php

return [
    'id'   => 'ht-hero',
    'name' => 'Encabezado de página',
    'icon' => '<i class="icon-layout"></i>',
    'tab'  => 'Academia',
    'fields' => [
        ['id' => 'pre_heading', 'type' => 'text', 'value' => '', 'label_title' => 'Etiqueta superior', 'placeholder' => 'Ej.: Información del curso'],
        ['id' => 'heading', 'type' => 'text', 'value' => 'Título principal', 'label_title' => 'Título', 'placeholder' => 'Título'],
        ['id' => 'text', 'type' => 'text', 'value' => '', 'label_title' => 'Descripción', 'placeholder' => 'Descripción breve'],
        ['id' => 'button_text', 'type' => 'text', 'value' => '', 'label_title' => 'Texto del botón', 'placeholder' => 'Ej.: Ver cursos'],
        ['id' => 'button_url', 'type' => 'text', 'value' => '', 'label_title' => 'URL del botón', 'placeholder' => '/cursos'],
        ['id' => 'style', 'type' => 'select', 'label_title' => 'Estilo', 'options' => ['navy' => 'Azul oscuro', 'gold' => 'Dorado', 'light' => 'Claro'], 'value' => 'navy'],
    ],
];
