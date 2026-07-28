<?php
$im = imagecreatefrompng("public/images/nuevo/meditacion-oficina.png");
$top = imagecolorsforindex($im, imagecolorat($im, 500, 10));
$bottom = imagecolorsforindex($im, imagecolorat($im, 500, 400));
print_r(['top' => $top, 'bottom' => $bottom]);
