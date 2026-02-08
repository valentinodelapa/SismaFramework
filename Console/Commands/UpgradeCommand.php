<?php

/*
 * The MIT License
 *
 * Copyright (c) 2020-present Valentino de Lapa.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace SismaFramework\Console\Commands;

use SismaFramework\Console\BaseClasses\BaseCommand;
use SismaFramework\Console\Services\Upgrade\UpgradeManager;
use SismaFramework\Console\Services\Upgrade\Utils\ReportGenerator;

/**
 * Command for upgrading modules between framework versions
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class UpgradeCommand extends BaseCommand
{

    public function __construct(private UpgradeManager $upgradeManager = new UpgradeManager(),
            private ReportGenerator $reportGenerator = new ReportGenerator())
    {
        
    }

    #[\Override]
    public function checkCompatibility(string $command): bool
    {
        return $command === 'upgrade';
    }

    #[\Override]
    protected function configure(): void
    {
        $this->output(<<<OUTPUT
Usage: php SismaFramework/Console/sisma upgrade <module> [options]

Arguments:
  module               The module name to upgrade

Options:
  --to=VERSION         Target framework version (e.g., 11.0.0) [REQUIRED]
  --from=VERSION       Source framework version (auto-detected from module.json if not provided)
  --dry-run            Preview changes without modifying files (recommended first run)
  --skip-critical      Skip critical files (Public/index.php, Config files)
  --skip-backup        Skip automatic backup creation (not recommended)
  --quiet              Minimal output mode

Description:
  Automatically upgrade a module to a new framework major version by applying
  code transformations for breaking changes.

  IMPORTANT: Always run with --dry-run first to preview changes!

Examples:
  # Preview upgrade (safe, recommended first)
  php SismaFramework/Console/sisma upgrade Blog --to=11.0.0 --dry-run

  # Apply upgrade after reviewing dry-run
  php SismaFramework/Console/sisma upgrade Blog --to=11.0.0

  # Upgrade from specific version
  php SismaFramework/Console/sisma upgrade Blog --from=10.1.7 --to=11.0.0

  # Skip critical files (manual review)
  php SismaFramework/Console/sisma upgrade Blog --to=11.0.0 --skip-critical

  # Minimal output
  php SismaFramework/Console/sisma upgrade Blog --to=11.0.0 --quiet

OUTPUT);
    }

    #[\Override]
    protected function execute(): bool
    {
        $moduleName = $this->getArgument('0');
        $targetVersion = $this->getOption('to');
        $sourceVersion = $this->getOption('from');
        $dryRun = $this->getOption('dry-run') !== null;
        $skipCritical = $this->getOption('skip-critical') !== null;
        $skipBackup = $this->getOption('skip-backup') !== null;
        $quiet = $this->getOption('quiet') !== null;
        if (!$moduleName) {
            $this->output('Error: Module name is required');
            return false;
        }
        if (!$targetVersion) {
            $this->output('Error: Target version is required (--to=VERSION)');
            return false;
        }
        try {
            $this->upgradeManager
                    ->setDryRun($dryRun)
                    ->setSkipCritical($skipCritical)
                    ->setSkipBackup($skipBackup);
            if ($dryRun && !$quiet) {
                $this->output('');
                $this->output('=== DRY-RUN MODE ===');
                $this->output('No files will be modified. This is a preview only.');
                $this->output('');
            }
            $report = $this->upgradeManager->upgrade($moduleName, $targetVersion, $sourceVersion);
            $output = $this->reportGenerator->generate($report, $quiet);
            $this->output($output);
            if ($dryRun && !$quiet && $report->filesModified > 0) {
                $this->output('');
                $this->output('To apply these changes, run the command again without --dry-run');
            }
            return true;
        } catch (\Exception $e) {
            $this->output('');
            $this->output('╔══════════════════════════════════════════════════════════════╗');
            $this->output('║                    UPGRADE FAILED                            ║');
            $this->output('╚══════════════════════════════════════════════════════════════╝');
            $this->output('');
            $this->output('Error: ' . $e->getMessage());
            $this->output('');
            if ($e->getPrevious()) {
                $this->output('Caused by: ' . $e->getPrevious()->getMessage());
                $this->output('');
            }
            return false;
        }
    }
}
