# Exportar e importar configuración

**Administración → Ajustes → Exportar / importar.** Sirve para llevar la configuración del sitio de una instalación a otra (p. ej. de tu equipo al servidor) sin copiar la base de datos. También por consola: `php artisan academy:export` y `php artisan academy:import`.

## Qué se puede exportar (tú eliges)
| Sección | Contiene | Por defecto |
|---|---|---|
| Páginas | Contenido y diseño de las páginas del constructor (puedes elegir cuáles). Sin historial de versiones ni páginas ligadas a cursos | sí |
| Banners de páginas fijas | Título, subtítulo, oscurecimiento y alineación del banner de Nosotros, Contacto y Solicitar acceso | sí |
| Menús | Menú principal y columnas del pie, con submenús | sí |
| SEO por URL | Reglas de Admin → SEO | sí |
| Ajustes generales | Nombre, moneda, formato de fecha, logos, textos de portada/pie, redes, etc. | sí |
| Integraciones | reCAPTCHA, GTM, WhatsApp, scripts y estilos | sí |
| Correo y notificaciones | Remitente/firma, plantillas de correo y de notificaciones | sí |
| Métodos de pago y comisiones | Configuración de pagos (**sin claves secretas**) | no |
| Cupones de descuento | Códigos, tipo y valor, vigencia, límites y cursos a los que aplican (por enlace del curso). Sin canjes ni contador de usos | no |
| Rutas de aprendizaje | Rutas con su descripción y el orden de sus cursos (por enlace del curso) | no |
| Categorías de cursos | Solo la estructura (nunca los cursos) | no |
| Datos de formularios | Mensajes de contacto y suscriptores del boletín (datos personales) | no |
| Claves de API y credenciales | Google, OpenAI, Zoom, almacenamiento. Solo con «Incluir credenciales» | no |

«Nosotros» y «Contacto» tienen vista propia: no viajan como páginas (su banner va en «Banners de páginas fijas»).

**Nunca** se exportan cursos, blogs, usuarios, pedidos ni el modo mantenimiento.

## Seguridad
- Las claves secretas (tokens, secretos, contraseñas, webhooks) quedan **fuera** salvo que marques «Incluir credenciales»; en ese caso el archivo es sensible: guárdalo bien y bórralo después.
- Al importar nunca se aceptan credenciales de un archivo que dice no tenerlas, ni secciones fuera de las permitidas.
- El HTML de las páginas importadas se limpia (sin `<script>` ni eventos); los enlaces de menú `javascript:`/`data:` y las rutas inexistentes se omiten; los slugs raros se rechazan; no se aceptan objetos serializados.
- El archivo lleva una suma de verificación: si se modificó o se dañó, se rechaza.
- Solo administradores.

## Importar
1. Sube el `.json`. Verás qué contiene (y qué páginas).
2. Elige secciones y qué hacer con lo que ya existe: **solo añadir lo que falta** o **reemplazar lo existente** (páginas con el mismo enlace, menús del mismo nombre y ubicación, ajustes, plantillas, reglas de SEO, categorías).
3. **Vista previa**: ejecuta la importación real dentro de una transacción que se deshace; lo que ves es exactamente lo que pasará.
4. **Importar**: antes hace un respaldo de la base de datos (puedes desmarcarlo) y, si algo falla, no se guarda nada.

Cupones y rutas enlazan los cursos por su *slug*: **importa antes los cursos** en el sitio de destino. Un cupón limitado a un curso que no existe **no se importa** (para no convertirlo en un descuento para todos); una ruta con cursos faltantes queda en borrador. Al reemplazar un cupón se conserva su contador de usos.

Detalles: los ajustes que apuntan a páginas (variaciones de encabezado/pie, SEO) se reenlazan por el enlace de la página; si falta se descartan. Si el archivo no traía secretos (pagos), se conservan los que ya tiene este sitio. Las imágenes y archivos **no se copian**: se ven si usas el mismo almacenamiento (mismo bucket de R2).

## Consola
```bash
php artisan academy:export [archivo] [--sections=pages,menus,seo] [--credentials]
php artisan academy:import archivo.json [--sections=...] [--mode=skip|overwrite] [--dry-run]
```
Pruebas: `ConfigTransferTest`, `ConfigTransferAcademyTest`, `ConfigTransferScreenTest` y el E2E `tests/e2e-panel/config-transfer.e2e.mjs`.
