<?php

declare(strict_types=1);

use DaemonManager\Config\EnvLoader;

test('loads env file from script directory', function () {
    $envFile = fixturesPath() . '/.env';

    file_put_contents($envFile, "DM_INTERVAL=3\nDM_MAX_MEMORY=100M\n");

    $loader = new EnvLoader();
    $config = $loader->load(null, fixturesPath() . '/success_script.php');

    expect(dirname(fixturesPath() . '/success_script.php'))->toBe(dirname($envFile));
    expect($config)->toHaveKey('interval');
    expect($config['interval'])->toBe(3);
    expect($config)->toHaveKey('maxMemory');
    expect($config['maxMemory'])->toBe('100M');

    unlink($envFile);
});

test('returns empty array when no env file exists', function () {
    // Ensure no .env file exists in fixtures
    $envFile = fixturesPath() . '/.env';
    if (file_exists($envFile)) {
        unlink($envFile);
    }

    $loader = new EnvLoader();
    $config = $loader->load(null, fixturesPath() . '/success_script.php');

    expect($config)->toBeArray();
    expect($config)->toBeEmpty();
});

test('loads env file from explicit path', function () {
    $envFile = getTmpPath() . '/custom.env';

    file_put_contents($envFile, "DM_INTERVAL=5\nDM_MAX_CYCLES=3\n");

    $loader = new EnvLoader();
    $config = $loader->load($envFile);

    expect($config['interval'])->toBe(5);
    expect($config['maxCycles'])->toBe(3);

    unlink($envFile);
});

test('returns empty array when script path is null', function () {
    $loader = new EnvLoader();
    $config = $loader->load(null, null);

    expect($config)->toBeEmpty();
});

test('casts integer values correctly', function () {
    $envFile = fixturesPath() . '/.env';

    file_put_contents($envFile, "DM_INTERVAL=2\nDM_MAX_RUNTIME=10\nDM_MAX_CYCLES=10\n");

    $loader = new EnvLoader();
    $config = $loader->load(null, fixturesPath() . '/success_script.php');

    expect($config['interval'])->toBe(2);
    expect($config['maxRuntime'])->toBe(10);
    expect($config['maxCycles'])->toBe(10);

    unlink($envFile);
});

test('handles empty values as null', function () {
    $envFile = fixturesPath() . '/.env';

    file_put_contents($envFile, "DM_INTERVAL=\nDM_LOG_LEVEL=info\n");

    $loader = new EnvLoader();
    $config = $loader->load(null, fixturesPath() . '/success_script.php');

    expect($config)->toHaveKey('interval');
    expect($config['interval'])->toBeNull();
    expect($config['logLevel'])->toBe('info');

    unlink($envFile);
});
