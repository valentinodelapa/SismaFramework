<?php

namespace SismaFramework\Console\BaseClasses;

/**
 * Base class for console commands
 */
abstract class BaseCommand {
    protected array $arguments = [];
    protected array $options = [];
    
    /**
     * Configure command name, arguments and options
     */
    abstract protected function configure(): void;
    
    /**
     * Execute command logic
     */
    abstract protected function execute(): int;
    
    /**
     * Set command arguments
     */
    public function setArguments(array $arguments): void {
        $this->arguments = $arguments;
    }
    
    /**
     * Set command options
     */
    public function setOptions(array $options): void {
        $this->options = $options;
    }
    
    /**
     * Run the command
     */
    public function run(): int {
        $this->configure();
        return $this->execute();
    }
    
    /**
     * Get argument value
     */
    protected function getArgument(string $name): ?string {
        return $this->arguments[$name] ?? null;
    }
    
    /**
     * Get option value
     */
    protected function getOption(string $name): ?string {
        return $this->options[$name] ?? null;
    }
    
    /**
     * Output a message to console
     */
    protected function output(string $message): void {
        echo $message . PHP_EOL;
    }
}
