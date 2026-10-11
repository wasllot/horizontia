<?php

return [
    'id'   => 'ht-video',
    'name' => 'Video',
    'icon' => '<i class="icon-video"></i>',
    'tab'  => 'Academia',
    'fields' => [
        ['id' => 'heading', 'type' => 'text', 'value' => '', 'label_title' => 'Título (opcional)', 'placeholder' => 'Título'],
        ['id' => 'video_url', 'type' => 'text', 'value' => '', 'label_title' => 'Enlace de YouTube o Vimeo', 'placeholder' => 'https://www.youtube.com/watch?v=…'],
        ['id' => 'caption', 'type' => 'text', 'value' => '', 'label_title' => 'Pie de video (opcional)', 'placeholder' => 'Descripción'],
    ],
];
