-- ============================================================================================
-- Horizontia · categorías de cursos del cliente (equivale a «php artisan academy:categories --archive-others»)
--
-- Crea las 5 temáticas (cada una con su subcategoría «General», porque el curso pide categoría y subcategoría):
--   Bienestar y estilo de vida · Desarrollo Profesional y Laboral · Tecnología con aplicación práctica ·
--   Emprendimiento y negocios · Ciencias y artes
-- y DESACTIVA las demás categorías que ningún curso usa (no borra nada y no toca las de cursos existentes).
--
-- phpMyAdmin → tu base → Importar. Es SEGURO repetirlo. Haz un respaldo antes. Prefijo de tablas supuesto: `courses_`.
-- ============================================================================================

-- 1) Temáticas (nivel principal)
INSERT INTO `courses_categories` (`name`, `slug`, `parent_id`, `status`, `created_at`, `updated_at`)
SELECT t.nombre, t.slug, NULL, 'active', NOW(), NOW()
FROM (
    SELECT 'Bienestar y estilo de vida' AS nombre, 'bienestar-y-estilo-de-vida' AS slug
    UNION ALL SELECT 'Desarrollo Profesional y Laboral', 'desarrollo-profesional-y-laboral'
    UNION ALL SELECT 'Tecnología con aplicación práctica', 'tecnologia-con-aplicacion-practica'
    UNION ALL SELECT 'Emprendimiento y negocios', 'emprendimiento-y-negocios'
    UNION ALL SELECT 'Ciencias y artes', 'ciencias-y-artes'
) AS t
WHERE NOT EXISTS (SELECT 1 FROM `courses_categories` c WHERE c.`slug` = t.slug AND c.`parent_id` IS NULL AND c.`deleted_at` IS NULL);

UPDATE `courses_categories` SET `status` = 'active', `updated_at` = NOW()
WHERE `parent_id` IS NULL AND `deleted_at` IS NULL
  AND `slug` IN ('bienestar-y-estilo-de-vida', 'desarrollo-profesional-y-laboral', 'tecnologia-con-aplicacion-practica', 'emprendimiento-y-negocios', 'ciencias-y-artes');

-- 2) Subcategoría «General» de cada temática
INSERT INTO `courses_categories` (`name`, `slug`, `parent_id`, `status`, `created_at`, `updated_at`)
SELECT CONCAT(p.`name`, ' · General'), CONCAT(p.`slug`, '-general'), p.`id`, 'active', NOW(), NOW()
FROM `courses_categories` p
WHERE p.`parent_id` IS NULL AND p.`deleted_at` IS NULL
  AND p.`slug` IN ('bienestar-y-estilo-de-vida', 'desarrollo-profesional-y-laboral', 'tecnologia-con-aplicacion-practica', 'emprendimiento-y-negocios', 'ciencias-y-artes')
  AND NOT EXISTS (SELECT 1 FROM `courses_categories` c WHERE c.`parent_id` = p.`id` AND c.`slug` = CONCAT(p.`slug`, '-general') AND c.`deleted_at` IS NULL);

UPDATE `courses_categories` c
JOIN `courses_categories` p ON p.`id` = c.`parent_id`
SET c.`status` = 'active', c.`updated_at` = NOW()
WHERE p.`parent_id` IS NULL AND p.`deleted_at` IS NULL AND c.`deleted_at` IS NULL
  AND p.`slug` IN ('bienestar-y-estilo-de-vida', 'desarrollo-profesional-y-laboral', 'tecnologia-con-aplicacion-practica', 'emprendimiento-y-negocios', 'ciencias-y-artes')
  AND c.`slug` = CONCAT(p.`slug`, '-general');

-- 3) Desactivar las demás categorías que ningún curso usa (ni como categoría, ni como subcategoría, ni como «padre» de una usada)
UPDATE `courses_categories`
SET `status` = 'deactive', `updated_at` = NOW()
WHERE `status` = 'active' AND `deleted_at` IS NULL
  AND `id` NOT IN (SELECT k.`id` FROM (
        SELECT c.`id` FROM `courses_categories` c
        LEFT JOIN `courses_categories` p ON p.`id` = c.`parent_id`
        WHERE c.`slug` IN ('bienestar-y-estilo-de-vida', 'desarrollo-profesional-y-laboral', 'tecnologia-con-aplicacion-practica', 'emprendimiento-y-negocios', 'ciencias-y-artes') AND c.`parent_id` IS NULL
           OR p.`slug` IN ('bienestar-y-estilo-de-vida', 'desarrollo-profesional-y-laboral', 'tecnologia-con-aplicacion-practica', 'emprendimiento-y-negocios', 'ciencias-y-artes') AND p.`parent_id` IS NULL
     ) AS k)
  AND `id` NOT IN (SELECT u.`id` FROM (
        SELECT `category_id` AS `id` FROM `courses_courses` WHERE `category_id` IS NOT NULL
        UNION SELECT `sub_category_id` FROM `courses_courses` WHERE `sub_category_id` IS NOT NULL
        UNION SELECT c2.`parent_id` FROM `courses_categories` c2 WHERE c2.`parent_id` IS NOT NULL
           AND c2.`id` IN (SELECT `category_id` FROM `courses_courses` WHERE `category_id` IS NOT NULL UNION SELECT `sub_category_id` FROM `courses_courses` WHERE `sub_category_id` IS NOT NULL)
     ) AS u);

SELECT 'Categorías listas' AS resultado;
