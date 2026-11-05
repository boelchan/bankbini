<?php

$url = 'https://github.com/pararawendy/border-desa-indonesia-geojson/raw/main/indonesia_villages_border.geojson.zip';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
curl_exec($ch);
$size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
echo "Size: " . round($size / 1024 / 1024, 2) . " MB\n";
curl_close($ch);
