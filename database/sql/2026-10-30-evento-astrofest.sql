-- ============================================================================================
-- Horizontia · Eventos con entrada única (Astrofest Gemínidas 2026)
-- Equivale a «php artisan migrate» (2026_10_30_000001_create_events_tables) + «php artisan events:astrofest».
--
--   · Crea las tablas `events`, `event_orders` y `event_tickets` (si no existen).
--   · Crea el evento Astrofest con lo confirmado. Las ventas quedan APAGADAS: se activan en
--     Administración → Eventos cuando haya políticas, precio y Mercado Pago configurado.
--   · Registra la migración en `migrations`, así un futuro `php artisan migrate` no la repite.
--
-- phpMyAdmin → tu base → Importar. Es SEGURO repetirlo. Haz un respaldo antes.
-- Supone APP_TIMEZONE=UTC (las fechas del evento van en UTC: 12-dic 15:00 hora de México = 21:00 UTC).
-- ============================================================================================

CREATE TABLE IF NOT EXISTS `events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `start_at` datetime DEFAULT NULL,
  `end_at` datetime DEFAULT NULL,
  `countdown_from` datetime DEFAULT NULL,
  `venue_name` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `venue_city` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `venue_address` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `venue_map_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hotel_booking_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hotel_phone` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hotel_whatsapp` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_email` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_whatsapp` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `iva_rate` decimal(5,2) NOT NULL DEFAULT '16.00',
  `capacity` int unsigned DEFAULT NULL,
  `max_per_order` tinyint unsigned NOT NULL DEFAULT '10',
  `sales_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `sales_open_at` datetime DEFAULT NULL,
  `sales_close_at` datetime DEFAULT NULL,
  `hold_minutes` smallint unsigned NOT NULL DEFAULT '45',
  `offline_hold_hours` smallint unsigned NOT NULL DEFAULT '72',
  `discounts` json DEFAULT NULL,
  `content` json DEFAULT NULL,
  `assets` json DEFAULT NULL,
  `policies_html` longtext COLLATE utf8mb4_unicode_ci,
  `redirect_events_index` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `events_slug_unique` (`slug`),
  KEY `events_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `event_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint unsigned NOT NULL,
  `code` varchar(26) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` tinyint unsigned NOT NULL,
  `minors` tinyint unsigned NOT NULL DEFAULT '0',
  `has_telescope` tinyint(1) DEFAULT NULL,
  `heard_from` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `newsletter` tinyint(1) NOT NULL DEFAULT '0',
  `wants_invoice` tinyint(1) NOT NULL DEFAULT '0',
  `invoice` json DEFAULT NULL,
  `policies_accepted_at` datetime DEFAULT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount_label` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `iva` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `expires_at` datetime DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `payment_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_method` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `utm` json DEFAULT NULL,
  `confirmation_sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_orders_code_unique` (`code`),
  KEY `event_orders_event_id_status_index` (`event_id`,`status`),
  KEY `event_orders_email_index` (`email`),
  KEY `event_orders_payment_id_index` (`payment_id`),
  CONSTRAINT `event_orders_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `event_tickets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint unsigned NOT NULL,
  `event_order_id` bigint unsigned NOT NULL,
  `attendee_name` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `checked_in_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_tickets_code_unique` (`code`),
  KEY `event_tickets_event_id_foreign` (`event_id`),
  KEY `event_tickets_event_order_id_foreign` (`event_order_id`),
  CONSTRAINT `event_tickets_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_tickets_event_order_id_foreign` FOREIGN KEY (`event_order_id`) REFERENCES `event_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- Evento Astrofest (solo si no existe; no pisa lo que ya se editó)
INSERT INTO `events` (`slug`, `name`, `status`, `start_at`, `end_at`, `countdown_from`, `venue_name`, `venue_city`, `price`, `iva_rate`,
                      `max_per_order`, `sales_enabled`, `hold_minutes`, `offline_hold_hours`, `discounts`, `content`, `redirect_events_index`, `created_at`, `updated_at`)
SELECT 'astrofest-geminidas-2026', 'Astrofest® Gemínidas 2026', 'published', '2026-12-12 21:00:00', '2026-12-13 19:00:00', '2026-12-01 06:00:00',
       'Club Campestre Teotihuacán', 'Estado de México', 1740.00, 16.00, 10, 0, 45, 72,
       '{"early_bird":{"enabled":false,"label":"Preventa","percent":15,"starts_at":null,"ends_at":"2026-10-31"},"group":{"enabled":false,"label":"Descuento por grupo","percent":10,"min_qty":4,"starts_at":null,"ends_at":null}}',
       '{"minors_age":6}', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `events` WHERE `slug` = 'astrofest-geminidas-2026');

-- Registro en `migrations` (un lote nuevo; no duplica si ya estaba)
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_30_000001_create_events_tables', b.lote
FROM (SELECT COALESCE(MAX(`batch`), 0) + 1 AS lote FROM `migrations`) AS b
WHERE NOT EXISTS (SELECT 1 FROM `migrations` x WHERE x.`migration` = '2026_10_30_000001_create_events_tables');
