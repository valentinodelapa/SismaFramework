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
 * Transformer for converting Response setResponseType() to constructor injection
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class ResponseConstructorTransformer implements TransformerInterface
{
    public function canTransform(string $filePath, string $content): bool
    {
        return str_contains($content, '->setResponseType(');
    }

    public function transform(string $content, string $filePath): TransformationResult
    {
        $transformedContent = $content;
        $changesCount = 0;
        $warnings = [];
        $pattern = '/\$([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*new\s+Response\s*\(\s*\)\s*;[\s\n]*\$\1\s*->\s*setResponseType\s*\(\s*([^)]+)\s*\)\s*;/s';
        $transformedContent = preg_replace_callback($pattern, function ($matches) use (&$changesCount) {
            $varName = $matches[1];
            $responseType = trim($matches[2]);
            $changesCount++;
            return "\${$varName} = new Response({$responseType});";
        }, $transformedContent);
        if (preg_match('/->setResponseType\s*\(/', $transformedContent)) {
            $warnings[] = "setResponseType() found but pattern is too complex for automatic transformation";
            $warnings[] = "Manual review required: convert 'new Response(); \$r->setResponseType(X)' to 'new Response(X)'";
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
        return 70;
    }

    public function getDescription(): string
    {
        return 'Converts Response setResponseType() calls to constructor injection';
    }
}
