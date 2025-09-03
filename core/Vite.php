<?php

namespace Core;

class Vite
{
    protected string $manifestPath;
    protected string $hotPath;
    protected string $buildDir;

    public function __construct()
    {
        $this->manifestPath = dirname(__DIR__) . '/public/build/.vite/manifest.json';
        $this->hotPath = dirname(__DIR__) . '/public/hot';
        $this->buildDir = '/build/';
    }

    public function isHot()
    {
        // Load environment if not already loaded
        if (!getenv('NODE_ENV') && file_exists(dirname(__DIR__) . '/.env')) {
            $dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__));
            $dotenv->load();
        }
        
        // Check if we're in development mode and hot file exists
        $nodeEnv = getenv('NODE_ENV') ?: 'development';
        return $nodeEnv === 'development' && file_exists($this->hotPath);
    }

    public function hotUrl()
    {
        return rtrim(trim(file_get_contents($this->hotPath)), '/');
    }

    public function asset(string $entry): string
    {
        if ($this->isHot()) {
            return $this->hotUrl() . '/' . $entry;
        }

        if (!file_exists($this->manifestPath)) {
            throw new \Exception("Vite manifest not found. Run `npm run build`.");
        }

        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        if (!isset($manifest[$entry])) {
            throw new \Exception("Entry {$entry} not found in manifest.");
        }

        return $this->buildDir . $manifest[$entry]['file'];
    }

    public function css(string $entry): array
    {
        if ($this->isHot()) {
            return []; // HMR injects CSS automatically
        }

        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        return $manifest[$entry]['css'] ?? [];
    }
}
