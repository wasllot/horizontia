<?php

namespace App\Support;

/**
 * The curated "Ajustes" screen of the academy: which settings are offered, grouped in pages, all in Spanish.
 *
 * Field definitions (widget type, options…) are reused from app/Optionbuilder/*.php, but labels/descriptions are written here
 * so the screen never shows leftovers of the marketplace theme (disputes, tutor profiles, bookings…). Values keep living in the
 * option-builder table under their original section (`_general`, `_email`…), so `setting('_general.site_name')` is unchanged.
 */
class SettingsRegistry
{
    /** @var array<string, array> */
    private static array $files = [];

    /** @return array<string, array> field definitions of one app/Optionbuilder file, by id */
    private static function file(string $name): array
    {
        if (!isset(self::$files[$name])) {
            $def = include app_path('Optionbuilder/' . $name . '.php');
            $byId = [];
            foreach ($def['fields'] ?? [] as $f) {
                if (!empty($f['id'])) {
                    $byId[$f['id']] = $f;
                }
            }
            self::$files[$name] = $byId;
        }

        return self::$files[$name];
    }

    /** One field: the base definition (or a clone of another), overridden with Spanish texts. */
    private static function f(string $file, string $id, array $over = [], ?string $cloneOf = null): array
    {
        $base = self::file($file)[$cloneOf ?? $id] ?? ['id' => $id, 'type' => 'text'];
        $field = array_merge($base, $over);
        $field['id'] = $id;
        unset($field['hint'], $field['tab_id'], $field['tab_title']);   // the (English) tooltips of the theme

        return $field;
    }

    private static function secret(array $field): array
    {
        $field['type'] = 'password';

        return $field;
    }

    private static function info(string $id, string $title, string $desc): array
    {
        return ['id' => $id, 'type' => 'info', 'label_title' => $title, 'label_desc' => $desc];
    }

