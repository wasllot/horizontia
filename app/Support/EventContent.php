<?php

namespace App\Support;

/** Textos por defecto de la página de un evento (guía de contenidos de Astrofest v1.0). Cada uno se puede sobrescribir desde el panel. */
class EventContent
{
    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return [
            'seo' => [
                'title' => 'Astrofest Gemínidas 2026 · Lluvia de estrellas Teotihuacán',
                'description' => 'Noche de telescopios, fogata y estrellas fugaces en Teotihuacán. Sábado 12 de diciembre de 2026, a una hora de la CDMX. Camping y hotel.',
                'h1' => 'Astrofest Gemínidas 2026: una noche bajo la lluvia de estrellas en Teotihuacán',
                'keywords' => 'lluvia de estrellas Gemínidas 2026, Teotihuacán, observación astronómica CDMX, camping astronómico, Astrofest',
            ],
            'hero' => [
                'supra' => 'Primera edición · Lluvia de estrellas Gemínidas',
                'title' => '¿Cuándo fue la última vez que viste caer una estrella?',
                'subtitle' => 'Una noche de telescopios, fogata y estrellas fugaces a los pies de la Ciudad de los Dioses. Ven a cazar Gemínidas en Teotihuacán, a una hora de la CDMX.',
                'cta' => 'Aparta tu lugar',
                'secondary' => '¿Qué vamos a ver?',
                'host' => 'Con la astrónoma Luz Calderón',
            ],
            'closing' => [
                'title' => '¿Cuándo fue la última vez que viste caer una estrella?',
                'text' => 'Aparta tu lugar y mira hacia arriba con nosotros.',
            ],
            'rooms' => [
                'text' => 'Una vez que te inscribas recibirás un correo de confirmación con un código para que realices la reservación directamente con el hotel a un precio especial.',
                'code_note' => 'Reserva directamente con el hotel usando este código, a un precio especial:',
            ],
            'sold_out' => 'Agotado',
        ];
    }
}
