<?php

declare(strict_types=1);

use DaemonManager\Log\Logger;

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

test('logs to file when logFile is set', function () {
    $logFile = getTmpPath() . '/test.log';
    $this->tempFiles[] = $logFile;

    $logger = new Logger(Logger::LEVEL_INFO, $logFile);
    $logger->info('Test message');

    expect(file_exists($logFile))->toBeTrue();
    $content = file_get_contents($logFile);
    expect($content)->toContain('[info] Test message');
});

test('logs to stdout when no logFile is set', function () {
    $stream = fopen('php://memory', 'w+');
    $logger = new Logger(Logger::LEVEL_INFO, null, $stream);

    $logger->info('Test stdout');

    rewind($stream);
    $content = stream_get_contents($stream);
    fclose($stream);

    expect($content)->toContain('[info] Test stdout');
});

test('respects log level - info does not log debug', function () {
    $logFile = getTmpPath() . '/level-test.log';
    $this->tempFiles[] = $logFile;

    $logger = new Logger(Logger::LEVEL_INFO, $logFile);
    $logger->debug('Debug message');
    $logger->info('Info message');

    $content = file_get_contents($logFile);
    expect($content)->not->toContain('Debug message');
    expect($content)->toContain('Info message');
});

test('respects log level - debug logs everything', function () {
    $logFile = getTmpPath() . '/debug-level.log';
    $this->tempFiles[] = $logFile;

    $logger = new Logger(Logger::LEVEL_DEBUG, $logFile);
    $logger->debug('Debug message');
    $logger->info('Info message');
    $logger->warning('Warning message');
    $logger->error('Error message');

    $content = file_get_contents($logFile);
    expect($content)->toContain('[debug] Debug message');
    expect($content)->toContain('[info] Info message');
    expect($content)->toContain('[warning] Warning message');
    expect($content)->toContain('[error] Error message');
});

test('quiet level logs nothing', function () {
    $logFile = getTmpPath() . '/quiet-test.log';
    $this->tempFiles[] = $logFile;

    $logger = new Logger(Logger::LEVEL_QUIET, $logFile);
    $logger->debug('Debug');
    $logger->info('Info');
    $logger->warning('Warning');
    $logger->error('Error');

    expect(file_exists($logFile))->toBeFalse();
});

test('warning level only logs warning and error', function () {
    $logFile = getTmpPath() . '/warning-level.log';
    $this->tempFiles[] = $logFile;

    $logger = new Logger(Logger::LEVEL_WARNING, $logFile);
    $logger->debug('Debug');
    $logger->info('Info');
    $logger->warning('Warning');
    $logger->error('Error');

    $content = file_get_contents($logFile);
    expect($content)->not->toContain('Debug');
    expect($content)->not->toContain('Info');
    expect($content)->toContain('[warning] Warning');
    expect($content)->toContain('[error] Error');
});

test('formats message with timestamp', function () {
    $logFile = getTmpPath() . '/format-test.log';
    $this->tempFiles[] = $logFile;

    $logger = new Logger(Logger::LEVEL_INFO, $logFile);
    $logger->info('Test');

    $content = file_get_contents($logFile);
    // Should match format: [YYYY-MM-DD HH:MM:SS] [level] message
    expect($content)->toMatch('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] \[info\] Test/');
});

test('getters return correct values', function () {
    $logger = new Logger(Logger::LEVEL_DEBUG, '/path/to/log.txt');

    expect($logger->getLevel())->toBe(Logger::LEVEL_DEBUG);
    expect($logger->getLogFile())->toBe('/path/to/log.txt');
});

test('getValidLevels returns all levels', function () {
    $levels = Logger::getValidLevels();

    expect($levels)->toContain(Logger::LEVEL_DEBUG);
    expect($levels)->toContain(Logger::LEVEL_INFO);
    expect($levels)->toContain(Logger::LEVEL_WARNING);
    expect($levels)->toContain(Logger::LEVEL_ERROR);
    expect($levels)->toContain(Logger::LEVEL_QUIET);
});

test('appends to existing log file', function () {
    $logFile = getTmpPath() . '/append-test.log';
    $this->tempFiles[] = $logFile;

    file_put_contents($logFile, "Existing content\n");

    $logger = new Logger(Logger::LEVEL_INFO, $logFile);
    $logger->info('New message');

    $content = file_get_contents($logFile);
    expect($content)->toContain('Existing content');
    expect($content)->toContain('New message');
});

test('default log level is info', function () {
    $logger = new Logger();

    expect($logger->getLevel())->toBe(Logger::LEVEL_INFO);
});

test('default log file is null', function () {
    $logger = new Logger();

    expect($logger->getLogFile())->toBeNull();
});
