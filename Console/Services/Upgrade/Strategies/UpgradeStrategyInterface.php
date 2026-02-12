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

namespace SismaFramework\Console\Services\Upgrade\Strategies;

/**
 * Interface for upgrade strategies
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
interface UpgradeStrategyInterface
{

    /**
     * Get the source version this strategy upgrades from
     *
     * @return string Source version (e.g., "10.1.7")
     */
    public function getSourceVersion(): string;

    /**
     * Get the target version this strategy upgrades to
     *
     * @return string Target version (e.g., "11.0.0")
     */
    public function getTargetVersion(): string;

    /**
     * Get the list of transformers to apply
     *
     * @return array Array of TransformerInterface instances
     */
    public function getTransformers(): array;

    /**
     * Get the list of breaking changes
     *
     * @return array Array of breaking change descriptions
     */
    public function getBreakingChanges(): array;

    /**
     * Check if this strategy requires manual intervention
     *
     * @return bool True if manual intervention is required
     */
    public function requiresManualIntervention(): bool;
}
