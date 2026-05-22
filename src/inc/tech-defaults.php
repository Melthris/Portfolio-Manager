<?php
/**
 * Default Portfolio Manager technology catalogue.
 *
 * This file is intentionally separate from page-rendering helpers so the same
 * default technology list can be used by database seed/migration code and by
 * the runtime catalogue helpers without copying the list into multiple files.
 */

declare(strict_types=1);

/**
 * Returns grouped technology categories for projects.
 *
 * @return array<string, string> Category key to label map.
 */
function pmTechCategories(): array
{
    return [
        'languages' => 'Languages',
        'frameworks_libraries' => 'Frameworks / Libraries',
        'game_engines' => 'Game Engines',
        'runtimes' => 'Runtimes',
        'databases' => 'Databases',
        'data_markup_config' => 'Data / Markup / Config',
        'tools_platforms' => 'Tools / Platforms',
        'cloud_hosting' => 'Cloud / Hosting',
        'design_ui' => 'Design / UI',
        'misc' => 'Other / Misc',
    ];
}

/**
 * Runtime catalogue note.
 *
 * The active catalogue is loaded from SQLite by pmTechCatalogue() in
 * functions.php. This file only owns the stable default category list and the
 * shipped seed data used when creating or repairing the database catalogue.
 */

/**
 * Returns the built-in technology catalogue shipped with Portfolio Manager.
 *
 * Custom user-added items are merged in by pmTechCatalogue(), so this function
 * stays as the stable default list that can be restored or updated safely.
 *
 * @return array<string, array{label:string,category:string,icon:string|null}> Built-in tech catalogue.
 */
