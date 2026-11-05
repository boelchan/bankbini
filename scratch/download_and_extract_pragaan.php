<?php

set_time_limit(300);
ini_set('memory_limit', '1024M');

$zipUrl = 'https://raw.githubusercontent.com/pararawendy/border-desa-indonesia-geojson/master/indonesia_villages_border.geojson.zip';
$zipPath = __DIR__ . '/indonesia_villages_border.geojson.zip';
$extractedJsonPath = __DIR__ . '/indonesia_villages_border.geojson';

echo "1. Downloading zip (42.8 MB)...\n";
if (!file_exists($zipPath)) {
    $fp = fopen($zipPath, 'w+');
    $ch = curl_init($zipUrl);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    $success = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fp);

    if (!$success || $httpCode >= 400) {
        echo "Download failed with HTTP code $httpCode\n";
        exit(1);
    }
    echo "Download completed: " . round(filesize($zipPath) / 1024 / 1024, 2) . " MB\n";
} else {
    echo "Zip file already exists locally (" . round(filesize($zipPath) / 1024 / 1024, 2) . " MB).\n";
}

echo "2. Extracting zip archive...\n";
$zip = new ZipArchive();
if ($zip->open($zipPath) === true) {
    // Check entry name
    $entryName = $zip->getNameIndex(0);
    echo "Entry in zip: $entryName\n";
    $zip->extractTo(__DIR__, $entryName);
    $zip->close();
    $extractedJsonPath = __DIR__ . '/' . $entryName;
    echo "Extracted to $extractedJsonPath (" . round(filesize($extractedJsonPath) / 1024 / 1024, 2) . " MB)\n";
} else {
    echo "Failed to open zip archive.\n";
    exit(1);
}

echo "3. Filtering for Kecamatan PRAGAAN / SUMENEP...\n";
// The file is a python-style or json array of objects
$content = file_get_contents($extractedJsonPath);

// Mapping nama desa ke kode wilayah BPS Kecamatan Pragaan (352911)
$villageCodes = [
    'KADUARA TIMUR'    => 3529112001,
    'SENDANG'          => 3529112002,
    'ROMBASAN'         => 3529112003,
    'SENTOL LAOK'      => 3529112004,
    'LARANGAN PERRENG' => 3529112005,
    'SENTOL DAYA'      => 3529112006,
    'PAKAMBAN DAYA'    => 3529112007,
    'PAKAMBAN LAOK'    => 3529112008,
    'JADDUNG'          => 3529112009,
    'PRAGAAN LAOK'     => 3529112010,
    'PRAGAAN DAYA'     => 3529112011,
    'PRENDUAN'         => 3529112012,
    'AENG PANAS'       => 3529112013,
    'KARDULUK'         => 3529112014,
];

// In Python representation, keys might be single-quoted: 'province': '...', 'border': [...]
// We can use regex or json if valid JSON
$isJson = ($content[0] === '[' || $content[0] === '{');
$data = json_decode($content, true);

if (!$data) {
    echo "Not standard JSON, converting python-like syntax to JSON...\n";
    // Replace single quotes around keys/strings if it's ast.literal_eval syntax
    // Or we can find objects matching PRAGAAN
    preg_match_all("/\{[^{}]*?'sub_district':\s*'PRAGAAN'[^{}]*?'border':\s*(\[[^}]+?\])\s*\}/is", $content, $matches, PREG_SET_ORDER);
    echo "Regex matches found: " . count($matches) . "\n";
} else {
    echo "Decoded standard JSON. Total rows: " . count($data) . "\n";
    $pragaanRows = [];
    foreach ($data as $row) {
        $sub = strtoupper(trim($row['sub_district'] ?? ''));
        $dist = strtoupper(trim($row['district'] ?? ''));
        if ($sub === 'PRAGAAN' && ($dist === 'SUMENEP' || empty($dist))) {
            $pragaanRows[] = $row;
        }
    }
    echo "Pragaan rows found: " . count($pragaanRows) . "\n";
}

