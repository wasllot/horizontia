<?php

namespace App\Support;

/**
 * Generador de códigos QR sin dependencias (modo byte, corrección M, versiones 1 a 10: hasta ~210 caracteres).
 * Sirve para los boletos de eventos: el hosting no permite instalar paquetes nuevos de Composer.
 */
class QrCode
{
    /** versión => [codewords totales, ec por bloque, [[bloques, datos por bloque], ...]] (nivel M). */
    private const SPEC = [
        1 => [26, 10, [[1, 16]]],
        2 => [44, 16, [[1, 28]]],
        3 => [70, 26, [[1, 44]]],
        4 => [100, 18, [[2, 32]]],
        5 => [134, 24, [[2, 43]]],
        6 => [172, 16, [[4, 27]]],
        7 => [196, 18, [[4, 31]]],
        8 => [242, 22, [[2, 38], [2, 39]]],
        9 => [292, 22, [[3, 36], [2, 37]]],
        10 => [346, 26, [[4, 43], [1, 44]]],
    ];

    private const ALIGN = [1 => [], 2 => [6, 18], 3 => [6, 22], 4 => [6, 26], 5 => [6, 30], 6 => [6, 34], 7 => [6, 22, 38], 8 => [6, 24, 42], 9 => [6, 26, 46], 10 => [6, 28, 50]];

    /** @var array<int,int> */
    private static array $exp = [];
    /** @var array<int,int> */
    private static array $log = [];

    /** @return array<int, array<int, bool>> matriz de módulos (true = oscuro) */
    public static function matrix(string $data): array
    {
        $bytes = array_values(unpack('C*', $data) ?: []);
        $version = null;
        foreach (self::SPEC as $v => [, , $groups]) {
            $capacity = 0;
            foreach ($groups as [$n, $d]) {
                $capacity += $n * $d;
            }
            $countBits = $v >= 10 ? 16 : 8;
            if (4 + $countBits + 8 * count($bytes) <= 8 * $capacity) {
                $version = $v;
                break;
            }
        }
        if ($version === null) {
            throw new \InvalidArgumentException('El texto es demasiado largo para el QR.');
        }

        [$total, $ecLen, $groups] = self::SPEC[$version];
        $dataCw = 0;
        foreach ($groups as [$n, $d]) {
            $dataCw += $n * $d;
        }

        // Flujo de bits: modo byte + longitud + datos + terminador + relleno.
        $bits = '0100' . str_pad(decbin(count($bytes)), $version >= 10 ? 16 : 8, '0', STR_PAD_LEFT);
        foreach ($bytes as $b) {
            $bits .= str_pad(decbin($b), 8, '0', STR_PAD_LEFT);
        }
        $bits .= str_repeat('0', min(4, $dataCw * 8 - strlen($bits)));
        $bits .= str_repeat('0', (8 - strlen($bits) % 8) % 8);
        $words = array_map('bindec', str_split($bits, 8));
        for ($pad = 0; count($words) < $dataCw; $pad++) {
            $words[] = $pad % 2 === 0 ? 0xEC : 0x11;
        }

        // Bloques con su corrección de errores, intercalados.
        self::initField();
        $gen = self::generator($ecLen);
        $dataBlocks = [];
        $ecBlocks = [];
        $offset = 0;
        foreach ($groups as [$n, $d]) {
            for ($i = 0; $i < $n; $i++) {
                $block = array_slice($words, $offset, $d);
                $offset += $d;
                $dataBlocks[] = $block;
                $ecBlocks[] = self::remainder($block, $gen);
            }
        }
        $final = [];
        $maxData = max(array_map('count', $dataBlocks));
        for ($i = 0; $i < $maxData; $i++) {
            foreach ($dataBlocks as $block) {
                if (isset($block[$i])) {
                    $final[] = $block[$i];
                }
            }
        }
        for ($i = 0; $i < $ecLen; $i++) {
            foreach ($ecBlocks as $block) {
                $final[] = $block[$i];
            }
        }
        $stream = '';
        foreach ($final as $w) {
            $stream .= str_pad(decbin($w), 8, '0', STR_PAD_LEFT);
        }
        $stream .= str_repeat('0', $version >= 2 && $version <= 6 ? 7 : 0);

        $size = 17 + 4 * $version;
        [$modules, $reserved] = self::functionPatterns($version, $size);

        // Colocar los datos en zigzag.
        $i = 0;
        $len = strlen($stream);
        $up = true;
        for ($col = $size - 1; $col > 0; $col -= 2) {
            if ($col === 6) {
                $col--;
            }
            for ($n = 0; $n < $size; $n++) {
                $row = $up ? $size - 1 - $n : $n;
                for ($c = 0; $c < 2; $c++) {
                    $x = $col - $c;
                    if ($reserved[$row][$x]) {
                        continue;
                    }
                    $modules[$row][$x] = $i < $len && $stream[$i] === '1';
                    $i++;
                }
            }
            $up = !$up;
        }

        // Elegir la máscara con menos penalización.
        $best = null;
        $bestScore = PHP_INT_MAX;
        for ($mask = 0; $mask < 8; $mask++) {
            $candidate = self::applyMask($modules, $reserved, $size, $mask);
            self::drawFormat($candidate, $size, $mask);
            if ($version >= 7) {
                self::drawVersion($candidate, $size, $version);
            }
            $score = self::penalty($candidate, $size);
            if ($score < $bestScore) {
                $bestScore = $score;
                $best = $candidate;
            }
        }

        return $best;
    }

