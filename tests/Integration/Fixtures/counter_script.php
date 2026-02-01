<?php

// Script that increments a counter in a file
// Uses DM_COUNTER_FILE env var or default path

$counterFile = getenv('DM_COUNTER_FILE') ?: dirname(__DIR__, 2) . '/tmp/counter.txt';

$count = 0;
if (file_exists($counterFile)) {
    $count = (int) file_get_contents($counterFile);
}

$count++;
file_put_contents($counterFile, (string) $count);

echo "Count: {$count}\n";
exit(0);
