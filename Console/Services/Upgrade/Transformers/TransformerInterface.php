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
 * Interface for code transformers
 *
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
interface TransformerInterface
{

    /**
     * Check if this transformer can transform the given file
     *
     * @param string $filePath Path to the file
     * @param string $content File content
     * @return bool True if can transform
     */
    public function canTransform(string $filePath, string $content): bool;

    /**
     * Transform the content
     *
     * @param string $content Content to transform
     * @param string $filePath Path to the file being transformed
     * @return TransformationResult Transformation result
     */
    public function transform(string $content, string $filePath): TransformationResult;

    /**
     * Get the confidence level of this transformer (0-100)
     *
     * @return int Confidence percentage
     */
    public function getConfidence(): int;

    /**
     * Get a description of what this transformer does
     *
     * @return string Description
     */
    public function getDescription(): string;
}