    /** @return array<int, array{id:string, label:string, desc:string, icon:string, groups: array<int, array>}> */
    public static function pages(): array
    {
        $dateFormats = [
            'd/m/Y' => '11/10/2026 (día/mes/año)', 'd-m-Y' => '11-10-2026', 'Y-m-d' => '2026-10-11', 'm/d/Y' => '10/11/2026 (mes/día/año)',
            'F j, Y' => 'October 11, 2026 (en inglés)', 'F j, Y, g:i a' => 'October 11, 2026, 4:30 pm (en inglés)',
        ];

        return [
            [
                'id' => 'sitio', 'label' => 'Sitio', 'icon' => 'globe',
                'desc' => 'El nombre de tu academia, su correo y las imágenes de marca.',
                'groups' => [
                    ['section' => '_general', 'title' => 'Identidad', 'fields' => [
                        self::f('general_settings', 'site_name', ['label_title' => 'Nombre de la academia', 'field_desc' => 'Aparece en el encabezado, los correos y el título de cada pestaña.', 'placeholder' => 'Ej. Horizontia']),
                        self::f('general_settings', 'site_email', ['label_title' => 'Correo de contacto', 'field_desc' => 'Dirección que se muestra al público y recibe los mensajes del formulario de contacto.', 'placeholder' => 'contacto@tudominio.com']),
                        self::f('general_settings', 'enable_multi_language', ['label_title' => 'Selector de idioma', 'field_desc' => 'Muestra el selector de idioma en el encabezado. Apágalo si tu academia es solo en español.']),
                    ]],
                    ['section' => '_general', 'title' => 'Logos e imágenes', 'fields' => [
                        self::f('general_settings', 'logo_default', ['label_title' => 'Logo principal', 'field_desc' => 'Para fondos claros. PNG o JPG, máximo 3 MB.']),
                        self::f('general_settings', 'logo_white', ['label_title' => 'Logo para fondos oscuros', 'field_desc' => 'Versión clara del logo (menú lateral, pie de página). PNG o JPG, máximo 3 MB.']),
                        self::f('general_settings', 'favicon', ['label_title' => 'Icono de la pestaña (favicon)', 'field_desc' => 'Cuadrado, PNG o JPG, máximo 3 MB.']),
                        self::f('general_settings', 'default_avatar_for_user', ['label_title' => 'Foto por defecto de los usuarios', 'field_desc' => 'Se usa cuando alguien no ha subido su foto.']),
                        self::f('general_settings', 'watermark_logo', ['label_title' => 'Marca de agua en los videos', 'field_desc' => 'Logo que se superpone en el reproductor de las lecciones. PNG con fondo transparente.'], 'logo_white'),
                    ]],
                    ['section' => '_lernen', 'title' => 'Cómo llamas a las personas', 'fields' => [
                        self::f('lernen_settings', 'tutor_display_name', ['label_title' => 'Nombre para los instructores', 'field_desc' => 'Así se les llama en la plataforma. Ej. Instructor, Docente, Facilitador.', 'placeholder' => 'Instructor']),
                        self::f('lernen_settings', 'student_display_name', ['label_title' => 'Nombre para los alumnos', 'field_desc' => 'Ej. Estudiante, Alumno, Participante.', 'placeholder' => 'Estudiante']),
                    ]],
                ],
            ],
            [
                'id' => 'formatos', 'label' => 'Formatos', 'icon' => 'sliders',
                'desc' => 'Moneda, fechas, horas y cuántos registros se muestran en las listas.',
                'groups' => [
                    ['section' => '_general', 'title' => 'Moneda y números', 'fields' => [
                        self::f('general_settings', 'currency', ['label_title' => 'Moneda', 'options' => self::currencies(), 'field_desc' => 'La moneda en la que se muestran y cobran los precios (los pagos con Mercado Pago usan MXN).']),
                        self::f('general_settings', 'currency_position', ['label_title' => 'Posición del símbolo', 'options' => ['left' => '$100 (izquierda)', 'right' => '100$ (derecha)', 'left_space' => '$ 100 (izquierda con espacio)', 'right_space' => '100 $ (derecha con espacio)']]),
                        self::f('general_settings', 'thousand_separator', ['label_title' => 'Separador de miles', 'placeholder' => ',']),
                        self::f('general_settings', 'decimal_separator', ['label_title' => 'Separador decimal', 'placeholder' => '.']),
                        self::f('general_settings', 'number_of_decimals', ['label_title' => 'Decimales', 'field_desc' => 'Cuántos decimales se muestran en los precios.', 'placeholder' => '2']),
                    ]],
                    ['section' => '_general', 'title' => 'Fechas y horas', 'fields' => [
                        self::f('general_settings', 'date_format', ['label_title' => 'Formato de fecha', 'options' => $dateFormats]),
                        self::f('lernen_settings', 'time_format', ['label_title' => 'Formato de hora', 'label_desc' => '', 'options' => ['12' => '12 horas (4:30 pm)', '24' => '24 horas (16:30)']]),
                        self::f('lernen_settings', 'start_of_week', ['label_title' => 'La semana empieza en', 'options' => ['Domingo', 'Lunes']]),
                    ]],
                    ['section' => '_general', 'title' => 'Listas y tablas', 'fields' => [
                        self::f('general_settings', 'per_page_record', ['label_title' => 'Registros por página', 'field_desc' => 'En las listas del panel de administración.']),
                        self::f('general_settings', 'table_responsive', ['label_title' => 'Tablas en pantallas pequeñas', 'options' => ['yes' => 'Apilar las filas (recomendado)', 'no' => 'Desplazamiento horizontal']]),
                    ]],
                ],
            ],
            [
                'id' => 'archivos', 'label' => 'Archivos', 'icon' => 'file',
                'desc' => 'Qué tipos de archivo se pueden subir y de qué tamaño máximo.',
                'groups' => [
                    ['section' => '_general', 'title' => 'Imágenes', 'fields' => [
                        self::f('general_settings', 'allowed_image_extensions', ['label_title' => 'Tipos permitidos', 'field_desc' => 'Extensiones separadas por coma. Ej. jpg,jpeg,png,webp', 'placeholder' => 'jpg,jpeg,png,webp']),
                        self::f('general_settings', 'max_image_size', ['label_title' => 'Tamaño máximo (MB)', 'placeholder' => '5']),
                    ]],
                    ['section' => '_general', 'title' => 'Videos', 'fields' => [
                        self::f('general_settings', 'allowed_video_extensions', ['label_title' => 'Tipos permitidos', 'field_desc' => 'Ej. mp4,webm', 'placeholder' => 'mp4,webm']),
                        self::f('general_settings', 'max_video_size', ['label_title' => 'Tamaño máximo (MB)', 'placeholder' => '500']),
                    ]],
                    ['section' => '_general', 'title' => 'Documentos y paquetes', 'fields' => [
                        self::f('general_settings', 'allowed_file_extensions', ['label_title' => 'Tipos permitidos', 'field_desc' => 'Archivos adjuntos de las lecciones. Ej. pdf,docx,xlsx,zip', 'placeholder' => 'pdf,docx,xlsx,zip']),
                        self::f('general_settings', 'max_file_size', ['label_title' => 'Tamaño máximo (MB)', 'placeholder' => '50']),
                        self::f('general_settings', 'max_scorm_size', ['label_title' => 'Paquetes SCORM: tamaño máximo (MB)', 'placeholder' => '500', 'field_desc' => 'Para los cursos interactivos en formato SCORM.'], 'max_file_size'),
                    ]],
                ],
            ],
            [
                'id' => 'redes', 'label' => 'Redes sociales', 'icon' => 'share',
                'desc' => 'Enlaces a tus perfiles; aparecen en el pie de página. Deja vacío lo que no uses.',
                'groups' => [
                    ['section' => '_general', 'title' => 'Tus perfiles', 'fields' => [
                        self::f('general_settings', 'fb_link', ['label_title' => 'Facebook', 'placeholder' => 'https://facebook.com/tu-pagina']),
                        self::f('general_settings', 'insta_link', ['label_title' => 'Instagram', 'placeholder' => 'https://instagram.com/tu-usuario']),
                        self::f('general_settings', 'linkedin_link', ['label_title' => 'LinkedIn', 'placeholder' => 'https://linkedin.com/company/tu-empresa']),
                        self::f('general_settings', 'yt_link', ['label_title' => 'YouTube', 'placeholder' => 'https://youtube.com/@tu-canal']),
                        self::f('general_settings', 'tiktok_link', ['label_title' => 'TikTok', 'placeholder' => 'https://tiktok.com/@tu-usuario']),
                    ]],
                ],
            ],
            [
                'id' => 'correo', 'label' => 'Correo', 'icon' => 'mail',
                'desc' => 'Cómo se ven y desde dónde salen los correos de la academia.',
                'tabs' => true,
                'groups' => [
                    ['tab' => 'correo-remitente', 'tab_title' => 'Remitente', 'section' => '_email', 'title' => 'Identidad de los correos', 'fields' => [
                        self::f('email_settings', 'email_logo', ['label_title' => 'Logo de los correos', 'field_desc' => 'PNG o JPG, máximo 3 MB.']),
                        self::f('email_settings', 'sender_name', ['label_title' => 'Nombre del remitente', 'field_desc' => 'Si lo dejas vacío se usa el nombre del sitio.', 'placeholder' => 'Academia Horizontia']),
                        self::f('email_settings', 'sender_email', ['label_title' => 'Correo del remitente', 'field_desc' => 'Si lo dejas vacío se usa el correo del sitio. Debe ser de tu dominio.', 'placeholder' => 'no-responder@tudominio.com']),
                        self::f('email_settings', 'sender_signature', ['label_title' => 'Firma', 'field_desc' => 'Cierra los correos. Ej. «Equipo de Horizontia».', 'placeholder' => 'Equipo de Horizontia']),
                        self::f('email_settings', 'footer_text', ['label_title' => 'Texto del pie', 'field_desc' => 'Derechos de autor u otra nota al final de cada correo.', 'placeholder' => '© 2026 Horizontia. Todos los derechos reservados.']),
                    ]],
                    ['tab' => 'correo-smtp', 'tab_title' => 'Servidor SMTP', 'section' => '_email', 'title' => 'Envío de correos', 'intro' => 'Opcional: si lo completas, estos datos reemplazan a los del archivo .env al guardar. En un servidor de producción lo habitual es configurarlos en el .env.', 'fields' => [
                        self::f('email_settings', 'smtp_host', ['label_title' => 'Servidor (host)', 'field_desc' => 'Ej. smtp.tuproveedor.com', 'placeholder' => 'smtp.tuproveedor.com']),
                        self::f('email_settings', 'smtp_port', ['label_title' => 'Puerto', 'field_desc' => 'Normalmente 587 (TLS) o 465 (SSL).', 'placeholder' => '587']),
                        self::f('email_settings', 'smtp_username', ['label_title' => 'Usuario', 'field_desc' => 'El usuario de tu proveedor de correo.', 'placeholder' => 'usuario']),
                        self::secret(self::f('email_settings', 'smtp_password', ['label_title' => 'Contraseña', 'field_desc' => 'La contraseña (o clave de aplicación) de tu proveedor de correo.', 'placeholder' => '••••••••'])),
                        self::f('email_settings', 'smtp_encryption', ['label_title' => 'Cifrado', 'field_desc' => 'Normalmente TLS con el puerto 587.', 'options' => ['none' => 'Ninguno', 'ssl' => 'SSL', 'tls' => 'TLS']]),
                        self::f('email_settings', 'smtp_from_email', ['label_title' => 'Correo de envío', 'field_desc' => 'Dirección desde la que se envían los correos.', 'placeholder' => 'no-responder@tudominio.com']),
                        self::f('email_settings', 'smtp_from_name', ['label_title' => 'Nombre de envío', 'field_desc' => 'Nombre que ve quien recibe el correo.', 'placeholder' => 'Horizontia']),
                    ]],
                ],
            ],
            [
                'id' => 'integraciones', 'label' => 'Integraciones', 'icon' => 'plug',
                'desc' => 'Protección anti-bots, analítica y un botón de WhatsApp.',
                'tabs' => true,
                'groups' => self::integrations(),
            ],
            [
                'id' => 'ia', 'label' => 'Tutor con IA', 'icon' => 'spark',
                'desc' => 'Un asistente que ayuda a los alumnos dentro de cada lección.',
                'groups' => [
                    ['section' => '_api', 'title' => 'Asistente (Google Gemini)', 'intro' => 'Se muestra a los alumnos inscritos en cada lección. Si está desactivado o no hay clave, el asistente NO aparece.', 'fields' => [
                        self::f('api_settings', 'gemini_tutor_enabled', ['label_title' => 'Activar el tutor con IA', 'field_desc' => 'Necesita una clave de la API de Google Gemini.']),
                        self::secret(self::f('api_settings', 'gemini_api_key', ['label_title' => 'Clave de la API', 'placeholder' => 'Pega aquí tu clave de Google AI Studio', 'field_desc' => 'Créala en aistudio.google.com/apikey. Con el nivel gratuito Google puede usar las conversaciones para mejorar sus productos: para alumnos de pago usa una cuenta con facturación.'])),
                        self::f('api_settings', 'gemini_model', ['label_title' => 'Modelo', 'placeholder' => 'gemini-3.5-flash-lite', 'field_desc' => 'Por ejemplo gemini-3.5-flash-lite (económico) o gemini-3.8-flash (más capaz).']),
                        self::f('api_settings', 'gemini_daily_limit', ['label_title' => 'Mensajes por alumno al día', 'placeholder' => '30', 'field_desc' => 'Controla el costo. 0 = sin límite.']),
                        self::f('api_settings', 'gemini_extra_instructions', ['label_title' => 'Instrucciones adicionales', 'placeholder' => 'Opcional: tono, nombre de la academia, temas que debe evitar…']),
                    ]],
                ],
            ],
            [
                'id' => 'acceso', 'label' => 'Acceso con Google', 'icon' => 'key',
                'desc' => 'Permite iniciar sesión con una cuenta de Google.',
                'tabs' => true,
                'groups' => [
                    ['tab' => 'acceso-google', 'tab_title' => 'Inicio de sesión', 'section' => '_api', 'title' => 'Google', 'intro' => 'Crea las credenciales en console.cloud.google.com (tipo «ID de cliente de OAuth», aplicación web) y registra como URI de redirección: ' . (\Illuminate\Support\Facades\Route::has('social.callback') ? route('social.callback', ['provider' => 'google']) : ''), 'fields' => [
                        self::f('api_settings', 'enable_social_login', ['label_title' => 'Permitir iniciar sesión con Google', 'field_desc' => 'Muestra el botón «Continuar con Google» en el acceso.']),
                        self::f('api_settings', 'social_google_client_id', ['label_title' => 'ID de cliente', 'placeholder' => '1234567890-abc.apps.googleusercontent.com']),
                        self::secret(self::f('api_settings', 'social_google_client_secret', ['label_title' => 'Secreto del cliente', 'placeholder' => '••••••••'])),
                    ]],
                ],
            ],
            [
                'id' => 'codigo', 'label' => 'Scripts y estilos', 'icon' => 'code',
                'desc' => 'Código propio para el sitio público. Úsalo solo si sabes lo que haces: un script mal escrito puede romper las páginas.',
                'groups' => [
                    ['section' => '_scripts_styles', 'title' => 'Código personalizado', 'fields' => [
                        self::f('scripts_styles_settings', 'header_scripts', ['label_title' => 'Scripts del encabezado', 'field_desc' => 'Se insertan antes de cerrar la etiqueta head (p. ej. verificación de dominio).']),
                        self::f('scripts_styles_settings', 'footer_scripts', ['label_title' => 'Scripts del pie de página', 'field_desc' => 'Se insertan al final de cada página.']),
                        self::f('scripts_styles_settings', 'custom_styles', ['label_title' => 'Estilos CSS personalizados', 'field_desc' => 'Reglas CSS que se aplican a todo el sitio público.']),
                    ]],
                ],
            ],
            [
                'id' => 'mantenimiento', 'label' => 'Mantenimiento', 'icon' => 'tool',
                'desc' => 'Cierra el sitio al público mientras haces cambios. Los administradores siguen pudiendo entrar.',
                'groups' => [
                    ['section' => '_maintenance', 'title' => 'Modo mantenimiento', 'fields' => [
                        self::f('maintenance_settings', 'maintenance_mode', ['label_title' => 'Sitio en mantenimiento', 'field_desc' => 'Si lo activas, los visitantes ven un aviso en lugar del sitio.', 'options' => ['Yes' => 'Sí, mostrar el aviso', 'No' => 'No, el sitio está abierto']]),
                        self::f('maintenance_settings', 'maintenance_logo', ['label_title' => 'Logo del aviso (opcional)', 'field_desc' => 'Si lo dejas vacío se muestra el logo de Horizontia. PNG o JPG, máximo 3 MB.']),
                        self::f('maintenance_settings', 'maintenance_title', ['label_title' => 'Título', 'field_desc' => 'Encabezado del aviso de mantenimiento.', 'placeholder' => 'Volvemos pronto']),
                        self::f('maintenance_settings', 'maintenance_description', ['label_title' => 'Mensaje', 'field_desc' => 'Explica a los visitantes qué está pasando y cuándo regresas.', 'placeholder' => 'Estamos mejorando la plataforma.']),
                        self::f('maintenance_settings', 'maintenance_email', ['label_title' => 'Correo de contacto', 'field_desc' => 'Para quien necesite escribirte mientras tanto.', 'placeholder' => 'contacto@tudominio.com']),
                    ]],
                ],
            ],
        ];
    }

