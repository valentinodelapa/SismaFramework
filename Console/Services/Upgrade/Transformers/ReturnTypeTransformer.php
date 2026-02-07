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
 * Transformer for changing customFilter return type from void to bool
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class ReturnTypeTransformer implements TransformerInterface
{
    public function canTransform(string $filePath, string $content): bool
    {
        return str_contains($filePath, '/Forms/') &&
               preg_match('/protected\s+function\s+customFilter\s*\(\s*\)\s*:\s*void/s', $content);
    }

    public function transform(string $content, string $filePath): TransformationResult
    {
        $transformedContent = $content;
        $changesCount = 0;
        $warnings = [];
        $transformedContent = preg_replace(
            '/protected\s+function\s+customFilter\s*\(\s*\)\s*:\s*void/s',
            'protected function customFilter(): bool',
            $transformedContent,
            -1,
            $count
        );
        $changesCount += $count;
        $pattern = '/(protected\s+function\s+customFilter\s*\(\s*\)\s*:\s*bool\s*\{)(.*?)(\n\s*\})/s';
        $transformedContent = preg_replace_callback($pattern, function ($matches) use (&$changesCount, &$warnings) {
            $body = $matches[2];
            $indent = $this->detectIndentation($body);
            if (preg_match('/return\s+(true|false)\s*;/', $body)) {
                return $matches[0];
            }
            $hasErrorAssignments = preg_match('/\$this->formFilterError->[a-zA-Z_]+ = true;/', $body);
            if ($hasErrorAssignments) {
                $body = preg_replace(
                    '/(\$this->formFilterError->[a-zA-Z_]+ = true;)(?!\s*return\s+false;)/m',
                    "$1\n{$indent}{$indent}return false;",
                    $body,
                    -1,
                    $count
                );
                $changesCount += $count;
            }
            if (!preg_match('/return\s+true\s*;\s*$/s', trim($body))) {
                $body .= "\n{$indent}{$indent}return true;";
                $changesCount++;
            }
            return $matches[1] . $body . $matches[3];
        }, $transformedContent);
        if ($changesCount === 0) {
            $warnings[] = "customFilter method found but could not be transformed automatically";
        }
        return new TransformationResult(
            transformedContent: $transformedContent,
            changesCount: $changesCount,
            confidence: $this->getConfidence(),
            warnings: $warnings,
            requiresManualReview: !empty($warnings)
        );
    }

    public function getConfidence(): int
    {
        return 85;
    }

    public function getDescription(): string
    {
        return 'Changes customFilter return type from void to bool and adds return statements';
    }

    /**
     * Detect the indentation used in the code
     *
     * @param string $code Code sample
     * @return string Indentation string (spaces or tab)
     */
    private function detectIndentation(string $code): string
    {
        if (preg_match('/^(\s+)/m', $code, $matches)) {
            return str_contains($matches[1], "\t") ? "\t" : '    ';
        }
        return '    ';
    }
}
