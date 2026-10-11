<?php

$categories = ['' => 'Todas las categorías'];
try {
    foreach (\Modules\Courses\Models\Category::whereNull('parent_id')->orderBy('name')->pluck('name', 'id') as $id => $name) {
        $categories[(string) $id] = $name;
    }
} catch (\Throwable $e) {
}

return [
    'id'   => 'ht-courses',
    'name' => 'Cursos destacados',
    'icon' => '<i class="icon-book-open"></i>',
    'tab'  => 'Academia',
    'fields' => [
        ['id' => 'heading', 'type' => 'text', 'value' => 'Cursos destacados', 'label_title' => 'Título', 'placeholder' => 'Título'],
        ['id' => 'category', 'type' => 'select', 'label_title' => 'Categoría', 'options' => $categories, 'value' => ''],
        ['id' => 'count', 'type' => 'select', 'label_title' => 'Cantidad', 'options' => ['3' => '3 cursos', '6' => '6 cursos', '9' => '9 cursos'], 'value' => '3'],
        ['id' => 'button_text', 'type' => 'text', 'value' => 'Ver todos los cursos', 'label_title' => 'Texto del botón', 'placeholder' => 'Ver todos'],
    ],
];
