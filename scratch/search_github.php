<?php

function searchGithub($query) {
    $url = "https://api.github.com/search/code?q=" . urlencode($query);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    $res = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($res, true);
    return $data;
}

$queries = [
    'Prenduan path:geojson',
    '352911 extension:geojson',
    'Karduluk extension:geojson',
    'Prenduan extension:json',
    'Pragaan extension:geojson'
];

foreach ($queries as $q) {
    echo "Query: $q\n";
    $result = searchGithub($q);
    if (isset($result['items'])) {
        echo "Found: " . count($result['items']) . " items\n";
        foreach (array_slice($result['items'], 0, 5) as $item) {
            echo " - " . $item['path'] . " in " . $item['repository']['full_name'] . "\n";
        }
    } else {
        echo "Message: " . ($result['message'] ?? 'none') . "\n";
    }
    sleep(2);
}
