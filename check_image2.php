<?php
$im = imagecreatefrompng("public/images/nuevo/meditacion-oficina.png");
$width = imagesx($im);
$height = imagesy($im);

$yellowRowEnd = 0;
for($y = 0; $y < $height; $y++) {
    $colors = imagecolorsforindex($im, imagecolorat($im, $width/2, $y));
    // Check if it's yellow roughly
    if(!($colors['red'] > 200 && $colors['green'] > 200 && $colors['blue'] < 50)) {
        $yellowRowEnd = $y;
        break;
    }
}

$whiteRowStart = $height;
for($y = $height - 1; $y >= 0; $y--) {
    $colors = imagecolorsforindex($im, imagecolorat($im, $width/2, $y));
    // Check if it's white roughly
    if(!($colors['red'] > 245 && $colors['green'] > 245 && $colors['blue'] > 245)) {
        $whiteRowStart = $y + 1;
        break;
    }
}

print_r(['yellow_height' => $yellowRowEnd, 'white_height' => ($height - $whiteRowStart), 'total_height' => $height]);
