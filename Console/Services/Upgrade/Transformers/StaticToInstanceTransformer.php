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
 * Transformer for converting static method calls to instance method calls
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class StaticToInstanceTransformer implements TransformerInterface
{
    private const STATIC_CLASSES = [
        'ErrorHandler' => 'errorHandler',
        'Debugger' => 'debugger'
    ];

    private const METHOD_RENAMES = [
        'handleNonThrowableError' => 'registerNonThrowableErrorHandler'
    ];

    public function canTransform(string $filePath, string $content): bool
    {
        foreach (self::STATIC_CLASSES as $className => $varName) {
            if (preg_match('/\b' . $className . '::[a-zA-Z_]+\s*\(/s', $content)) {
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
        $isIndexFile = basename($filePath) === 'index.php';
        if ($isIndexFile) {
            $result = $this->transformIndexFile($content);
            $transformedContent = $result['content'];
            $changesCount = $result['changes'];
            $warnings = $result['warnings'];
        } else {
            $result = $this->transformRegularFile($content);
            $transformedContent = $result['content'];
            $changesCount = $result['changes'];
            $warnings = $result['warnings'];
            if ($changesCount > 0) {
                $warnings[] = "Static method calls found in non-index.php file - requires manual review";
            }
        }
        return new TransformationResult(
            transformedContent: $transformedContent,
            changesCount: $changesCount,
            confidence: $isIndexFile ? 75 : 60,
            warnings: $warnings,
            requiresManualReview: !$isIndexFile
        );
    }

    public function getConfidence(): int
    {
        return 75;
    }

    public function getDescription(): string
    {
        return 'Converts ErrorHandler and Debugger static calls to instance calls';
    }

    /**
     * Transform index.php file
     *
     * @param string $content File content
     * @return array{content: string, changes: int, warnings: array}
     */
    private function transformIndexFile(string $content): array
    {
        $changesCount = 0;
        $warnings = [];
        $transformedContent = $content;
        $hasErrorHandler = preg_match('/\bErrorHandler::[a-zA-Z_]+\s*\(/s', $content);
        $hasDebugger = preg_match('/\bDebugger::[a-zA-Z_]+\s*\(/s', $content);
        if (!$hasErrorHandler && !$hasDebugger) {
            return ['content' => $content, 'changes' => 0, 'warnings' => []];
        }
        $instanceCode = '';
        if ($hasErrorHandler) {
            $instanceCode .= '$errorHandler = new ErrorHandler();' . PHP_EOL;
        }
        if ($hasDebugger) {
            $instanceCode .= '$debugger = new Debugger();' . PHP_EOL;
        }
        $pattern = '/(require_once\s+[\'"](?:.*?Autoload\.php|.*?autoload\.php)[\'"];)\s*/s';
        if (preg_match($pattern, $content)) {
            $transformedContent = preg_replace(
                $pattern,
                "$1\n\n{$instanceCode}\n",
                $transformedContent,
                1,
                $count
            );
            if ($count > 0) {
                $changesCount++;
            }
        } else {
            $warnings[] = "Could not find autoload statement to insert instance declarations";
        }
        if ($hasErrorHandler) {
            $transformedContent = preg_replace(
                '/\bErrorHandler::/',
                '$errorHandler->',
                $transformedContent,
                -1,
                $count
            );
            $changesCount += $count;
        }
        if ($hasDebugger) {
            $transformedContent = preg_replace(
                '/\bDebugger::/',
                '$debugger->',
                $transformedContent,
                -1,
                $count
            );
            $changesCount += $count;
        }
        foreach (self::METHOD_RENAMES as $oldMethod => $newMethod) {
            $transformedContent = preg_replace(
                '/\b' . preg_quote($oldMethod, '/') . '\b/',
                $newMethod,
                $transformedContent,
                -1,
                $count
            );
            $changesCount += $count;
        }
        $pattern = '/(\$dispatcher\s*=\s*new\s+Dispatcher\s*\()\s*\)/s';
        if (preg_match($pattern, $transformedContent) && $hasDebugger) {
            $transformedContent = preg_replace(
                $pattern,
                '$1debugger: $debugger)',
                $transformedContent,
                -1,
                $count
            );
            $changesCount += $count;
        }
        return ['content' => $transformedContent, 'changes' => $changesCount, 'warnings' => $warnings];
    }

    /**
     * Transform regular (non-index) file
     *
     * @param string $content File content
     * @return array{content: string, changes: int, warnings: array}
     */
    private function transformRegularFile(string $content): array
    {
        $changesCount = 0;
        $warnings = [];
        foreach (self::STATIC_CLASSES as $className => $varName) {
            $pattern = '/\b' . $className . '::[a-zA-Z_]+\s*\(/s';
            if (preg_match($pattern, $content)) {
                $changesCount++;
                $warnings[] = "Found {$className}:: static calls - consider refactoring to use dependency injection";
            }
        }
        return ['content' => $content, 'changes' => $changesCount, 'warnings' => $warnings];
    }
}
