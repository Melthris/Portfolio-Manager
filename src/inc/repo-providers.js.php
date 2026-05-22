<?php
/**
 * Outputs the repository-provider catalogue for browser-side scripts.
 *
 * This is the single source of truth for repository providers used by both the
 * Manage Projects admin page and the public Portfolio renderer. Provider domain
 * lists are used for strict saved-link detection, while aliases are used only by
 * the admin preview field when a user is still typing.
 */

declare(strict_types=1);

header('Content-Type: application/javascript; charset=UTF-8');

$providers = [
    'github' => [
        'label' => 'GitHub',
        'domains' => ['github.com'],
        'aliases' => ['github', 'gith'],
    ],
    'gitlab' => [
        'label' => 'GitLab',
        'domains' => ['gitlab.com'],
        'aliases' => ['gitlab', 'gitl'],
    ],
    'bitbucket' => [
        'label' => 'Bitbucket',
        'domains' => ['bitbucket.org'],
        'aliases' => ['bitbucket', 'bit'],
    ],
    'azure' => [
        'label' => 'Azure Repos',
        'domains' => ['dev.azure.com', 'visualstudio.com', 'ssh.dev.azure.com'],
        'aliases' => ['azure', 'az'],
    ],
    'codeberg' => [
        'label' => 'Codeberg',
        'domains' => ['codeberg.org'],
        'aliases' => ['codeberg'],
    ],
    'gitea' => [
        'label' => 'Gitea',
        'domains' => ['gitea.com', 'try.gitea.io', 'gitea.io'],
        'aliases' => ['gitea'],
    ],
    'sourcehut' => [
        'label' => 'SourceHut',
        'domains' => ['sr.ht', 'git.sr.ht', 'hg.sr.ht'],
        'aliases' => ['sourcehut', 'sr'],
    ],
];
?>
/**
 * Repository providers supported by Portfolio Manager.
 *
 * `domains` are used by public rendering for strict URL matching.
 * `aliases` are used by admin-side live preview while users are typing.
 */
window.portfolioManagerRepoProviders = <?= json_encode($providers, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>;
