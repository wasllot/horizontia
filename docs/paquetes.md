# Paquetes de cursos

Varios cursos en línea vendidos juntos a un precio menor que la suma. **Admin → Gestionar cursos → Paquetes** (`/admin/paquetes-de-cursos`); público en `/paquetes` y `/paquete/{slug}`.

## Cómo funciona la compra
No hay un tipo de pedido nuevo: el paquete entra al carrito como **sus cursos sueltos con el descuento repartido en proporción** a su precio (mismo % para todos; el último absorbe el redondeo, el total es exacto).
Así inscripciones, recibos, comisiones y reembolsos siguen funcionando por curso. Las líneas del carrito llevan `options.bundle_id` / `bundle_title`.

- Quitar un curso de un paquete del carrito **quita el paquete completo** (`CartService::remove`), para que nadie conserve el descuento sobre un solo curso.
- Si el alumno ya tiene algún curso del paquete, paga solo los que le faltan, con el mismo % de descuento.
- Un curso que ya estaba en el carrito a precio normal se reemplaza por su precio de paquete.
- En el checkout (`Checkout::updateInfo`) `BundleService::validateCart` revisa cada paquete: si cambió el precio, se pausó, un curso salió del paquete o se compró por otro lado, el carrito se reconstruye o se limpia y se avisa; no se cobra la cifra vieja.
- Reembolsar el pedido retira todos los cursos del paquete (`OrderRefundService`).

## Reglas
- Solo cursos **publicados, en línea y con precio**: un curso presencial necesita elegir fecha y uno gratis no ahorra nada.
- Mínimo 2 cursos y el precio debe ser **menor** que la suma (el formulario lo valida; un paquete sin ahorro no se puede vender).
- Los borradores solo los ve el administrador (`?` en la URL pública da 404 a los demás).
- La ficha de un curso muestra «Ahorra X%» con el mejor paquete publicado que lo incluye.

## Pruebas
`tests/Feature/BundlesTest.php` y `tests/e2e-panel/bundles.e2e.mjs`. Datos de prueba: `DemoLiveAndRegularCoursesSeeder` crea «Pack Negocio Digital».