function pmDefaultTechCatalogue(): array
{
    $items = [
        // Languages.
        'html' => ['label' => 'HTML', 'category' => 'languages', 'icon' => 'HTML.svg'],
        'css' => ['label' => 'CSS', 'category' => 'languages', 'icon' => 'CSS.svg'],
        'javascript' => ['label' => 'Javascript', 'category' => 'languages', 'icon' => 'Javascript.svg'],
        'typescript' => ['label' => 'Typescript', 'category' => 'languages', 'icon' => 'Typescript.svg'],
        'php' => ['label' => 'PHP', 'category' => 'languages', 'icon' => 'PHP.svg'],
        'python' => ['label' => 'Python', 'category' => 'languages', 'icon' => 'Python.webp'],
        'csharp' => ['label' => 'C#', 'category' => 'languages', 'icon' => 'C Sharp.svg'],
        'c' => ['label' => 'C', 'category' => 'languages', 'icon' => 'C.svg'],
        'cplusplus' => ['label' => 'C++', 'category' => 'languages', 'icon' => 'C++.svg'],
        'assembly' => ['label' => 'Assembly', 'category' => 'languages', 'icon' => 'Assembly.svg'],
        'lua' => ['label' => 'Lua', 'category' => 'languages', 'icon' => 'Lua.svg'],
        'ruby' => ['label' => 'Ruby', 'category' => 'languages', 'icon' => 'Ruby.svg'],
        'rust' => ['label' => 'Rust', 'category' => 'languages', 'icon' => 'Rust.webp'],
        'java' => ['label' => 'Java', 'category' => 'languages', 'icon' => 'Java.svg'],
        'go' => ['label' => 'Go', 'category' => 'languages', 'icon' => 'Go.png'],
        'kotlin' => ['label' => 'Kotlin', 'category' => 'languages', 'icon' => 'Kotlin.png'],
        'swift' => ['label' => 'Swift', 'category' => 'languages', 'icon' => 'Swift.png'],
        'dart' => ['label' => 'Dart', 'category' => 'languages', 'icon' => 'Dart.svg'],
        'objectivec' => ['label' => 'Objective-C', 'category' => 'languages', 'icon' => 'Objective-C.svg'],
        'sql' => ['label' => 'SQL', 'category' => 'languages', 'icon' => 'SQL.png'],
        'bash' => ['label' => 'Bash', 'category' => 'languages', 'icon' => 'Bash.svg'],
        'powershell' => ['label' => 'PowerShell', 'category' => 'languages', 'icon' => 'PowerShell.png'],
        'r' => ['label' => 'R', 'category' => 'languages', 'icon' => 'R.png'],
        'scala' => ['label' => 'Scala', 'category' => 'languages', 'icon' => 'Scala.svg'],
        'elixir' => ['label' => 'Elixir', 'category' => 'languages', 'icon' => 'Elixir.svg'],
        'erlang' => ['label' => 'Erlang', 'category' => 'languages', 'icon' => 'Erlang.svg'],
        'haskell' => ['label' => 'Haskell', 'category' => 'languages', 'icon' => 'Haskell.svg'],
        'fsharp' => ['label' => 'F#', 'category' => 'languages', 'icon' => 'FSharp.svg'],
        'perl' => ['label' => 'Perl', 'category' => 'languages', 'icon' => 'Perl.png'],
        'matlab' => ['label' => 'MATLAB', 'category' => 'languages', 'icon' => 'MATLAB.svg'],
        'groovy' => ['label' => 'Groovy', 'category' => 'languages', 'icon' => 'Groovy.svg'],
        'vbnet' => ['label' => 'Visual Basic .NET', 'category' => 'languages', 'icon' => 'VisualBasic.NET.svg'],
        'webassembly' => ['label' => 'WebAssembly', 'category' => 'languages', 'icon' => 'WebAssembly.svg'],

        // Frameworks / libraries.
        'dotnet' => ['label' => '.Net', 'category' => 'frameworks_libraries', 'icon' => '.NET.svg'],
        'ajax' => ['label' => 'Ajax', 'category' => 'frameworks_libraries', 'icon' => 'Ajax.svg'],
        'angular' => ['label' => 'Angular', 'category' => 'frameworks_libraries', 'icon' => 'Angular.svg'],
        'bootstrap' => ['label' => 'Bootstrap', 'category' => 'frameworks_libraries', 'icon' => 'Bootstrap.svg'],
        'react' => ['label' => 'React', 'category' => 'frameworks_libraries', 'icon' => 'React.svg'],
        'tailwindcss' => ['label' => 'TailwindCSS', 'category' => 'frameworks_libraries', 'icon' => 'TailwindCSS.svg'],
        'shadcn' => ['label' => 'ShadCN', 'category' => 'frameworks_libraries', 'icon' => 'ShadCN.png'],
        'jquery' => ['label' => 'jQuery', 'category' => 'frameworks_libraries', 'icon' => 'jQuery.svg'],
        'rails' => ['label' => 'RAILS', 'category' => 'frameworks_libraries', 'icon' => 'RubyOnRails.svg'],
        'rubyonrails' => ['label' => 'RAILS', 'category' => 'frameworks_libraries', 'icon' => 'RubyOnRails.svg'],
        'selenium' => ['label' => 'Selenium', 'category' => 'frameworks_libraries', 'icon' => 'Selenium.svg'],
        'laravel' => ['label' => 'Laravel', 'category' => 'frameworks_libraries', 'icon' => 'Laravel.svg'],
        'symfony' => ['label' => 'Symfony', 'category' => 'frameworks_libraries', 'icon' => 'Symfony.webp'],
        'codeigniter' => ['label' => 'CodeIgniter', 'category' => 'frameworks_libraries', 'icon' => 'CodeIgniter.png'],
        'cakephp' => ['label' => 'CakePHP', 'category' => 'frameworks_libraries', 'icon' => 'CakePHP.png'],
        'slim' => ['label' => 'Slim', 'category' => 'frameworks_libraries', 'icon' => 'Slim.png'],
        'express' => ['label' => 'Express.js', 'category' => 'frameworks_libraries', 'icon' => 'Express.js.webp'],
        'nestjs' => ['label' => 'NestJS', 'category' => 'frameworks_libraries', 'icon' => 'NestJS.svg'],
        'nextjs' => ['label' => 'Next.js', 'category' => 'frameworks_libraries', 'icon' => 'NextJS.svg'],
        'nuxt' => ['label' => 'Nuxt', 'category' => 'frameworks_libraries', 'icon' => 'Nuxt.png'],
        'sveltekit' => ['label' => 'SvelteKit', 'category' => 'frameworks_libraries', 'icon' => 'Svelte.svg'],
        'astro' => ['label' => 'Astro', 'category' => 'frameworks_libraries', 'icon' => 'Astro.png'],
        'remix' => ['label' => 'Remix', 'category' => 'frameworks_libraries', 'icon' => 'Remix.svg'],
        'solidjs' => ['label' => 'SolidJS', 'category' => 'frameworks_libraries', 'icon' => 'SolidJS.png'],
        'alpinejs' => ['label' => 'Alpine.js', 'category' => 'frameworks_libraries', 'icon' => 'Alpine.js.svg'],
        'lit' => ['label' => 'Lit', 'category' => 'frameworks_libraries', 'icon' => 'Lit.svg'],
        'threejs' => ['label' => 'Three.js', 'category' => 'frameworks_libraries', 'icon' => 'Three.js.png'],
        'd3js' => ['label' => 'D3.js', 'category' => 'frameworks_libraries', 'icon' => 'D3.js.svg'],
        'chartjs' => ['label' => 'Chart.js', 'category' => 'frameworks_libraries', 'icon' => 'Chart.js.png'],
        'daisyui' => ['label' => 'DaisyUI', 'category' => 'frameworks_libraries', 'icon' => 'DaisyUI.svg'],
        'mui' => ['label' => 'Material UI', 'category' => 'frameworks_libraries', 'icon' => 'MaterialUI.svg'],
        'bulma' => ['label' => 'Bulma', 'category' => 'frameworks_libraries', 'icon' => 'Bulma.svg'],
        'foundation' => ['label' => 'Foundation', 'category' => 'frameworks_libraries', 'icon' => 'Foundation.svg'],
        'sass' => ['label' => 'Sass / SCSS', 'category' => 'frameworks_libraries', 'icon' => 'Sass.png'],
        'less' => ['label' => 'Less', 'category' => 'frameworks_libraries', 'icon' => 'Less.png'],
        'django' => ['label' => 'Django', 'category' => 'frameworks_libraries', 'icon' => 'Django.svg'],
        'fastapi' => ['label' => 'FastAPI', 'category' => 'frameworks_libraries', 'icon' => 'FastAPI.svg'],
        'tensorflow' => ['label' => 'TensorFlow', 'category' => 'frameworks_libraries', 'icon' => 'TensorFlow.png'],
        'scikitlearn' => ['label' => 'scikit-learn', 'category' => 'frameworks_libraries', 'icon' => 'Scikit-learn.png'],
        'pandas' => ['label' => 'Pandas', 'category' => 'frameworks_libraries', 'icon' => 'Pandas.svg'],
        'numpy' => ['label' => 'NumPy', 'category' => 'frameworks_libraries', 'icon' => 'NumPy.svg'],
        'opencv' => ['label' => 'OpenCV', 'category' => 'frameworks_libraries', 'icon' => 'OpenCV.png'],
        'mediapipe' => ['label' => 'MediaPipe', 'category' => 'frameworks_libraries', 'icon' => 'MediaPipe.png'],
        'springboot' => ['label' => 'Spring Boot', 'category' => 'frameworks_libraries', 'icon' => 'Spring Boot.png'],
        'blazor' => ['label' => 'Blazor', 'category' => 'frameworks_libraries', 'icon' => 'Blazor.png'],
        'avalonia' => ['label' => 'Avalonia UI', 'category' => 'frameworks_libraries', 'icon' => 'Avalonia.svg'],
        'dotnetmaui' => ['label' => '.NET MAUI', 'category' => 'frameworks_libraries', 'icon' => 'DotnetMAUI.png'],
        'winui' => ['label' => 'WinUI', 'category' => 'frameworks_libraries', 'icon' => 'WinUI.png'],
        'electron' => ['label' => 'Electron', 'category' => 'frameworks_libraries', 'icon' => 'Electron.png'],
        'tauri' => ['label' => 'Tauri', 'category' => 'frameworks_libraries', 'icon' => 'Tauri.webp'],
        'flutter' => ['label' => 'Flutter', 'category' => 'frameworks_libraries', 'icon' => 'Flutter.png'],
        'reactnative' => ['label' => 'React Native', 'category' => 'frameworks_libraries', 'icon' => 'React.svg'],
        'ionic' => ['label' => 'Ionic', 'category' => 'frameworks_libraries', 'icon' => 'Ionic.svg'],

        // Game engines.
        'bevy' => ['label' => 'Bevy', 'category' => 'game_engines', 'icon' => 'Bevy.svg'],
        'godot' => ['label' => 'Godot', 'category' => 'game_engines', 'icon' => 'Godot.svg'],
        'unity' => ['label' => 'Unity', 'category' => 'game_engines', 'icon' => 'Unity.png'],
        'unrealengine' => ['label' => 'Unreal Engine', 'category' => 'game_engines', 'icon' => 'UnrealEngine.png'],

        // Runtimes.
        'nodejs' => ['label' => 'NodeJS', 'category' => 'runtimes', 'icon' => 'NodeJS.svg'],
        'deno' => ['label' => 'Deno', 'category' => 'runtimes', 'icon' => 'Deno.svg'],
        'phpfpm' => ['label' => 'PHP-FPM', 'category' => 'runtimes', 'icon' => 'PHPfpm.png'],
        'apache' => ['label' => 'Apache', 'category' => 'runtimes', 'icon' => 'Apache.svg'],
        'nginx' => ['label' => 'Nginx', 'category' => 'runtimes', 'icon' => 'Nginx.svg'],
        'v8' => ['label' => 'V8', 'category' => 'runtimes', 'icon' => 'V8.png'],
        'javascriptcore' => ['label' => 'JavaScriptCore', 'category' => 'runtimes', 'icon' => 'JavaScriptCore.svg'],
        'cpython' => ['label' => 'CPython', 'category' => 'runtimes', 'icon' => 'CPython.png'],
        'pypy' => ['label' => 'PyPy', 'category' => 'runtimes', 'icon' => 'PyPy.webp'],


        // Databases.
        'sqlite' => ['label' => 'SQLite', 'category' => 'databases', 'icon' => 'SQLite.svg'],
        'mysql' => ['label' => 'MySQL', 'category' => 'databases', 'icon' => 'MySQL.svg'],
        'postgresql' => ['label' => 'PostgreSQL', 'category' => 'databases', 'icon' => 'PostgreSQL.svg'],
        'mongodb' => ['label' => 'MongoDB', 'category' => 'databases', 'icon' => 'MongoDB.svg'],
        'mariadb' => ['label' => 'MariaDB', 'category' => 'databases', 'icon' => 'MariaDB.svg'],
        'sqlserver' => ['label' => 'MS SQL Server', 'category' => 'databases', 'icon' => 'Microsoft SQL Server.svg'],
        'oracledatabase' => ['label' => 'Oracle Database', 'category' => 'databases', 'icon' => 'Oracle Database.png'],
        'redis' => ['label' => 'Redis', 'category' => 'databases', 'icon' => 'Redis.png'],
        'valkey' => ['label' => 'Valkey', 'category' => 'databases', 'icon' => 'Valkey.png'],
        'cassandra' => ['label' => 'Cassandra', 'category' => 'databases', 'icon' => 'Cassandra.png'],
        'couchdb' => ['label' => 'CouchDB', 'category' => 'databases', 'icon' => 'CouchDB.png'],
        'dynamodb' => ['label' => 'DynamoDB', 'category' => 'databases', 'icon' => 'DynamoDB.png'],
        'cloudfirestore' => ['label' => 'Cloud Firestore', 'category' => 'databases', 'icon' => 'Cloud Firestore.svg'],
        'supabase' => ['label' => 'Supabase', 'category' => 'databases', 'icon' => 'Supabase.svg'],
        'planetscale' => ['label' => 'PlanetScale', 'category' => 'databases', 'icon' => 'PlanetScale.svg'],
        'neon' => ['label' => 'Neon', 'category' => 'databases', 'icon' => 'Neon.webp'],
        'duckdb' => ['label' => 'DuckDB', 'category' => 'databases', 'icon' => 'DuckDB.jpg'],
        'clickhouse' => ['label' => 'ClickHouse', 'category' => 'databases', 'icon' => 'ClickHouse.svg'],
        'influxdb' => ['label' => 'InfluxDB', 'category' => 'databases', 'icon' => 'InfluxDB.svg'],
        'timescaledb' => ['label' => 'TimescaleDB', 'category' => 'databases', 'icon' => 'TimescaleDB.svg'],
        'elasticsearch' => ['label' => 'Elasticsearch', 'category' => 'databases', 'icon' => 'Elasticsearch.svg'],
        'opensearch' => ['label' => 'OpenSearch', 'category' => 'databases', 'icon' => 'OpenSearch.svg'],
        'meilisearch' => ['label' => 'Meilisearch', 'category' => 'databases', 'icon' => 'Meilisearch.svg'],
        'typesense' => ['label' => 'Typesense', 'category' => 'databases', 'icon' => 'Typesense.png'],
        'neo4j' => ['label' => 'Neo4j', 'category' => 'databases', 'icon' => 'Neo4j.svg'],
        'arangodb' => ['label' => 'ArangoDB', 'category' => 'databases', 'icon' => 'ArangoDB.png'],
        'fauna' => ['label' => 'Fauna', 'category' => 'databases', 'icon' => 'Fauna.svg'],
        'turso' => ['label' => 'Turso', 'category' => 'databases', 'icon' => 'Turso.svg'],
        'litefs' => ['label' => 'LiteFS', 'category' => 'databases', 'icon' => 'LiteFS.png'],

        // Data / markup / config.
        'json' => ['label' => 'JSON', 'category' => 'data_markup_config', 'icon' => 'JSON.svg'],
        'yaml' => ['label' => 'YAML', 'category' => 'data_markup_config', 'icon' => 'YAML.svg'],
        'xml' => ['label' => 'XML', 'category' => 'data_markup_config', 'icon' => 'XML.svg'],
        'markdown' => ['label' => 'Markdown', 'category' => 'data_markup_config', 'icon' => 'Markdown.svg'],
        'toml' => ['label' => 'TOML', 'category' => 'data_markup_config', 'icon' => 'TOML.svg'],
        'csv' => ['label' => 'CSV', 'category' => 'data_markup_config', 'icon' => 'CSV.svg'],
        'graphql' => ['label' => 'GraphQL', 'category' => 'data_markup_config', 'icon' => 'GraphQL.svg'],

        // Tools / platforms.

        'docker' => ['label' => 'Docker', 'category' => 'tools_platforms', 'icon' => 'Docker.svg'],
        'stripe' => ['label' => 'Stripe', 'category' => 'tools_platforms', 'icon' => 'Stripe.svg'],
        'googlemapsapi' => ['label' => 'Google Maps API', 'category' => 'tools_platforms', 'icon' => 'GoogleMapsAPI.svg'],
        'arduino' => ['label' => 'Arduino', 'category' => 'tools_platforms', 'icon' => 'Arduino.svg'],

        // Cloud / hosting.
        'aws' => ['label' => 'AWS', 'category' => 'cloud_hosting', 'icon' => 'AWS.png'],
        'vercel' => ['label' => 'Vercel', 'category' => 'cloud_hosting', 'icon' => 'Vercel.svg'],
        'netlify' => ['label' => 'Netlify', 'category' => 'cloud_hosting', 'icon' => 'Netlify.png'],
        'cloudflare' => ['label' => 'Cloudflare', 'category' => 'cloud_hosting', 'icon' => 'Cloudflare.png'],
        'azure' => ['label' => 'Azure', 'category' => 'cloud_hosting', 'icon' => 'Azure.svg'],
        'digitalocean' => ['label' => 'DigitalOcean', 'category' => 'cloud_hosting', 'icon' => 'DigitalOcean.svg'],

        // Design / UI.
        'aseprite' => ['label' => 'Aseprite', 'category' => 'design_ui', 'icon' => 'Aseprite.svg'],
        'blender' => ['label' => 'Blender', 'category' => 'design_ui', 'icon' => 'Blender.svg'],
        'canva' => ['label' => 'Canva', 'category' => 'design_ui', 'icon' => 'Canva.svg'],
        'figma' => ['label' => 'Figma', 'category' => 'design_ui', 'icon' => 'Figma.svg'],
        'photoshop' => ['label' => 'Photoshop', 'category' => 'design_ui', 'icon' => 'Photoshop.svg'],
    ];

    uasort($items, static function (array $left, array $right): int {
        $categoryOrder = array_flip(array_keys(pmTechCategories()));
        $leftCategory = (string) ($left['category'] ?? 'misc');
        $rightCategory = (string) ($right['category'] ?? 'misc');
        $categoryCompare = ($categoryOrder[$leftCategory] ?? PHP_INT_MAX) <=> ($categoryOrder[$rightCategory] ?? PHP_INT_MAX);

        if ($categoryCompare !== 0) {
            return $categoryCompare;
        }

        return strcasecmp((string) ($left['label'] ?? ''), (string) ($right['label'] ?? ''));
    });

    return $items;

}