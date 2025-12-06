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

namespace SismaFramework\Core\HelperClasses\Dispatcher;

use SismaFramework\Core\Enumerations\Resource;
use SismaFramework\Core\Enumerations\ResponseType;
use SismaFramework\Core\Exceptions\AccessDeniedException;
use SismaFramework\Core\Exceptions\RangeNotSatisfiableException;
use SismaFramework\Core\HelperClasses\Config;
use SismaFramework\Core\HelperClasses\Locker;
use SismaFramework\Core\HttpClasses\Request;
use SismaFramework\Core\HttpClasses\Response;

/**
 * @internal
 *
 * @author Valentino de Lapa
 */
class ResourceMaker
{

    private Request $request;
    private Config $config;

    public function __construct(Request $request = new Request(), ?Config $customConfig = null)
    {
        $this->config = $customConfig ?? Config::getInstance();
        $this->request = $request;
    }

    public function isAcceptedResourceFile(string $path): bool
    {
        $extension = $this->getExtension($path);
        if (Resource::tryFrom($extension) instanceof Resource) {
            return true;
        } elseif ($this->isCustomRenderableResourceType($extension)) {
            return true;
        } else {
            return $this->isCustomDownloadableResourceType($extension);
        }
    }

    private function isCustomRenderableResourceType(string $extension): bool
    {
        return in_array($extension, array_keys($this->config->customRenderableResourceTypes));
    }

    private function isCustomDownloadableResourceType(string $extension): bool
    {
        return in_array($extension, array_keys($this->config->customDownloadableResourceTypes));
    }

    private function getExtension(string $path): string
    {
        $splittedPath = explode(".", $path);
        return strtolower(end($splittedPath));
    }

    public function makeResource(string $filename, $forceDownload = false, Locker $locker = new Locker()): Response
    {
        if ($locker->fileIsLocked($filename) || $locker->folderIsLocked(dirname($filename))) {
            throw new AccessDeniedException($filename);
        } else {
            $resource = Resource::tryFrom($this->getExtension($filename));
            if ($resource instanceof Resource) {
                return $this->makeStandardResource($filename, $resource, $forceDownload);
            } else {
                return $this->makeCustomResource($filename, $forceDownload);
            }
        }
    }

    private function makeStandardResource(string $filename, Resource $resource, $forceDownload = false): Response
    {
        $mime = $resource->getContentType()->getMime();
        if ($resource->isRenderable() && ($forceDownload === false)) {
            return $this->viewResource($filename, $mime);
        } elseif ($resource->isDownloadable()) {
            return $this->downloadResource($filename, $mime);
        } else {
            throw new AccessDeniedException($filename);
        }
    }

    private function viewResource(string $filename, string $mime): Response
    {
        $fileSize = filesize($filename);
        $rangeHeader = $this->request->headers["Range"] ?? ($this->request->headers["range"] ?? null);
        header("Expires: " . gmdate("D, d-M-Y H:i:s \G\M\T", time() + 60));
        header("Accept-Ranges: bytes");
        header("Content-type: " . $mime);
        header("X-Content-Type-Options: nosniff");
        header("Content-Disposition: inline");
        if ($rangeHeader !== null) {
            return $this->servePartialContent($filename, $fileSize, $rangeHeader);
        } else {
            header("Content-Length: " . $fileSize);
            $this->getResourceData($filename);
            return new Response();
        }
    }

    private function getResourceData(string $filename): void
    {
        $handle = fopen($filename, "rb");
        if ($handle === false) {
            throw new AccessDeniedException($filename);
        }
        while (!feof($handle)) {
            echo fread($handle, 8192);
            flush();
        }
        fclose($handle);
    }

    private function downloadResource(string $filename, string $mime): Response
    {
        $fileSize = filesize($filename);
        $rangeHeader = $this->request->headers["Range"] ?? ($this->request->headers["range"] ?? null);
        header("Expires: " . gmdate("D, d-M-Y H:i:s \G\M\T", time() + 60));
        header("Accept-Ranges: bytes");
        header("Content-type: " . $mime);
        header("X-Content-Type-Options: nosniff");
        header("Content-Disposition: attachment; filename=\"" . basename($filename) . "\"");
        if ($rangeHeader !== null) {
            return $this->servePartialContent($filename, $fileSize, $rangeHeader);
        } else {
            header("Content-Length: " . $fileSize);
            $this->getResourceData($filename);
            return new Response();
        }
    }

    private function servePartialContent(string $filename, int $fileSize, string $rangeHeader): Response
    {
        [$start, $end] = $this->parseRangeHeader($rangeHeader, $fileSize);
        $length = $end - $start + 1;
        header("Content-Range: bytes " . $start . "-" . $end . "/" . $fileSize);
        header("Content-Length: " . $length);
        $this->getResourceDataRange($filename, $start, $length);
        return new Response(ResponseType::httpPartialContent);
    }

    private function parseRangeHeader(string $rangeHeader, int $fileSize): array
    {
        if (!preg_match("/bytes=(\d+)-(\d*)/", $rangeHeader, $matches)) {
            throw new RangeNotSatisfiableException("Invalid range header format: " . $rangeHeader, $fileSize);
        }
        $start = intval($matches[1]);
        $end = !empty($matches[2]) ? intval($matches[2]) : $fileSize - 1;
        if ($start > $end || $start >= $fileSize || $end >= $fileSize) {
            throw new RangeNotSatisfiableException("Range not satisfiable: bytes=" . $start . "-" . $end . " for file size " . $fileSize, $fileSize);
        }
        return [$start, $end];
    }

    private function getResourceDataRange(string $filename, int $start, int $length): void
    {
        $handle = fopen($filename, "rb");
        if ($handle === false) {
            throw new AccessDeniedException($filename);
        }
        fseek($handle, $start);
        $remaining = $length;
        while ($remaining > 0 && !feof($handle)) {
            $chunkSize = min(8192, $remaining);
            echo fread($handle, $chunkSize);
            flush();
            $remaining -= $chunkSize;
        }
        fclose($handle);
    }

    private function makeCustomResource(string $filename, $forceDownload = false): Response
    {
        $extension = $this->getExtension($filename);
        if ($this->isCustomRenderableResourceType($extension) && ($forceDownload === false)) {
            $mime = $this->config->customRenderableResourceTypes[$this->getExtension($filename)];
            return $this->viewResource($filename, $mime);
        } elseif ($this->isCustomDownloadableResourceType($extension)) {
            $mime = $this->config->customDownloadableResourceTypes[$this->getExtension($filename)];
            return $this->downloadResource($filename, $mime);
        } else {
            throw new AccessDeniedException($filename);
        }
    }
}
