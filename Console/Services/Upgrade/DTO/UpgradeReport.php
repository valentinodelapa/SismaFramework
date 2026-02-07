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

namespace SismaFramework\Console\Services\Upgrade\DTO;

/**
 * Data Transfer Object for upgrade report
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class UpgradeReport
{
    /**
     * @param string $moduleName Module name
     * @param string $fromVersion Source version
     * @param string $toVersion Target version
     * @param string $status Status (SUCCESS, DRY-RUN, FAILED)
     * @param int $filesModified Number of files modified
     * @param int $filesSkipped Number of files skipped
     * @param int $warningsCount Total warnings count
     * @param array $fileResults Array of file transformation results
     * @param array $manualActions Array of manual actions required
     * @param string|null $backupPath Path to backup file (if created)
     */
    public function __construct(
        public readonly string $moduleName,
        public readonly string $fromVersion,
        public readonly string $toVersion,
        public readonly string $status,
        public readonly int $filesModified,
        public readonly int $filesSkipped,
        public readonly int $warningsCount,
        public readonly array $fileResults = [],
        public readonly array $manualActions = [],
        public readonly ?string $backupPath = null
    ) {
    }
}
