<?php declare(strict_types=1);

function processResponse(&$projects, $contents) {
    if (empty($contents->list)) {
        var_dump($contents);
        exit(1);
    }
    foreach ($contents->list as $project) {
        print $project->field_project_machine_name . PHP_EOL;
        if (strpos($project->field_project_machine_name, 'commerce_') === 0) {
            $projects[] = $project;
        }
        elseif (isset($project->field_project_ecosystem[0]->id) && $project->field_project_ecosystem[0]->id === '605898') {
            $projects[] = $project;
        }
    }
}
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

$rootUrl = 'https://www.drupal.org/api-d7/node?type=project_module&field_project_type=full&sort=nid&direction=desc';
$projects = [];

$contents = jsonDecode(fetch($rootUrl));
processResponse($projects, $contents);
while(isset($contents->next)) {
    print sprintf('Fetching %s', $contents->next) . PHP_EOL;
    $contents = jsonDecode(fetch($contents->next));
    processResponse($projects, $contents);
    sleep(1);
    file_put_contents('commerce-modules.json', json_encode($projects, JSON_PRETTY_PRINT));
}

file_put_contents('commerce-modules.json', json_encode($projects, JSON_PRETTY_PRINT));
