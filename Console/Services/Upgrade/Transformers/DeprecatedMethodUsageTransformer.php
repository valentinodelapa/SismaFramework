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

namespace SismaFramework\Console\Services\Upgrade\Transformers;

use SismaFramework\Console\Services\Upgrade\DTO\TransformationResult;

/**
 * Detects calls to methods removed by an upgrade strategy and flags them for
 * manual review. Unlike MethodRenameTransformer, the replacement is not a
 * fixed identifier (it depends on call-site context, e.g. the property name
 * used to build a magic method name), so no content is modified.
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class DeprecatedMethodUsageTransformer implements TransformerInterface
{

    /**
     * @param array<string, string> $deprecations Map of removed method name to migration guidance
     */
    public function __construct(private array $deprecations = []) {}

    public function canTransform(string $filePath, string $content): bool
    {
        foreach ($this->deprecations as $methodName => $guidance) {
            if (preg_match('/\b' . preg_quote($methodName, '/') . '\s*\(/', $content)) {
                return true;
            }
        }
        return false;
    }

    public function transform(string $content, string $filePath): TransformationResult
    {
        $warnings = [];
        foreach ($this->deprecations as $methodName => $guidance) {
            $occurrences = preg_match_all('/\b' . preg_quote($methodName, '/') . '\s*\(/', $content);
            if ($occurrences > 0) {
                $warnings[] = "Found {$occurrences} call(s) to removed method {$methodName}(): {$guidance}";
            }
        }
        return new TransformationResult(
            transformedContent: $content,
            changesCount: 0,
            confidence: $this->getConfidence(),
            warnings: $warnings,
            requiresManualReview: !empty($warnings),
        );
    }

    public function getConfidence(): int
    {
        return 100;
    }

    public function getDescription(): string
    {
        return 'Detects calls to methods removed by the upgrade strategy and flags them for manual migration';
    }
}
