<?php

$url = "https://gis.bnpb.go.id/arcgis/rest/services/Basemap/Batas_Desa/MapServer/0?f=json";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
$data = json_decode($response, true);
if ($data && isset($data['fields'])) {
    echo "Layer Name: " . ($data['name'] ?? '') . "\n";
    echo "Fields:\n";
    foreach ($data['fields'] as $f) {
        echo " - " . $f['name'] . " (" . $f['type'] . ")\n";
    }
} else {
    echo "Response: " . substr($response, 0, 500) . "\n";
}
