<?php

return [
    'id'   => 'ht-rich-text',
    'name' => 'Texto enriquecido',
    'icon' => '<i class="icon-file-text"></i>',
    'tab'  => 'Academia',
    'fields' => [
        ['id' => 'heading', 'type' => 'text', 'value' => 'Título de la sección', 'label_title' => 'Título', 'placeholder' => 'Escribe el título'],
        ['id' => 'subheading', 'type' => 'text', 'value' => '', 'label_title' => 'Subtítulo (opcional)', 'placeholder' => 'Escribe un subtítulo'],
        ['id' => 'content', 'type' => 'editor', 'value' => '<p>Escribe aquí tu contenido. Puedes usar títulos, listas, enlaces e imágenes.</p>', 'label_title' => 'Contenido', 'placeholder' => 'Contenido'],
        ['id' => 'width', 'type' => 'select', 'label_title' => 'Ancho del contenido', 'options' => ['narrow' => 'Estrecho (lectura cómoda)', 'normal' => 'Normal', 'wide' => 'Ancho'], 'value' => 'narrow'],
    ],
];
