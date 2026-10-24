-- ============================================================================================
-- Horizontia · actualización de la base de datos del 24-oct-2026 (equivale a «php artisan migrate»)
--
-- Para hostings sin SSH/artisan (Plesk): Bases de datos → phpMyAdmin → tu base → pestaña «Importar»
-- (o «SQL» y pegar este contenido) → Continuar.
--
--   · Es SEGURO repetirlo: crea o agrega solo lo que falta y no toca datos existentes.
--   · Registra las migraciones en la tabla `migrations`, así un futuro `php artisan migrate` no las repite.
--   · Haz un respaldo de la base antes (Plesk → Bases de datos → Exportar).
--   · Supone el prefijo de tablas del módulo de cursos por defecto: `courses_`.
--   · Migraciones que incluye:
--       2026_10_24_000001_add_onboarding_dismissed_at_to_users
--       2026_10_24_000002_create_refund_requests
--       2026_10_24_000003_add_promo_video_url_to_courses
--       2026_10_24_000004_add_reminded_at_to_live_streams
-- ============================================================================================

-- 1) users.onboarding_dismissed_at — el estudiante ocultó la lista de «Primeros pasos»
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'onboarding_dismissed_at') = 0,
    'ALTER TABLE `users` ADD COLUMN `onboarding_dismissed_at` DATETIME NULL',
    'SELECT ''users.onboarding_dismissed_at ya existe'' AS aviso');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2) refund_requests — solicitudes de reembolso (garantía)
CREATE TABLE IF NOT EXISTS `refund_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `reason` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_note` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `progress` tinyint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `refund_requests_order_id_foreign` (`order_id`),
  KEY `refund_requests_user_id_index` (`user_id`),
  KEY `refund_requests_status_index` (`status`),
  CONSTRAINT `refund_requests_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) courses_courses.promo_video_url — video promocional por enlace de YouTube o Vimeo
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'courses_courses' AND COLUMN_NAME = 'promo_video_url') = 0,
    'ALTER TABLE `courses_courses` ADD COLUMN `promo_video_url` VARCHAR(300) NULL',
    'SELECT ''courses_courses.promo_video_url ya existe'' AS aviso');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4) courses_course_live_streams.reminded_at — el recordatorio de la sesión en vivo sale una sola vez
SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'courses_course_live_streams' AND COLUMN_NAME = 'reminded_at') = 0,
    'ALTER TABLE `courses_course_live_streams` ADD COLUMN `reminded_at` DATETIME NULL',
    'SELECT ''courses_course_live_streams.reminded_at ya existe'' AS aviso');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5) Registro en `migrations` (un lote nuevo; no duplica si ya estaban)
INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.nombre, b.lote
FROM (
    SELECT '2026_10_24_000001_add_onboarding_dismissed_at_to_users' AS nombre
    UNION ALL SELECT '2026_10_24_000002_create_refund_requests'
    UNION ALL SELECT '2026_10_24_000003_add_promo_video_url_to_courses'
    UNION ALL SELECT '2026_10_24_000004_add_reminded_at_to_live_streams'
) AS m
CROSS JOIN (SELECT COALESCE(MAX(`batch`), 0) + 1 AS lote FROM `migrations`) AS b
WHERE NOT EXISTS (SELECT 1 FROM `migrations` x WHERE x.`migration` = m.nombre);

SELECT 'Actualización lista' AS resultado;
