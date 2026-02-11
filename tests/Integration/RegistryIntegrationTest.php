<?php

declare(strict_types=1);

use Cadence\App\Registry;
use Cadence\Console\Application;

beforeEach(function () {
    $this->registryDir = getTmpPath() . '/registry_int_' . uniqid();
    mkdir($this->registryDir, 0755, true);
    $this->registry = new Registry($this->registryDir);
});

afterEach(function () {
    // Clean up registry files
    $files = glob($this->registryDir . '/*.json');
    foreach ($files as $file) {
        unlink($file);
    }
    if (is_dir($this->registryDir)) {
        rmdir($this->registryDir);
    }
});

test('list shows no daemons when registry is empty', function () {
    $app = new Application(
        registry: $this->registry,
        stderr: nullStream()
    );

    ob_start();
    $exitCode = $app->run(['cadence', 'list']);
    $output = ob_get_clean();

    expect($exitCode)->toBe(0);
    expect($output)->toContain('No registered daemons');
});

test('list shows registered daemons', function () {
    $this->registry->register('wp-cron', 12345, '/var/www/html/wp-cron.php');
    $this->registry->register('worker', 12346, '/var/www/html/worker.php');

    $app = new Application(
        registry: $this->registry,
        stderr: nullStream()
    );

    ob_start();
    $exitCode = $app->run(['cadence', 'list']);
    $output = ob_get_clean();

    expect($exitCode)->toBe(0);
    expect($output)->toContain('wp-cron');
    expect($output)->toContain('worker');
    expect($output)->toContain('NAME');
    expect($output)->toContain('PID');
});

test('status shows process info', function () {
    $this->registry->register('wp-cron', getmypid(), '/var/www/html/wp-cron.php');

    $app = new Application(
        registry: $this->registry,
        stderr: nullStream()
    );

    ob_start();
    $exitCode = $app->run(['cadence', 'status', 'wp-cron']);
    $output = ob_get_clean();

    expect($exitCode)->toBe(0);
    expect($output)->toContain('wp-cron');
    expect($output)->toContain('running');
    expect($output)->toContain('/var/www/html/wp-cron.php');
});

test('status returns error for non-existent name', function () {
    $app = new Application(
        registry: $this->registry,
        stderr: nullStream()
    );

    ob_start();
    $exitCode = $app->run(['cadence', 'status', 'unknown']);
    $output = ob_get_clean();

    expect($exitCode)->toBe(1);
    expect($output)->toContain('No process found');
});

test('status requires a name argument', function () {
    $app = new Application(
        registry: $this->registry,
        stderr: nullStream()
    );

    ob_start();
    $exitCode = $app->run(['cadence', 'status']);
    ob_end_clean();

    expect($exitCode)->toBe(1);
});

test('stop returns error for non-existent name', function () {
    $app = new Application(
        registry: $this->registry,
        stderr: nullStream()
    );

    ob_start();
    $exitCode = $app->run(['cadence', 'stop', 'unknown']);
    ob_end_clean();

    expect($exitCode)->toBe(1);
});

test('stop requires a name argument', function () {
    $app = new Application(
        registry: $this->registry,
        stderr: nullStream()
    );

    ob_start();
    $exitCode = $app->run(['cadence', 'stop']);
    ob_end_clean();

    expect($exitCode)->toBe(1);
});

test('stop cleans up stale entry', function () {
    // Register with a dead PID
    $this->registry->register('stale-process', 999999, '/path/to/script.php');

    $app = new Application(
        registry: $this->registry,
        stderr: nullStream()
    );

    ob_start();
    $exitCode = $app->run(['cadence', 'stop', 'stale-process']);
    $output = ob_get_clean();

    expect($exitCode)->toBe(0);
    expect($output)->toContain('not running');
    expect($this->registry->get('stale-process'))->toBeNull();
});

test('help shows subcommands', function () {
    $app = new Application(
        registry: $this->registry,
        stderr: nullStream()
    );

    ob_start();
    $exitCode = $app->run(['cadence', '--help']);
    $output = ob_get_clean();

    expect($exitCode)->toBe(0);
    expect($output)->toContain('Commands:');
    expect($output)->toContain('stop');
    expect($output)->toContain('status');
    expect($output)->toContain('list');
});

test('help shows --name option', function () {
    $app = new Application(
        registry: $this->registry,
        stderr: nullStream()
    );

    ob_start();
    $exitCode = $app->run(['cadence', '--help']);
    $output = ob_get_clean();

    expect($exitCode)->toBe(0);
    expect($output)->toContain('--name');
});

test('ticker registers and unregisters process', function () {
    $app = new Application(
        registry: $this->registry,
        stderr: nullStream()
    );

    ob_start();
    $exitCode = $app->run([
        'cadence',
        fixturesPath() . '/success_script.php',
        '--max-cycles', '1',
        '--interval', '1',
        '--quiet',
        '--name', 'test-process',
    ]);
    ob_end_clean();

    expect($exitCode)->toBe(0);
    // After ticker finishes, process should be unregistered
    expect($this->registry->get('test-process'))->toBeNull();
});

test('ticker auto-derives name when --name not provided', function () {
    $app = new Application(
        registry: $this->registry,
        stderr: nullStream()
    );

    ob_start();
    $exitCode = $app->run([
        'cadence',
        fixturesPath() . '/success_script.php',
        '--max-cycles', '1',
        '--interval', '1',
        '--quiet',
    ]);
    ob_end_clean();

    expect($exitCode)->toBe(0);
    // Process should be unregistered after completion
    expect($this->registry->get('success_script'))->toBeNull();
});
