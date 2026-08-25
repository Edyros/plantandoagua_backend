<?php

$json = json_decode(file_get_contents('C:\\Users\\eduar\\.cursor\\projects\\c-xampp-htdocs-reflorea-backend-reflora-api\\agent-tools\\d1dd4055-d3f4-455d-8a63-dfd84e690a43.txt'), true);

foreach ($json['features'] as $feature) {
    $p = $feature['properties'];
    $iso = $p['ISO_A3'] ?? $p['ADM0_A3'] ?? '';
    $admin = $p['ADMIN'] ?? $p['NAME'] ?? '';

    if ($iso !== 'BRA' && stripos((string) $admin, 'Brazil') === false) {
        continue;
    }

    echo $admin.' '.$iso.PHP_EOL;
    $g = $feature['geometry'];
    echo $g['type'].PHP_EOL;

    file_put_contents(__DIR__.'/brazil_geometry.json', json_encode($g));
    echo 'saved brazil_geometry.json'.PHP_EOL;

    if ($g['type'] === 'Polygon') {
        echo 'pts='.count($g['coordinates'][0]).PHP_EOL;
    }

    if ($g['type'] === 'MultiPolygon') {
        echo 'polys='.count($g['coordinates']).PHP_EOL;
        foreach ($g['coordinates'] as $i => $poly) {
            echo "  {$i} rings=".count($poly).' pts='.count($poly[0]).PHP_EOL;
        }
    }

    break;
}
