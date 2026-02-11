<?php

declare(strict_types=1);

use Cadence\App\Registry;

beforeEach(function () {
    $this->registryDir = getTmpPath() . '/registry_' . uniqid();
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

test('registers a process', function () {
    $result = $this->registry->register('wp-cron', 12345, '/var/www/html/wp-cron.php');

    expect($result)->toBeTrue();
    expect(file_exists($this->registryDir . '/wp-cron.json'))->toBeTrue();

    $data = json_decode(file_get_contents($this->registryDir . '/wp-cron.json'), true);
    expect($data['pid'])->toBe(12345);
    expect($data['name'])->toBe('wp-cron');
    expect($data['script'])->toBe('/var/www/html/wp-cron.php');
    expect($data['started_at'])->not->toBeNull();
});

test('unregisters a process', function () {
    $this->registry->register('wp-cron', 12345, '/var/www/html/wp-cron.php');
    $this->registry->unregister('wp-cron');

    expect(file_exists($this->registryDir . '/wp-cron.json'))->toBeFalse();
});

test('unregister non-existent name does nothing', function () {
    $this->registry->unregister('non-existent');

    // No exception thrown
    expect(true)->toBeTrue();
});

test('gets a registered process', function () {
    $this->registry->register('wp-cron', 12345, '/var/www/html/wp-cron.php');

    $data = $this->registry->get('wp-cron');

    expect($data)->not->toBeNull();
    expect($data['pid'])->toBe(12345);
    expect($data['name'])->toBe('wp-cron');
    expect($data['script'])->toBe('/var/www/html/wp-cron.php');
});

test('returns null for non-existent process', function () {
    $data = $this->registry->get('non-existent');

    expect($data)->toBeNull();
});

test('lists all registered processes', function () {
    $this->registry->register('wp-cron', 12345, '/var/www/html/wp-cron.php');
    $this->registry->register('queue-worker', 12346, '/var/www/html/worker.php');

    $all = $this->registry->all();

    expect($all)->toHaveCount(2);
});

test('lists empty when no processes registered', function () {
    $all = $this->registry->all();

    expect($all)->toHaveCount(0);
});

test('detects duplicate name with alive process', function () {
    // Register with current PID (which is alive)
    $pid = getmypid();
    $this->registry->register('wp-cron', $pid, '/var/www/html/wp-cron.php');

    $result = $this->registry->register('wp-cron', 99999, '/var/www/html/wp-cron.php');

    expect($result)->toBeString();
    expect($result)->toContain('already running');
});

test('allows reuse of name when PID is dead', function () {
    // Register with a PID that is almost certainly not alive
    $this->registry->register('wp-cron', 999999, '/var/www/html/wp-cron.php');

    $result = $this->registry->register('wp-cron', 12345, '/var/www/html/wp-cron.php');

    expect($result)->toBeTrue();
});

test('derives name from PHP script path', function () {
    $name = $this->registry->deriveName('/var/www/html/wp-cron.php');
    expect($name)->toBe('wp-cron');

    $name = $this->registry->deriveName('/var/www/html/worker.php');
    expect($name)->toBe('worker');

    $name = $this->registry->deriveName('/path/to/My_Script.php');
    expect($name)->toBe('my_script');
});

test('derives name from CLI command', function () {
    $name = $this->registry->deriveName('curl -s https://example.com');
    expect($name)->toBe('curl');

    $name = $this->registry->deriveName('echo hello');
    expect($name)->toBe('echo');
});

test('sanitizes name correctly', function () {
    expect($this->registry->sanitizeName('My Script'))->toBe('my-script');
    expect($this->registry->sanitizeName('wp_cron'))->toBe('wp_cron');
    expect($this->registry->sanitizeName('---test---'))->toBe('test');
    expect($this->registry->sanitizeName('Hello World!'))->toBe('hello-world');
    expect($this->registry->sanitizeName(''))->toBe('cadence-process');
});

test('returns registry directory', function () {
    expect($this->registry->getRegistryDir())->toBe($this->registryDir);
});

test('creates registry directory if not exists', function () {
    $newDir = getTmpPath() . '/new_registry_' . uniqid();
    $registry = new Registry($newDir);

    $registry->register('test', 12345, '/path/to/test.php');

    expect(is_dir($newDir))->toBeTrue();

    // Cleanup
    unlink($newDir . '/test.json');
    rmdir($newDir);
});

test('handles corrupted JSON file gracefully', function () {
    file_put_contents($this->registryDir . '/broken.json', 'not valid json');

    $data = $this->registry->get('broken');

    expect($data)->toBeNull();
});

test('isPidAlive returns true for current process', function () {
    $pid = getmypid();

    expect($this->registry->isPidAlive($pid))->toBeTrue();
});

test('isPidAlive returns false for non-existent PID', function () {
    // Use a very high PID that is almost certainly not running
    expect($this->registry->isPidAlive(999999))->toBeFalse();
});
