<?php
/**
 * Project JSON API.
 *
 * Accepts authenticated JSON POST requests for add, update, and delete actions.
 * Project technology data supports both old flat arrays and new categorised tech.
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

pmRequirePermission('can_manage_projects');
pmSendNoCacheHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    pmJsonResponse(['success' => false, 'message' => 'POST required.'], 405);
}

$payload = json_decode((string) file_get_contents('php://input'), true);

if (!is_array($payload)) {
    pmJsonResponse(['success' => false, 'message' => 'Invalid JSON payload.'], 400);
}

$action = pmString($payload['action'] ?? '');
$projects = pmReadProjects();
$projectPayload = is_array($payload['project'] ?? null) ? $payload['project'] : $payload;

/**
 * Builds a normalised project record from API input.
 *
 * @param array<string, mixed> $payload Input payload.
 * @param int $id Project ID to use.
 * @return array<string, mixed> Normalised project.
 */
function pmProjectFromPayload(array $payload, int $id): array
{
    return [
        'id' => $id,
        'title' => pmString($payload['title'] ?? ''),
        'linkref' => pmString($payload['linkref'] ?? ''),
        'githubref' => pmString($payload['githubref'] ?? ''),
        'date' => pmString($payload['date'] ?? ''),
        'overview' => pmString($payload['overview'] ?? ''),
        'tech' => pmNormaliseProjectTech($payload['tech'] ?? []),
        'os' => pmStringList($payload['os'] ?? []),
        'is_visible' => (bool) ($payload['is_visible'] ?? true),
    ];
}

if ($action === 'add') {
    $nextId = $projects === [] ? 1 : max(array_map(static fn (array $project): int => (int) ($project['id'] ?? 0), $projects)) + 1;
    $project = pmProjectFromPayload($projectPayload, $nextId);

    if ($project['title'] === '') {
        pmJsonResponse(['success' => false, 'message' => 'Project title is required.'], 422);
    }

    $projects[] = $project;
    pmWriteProjects($projects);
    pmJsonResponse(['success' => true, 'status' => 'success', 'project' => $project]);
}

if ($action === 'update') {
    $id = (int) ($payload['id'] ?? ($projectPayload['id'] ?? 0));
    $found = false;

    foreach ($projects as &$project) {
        if ((int) ($project['id'] ?? 0) === $id) {
            $project = pmProjectFromPayload($projectPayload, $id);
            $found = true;
            break;
        }
    }
    unset($project);

    if (!$found) {
        pmJsonResponse(['success' => false, 'message' => 'Project not found.'], 404);
    }

    pmWriteProjects($projects);
    pmJsonResponse(['success' => true, 'status' => 'success']);
}

if ($action === 'delete') {
    $id = (int) ($payload['id'] ?? 0);
    $before = count($projects);
    $projects = array_values(array_filter($projects, static fn (array $project): bool => (int) ($project['id'] ?? 0) !== $id));

    if (count($projects) === $before) {
        pmJsonResponse(['success' => false, 'message' => 'Project not found.'], 404);
    }

    pmWriteProjects($projects);
    pmJsonResponse(['success' => true, 'status' => 'success']);
}

pmJsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
