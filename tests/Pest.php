<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Helper Functions
|--------------------------------------------------------------------------
*/

function fixturesPath(): string
{
    return __DIR__ . '/Integration/Fixtures';
}

function getTmpPath(): string
{
    return __DIR__ . '/tmp';
}

// Ensure tmp directory exists
if (! is_dir(getTmpPath())) {
    mkdir(getTmpPath(), 0777, true);
}

function createTempFile(?string $fileName): string
{
    $path = __DIR__ . '/tmp/' . $fileName . '.txt';
    if (file_exists($path)) {
        unlink($path);
    }

    touch($path);

    return $path;
}

/**
 * Create a null stream for suppressing output in tests.
 *
 * @return resource
 */
function nullStream(): mixed
{
    return fopen('php://memory', 'w');
}
