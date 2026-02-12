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
 * Transformer for renaming methods
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class MethodRenameTransformer implements TransformerInterface
{
    /**
     * @var array<string, string> Map of old method names to new method names
     */
    private array $renames;

    /**
     * @param array<string, string> $renames Map of old method names to new method names
     */
    public function __construct(array $renames = [])
    {
        $this->renames = $renames;
    }

    public function canTransform(string $filePath, string $content): bool
    {
        foreach ($this->renames as $oldMethod => $newMethod) {
            if (str_contains($content, $oldMethod)) {
                return true;
            }
        }
        return false;
    }

    public function transform(string $content, string $filePath): TransformationResult
    {
        $transformedContent = $content;
        $changesCount = 0;
        $warnings = [];
        foreach ($this->renames as $oldMethod => $newMethod) {
            $pattern = '/\b' . preg_quote($oldMethod, '/') . '\b/';
            $count = 0;
            $transformedContent = preg_replace($pattern, $newMethod, $transformedContent, -1, $count);
            $changesCount += $count;
        }
        return new TransformationResult(
            transformedContent: $transformedContent,
            changesCount: $changesCount,
            confidence: $this->getConfidence(),
            warnings: $warnings,
            requiresManualReview: false
        );
    }

    public function getConfidence(): int
    {
        return 90;
    }

    public function getDescription(): string
    {
        return 'Renames methods according to the upgrade strategy';
    }
}
