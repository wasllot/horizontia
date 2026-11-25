-- ============================================================================================
-- Horizontia · Astrofest: datos que usan los correos nuevos (modelos del cliente, 11 de noviembre)
--
--   · hotel_whatsapp: el número al que el comprador escribe con su clave de confirmación
--     (sale en el correo «Confirmación de registro» y en la página de confirmación).
--
-- NO cambia columnas ni crea tablas: solo llena el WhatsApp del hotel si está vacío.
-- phpMyAdmin → tu base → Importar (o pegar en SQL). Se puede repetir: no pisa un número ya capturado.
--
-- PENDIENTES (los captura el equipo en Admin → Eventos; no se inventan aquí):
--   · correo del hotel (hotel_email): sin él NO se avisa al hotel de cada compra ni se le manda la lista final.
--   · correo de contacto del evento (contact_email): es el «Responder a» de los correos y la copia del aviso al hotel.
-- ============================================================================================

UPDATE `events`
SET `hotel_whatsapp` = '+52 55 4766 8637', `updated_at` = NOW()
WHERE `slug` = 'astrofest-geminidas-2026'
  AND (`hotel_whatsapp` IS NULL OR `hotel_whatsapp` = '');
