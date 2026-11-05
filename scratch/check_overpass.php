<?php

$bbox = "-7.15,113.58,-7.05,113.73";
$query = <<<OVERPASS
[out:json][timeout:30];
(
  relation["boundary"="administrative"]($bbox);
);
out tags;
OVERPASS;

$ch = curl_init('https://overpass-api.de/api/interpreter');
curl_setopt($ch, CURLOPT_POSTFIELDS, "data=" . urlencode($query));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'KantongKehamilan/1.0');
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
if (!$data || !isset($data['elements'])) {
    echo "Error or no data: " . substr($response, 0, 300) . "\n";
    exit;
}

foreach ($data['elements'] as $el) {
    $tags = $el['tags'] ?? [];
    echo sprintf(
        "ID: %-10s | Admin Level: %-2s | Name: %s\n",
        $el['id'],
        $tags['admin_level'] ?? '?',
        $tags['name'] ?? '?'
    );
}
