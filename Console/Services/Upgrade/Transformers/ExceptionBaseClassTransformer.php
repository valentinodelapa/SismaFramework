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
 * Transformer for the removal of Security\ExtendedClasses\LogException and
 * NoLogException (v11 → v12). Both classes are replaced by BaseException,
 * with LogException additionally requiring the ShouldBeLoggedException
 * interface to preserve its logging behaviour.
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class ExceptionBaseClassTransformer implements TransformerInterface
{

    private const BASE_EXCEPTION_USE = 'use SismaFramework\Security\BaseClasses\BaseException;';
    private const SHOULD_BE_LOGGED_USE = 'use SismaFramework\Security\Interfaces\Exceptions\ShouldBeLoggedException;';

    /**
     * @var array<string, bool> Removed class name => whether ShouldBeLoggedException must be implemented
     */
    private const REMOVED_CLASSES = [
        'NoLogException' => false,
        'LogException' => true,
    ];

    public function canTransform(string $filePath, string $content): bool
    {
        foreach (array_keys(self::REMOVED_CLASSES) as $className) {
            if (preg_match('/\b' . $className . '\b/', $content)) {
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
        $requiresManualReview = false;

        foreach (self::REMOVED_CLASSES as $className => $needsInterface) {
            if (!preg_match('/\b' . $className . '\b/', $transformedContent)) {
                continue;
            }

            $extendsPattern = '/\bextends\s+' . $className . '\b(\s+implements\s+([^\{]+))?/';
            if (!preg_match($extendsPattern, $transformedContent, $matches)) {
                $warnings[] = "{$className} is referenced but no matching 'extends {$className}' declaration was found — manual review required";
                $requiresManualReview = true;
                continue;
            }

            $existingInterfaces = isset($matches[2]) ? trim($matches[2]) : '';
            if ($needsInterface) {
                $replacement = $existingInterfaces !== ''
                        ? "extends BaseException implements ShouldBeLoggedException, {$existingInterfaces}"
                        : 'extends BaseException implements ShouldBeLoggedException';
            } else {
                $replacement = $existingInterfaces !== ''
                        ? "extends BaseException implements {$existingInterfaces}"
                        : 'extends BaseException';
            }
            $transformedContent = preg_replace($extendsPattern, $replacement, $transformedContent, 1, $extendsCount);
            $changesCount += $extendsCount;

            $usePattern = '/^use\s+SismaFramework\\\\Security\\\\ExtendedClasses\\\\' . $className . '\s*;\s*\n?/m';
            if (!preg_match($usePattern, $transformedContent)) {
                $warnings[] = "Class extends {$className} but no corresponding use statement was found — verify imports manually";
                $requiresManualReview = true;
                continue;
            }

            $newUseStatements = $needsInterface
                    ? [self::BASE_EXCEPTION_USE, self::SHOULD_BE_LOGGED_USE]
                    : [self::BASE_EXCEPTION_USE];
            $toInsert = array_filter($newUseStatements, fn(string $useStatement): bool => !str_contains($transformedContent, $useStatement));
            $useReplacement = empty($toInsert) ? '' : implode("\n", $toInsert) . "\n";
            $transformedContent = preg_replace($usePattern, $useReplacement, $transformedContent, 1, $useCount);
            $changesCount += $useCount;
        }

        return new TransformationResult(
            transformedContent: $transformedContent,
            changesCount: $changesCount,
            confidence: $this->getConfidence(),
            warnings: $warnings,
            requiresManualReview: $requiresManualReview,
        );
    }

    public function getConfidence(): int
    {
        return 80;
    }

    public function getDescription(): string
    {
        return 'Replaces removed LogException/NoLogException base classes with BaseException (implementing ShouldBeLoggedException where needed)';
    }
}
