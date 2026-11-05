<?php

function searchGithubRepo($query) {
    $url = "https://api.github.com/search/repositories?q=" . urlencode($query);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

$queries = [
    'batas desa jawa timur',
    'batas desa sumenep',
    'geojson desa jawa timur',
    'peta desa geojson',
    'batas administrasi desa indonesia'
];

foreach ($queries as $q) {
    echo "Query: $q\n";
    $result = searchGithubRepo($q);
    if (isset($result['items'])) {
        echo "Found: " . count($result['items']) . " repos\n";
        foreach (array_slice($result['items'], 0, 5) as $item) {
            echo " - " . $item['full_name'] . ": " . $item['description'] . "\n";
            echo "   URL: " . $item['html_url'] . "\n";
        }
    } else {
        echo "Message: " . ($result['message'] ?? 'none') . "\n";
    }
}
