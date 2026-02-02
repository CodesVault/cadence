<?php

declare(strict_types=1);

use DaemonManager\Console\Application;

test('shows help', function () {
    $app = new Application();

    ob_start();
    $exitCode = $app->run(['dm', '--help']);
    $output = ob_get_clean();

    expect($exitCode)->toBe(0);
    expect($output)->toContain('Daemon Manager');
    expect($output)->toContain('Usage:');
    expect($output)->toContain('Options:');
});

test('shows version', function () {
    $app = new Application();

    ob_start();
    $exitCode = $app->run(['dm', '--version']);
    $output = ob_get_clean();

    expect($exitCode)->toBe(0);
    expect($output)->toContain('Daemon Manager');
    expect($output)->toContain('v1.0.0');
});

test('requires script path', function () {
    $app = new Application(stderr: nullStream());

    ob_start();
    $exitCode = $app->run(['dm']);
    ob_end_clean();

    expect($exitCode)->toBe(1);
});

test('shows config with valid script', function () {
    $app = new Application();

    ob_start();
    $exitCode = $app->run(['dm', fixturesPath() . '/success_script.php', '--config']);
    $output = ob_get_clean();

    expect($exitCode)->toBe(0);
    expect($output)->toContain('Default Configuration:');
    expect($output)->toContain('interval:');
});

test('parses interval option', function () {
    $app = new Application();

    ob_start();
    $exitCode = $app->run([
        'dm',
        fixturesPath() . '/success_script.php',
        '--interval', '30',
        '--config',
    ]);
    $output = ob_get_clean();

    expect($exitCode)->toBe(0);
    expect($output)->toContain('interval: 30');
});

test('parses short options', function () {
    $app = new Application();

    ob_start();
    $exitCode = $app->run([
        'dm',
        fixturesPath() . '/success_script.php',
        '-i', '15',
        '--config',
    ]);
    $output = ob_get_clean();

    expect($exitCode)->toBe(0);
    expect($output)->toContain('interval: 15');
});

test('validates script exists', function () {
    $app = new Application(stderr: nullStream());

    ob_start();
    $exitCode = $app->run(['dm', fixturesPath() . '/nonexistent-script.php']);
    ob_end_clean();

    expect($exitCode)->toBe(1);
});

test('handles unknown options', function () {
    $app = new Application(stderr: nullStream());

    ob_start();
    $exitCode = $app->run(['dm', '--unknown-option']);
    ob_end_clean();

    expect($exitCode)->toBe(1);
});

test('runs ticker with max cycles', function () {
    $app = new Application();

    ob_start();
    $exitCode = $app->run([
        'dm',
        fixturesPath() . '/success_script.php',
        '--max-cycles', '1',
        '--interval', '1',
        '--quiet',
    ]);
    ob_end_clean();

    expect($exitCode)->toBe(0);
});
