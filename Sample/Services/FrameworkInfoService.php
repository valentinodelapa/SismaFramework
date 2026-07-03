<?php

namespace SismaFramework\Sample\Services;

/**
 * Espone versione e data di rilascio correnti del framework,
 * ricavandole da composer.json e CHANGELOG.md
 *
 * @author Valentino de Lapa
 */
class FrameworkInfoService
{
    private ?string $version = null;
    private ?string $releaseDate = null;

    public function getVersion(): string
    {
        if ($this->version === null) {
            $composerPath =
                dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . "composer.json";
            $composerData = json_decode(
                file_get_contents($composerPath) ?: "{}",
                true,
            );
            $this->version = $composerData["version"] ?? "";
        }
        return $this->version;
    }

    public function getReleaseDate(): string
    {
        if ($this->releaseDate === null) {
            $this->releaseDate = "";
            $changelogPath =
                dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . "CHANGELOG.md";
            $version = $this->getVersion();
            if ($version !== "" && file_exists($changelogPath)) {
                $changelogContent = file_get_contents($changelogPath);
                $pattern =
                    '/^## \[' .
                    preg_quote($version, "/") .
                    '\] - (\d{4}-\d{2}-\d{2})/m';
                if (preg_match($pattern, $changelogContent, $matches)) {
                    $this->releaseDate = $matches[1];
                }
            }
        }
        return $this->releaseDate;
    }
}
