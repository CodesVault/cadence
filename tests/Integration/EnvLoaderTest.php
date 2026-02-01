<?php

declare(strict_types=1);

use DaemonManager\Config\EnvLoader;

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

test('loads env file from script directory', function () {
    $envFile = fixturesPath() . '/.env';
    $this->tempFiles[] = $envFile;

    file_put_contents($envFile, "DM_INTERVAL=30\nDM_MAX_MEMORY=256M\n");

    $loader = new EnvLoader();
    $config = $loader->load(null, fixturesPath() . '/success_script.php');

    expect(dirname(fixturesPath() . '/success_script.php'))->toBe(dirname($envFile));
    expect($config)->toHaveKey('interval');
    expect($config['interval'])->toBe(30);
    expect($config)->toHaveKey('maxMemory');
    expect($config['maxMemory'])->toBe('256M');
});

test('returns empty array when no env file exists', function () {
    $loader = new EnvLoader();
    $config = $loader->load(null, fixturesPath() . '/success_script.php');

    expect($config)->toBeArray();
    expect($config)->toBeEmpty();
});

test('loads env file from explicit path', function () {
    $envFile = getTmpPath() . '/custom.env';
    $this->tempFiles[] = $envFile;

    file_put_contents($envFile, "DM_INTERVAL=5\nDM_MAX_ITERATIONS=3\n");

    $loader = new EnvLoader();
    $config = $loader->load($envFile);

    expect($config['interval'])->toBe(5);
    expect($config['maxIterations'])->toBe(3);
});

test('returns empty array when script path is null', function () {
    $loader = new EnvLoader();
    $config = $loader->load(null, null);

    expect($config)->toBeEmpty();
});

test('casts integer values correctly', function () {
    $envFile = fixturesPath() . '/.env';
    $this->tempFiles[] = $envFile;

    file_put_contents($envFile, "DM_INTERVAL=60\nDM_MAX_RUNTIME=3600\nDM_MAX_ITERATIONS=100\n");

    $loader = new EnvLoader();
    $config = $loader->load(null, fixturesPath() . '/success_script.php');

    expect($config['interval'])->toBe(60);
    expect($config['maxRuntime'])->toBe(3600);
    expect($config['maxIterations'])->toBe(100);
});

test('handles empty values as null', function () {
    $envFile = fixturesPath() . '/.env';
    $this->tempFiles[] = $envFile;

    file_put_contents($envFile, "DM_INTERVAL=\nDM_LOG_LEVEL=info\n");

    $loader = new EnvLoader();
    $config = $loader->load(null, fixturesPath() . '/success_script.php');

    expect($config)->toHaveKey('interval');
    expect($config['interval'])->toBeNull();
    expect($config['logLevel'])->toBe('info');
});
