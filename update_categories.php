$blogTranslations = [
    1 => "Tecnología", 2 => "Salud", 3 => "Viajes", 4 => "Comida",
    5 => "Estilo de vida", 6 => "Negocios", 7 => "Entretenimiento",
    8 => "Deportes", 9 => "Ciencia", 10 => "Educación",
    11 => "Política", 12 => "Cultura", 13 => "Arte"
];

$courseTranslations = [
    1 => "Productividad", 2 => "Gestión del tiempo", 3 => "Fijación de metas",
    4 => "Enfoque y concentración", 5 => "Motivación", 6 => "Liderazgo",
    7 => "Redes de contacto", 8 => "Aprendizaje continuo",
    9 => "Innovación y creatividad", 10 => "Comunicación",
    11 => "Equilibrio trabajo-vida", 12 => "Desarrollo Web",
    13 => "HTML y CSS", 14 => "Temas de WordPress", 15 => "Bootstrap",
    16 => "Diseño Gráfico", 17 => "Photoshop", 18 => "Adobe Illustrator",
    19 => "Dibujo", 20 => "Teoría del color", 21 => "3D y Animación",
    22 => "Blender", 23 => "Gráficos en movimiento", 24 => "Moda y Textil",
    25 => "Costura", 26 => "Conceptos básicos de diseño de moda",
    27 => "Desarrollo y diseño móvil", 28 => "Diseño de aplicaciones móviles",
    29 => "UX/UI para móviles", 30 => "Arte digital e ilustración",
    31 => "Dibujo", 32 => "Teoría del color", 33 => "Adobe Illustrator",
    34 => "Herramientas de software creativo", 35 => "Photoshop",
    36 => "Blender", 37 => "Adobe Illustrator",
    38 => "Diseño de interiores e iluminación", 39 => "Diseño de iluminación",
    40 => "Conceptos básicos de diseño de interiores", 41 => "Artesanía y bricolaje",
    42 => "Costura", 43 => "Conceptos básicos de manualidades",
    44 => "Medios y artes visuales", 45 => "Gráficos en movimiento",
    46 => "Dibujo", 47 => "Diseño de iluminación"
];

foreach ($blogTranslations as $id => $name) {
    DB::table('blog_categories')->where('id', $id)->update(['name' => $name]);
}

foreach ($courseTranslations as $id => $name) {
    DB::table('courses_categories')->where('id', $id)->update(['name' => $name]);
}

echo "Todas las categorias han sido traducidas.\n";
