<?php

declare(strict_types=1);

use DaemonManager\Config\Config;
use DaemonManager\Runner\Ticker;

beforeEach(function () {
    /* @disregard P1014 Undefined property - Pest dynamic property */
    $this->tempFiles = [];
});

afterEach(function () {
    /* @disregard P1014 Undefined property - Pest dynamic property */
    foreach ($this->tempFiles as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
});

test('executes script successfully', function () {
    $config = new Config([
        'script'        => fixturesPath() . '/success_script.php',
        'interval'      => 1,
        'maxIterations' => 1,
        'logLevel'      => 'quiet',
    ]);

    $ticker = new Ticker($config);
    $exitCode = $ticker->run();

    expect($exitCode)->toBe(0);
    expect($ticker->getIterations())->toBe(1);
});

test('stops after max iterations', function () {
    $counterFile = createTempFile('counter');
    $this->tempFiles[] = $counterFile;

    // Set env var for counter script
    putenv('DM_COUNTER_FILE=' . $counterFile);

    $config = new Config([
        'script'        => fixturesPath() . '/counter_script.php',
        'interval'      => 1,
        'maxIterations' => 3,
        'logLevel'      => 'quiet',
    ]);

    $ticker = new Ticker($config);
    $exitCode = $ticker->run();

    // Clean up env
    putenv('DM_COUNTER_FILE');

    expect($exitCode)->toBe(0);
    expect($ticker->getIterations())->toBe(3);
    expect(file_get_contents($counterFile))->toBe('3');
});

test('continues after script failure', function () {
    $config = new Config([
        'script'        => fixturesPath() . '/failing_script.php',
        'interval'      => 1,
        'maxIterations' => 2,
        'logLevel'      => 'quiet',
    ]);

    $ticker = new Ticker($config);
    $exitCode = $ticker->run();

    expect($exitCode)->toBe(0);
    expect($ticker->getIterations())->toBe(2);
});

test('stops after max runtime', function () {
    $config = new Config([
        'script'     => fixturesPath() . '/success_script.php',
        'interval'   => 1,
        'maxRuntime' => 2,
        'logLevel'   => 'quiet',
    ]);

    $ticker = new Ticker($config);
    $startTime = time();
    $exitCode = $ticker->run();
    $elapsed = time() - $startTime;

    expect($exitCode)->toBe(0);
    expect($elapsed)->toBeGreaterThanOrEqual(2);
    expect($elapsed)->toBeLessThanOrEqual(4);
});

test('tracks elapsed time', function () {
    $config = new Config([
        'script'        => fixturesPath() . '/success_script.php',
        'interval'      => 1,
        'maxIterations' => 1,
        'logLevel'      => 'quiet',
    ]);

    $ticker = new Ticker($config);
    $ticker->run();

    expect($ticker->getElapsedTime())->toBeGreaterThanOrEqual(0);
});

test('can be stopped', function () {
    $config = new Config([
        'script'        => fixturesPath() . '/success_script.php',
        'interval'      => 1,
        'maxIterations' => 100,
        'logLevel'      => 'quiet',
    ]);

    $ticker = new Ticker($config);
    $ticker->stop();
    $exitCode = $ticker->run();

    expect($exitCode)->toBe(0);
    expect($ticker->getIterations())->toBe(0);
});

test('with custom logger', function () {
    $logs = [];
    $logger = function (string $level, string $message) use (&$logs): void {
        $logs[] = ['level' => $level, 'message' => $message];
    };

    $config = new Config([
        'script'        => fixturesPath() . '/success_script.php',
        'interval'      => 1,
        'maxIterations' => 1,
        'logLevel'      => 'info',
    ]);

    $ticker = new Ticker($config, $logger);
    $ticker->run();

    expect($logs)->not->toBeEmpty();
    expect($logs[0]['message'])->toContain('Starting ticker');
});

test('with debug log level', function () {
    $logs = [];
    $logger = function (string $level, string $message) use (&$logs): void {
        $logs[] = ['level' => $level, 'message' => $message];
    };

    $config = new Config([
        'script'        => fixturesPath() . '/success_script.php',
        'interval'      => 1,
        'maxIterations' => 1,
        'logLevel'      => 'debug',
    ]);

    $ticker = new Ticker($config, $logger);
    $ticker->run();

    $debugLogs = array_filter($logs, fn ($log) => str_contains($log['message'], '[debug]'));
    expect($debugLogs)->not->toBeEmpty();
});
