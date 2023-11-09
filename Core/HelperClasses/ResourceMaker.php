<?php

/*
 * The MIT License
 *
 * Copyright 2023 Valentino de Lapa.
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

use SismaFramework\Core\Enumerations\Resource;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Core\HttpClasses\Response;
use SismaFramework\Core\Exceptions\AccessDeniedException;

/**
 * Description of ResourceMaker
 *
 * @author Valentino de Lapa
 */
class ResourceMaker
{

    private Request $request;
    private $streamContex = null;
    private int $fileGetContentMaxBytesLimit = \Config\FILE_GET_CONTENT_MAX_BYTES_LIMIT;
    private int $readfileMaxBytesLimit = \Config\READFILE_MAX_BYTES_LIMIT;

    public function __construct(Request $request = new Request())
    {
        $this->request = $request;
    }

    public function setFileGetContentMaxBytesLimit(int $fileGetContentMaxBytesLimit): void
    {
        $this->fileGetContentMaxBytesLimit = $fileGetContentMaxBytesLimit;
    }

    public function setReadfileMaxBytesLimit(int $readfileMaxBytesLimit): void
    {
        $this->readfileMaxBytesLimit = $readfileMaxBytesLimit;
    }

    public function setStreamContex(): void
    {
        $this->streamContex = $this->request->getStreamContentResource();
    }

    public function isAcceptedResourceFile(string $path): bool
    {
        return Resource::tryFrom($this->getExtension($path)) instanceof Resource;
    }

    private function getExtension(string $path): string
    {
        $splittedPath = explode('.', $path);
        return strtolower(end($splittedPath));
    }

    public function makeResource(string $filename): Response
    {
        $resource = Resource::from($this->getExtension($filename));
        if ($resource->isRenderable() && ($this->folderIsLocked($filename) === false)) {
            header("Expires: " . gmdate('D, d-M-Y H:i:s \G\M\T', time() + 60));
            header("Accept-Ranges: bytes");
            header("Content-type: " . $resource->getMime());
            header('X-Content-Type-Options: nosniff');
            header("Content-Disposition: inline");
            header("Content-Length: " . filesize($filename));
            if (filesize($filename) < $this->fileGetContentMaxBytesLimit) {
                echo file_get_contents($filename, false, $this->streamContex);
            } elseif (filesize($filename) < $this->readfileMaxBytesLimit) {
                readfile($filename, false, $this->streamContex);
            } else {
                $stream = fopen($filename, 'rb', false, $this->streamContex);
                fpassthru($stream);
            }
            return new Response();
        } else {
            throw new AccessDeniedException();
        }
    }
    
    private function folderIsLocked(string $filename):bool
    {
        return file_exists(dirname($filename).DIRECTORY_SEPARATOR.'.lock');
    }

    public function isRobotsFile(array $pathParts): bool
    {
        return strtolower(end($pathParts)) === \Config\ROBOTS_FILE;
    }

    public function makeRobotsFile(): Response
    {
        $filename = \Config\ROOT_PATH . DIRECTORY_SEPARATOR . \Config\ROBOTS_FILE;
        header("Expires: " . gmdate('D, d-M-Y H:i:s \G\M\T', time() + 60));
        header("Accept-Ranges: bytes");
        header("Content-type: text/plain");
        header('X-Content-Type-Options: nosniff');
        header("Content-Disposition: inline");
        header("Content-Length: " . filesize($filename));
        echo file_get_contents($filename, false);
        return new Response();
    }
}
