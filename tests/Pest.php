<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

// uses(TestCase::class)->in('Unit');
// uses(TestCase::class)->in('Integration');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

function fixturesPath(): string
{
    return __DIR__ . '/Integration/Fixtures';
}

function createTempFile(string $fileName): string
{
    $path = __DIR__ . '/tmp/' . $fileName . '.txt';
    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0777, true);
    }
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
