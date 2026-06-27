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
use SismaFramework\Console\Traits\InteractiveInputTrait;

/**
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class InstallationCommand extends BaseCommand
{

    use InteractiveInputTrait;

    public function __construct(private InstallationManager $installationManager = new InstallationManager())
    {
        
    }

    #[\Override]
    public function checkCompatibility(string $command): bool
    {
        return $command === "install";
    }

    #[\Override]
    protected function configure(): void
    {
        $this->output(
                <<<OUTPUT
            Usage: php SismaFramework/Console/sisma install <projectName> [options]

            Arguments:
              projectName    The name of the project to install

            Options:
              --force            Force overwrite existing files
              --skip-db          Skip database configuration
              --db-host=HOST     Relational (ORM) database host (default: 127.0.0.1)
              --db-name=NAME     Relational (ORM) database name
              --db-user=USER     Relational (ORM) database username
              --db-pass=PASS     Relational (ORM) database password
              --db-port=PORT     Relational (ORM) database port (default: 3306)
              --odm-host=HOST    Non-relational (ODM) database host (default: 127.0.0.1)
              --odm-name=NAME    Non-relational (ODM) database name
              --odm-user=USER    Non-relational (ODM) database username
              --odm-pass=PASS    Non-relational (ODM) database password
              --odm-port=PORT    Non-relational (ODM) database port (default: 27017)

            Note:
              If ORM_DATABASE_*/ODM_DATABASE_* environment variables are already
              set (e.g. via Docker env_file), the corresponding interactive/CLI
              database prompt is skipped: Config/configFramework.php reads
              credentials from the environment at runtime, so no value needs to
              be written into the file.

            Example:
              php SismaFramework/Console/sisma install MyProject
              php SismaFramework/Console/sisma install MyProject --skip-db
              php SismaFramework/Console/sisma install MyProject --db-name=mydb --db-user=root
              php SismaFramework/Console/sisma install MyProject --odm-name=mydb --odm-user=root
            OUTPUT,
        );
    }

    #[\Override]
    protected function execute(): bool
    {
        $projectName = $this->getArgument("0");
        if (!$projectName) {
            $this->output("Error: Project name is required");
            return false;
        }

        $force = $this->getOption("force") !== null;
        $skipDb = $this->getOption("skip-db") !== null;

        $this->installationManager->setForce($force);

        $this->output("Installing SismaFramework project: {$projectName}");
        $this->output("");

        $config = [];

        if (!$skipDb) {
            $config = $this->collectDatabaseConfiguration();
        }

        $this->output("");
        $this->output("Creating project structure...");
        $this->installationManager->install($projectName, $config);
        $this->output("");
        $this->output("Installation completed successfully!");
        $this->output("");
        $this->output("Project structure created:");
        $this->output("  - Config/configFramework.php");
        $this->output("  - Public/index.php");
        $this->output("  - .htaccess");
        $this->output("  - composer.json");
        $this->output("  - Cache/");
        $this->output("  - Logs/");
        $this->output("  - filesystemMedia/");
        $this->output("  - {$projectName}/Application/  (module structure)");
        $this->output("");
        $this->output("Next steps:");
        $this->output('  1. Run "composer install" to install dependencies');
        $this->output(
                "  2. Review and update Config/configFramework.php with your settings",
        );
        $this->output(
                "  3. Configure your web server to point to the Public directory",
        );
        $this->output("  4. Start building your application!");
        return true;
    }

    private function collectDatabaseConfiguration(): array
    {
        $config = $this->collectOrmConfiguration();
        $config += $this->collectOdmConfiguration();
        return $config;
    }

    private function collectOrmConfiguration(): array
    {
        if ($this->hasOptionsFromCommandLine(["db-host", "db-name", "db-user", "db-pass", "db-port"])) {
            return $this->getConfigFromOptions("ORM", ["db-host" => "HOST", "db-name" => "NAME", "db-user" => "USERNAME", "db-pass" => "PASSWORD", "db-port" => "PORT"]);
        }

        if ($this->hasConfigFromEnvironment("ORM")) {
            $this->output("Relational database environment variables detected (e.g. ORM_DATABASE_HOST).");
            $this->output("Skipping interactive prompt: Config/configFramework.php already reads these values via getenv() at runtime.");
            return [];
        }

        $this->output("Relational Database Configuration (ORM, optional)");
        $this->output("Press Enter to skip each field or use defaults.");
        $this->output("");

        $configureOrm = $this->askConfirmation("Is there a relational database to configure?", false);

        if (!$configureOrm) {
            return [];
        }

        return $this->askDatabaseCredentials("ORM", "127.0.0.1", "3306");
    }

    private function collectOdmConfiguration(): array
    {
        if ($this->hasOptionsFromCommandLine(["odm-host", "odm-name", "odm-user", "odm-pass", "odm-port"])) {
            return $this->getConfigFromOptions("ODM", ["odm-host" => "HOST", "odm-name" => "NAME", "odm-user" => "USERNAME", "odm-pass" => "PASSWORD", "odm-port" => "PORT"]);
        }

        if ($this->hasConfigFromEnvironment("ODM")) {
            $this->output("Non-relational database environment variables detected (e.g. ODM_DATABASE_HOST).");
            $this->output("Skipping interactive prompt: Config/configFramework.php already reads these values via getenv() at runtime.");
            return [];
        }

        $this->output("Non-Relational Database Configuration (ODM, optional)");
        $this->output("Press Enter to skip each field or use defaults.");
        $this->output("");

        $configureOdm = $this->askConfirmation("Is there a non-relational database to configure?", false);

        if (!$configureOdm) {
            return [];
        }

        return $this->askDatabaseCredentials("ODM", "127.0.0.1", "27017");
    }

    private function askDatabaseCredentials(string $prefix, string $defaultHost, string $defaultPort): array
    {
        $config = [];

        $host = $this->ask("Database Host", $defaultHost);
        if (!empty($host)) {
            $config["{$prefix}_DATABASE_HOST"] = $host;
        }

        $port = $this->ask("Database Port", $defaultPort);
        if (!empty($port)) {
            $config["{$prefix}_DATABASE_PORT"] = $port;
        }

        $name = $this->ask("Database Name", "");
        if (!empty($name)) {
            $config["{$prefix}_DATABASE_NAME"] = $name;
        }

        $user = $this->ask("Database Username", "");
        if (!empty($user)) {
            $config["{$prefix}_DATABASE_USERNAME"] = $user;
        }

        $pass = $this->askSecret("Database Password");
        if (!empty($pass)) {
            $config["{$prefix}_DATABASE_PASSWORD"] = $pass;
        }

        return $config;
    }

    private function hasOptionsFromCommandLine(array $options): bool
    {
        foreach ($options as $option) {
            if ($this->getOption($option) !== null) {
                return true;
            }
        }

        return false;
    }

    private function hasConfigFromEnvironment(string $prefix): bool
    {
        $envVars = ["{$prefix}_DATABASE_HOST", "{$prefix}_DATABASE_NAME", "{$prefix}_DATABASE_USERNAME", "{$prefix}_DATABASE_PASSWORD", "{$prefix}_DATABASE_PORT"];

        foreach ($envVars as $envVar) {
            $value = getenv($envVar);
            if ($value !== false && $value !== "") {
                return true;
            }
        }

        return false;
    }

    private function getConfigFromOptions(string $prefix, array $optionToSuffix): array
    {
        $config = [];

        foreach ($optionToSuffix as $option => $suffix) {
            if ($value = $this->getOption($option)) {
                $config["{$prefix}_DATABASE_{$suffix}"] = $value;
            }
        }

        return $config;
    }
}
