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
 * Transformer for Query::setFulltextIndexColumn() parameter reordering (v11 → v12)
 *
 * Old signature: (array $columns, $value, ?string $columnAlias, bool $append, TextSearchMode $textSearchMode)
 * New signature: (array $columns, $value, TextSearchMode $textSearchMode, ?string $columnAlias, bool $append)
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class FulltextIndexColumnTransformer implements TransformerInterface
{

    private const METHOD_NAME = 'setFulltextIndexColumn';

    public function canTransform(string $filePath, string $content): bool
    {
        return str_contains($content, self::METHOD_NAME);
    }

    public function transform(string $content, string $filePath): TransformationResult
    {
        $warnings = [];
        $changesCount = 0;
        $requiresManualReview = false;
        $transformedContent = $content;

        $offset = 0;
        while (($pos = strpos($transformedContent, self::METHOD_NAME . '(', $offset)) !== false) {
            $argsStart = $pos + strlen(self::METHOD_NAME) + 1;
            $args = $this->extractArguments($transformedContent, $argsStart, $argsEnd);

            if ($args === null) {
                $warnings[] = 'Could not parse setFulltextIndexColumn() call — manual review required';
                $requiresManualReview = true;
                $offset = $argsStart;
                continue;
            }

            if (count($args) <= 2) {
                $offset = $argsEnd;
                continue;
            }

            if (count($args) === 3) {
                // 3rd arg was columnAlias (now it should be textSearchMode) — flag for manual review
                $warnings[] = 'setFulltextIndexColumn() called with 3 positional arguments: the 3rd argument meaning changed from $columnAlias to $textSearchMode — manual review required';
                $requiresManualReview = true;
                $offset = $argsEnd;
                continue;
            }

            // 4 or 5 args: reorder (old: 0,1,2,3,4 = columns,value,columnAlias,append,textSearchMode)
            //              → new: 0,1,4,2,3 = columns,value,textSearchMode,columnAlias,append
            // For 4 args: old (columns, value, columnAlias, append) — textSearchMode was default
            // New expects (columns, value, textSearchMode, columnAlias, append)
            // So 4-arg call with old order needs: (args[0], args[1], args[2], args[3]) to become
            // (args[0], args[1], TextSearchMode::inNaturaLanguageMode, args[2], args[3]) — can't auto-fix
            if (count($args) === 4) {
                $warnings[] = 'setFulltextIndexColumn() called with 4 positional arguments: manual reordering required — new signature is (columns, value, textSearchMode, columnAlias, append)';
                $requiresManualReview = true;
                $offset = $argsEnd;
                continue;
            }

            // Exactly 5 args: can auto-reorder
            // old[2]=columnAlias, old[3]=append, old[4]=textSearchMode
            // new[2]=textSearchMode, new[3]=columnAlias, new[4]=append
            $reordered = [$args[0], $args[1], $args[4], $args[2], $args[3]];
            $oldCall = substr($transformedContent, $pos, $argsEnd - $pos);
            $newArgs = implode(', ', array_map('trim', $reordered));
            $newCall = self::METHOD_NAME . '(' . $newArgs . ')';
            $transformedContent = substr($transformedContent, 0, $pos) . $newCall . substr($transformedContent, $argsEnd);
            $changesCount++;
            $offset = $pos + strlen($newCall);
        }

        return new TransformationResult(
            transformedContent: $transformedContent,
            changesCount: $changesCount,
            confidence: $this->getConfidence(),
            warnings: $warnings,
            requiresManualReview: $requiresManualReview,
        );
    }

    /**
     * Extracts comma-separated arguments from a position after the opening parenthesis,
     * respecting nested parentheses, brackets, and string literals.
     *
     * @param string $content Full file content
     * @param int $start Position right after the opening '('
     * @param int|null $end Will be set to position right after the closing ')'
     * @return array<string>|null Array of argument strings, or null if parsing failed
     */
    private function extractArguments(string $content, int $start, ?int &$end): ?array
    {
        $args = [];
        $currentArg = '';
        $depth = 0;
        $inString = false;
        $stringChar = '';
        $len = strlen($content);

        for ($i = $start; $i < $len; $i++) {
            $char = $content[$i];

            if ($inString) {
                $currentArg .= $char;
                if ($char === $stringChar && ($i === $start || $content[$i - 1] !== '\\')) {
                    $inString = false;
                }
                continue;
            }

            if ($char === '"' || $char === "'") {
                $inString = true;
                $stringChar = $char;
                $currentArg .= $char;
                continue;
            }

            if ($char === '(' || $char === '[' || $char === '{') {
                $depth++;
                $currentArg .= $char;
                continue;
            }

            if ($char === ')' || $char === ']' || $char === '}') {
                if ($depth === 0) {
                    $args[] = $currentArg;
                    $end = $i + 1;
                    return $args;
                }
                $depth--;
                $currentArg .= $char;
                continue;
            }

            if ($char === ',' && $depth === 0) {
                $args[] = $currentArg;
                $currentArg = '';
                continue;
            }

            $currentArg .= $char;
        }

        return null;
    }

    public function getConfidence(): int
    {
        return 70;
    }

    public function getDescription(): string
    {
        return 'Reorders Query::setFulltextIndexColumn() parameters from v11 to v12 signature';
    }
}
