<?php
// Always return JSON and prevent caching
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "status"  => "error",
        "message" => "Method not allowed. Use POST."
    ]);
    exit;
}

// Read JSON request body
$rawBody = file_get_contents("php://input");
$request = json_decode($rawBody, true);

if (!is_array($request)) {
    http_response_code(400);
    echo json_encode([
        "status"  => "error",
        "message" => "Invalid JSON payload",
        "raw"     => $rawBody
    ]);
    exit;
}

$action = $request['action'] ?? '';

$jsonFile = __DIR__ . '/../js/projects.json';
$dataRaw = @file_get_contents($jsonFile);
$data = json_decode($dataRaw, true);

// Ensure $data is an array
if (!is_array($data)) {
    $data = [];
}

switch ($action) {
    case 'update':
        if (!isset($request['project']['id'])) {
            http_response_code(400);
            echo json_encode([
                "status"  => "error",
                "message" => "Missing project id for update"
            ]);
            exit;
        }

        foreach ($data as &$project) {
            if ((int)$project['id'] === (int)$request['project']['id']) {
                $project['title']     = $request['project']['title']     ?? $project['title'];
                $project['overview']  = $request['project']['overview']  ?? $project['overview'];
                $project['tech']      = $request['project']['tech']      ?? $project['tech'];
                $project['linkref']   = $request['project']['linkref']   ?? $project['linkref'];
                $project['githubref'] = $request['project']['githubref'] ?? $project['githubref'];
                $project['date']      = $request['project']['date']      ?? $project['date']; // full date (YYYY-MM-DD)
                break;
            }
        }
        unset($project); // break reference
        break;

    case 'delete':
        if (!isset($request['id'])) {
            http_response_code(400);
            echo json_encode([
                "status"  => "error",
                "message" => "Missing id for delete"
            ]);
            exit;
        }

        $idToDelete = (int)$request['id'];
        $data = array_values(
            array_filter($data, fn($p) => (int)$p['id'] !== $idToDelete)
        );
        break;

    case 'add':
        if (!isset($request['project'])) {
            http_response_code(400);
            echo json_encode([
                "status"  => "error",
                "message" => "Missing project data for add"
            ]);
            exit;
        }

        // Generate a new ID
        $ids = array_column($data, 'id');
        $maxId = empty($ids) ? 0 : max(array_map('intval', $ids));
        $newId = $maxId + 1;

        $newProject = $request['project'];
        $newProject['id'] = $newId;

        // Ensure required fields exist (basic safety)
        $newProject['title']     = $newProject['title']     ?? 'Untitled Project';
        $newProject['overview']  = $newProject['overview']  ?? '';
        $newProject['tech']      = $newProject['tech']      ?? [];
        $newProject['linkref']   = $newProject['linkref']   ?? '';
        $newProject['githubref'] = $newProject['githubref'] ?? '';
        $newProject['date']      = $newProject['date']      ?? date('d-m-Y');

        $data[] = $newProject;
        break;

    default:
        http_response_code(400);
        echo json_encode([
            "status"  => "error",
            "message" => "Invalid or missing action"
        ]);
        exit;
}

// Write back to JSON file
$result = @file_put_contents(
    $jsonFile,
    json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    LOCK_EX
);

if ($result === false) {
    http_response_code(500);
    echo json_encode([
        "status"  => "error",
        "message" => "Failed to write projects.json (check file permissions)"
    ]);
    exit;
}

// All good
echo json_encode(["status" => "success"]);
