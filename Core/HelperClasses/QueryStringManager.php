<?php

/*
 * The MIT License
 *
 * Copyright 2023 Valentino de Lapa <valentino.delapa@gmail.com>.
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

namespace SismaFramework\Core\HelperClasses;

use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Core\Exceptions\QueryStringException;

/**
 * @author Valentino de Lapa <valentino.delapa@gmail.com>
 */
class QueryStringManager
{

    private Request $request;
    private ResourceMaker $resourceMaker;

    public function __construct(Request $request = new Request(),
            ResourceMaker $resourceMaker = new ResourceMaker(),
            Router $router = new Router())
    {
        $this->request = $request;
        $this->resourceMaker = $resourceMaker;
    }

    public function switchOptions(string $path): void
    {
        if (strlen($this->request->server['QUERY_STRING']) > 0) {
            if ($this->resourceMaker->isAcceptedResourceFile($path)) {
                $this->resourceMaker->setStreamContex($path);
            } else {
                throw new QueryStringException();
            }
        }
    }

}