    /** The integrations file already has Spanish texts and one tab per service: reuse it as is. */
    /** Monedas habituales con nombre en español; la clave es el código que guarda la plataforma (se conserva la ya guardada). */
    private static function currencies(): array
    {
        $names = ['MXN' => 'Peso mexicano', 'USD' => 'Dólar estadounidense', 'EUR' => 'Euro', 'COP' => 'Peso colombiano', 'ARS' => 'Peso argentino', 'CLP' => 'Peso chileno', 'PEN' => 'Sol peruano', 'UYU' => 'Peso uruguayo', 'BOB' => 'Boliviano', 'GTQ' => 'Quetzal', 'CRC' => 'Colón costarricense', 'DOP' => 'Peso dominicano', 'BRL' => 'Real brasileño', 'CAD' => 'Dólar canadiense', 'GBP' => 'Libra esterlina'];
        $list = currencyList();
        $current = (string) setting('_general.currency');
        if ($current !== '' && !isset($names[$current]) && isset($list[$current])) {
            $names[$current] = $list[$current]['name'];
        }
        $out = [];
        foreach ($names as $code => $name) {
            $out[$code] = $name . ' (' . $code . (isset($list[$code]['symbol']) ? ' · ' . $list[$code]['symbol'] : '') . ')';
        }

        return $out;
    }

    private static function integrations(): array
    {
        $def = include app_path('Optionbuilder/integrations_settings.php');
        $tabs = [];
        foreach ($def['fields'] as $f) {
            $t = $f['tab_id'] ?? 'general';
            $tabs[$t]['title'] = $f['tab_title'] ?? $t;
            unset($f['tab_id'], $f['tab_title']);
            $tabs[$t]['fields'][] = $f;
        }
        $groups = [];
        foreach ($tabs as $id => $t) {
            $groups[] = ['tab' => $id, 'tab_title' => $t['title'], 'section' => '_integrations', 'title' => $t['title'], 'fields' => $t['fields']];
        }

        return $groups;
    }

    /** @return array<string, array{section:string, field:array}> field id => storage section and definition, for one page */
    public static function fieldsOf(array $page): array
    {
        $out = [];
        foreach ($page['groups'] as $g) {
            foreach ($g['fields'] as $f) {
                if (($f['type'] ?? '') !== 'info') {
                    $out[$f['id']] = ['section' => $g['section'], 'field' => $f];
                }
            }
        }

        return $out;
    }

    public static function page(string $id): ?array
    {
        foreach (self::pages() as $p) {
            if ($p['id'] === $id) {
                return $p;
            }
        }

        return null;
    }
}
