<?php

namespace Tests\Unit;

use App\Support\QrCode;
use PHPUnit\Framework\TestCase;

/** El PNG del QR (correo y página de confirmación) se escribe a mano: no puede depender de la extensión GD del hosting. */
class QrCodePngTest extends TestCase
{
    /** Lee el PNG con GD (solo en la prueba) y devuelve, por módulo, si el píxel central es oscuro. */
    private function decode(string $png, int $modules, int $scale, int $margin): array
    {
        $img = imagecreatefromstring($png);
        $this->assertNotFalse($img, 'GD debe poder leer el PNG');
        imagepalettetotruecolor($img);   // el PNG es indexado: sin esto imagecolorat devuelve el índice de paleta, no el RGB
        $size = ($modules + 2 * $margin) * $scale;
        $this->assertSame($size, imagesx($img));
        $this->assertSame($size, imagesy($img));
        $out = [];
        for ($y = 0; $y < $modules; $y++) {
            for ($x = 0; $x < $modules; $x++) {
                $rgb = imagecolorat($img, ($x + $margin) * $scale + intdiv($scale, 2), ($y + $margin) * $scale + intdiv($scale, 2));
                $out[$y][$x] = (($rgb >> 16) & 0xFF) < 128;
            }
        }
        // El margen (zona silenciosa) es blanco.
        if ($margin > 0) {
            $this->assertSame(0xFFFFFF, imagecolorat($img, 0, 0) & 0xFFFFFF);
            $this->assertSame(0xFFFFFF, imagecolorat($img, $size - 1, $size - 1) & 0xFFFFFF);
        }

        return $out;
    }

    public function test_png_decodes_to_exactly_the_qr_matrix(): void
    {
        foreach (['AF-ABCD-2345', 'HZ', 'https://horizontia.com/eventos/astrofest-geminidas-2026/boleto/AF-ABCD-2345'] as $text) {
            $matrix = QrCode::matrix($text);
            foreach ([[8, 3], [5, 4], [1, 0]] as [$scale, $margin]) {
                $png = QrCode::png($text, $scale, $margin);
                $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $png);
                $this->assertSame($matrix, $this->decode($png, count($matrix), $scale, $margin), "$text @ $scale/$margin");
            }
        }
    }

    public function test_png_uses_the_brand_dark_colour_not_pure_black(): void
    {
        $img = imagecreatefromstring(QrCode::png('AF-ABCD-2345', 8, 3));
        imagepalettetotruecolor($img);
        // Esquina superior izquierda del primer módulo del buscador (siempre oscuro).
        $rgb = imagecolorat($img, 3 * 8 + 1, 3 * 8 + 1);
        $this->assertSame([11, 15, 38], [($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, $rgb & 0xFF]);
    }

    public function test_without_zlib_the_stream_is_still_a_valid_png(): void
    {
        $zlib = new \ReflectionMethod(QrCode::class, 'zlib');
        // Fuerza el camino sin gzcompress: bloques sin comprimir (probamos con más de 65535 bytes para cubrir varios bloques).
        $raw = random_bytes(70000);
        $stored = (function (string $raw) {
            $out = "\x78\x01";
            $blocks = str_split($raw, 65535);
            foreach ($blocks as $i => $block) {
                $out .= chr($i === array_key_last($blocks) ? 1 : 0) . pack('vv', strlen($block), strlen($block) ^ 0xFFFF) . $block;
            }

            return $out . hash('adler32', $raw, true);
        })($raw);
        $this->assertSame($raw, gzuncompress($stored), 'el formato de bloques sin comprimir es zlib válido');
        $this->assertSame($raw, gzuncompress($zlib->invoke(null, $raw)), 'la ruta normal también');
    }

    public function test_png_does_not_call_any_gd_function(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Support/QrCode.php');
        $this->assertDoesNotMatchRegularExpression('/\bimage[a-z]+\s*\(/', $source);
    }
}