    /** PNG (GD) listo para incrustar en un correo. */
    public static function png(string $data, int $scale = 8, int $margin = 3): string
    {
        $m = self::matrix($data);
        $n = count($m);
        $px = ($n + 2 * $margin) * $scale;
        $img = imagecreatetruecolor($px, $px);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 11, 15, 38);
        imagefilledrectangle($img, 0, 0, $px, $px, $white);
        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x < $n; $x++) {
                if ($m[$y][$x]) {
                    imagefilledrectangle($img, ($x + $margin) * $scale, ($y + $margin) * $scale, ($x + $margin + 1) * $scale - 1, ($y + $margin + 1) * $scale - 1, $black);
                }
            }
        }
        ob_start();
        imagepng($img);
        $png = (string) ob_get_clean();
        imagedestroy($img);

        return $png;
    }

    public static function svg(string $data, int $margin = 3): string
    {
        $m = self::matrix($data);
        $n = count($m);
        $d = '';
        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x < $n; $x++) {
                if ($m[$y][$x]) {
                    $d .= 'M' . ($x + $margin) . ' ' . ($y + $margin) . 'h1v1h-1z';
                }
            }
        }
        $s = $n + 2 * $margin;

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $s . ' ' . $s . '" shape-rendering="crispEdges"><rect width="' . $s . '" height="' . $s . '" fill="#fff"/><path d="' . $d . '" fill="#0b0f26"/></svg>';
    }

    // ---- Aritmética GF(256) y Reed-Solomon ----

    private static function initField(): void
    {
        if (self::$exp) {
            return;
        }
        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            self::$exp[$i] = $x;
            self::$log[$x] = $i;
            $x <<= 1;
            if ($x & 0x100) {
                $x ^= 0x11D;
            }
        }
        for ($i = 255; $i < 512; $i++) {
            self::$exp[$i] = self::$exp[$i - 255];
        }
    }

    private static function mul(int $a, int $b): int
    {
        return ($a === 0 || $b === 0) ? 0 : self::$exp[self::$log[$a] + self::$log[$b]];
    }

    /** @return array<int,int> */
    private static function generator(int $degree): array
    {
        $g = [1];
        for ($i = 0; $i < $degree; $i++) {
            $next = array_fill(0, count($g) + 1, 0);
            foreach ($g as $j => $coef) {
                $next[$j] ^= $coef;
                $next[$j + 1] ^= self::mul($coef, self::$exp[$i]);
            }
            $g = $next;
        }

        return $g;
    }

    /** @return array<int,int> */
    private static function remainder(array $data, array $gen): array
    {
        $degree = count($gen) - 1;
        $res = array_merge($data, array_fill(0, $degree, 0));
        foreach ($data as $i => $_) {
            $coef = $res[$i];
            if ($coef !== 0) {
                foreach ($gen as $j => $g) {
                    $res[$i + $j] ^= self::mul($g, $coef);
                }
            }
        }

        return array_slice($res, count($data));
    }

    // ---- Patrones de función ----

    /** @return array{0: array<int,array<int,bool>>, 1: array<int,array<int,bool>>} */
    private static function functionPatterns(int $version, int $size): array
    {
        $m = array_fill(0, $size, array_fill(0, $size, false));
        $r = $m;
        $set = function (int $y, int $x, bool $dark) use (&$m, &$r, $size) {
            if ($y >= 0 && $y < $size && $x >= 0 && $x < $size) {
                $m[$y][$x] = $dark;
                $r[$y][$x] = true;
            }
        };

        foreach ([[0, 0], [0, $size - 7], [$size - 7, 0]] as [$oy, $ox]) {
            for ($dy = -1; $dy <= 7; $dy++) {
                for ($dx = -1; $dx <= 7; $dx++) {
                    $inside = $dy >= 0 && $dy <= 6 && $dx >= 0 && $dx <= 6;
                    $dark = $inside && ($dy === 0 || $dy === 6 || $dx === 0 || $dx === 6 || ($dy >= 2 && $dy <= 4 && $dx >= 2 && $dx <= 4));
                    $set($oy + $dy, $ox + $dx, $dark);
                }
            }
        }
        for ($i = 8; $i < $size - 8; $i++) {
            $set(6, $i, $i % 2 === 0);
            $set($i, 6, $i % 2 === 0);
        }
        $pos = self::ALIGN[$version];
        foreach ($pos as $ay) {
            foreach ($pos as $ax) {
                if (($ay === 6 && $ax === 6) || ($ay === 6 && $ax === end($pos)) || ($ax === 6 && $ay === end($pos))) {
                    continue;
                }
                for ($dy = -2; $dy <= 2; $dy++) {
                    for ($dx = -2; $dx <= 2; $dx++) {
                        $set($ay + $dy, $ax + $dx, max(abs($dy), abs($dx)) !== 1);
                    }
                }
            }
        }
        // Módulo oscuro y zonas de formato/versión (se dibujan después).
        $set($size - 8, 8, true);
        for ($i = 0; $i < 9; $i++) {
            if (!$r[8][$i]) {
                $r[8][$i] = true;
            }
            if (!$r[$i][8]) {
                $r[$i][8] = true;
            }
        }
        for ($i = 0; $i < 8; $i++) {
            $r[8][$size - 1 - $i] = true;
            $r[$size - 1 - $i][8] = true;
        }
        if ($version >= 7) {
            for ($i = 0; $i < 6; $i++) {
                for ($j = 0; $j < 3; $j++) {
                    $r[$i][$size - 11 + $j] = true;
                    $r[$size - 11 + $j][$i] = true;
                }
            }
        }

        return [$m, $r];
    }

    private static function applyMask(array $modules, array $reserved, int $size, int $mask): array
    {
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if ($reserved[$y][$x]) {
                    continue;
                }
                $flip = match ($mask) {
                    0 => ($y + $x) % 2 === 0,
                    1 => $y % 2 === 0,
                    2 => $x % 3 === 0,
                    3 => ($y + $x) % 3 === 0,
                    4 => (intdiv($y, 2) + intdiv($x, 3)) % 2 === 0,
                    5 => ($y * $x) % 2 + ($y * $x) % 3 === 0,
                    6 => (($y * $x) % 2 + ($y * $x) % 3) % 2 === 0,
                    default => (($y + $x) % 2 + ($y * $x) % 3) % 2 === 0,
                };
                if ($flip) {
                    $modules[$y][$x] = !$modules[$y][$x];
                }
            }
        }

        return $modules;
    }

    private static function drawFormat(array &$m, int $size, int $mask): void
    {
        $data = (0b00 << 3) | $mask;   // nivel M = 00
        $rem = $data;
        for ($i = 0; $i < 10; $i++) {
            $rem = ($rem << 1) ^ (($rem >> 9) * 0x537);
        }
        $bits = (($data << 10) | $rem) ^ 0x5412;
        $bit = fn (int $i) => (($bits >> $i) & 1) === 1;

        for ($i = 0; $i <= 5; $i++) {
            $m[$i][8] = $bit($i);
        }
        $m[7][8] = $bit(6);
        $m[8][8] = $bit(7);
        $m[8][7] = $bit(8);
        for ($i = 9; $i < 15; $i++) {
            $m[8][14 - $i] = $bit($i);
        }
        for ($i = 0; $i < 8; $i++) {
            $m[8][$size - 1 - $i] = $bit($i);
        }
        for ($i = 8; $i < 15; $i++) {
            $m[$size - 15 + $i][8] = $bit($i);
        }
        $m[$size - 8][8] = true;
    }

    private static function drawVersion(array &$m, int $size, int $version): void
    {
        $rem = $version;
        for ($i = 0; $i < 12; $i++) {
            $rem = ($rem << 1) ^ (($rem >> 11) * 0x1F25);
        }
        $bits = ($version << 12) | $rem;
        for ($i = 0; $i < 18; $i++) {
            $dark = (($bits >> $i) & 1) === 1;
            $a = $size - 11 + $i % 3;
            $b = intdiv($i, 3);
            $m[$b][$a] = $dark;
            $m[$a][$b] = $dark;
        }
    }

    private static function penalty(array $m, int $size): int
    {
        $score = 0;
        for ($pass = 0; $pass < 2; $pass++) {
            for ($a = 0; $a < $size; $a++) {
                $run = 1;
                $line = '';
                for ($b = 0; $b < $size; $b++) {
                    $cur = $pass === 0 ? $m[$a][$b] : $m[$b][$a];
                    $line .= $cur ? '1' : '0';
                    if ($b > 0) {
                        $prev = $pass === 0 ? $m[$a][$b - 1] : $m[$b - 1][$a];
                        if ($cur === $prev) {
                            $run++;
                            if ($run === 5) {
                                $score += 3;
                            } elseif ($run > 5) {
                                $score++;
                            }
                        } else {
                            $run = 1;
                        }
                    }
                }
                $score += 40 * (substr_count($line, '10111010000') + substr_count($line, '00001011101'));
            }
        }
        for ($y = 0; $y < $size - 1; $y++) {
            for ($x = 0; $x < $size - 1; $x++) {
                $c = $m[$y][$x];
                if ($c === $m[$y][$x + 1] && $c === $m[$y + 1][$x] && $c === $m[$y + 1][$x + 1]) {
                    $score += 3;
                }
            }
        }
        $dark = 0;
        foreach ($m as $row) {
            $dark += count(array_filter($row));
        }
        $percent = $dark * 100 / ($size * $size);
        $score += (int) (abs($percent - 50) / 5) * 10;

        return $score;
    }
}
