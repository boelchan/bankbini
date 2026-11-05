<?php

$url = "https://geoservices.big.go.id/arcgis/rest/services/BATASWILAYAH/Administrasi_AR_KelDesa_10K/MapServer/0/query";

$params = [
    'where' => "WADMKC = 'Pragaan' AND WADMKK = 'Sumenep'",
    'outFields' => '*',
    'f' => 'geojson',
    'outSR' => '4326',
    'returnGeometry' => 'true',
];

$fullUrl = $url . '?' . http_build_query($params);
echo "Fetching: " . $fullUrl . "\n";

$ch = curl_init($fullUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($error) {
    echo "CURL Error: $error\n";
    exit;
}

$data = json_decode($response, true);
if (!$data || !isset($data['features'])) {
    echo "Response preview: " . substr($response, 0, 500) . "\n";
    exit;
}

echo "Total Features Found: " . count($data['features']) . "\n";

foreach ($data['features'] as $i => $feature) {
    $props = $feature['properties'] ?? [];
    echo sprintf(
        "[%2d] %-25s | Desa: %-20s | Kec: %s\n",
        $i + 1,
        $props['NAMOBJ'] ?? '?',
        $props['WADMKD'] ?? $props['NAMOBJ'] ?? '?',
        $props['WADMKC'] ?? '?'
    );
}

if (count($data['features']) > 0) {
    file_put_contents('scratch/pragaan_desa_big.geojson', $response);
    echo "Saved to scratch/pragaan_desa_big.geojson\n";
}
