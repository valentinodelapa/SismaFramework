<?php

/*
 * The MIT License
 *
 * Copyright 2025 Valentino de Lapa <valentino.delapa@gmail.com>
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
use SismaFramework\Console\Services\Installation\InstallationManager;

/**
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class InstallationCommand extends BaseCommand
{
    private InstallationManager $installationManager;

    public function __construct(InstallationManager $installationManager = new InstallationManager())
    {
        $this->installationManager = $installationManager;
    }

    #[\Override]
    public function checkCompatibility(string $command): bool
    {
        return $command === 'install';
    }

    #[\Override]
    protected function configure(): void
    {
        $this->output(<<<OUTPUT
Usage: php SismaFramework/Console/sisma install <projectName> [options]

Arguments:
  projectName    The name of the project to install

Options:
  --force        Force overwrite existing files
  --db-host=HOST      Database host (default: 127.0.0.1)
  --db-name=NAME      Database name
  --db-user=USER      Database username
  --db-pass=PASS      Database password
  --db-port=PORT      Database port (default: 3306)

Example:
  php SismaFramework/Console/sisma install MyProject --db-name=mydb --db-user=root
OUTPUT);
    }

    #[\Override]
    protected function execute(): bool
    {

        $projectName = $this->getArgument('0');
        if (!$projectName) {
            $this->output('Error: Project name is required');
            return false;
        }
        $force = $this->getOption('force') !== null;
        $config = [];
        if ($dbHost = $this->getOption('db-host')) {
            $config['DATABASE_HOST'] = $dbHost;
        }
        if ($dbName = $this->getOption('db-name')) {
            $config['DATABASE_NAME'] = $dbName;
        }
        if ($dbUser = $this->getOption('db-user')) {
            $config['DATABASE_USERNAME'] = $dbUser;
        }
        if ($dbPass = $this->getOption('db-pass')) {
            $config['DATABASE_PASSWORD'] = $dbPass;
        }
        if ($dbPort = $this->getOption('db-port')) {
            $config['DATABASE_PORT'] = $dbPort;
        }
        $this->installationManager->setForce($force);
        $this->output("Installing SismaFramework project: {$projectName}");
        $this->output('Creating project structure...');
        $this->installationManager->install($projectName, $config);
        $this->output('');
        $this->output('Installation completed successfully!');
        $this->output('');
        $this->output('Project structure created:');
        $this->output('  - Config/configFramework.php');
        $this->output('  - Public/index.php');
        $this->output('  - composer.json');
        $this->output('  - Cache/');
        $this->output('  - Logs/');
        $this->output('  - filesystemMedia/');
        $this->output('');
        $this->output('Next steps:');
        $this->output('  1. Run "composer install" to install dependencies');
        $this->output('  2. Review and update Config/configFramework.php with your settings');
        $this->output('  3. Configure your web server to point to the Public directory');
        $this->output('  4. Start building your application!');
        return true;
    }
}
