<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
$jsonFile = '../js/projects.json';
$data = json_decode(file_get_contents($jsonFile), true);
$request = json_decode(file_get_contents("php://input"), true);

switch ($request['action']) {
    case 'update':
        foreach ($data as &$project) {
            if ($project['id'] == $request['project']['id']) {
                // Update all necessary fields
                $project['title'] = $request['project']['title'];
                $project['overview'] = $request['project']['overview'];
                $project['tech'] = $request['project']['tech']; // Update tech field
                $project['linkref'] = $request['project']['linkref']; // Update linkref field
                $project['githubref'] = $request['project']['githubref']; // Update githubref field
                $project['year'] = $request['project']['year']; // Update year field
                break;
            }
        }
        break;

    case 'delete':
        $data = array_filter($data, fn($p) => $p['id'] != $request['id']);
        $data = array_values($data); // Reindex
        break;

    case 'add':
        // Add a new project
        $newId = max(array_column($data, 'id')) + 1;
        $request['project']['id'] = $newId;
        $data[] = $request['project'];
        break;
}

file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT));
echo json_encode(["status" => "success"]);
?>