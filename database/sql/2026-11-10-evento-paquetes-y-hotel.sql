-- ============================================================================================
-- Horizontia · Eventos: paquetes por número de personas + código y aviso al hotel
-- Equivale a «php artisan migrate» (2026_11_10_000001_add_packages_and_hotel_to_events).
--
--   · events:        packages (JSON), hotel_email, hotel_summary_sent_at
--   · event_orders:  items (JSON), hotel_code (único), hotel_notified_at
--   · Astrofest estrena los paquetes del flyer (1 persona $1,300 · 2 personas $2,100 · 3 personas $2,900),
--     solo si todavía no tiene paquetes configurados.
--   · Registra la migración en `migrations`.
--
-- phpMyAdmin → tu base → Importar. Es SEGURO repetirlo (cada columna se agrega solo si falta). Haz un respaldo antes.
-- Requiere que ya existan las tablas de eventos (2026-10-30-evento-astrofest.sql o «php artisan migrate»).
-- ============================================================================================

DROP PROCEDURE IF EXISTS `hz_evento_paquetes`;
DELIMITER //
CREATE PROCEDURE `hz_evento_paquetes`()
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'events' AND COLUMN_NAME = 'packages') THEN
    ALTER TABLE `events` ADD COLUMN `packages` JSON NULL AFTER `price`;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'events' AND COLUMN_NAME = 'hotel_email') THEN
    ALTER TABLE `events` ADD COLUMN `hotel_email` VARCHAR(255) NULL AFTER `hotel_whatsapp`;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'events' AND COLUMN_NAME = 'hotel_summary_sent_at') THEN
    ALTER TABLE `events` ADD COLUMN `hotel_summary_sent_at` DATETIME NULL;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'event_orders' AND COLUMN_NAME = 'items') THEN
    ALTER TABLE `event_orders` ADD COLUMN `items` JSON NULL AFTER `quantity`;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'event_orders' AND COLUMN_NAME = 'hotel_code') THEN
    ALTER TABLE `event_orders` ADD COLUMN `hotel_code` VARCHAR(24) NULL AFTER `payment_method`;
    ALTER TABLE `event_orders` ADD UNIQUE KEY `event_orders_hotel_code_unique` (`hotel_code`);
  END IF;
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'event_orders' AND COLUMN_NAME = 'hotel_notified_at') THEN
    ALTER TABLE `event_orders` ADD COLUMN `hotel_notified_at` DATETIME NULL AFTER `confirmation_sent_at`;
  END IF;
END//
DELIMITER ;
CALL `hz_evento_paquetes`();
DROP PROCEDURE `hz_evento_paquetes`;

-- Paquetes del flyer para Astrofest (no pisa lo que ya se haya configurado en Administración → Eventos)
UPDATE `events`
SET `packages` = '[{"key":"p1","name":"1 persona","persons":1,"price":1300,"active":true},{"key":"p2","name":"2 personas","persons":2,"price":2100,"active":true},{"key":"p3","name":"3 personas","persons":3,"price":2900,"active":true}]'
WHERE `slug` = 'astrofest-geminidas-2026' AND `packages` IS NULL;

-- Hasta 10 personas por compra (solo sube el límite; nunca lo baja)
UPDATE `events` SET `max_per_order` = 10 WHERE `slug` = 'astrofest-geminidas-2026' AND `max_per_order` < 10;

-- Registro en `migrations` (un lote nuevo; no duplica si ya estaba)
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_10_000001_add_packages_and_hotel_to_events', b.lote
FROM (SELECT COALESCE(MAX(`batch`), 0) + 1 AS lote FROM `migrations`) AS b
WHERE NOT EXISTS (SELECT 1 FROM `migrations` x WHERE x.`migration` = '2026_11_10_000001_add_packages_and_hotel_to_events');
