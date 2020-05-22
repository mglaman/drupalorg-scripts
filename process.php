<?php declare(strict_types=1);

function fetch($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json',
    ]);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

function jsonDecode($contents) {
    $decoded = json_decode($contents);
    if (json_last_error()) {
        throw new \RuntimeException(json_last_error_msg());
    }
    return $decoded;
}

$modules = jsonDecode(file_get_contents('commerce-modules.json'));
$procesed_modules = [
    'd7' => [],
    'd8' => [],
    'unknown' => [],
    'error' => [],
];

$modules = array_map(static function (\stdClass $project) {
    return [
        'nid' => $project->nid,
        'machine_name' => $project->field_project_machine_name,
        'url' => $project->url,
    ];
}, $modules);

foreach ($modules as $module) {
    $releases = fetch('https://www.drupal.org/api-d7/node.json?field_release_project=' . $module['nid']);
    if (empty($releases)) {
        $procesed_modules['error'] = $module;
        continue;
    }
    $releases = jsonDecode($releases);
    if (empty($releases->list)) {
        $procesed_modules['unknown'][] = $module;
        continue;
    }
    foreach ($releases->list as $release) {
        if (strpos($release->field_release_version, '8.x') === 0) {
            $procesed_modules['d8'][] = $module;
            break;
        }
        elseif (strpos($release->field_release_version, '7.x') === 0) {
            $procesed_modules['d7'][] = $module;
            break;
        }
        else {
            // Assume semver
            $procesed_modules['d8'][] = $module;
            break;
        }
    }
    file_put_contents('commerce-modules-d8.json', json_encode($procesed_modules['d8'], JSON_PRETTY_PRINT));
    file_put_contents('commerce-modules-d7.json', json_encode($procesed_modules['d7'], JSON_PRETTY_PRINT));
    file_put_contents('commerce-modules-unknown.json', json_encode($procesed_modules['unknown'], JSON_PRETTY_PRINT));
    sleep(1);
}

file_put_contents('commerce-modules-d8.json', json_encode($procesed_modules['d8'], JSON_PRETTY_PRINT));
file_put_contents('commerce-modules-d7.json', json_encode($procesed_modules['d7'], JSON_PRETTY_PRINT));
file_put_contents('commerce-modules-unknown.json', json_encode($procesed_modules['unknown'], JSON_PRETTY_PRINT));
