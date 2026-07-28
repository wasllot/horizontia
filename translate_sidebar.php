<?php

$sidebar_es = [
    'tags' => 'Etiquetas', 'skills' => 'Habilidades', 'payment_modes' => 'Modos de pago', 'languages' => 'Idiomas',
    'dashboard' => 'Panel', 'my_account' => 'Mi cuenta', 'profile_settings' => 'Ajustes de perfil',
    'tuition_settings' => 'Ajustes de enseñanza', 'manage_bookings' => 'Gestionar reservas',
    'billing_detail' => 'Detalles de facturación', 'favourites' => 'Favoritos', 'find_tutors' => 'Encontrar tutores',
    'messages' => 'Bandeja de entrada', 'upgrade' => 'Mejorar', 'logout' => 'Cerrar sesión',
    'taxonomies' => 'Taxonomías', 'expert_levels' => 'Niveles de experto', 'forums' => 'Comunidad',
    'settings' => 'Configuraciones globales', 'site_settings' => 'Configuración del sitio',
    'social_media_settings' => 'Redes sociales', 'contact_us_settings' => 'Contáctenos',
    'api_settings' => 'Ajustes de API', 'proposal_settings' => 'Propuestas', 'email_settings' => 'Ajustes de correo',
    'notification_settings' => 'Notificaciones', 'script_style_settings' => 'Scripts y estilos',
    'front_pages_settings' => 'Páginas principales', 'packages' => 'Paquetes',
    'acc_deactive_reasons' => 'Razones de desactivación', 'general_settings' => 'Ajustes generales',
    'front_page_setting' => 'Página de inicio', 'dispute_settings' => 'Disputas',
    'seller_settings' => 'Vendedor', 'commission_settings' => 'Ajustes de comisión',
    'payment_methods' => 'Métodos de pago', 'disputes' => 'Disputas', 'proposals' => 'Propuestas',
    'users' => 'Usuarios', 'earnings' => 'Ganancias', 'sitepages' => 'Páginas',
    'email_templates' => 'Plantillas de correo', 'profile' => 'Mi perfil', 'clear-cache' => 'Limpiar caché',
    'menu' => 'Menús', 'site_management' => 'Gestión del sitio', 'transaction_payment' => 'Transacciones y pagos',
    'blogs' => 'Blogs', 'withdraw_requests' => 'Solicitudes de retiro', 'adsense_settings' => 'Adsense',
    'theme_settings' => 'Color y tema', 'ai_writer_settings' => 'IA Writer', 'translation_settings' => 'Traducciones',
    'groups' => 'Grupos', 'subjects' => 'Materias', 'subject_groups' => 'Grupos de materias',
    'bookings' => 'Mis reservas', 'invoices' => 'Facturas', 'certificates' => 'Certificados',
    'my_certificates' => 'Mis certificados', 'upcomming_bookings' => 'Próximas reservas',
    'booking_settings' => 'Ajustes de reservas', 'home' => 'Inicio', 'find_tutor' => 'Encontrar tutor',
    'manage_packages' => 'Complementos', 'installed_packages' => 'Instalados', 'add_new_package' => 'Nuevo complemento',
    'insights' => 'Panel general', 'coupons' => 'Cupones', 'disputes_system' => 'Sistema de disputas',
    'manage-dispute' => 'Gestionar disputas', 'manage_subscriptions' => 'Suscripciones',
    'subscriptions_list' => 'Suscripciones', 'user_subscriptions' => 'Usuarios suscritos',
    'badges' => 'Insignias', 'categories' => 'Categorías', 'purchased_subscriptions' => 'Compradas',
];

$admin_sidebar_es = [
    'users' => 'Usuarios',
    'invoices' => 'Facturas',
    'bookings' => 'Reservas'
];

$admin_general_es = [
    'identity_verification' => 'Verificación de identidad'
];

$blogs_es = [
    'manage_blogs' => 'Gestionar blogs',
    'create_blog' => 'Crear blog',
    'blog_listing' => 'Lista de blogs',
    'blog_categories' => 'Categorías de blog'
];

$courses_es = [
    'manage_courses' => 'Gestionar cursos',
];

file_put_contents('lang/es/sidebar.php', "<?php\n\nreturn " . var_export($sidebar_es, true) . ";\n");

if(!is_dir('lang/es/admin')) {
    mkdir('lang/es/admin', 0777, true);
}
// Load existing and merge if exists
$adminSidebarPath = 'lang/es/admin/sidebar.php';
$existing = file_exists($adminSidebarPath) ? include($adminSidebarPath) : [];
$merged = array_merge($existing, $admin_sidebar_es);
file_put_contents($adminSidebarPath, "<?php\n\nreturn " . var_export($merged, true) . ";\n");

$adminGeneralPath = 'lang/es/admin/general.php';
$existing = file_exists($adminGeneralPath) ? include($adminGeneralPath) : [];
$merged = array_merge($existing, $admin_general_es);
file_put_contents($adminGeneralPath, "<?php\n\nreturn " . var_export($merged, true) . ";\n");

$blogsPath = 'lang/es/blogs.php';
$existing = file_exists($blogsPath) ? include($blogsPath) : [];
$merged = array_merge($existing, $blogs_es);
file_put_contents($blogsPath, "<?php\n\nreturn " . var_export($merged, true) . ";\n");

// Translate courses module menus as well (if they exist)
file_put_contents('lang/es/courses.php', "<?php\n\nreturn " . var_export($courses_es, true) . ";\n");

echo "Traducciones creadas correctamente.";
