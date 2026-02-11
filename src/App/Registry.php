<?php

declare(strict_types=1);

namespace Cadence\App;

class Registry
{
    private string $registryDir;

    public function __construct(?string $registryDir = null)
    {
        $this->registryDir = $registryDir ?? $this->defaultRegistryDir();
    }

    public function register(string $name, int $pid, string $script): bool|string
    {
        $this->ensureRegistryDir();

        $existing = $this->get($name);
        if ($existing !== null && $this->isPidAlive($existing['pid'])) {
            return "Process '{$name}' is already running (PID: {$existing['pid']})";
        } elseif ($existing !== null && !$this->isPidAlive($existing['pid'])) {
            if ($script !== $existing['script']) {
                return "Use unique name for running multiple daemon process";
            }
        }

        $data = [
            'pid'        => $pid,
            'name'       => $name,
            'script'     => $script,
            'started_at' => date('Y-m-d H:i:s'),
        ];

        $file = $this->registryDir . '/' . $name . '.json';
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));

        return true;
    }

    public function unregister(string $name): void
    {
        $file = $this->registryDir . '/' . $name . '.json';

        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function get(string $name): ?array
    {
        $file = $this->registryDir . '/' . $name . '.json';

        if (! file_exists($file)) {
            return null;
        }

        $content = file_get_contents($file);
        $data = json_decode($content, true);

        if (! is_array($data)) {
            return null;
        }

        return $data;
    }

    public function all(): array
    {
        $this->ensureRegistryDir();

        $files = glob($this->registryDir . '/*.json');
        $entries = [];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $data = json_decode($content, true);

            if (is_array($data) && isset($data['name'])) {
                $data['alive'] = isset($data['pid']) && $this->isPidAlive($data['pid']);
                $entries[] = $data;
            }
        }

        return $entries;
    }

    public function isPidAlive(int $pid): bool
    {
        if (! function_exists('posix_kill')) {
            // Fallback
            exec("kill -0 {$pid} 2>/dev/null", $output, $exitCode);

            return $exitCode === 0;
        }

        return posix_kill($pid, 0);
    }

    public function deriveName(string $script): string
    {
        // For PHP scripts: extract filename without extension
        if (str_ends_with($script, '.php')) {
            $basename = basename($script, '.php');

            return $this->sanitizeName($basename);
        }

        // For CLI commands: use first word
        $parts = preg_split('/\s+/', trim($script));
        $firstWord = basename($parts[0]);

        return $this->sanitizeName($firstWord);
    }

    public function sanitizeName(string $name): string
    {
        $name = strtolower($name);
        $name = preg_replace('/[^a-z0-9_-]/', '-', $name);
        $name = preg_replace('/-+/', '-', $name);
        $name = trim($name, '-');

        return $name ?: 'cadence-process';
    }

    public function getRegistryDir(): string
    {
        return $this->registryDir;
    }

    private function defaultRegistryDir(): string
    {
        $home = $_SERVER['HOME'] ?? $_ENV['HOME'] ?? getenv('HOME');

        return $home . '/.cadence/registry';
    }

    private function ensureRegistryDir(): void
    {
        if (! is_dir($this->registryDir)) {
            mkdir($this->registryDir, 0755, true);
        }
    }
}
