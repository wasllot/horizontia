<?php

return [
    'id'   => 'ht-course-info',
    'name' => 'Datos y compra del curso',
    'icon' => '<i class="icon-shopping-cart"></i>',
    'tab'  => 'Academia',
    'fields' => [
        ['id' => 'heading', 'type' => 'text', 'value' => '', 'label_title' => 'Título (opcional)', 'placeholder' => 'Se usa el nombre del curso si lo dejas vacío'],
        ['id' => 'show_price', 'type' => 'select', 'label_title' => 'Mostrar precio y botón de compra', 'options' => ['1' => 'Sí', '0' => 'No'], 'value' => '1'],
    ],
];
