<?php declare(strict_types=1);
namespace Webkernel\Arcanes\Scaffold;

use Webkernel\Arcanes\Matrix\ArtifactMatrix;

/**
 * Writes all files for a new module or aptitude.
 * No prompts, no console output. Throws on failure.
 */
final class Scaffolder
{
    public function scaffold(ScaffoldParams $p): ScaffoldResult
    {
        $result   = new ScaffoldResult();
        $manifest = [];

        $this->mkdir($p->targetPath, $result);
        $this->mkdir($p->targetPath . '/src/Providers', $result);
        $this->touch($p->targetPath . '/src/Providers/.gitkeep', $result);

        foreach ($p->extraDirs as $dir) {
            $full = $p->targetPath . '/' . $dir;
            $this->mkdir($full, $result);
            $this->touch($full . '/.gitkeep', $result);
        }

        $slots = ArtifactMatrix::slots();
        foreach ($p->enabledSlots as $slotName) {
            $slot  = $slots[$slotName] ?? throw new \InvalidArgumentException("Unknown slot [{$slotName}]");
            $key   = $slot['manifestKey'];
            $value = ArtifactMatrix::resolveSlotValue($slotName, $p->handle());

            foreach ($slot['dirs'] as $dir) {
                $this->mkdir($p->targetPath . '/' . $dir, $result);
            }
            foreach ($slot['gitkeep'] as $dir) {
                $this->touch($p->targetPath . '/' . $dir . '/.gitkeep', $result);
            }

            if ($key === 'route_groups') {
                $manifest[$key] = ArtifactMatrix::mergeRouteGroups($manifest[$key] ?? [], $value);
            } elseif ($key === 'helpers_paths') {
                $manifest[$key] = array_merge($manifest[$key] ?? [], (array) $value);
            } elseif (is_array($value) && array_is_list($value)) {
                $manifest[$key] = array_merge($manifest[$key] ?? [], $value);
            } else {
                $manifest[$key] = array_merge($manifest[$key] ?? [], (array) $value);
            }
        }

        $this->writeSlotContent($p, $manifest, $result);

        $providerClass = rtrim($p->namespace, '\\') . '\\Providers\\' . $p->pascal . 'ServiceProvider';
        $this->write($p->targetPath . '/src/Providers/' . $p->pascal . 'ServiceProvider.php', StubRenderer::serviceProvider($p->namespace, $p->pascal), $result);
        $this->write($p->targetPath . '/' . ($p->isAptitude() ? 'aptitude.php' : 'module.php'), StubRenderer::manifest($p, $providerClass, $manifest), $result);
        $this->write($p->targetPath . '/README.md', StubRenderer::readme($p), $result);

        if ($p->isModule()) {
            $this->write($p->targetPath . '/composer.json', StubRenderer::composerJson($p), $result);
            if ($p->license === 'proprietary') {
                $this->write($p->targetPath . '/LICENSE', StubRenderer::licenseFile($p), $result);
            }
        }

        return $result;
    }

    private function writeSlotContent(ScaffoldParams $p, array $manifest, ScaffoldResult $result): void
    {
        foreach ($manifest['lang_paths'] ?? [] as $rel) {
            $enDir = $p->targetPath . '/' . $rel . '/en';
            $this->mkdir($enDir, $result);
            $file = $enDir . '/messages.php';
            if (!file_exists($file)) {
                $this->write($file, StubRenderer::langMessages(), $result);
            }
        }
        foreach ($manifest['config_paths'] ?? [] as $rel) {
            $file = $p->targetPath . '/' . $rel . '/' . $p->slug . '.php';
            if (!file_exists($file)) {
                $this->write($file, StubRenderer::configFile($p->slug, $p->label), $result);
            }
        }
        foreach ($manifest['route_groups'] ?? [] as $group => $files) {
            foreach ((array) $files as $rel) {
                $file = $p->targetPath . '/' . $rel;
                $this->mkdir(dirname($file), $result);
                if (!file_exists($file)) {
                    $this->write($file, StubRenderer::routeFile($p->namespace, $group), $result);
                }
            }
        }
    }

    private function mkdir(string $path, ScaffoldResult $result): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
            $result->recordDir($path);
        }
    }

    private function touch(string $path, ScaffoldResult $result): void
    {
        if (!file_exists($path)) {
            file_put_contents($path, '');
            $result->recordFile($path);
        }
    }

    private function write(string $path, string $content, ScaffoldResult $result): void
    {
        $this->mkdir(dirname($path), $result);
        file_put_contents($path, $content);
        $result->recordFile($path);
    }
}
