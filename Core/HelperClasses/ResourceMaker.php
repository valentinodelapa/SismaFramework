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

use SismaFramework\Core\HelperClasses\Config;
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
        } else {
            return $this->isCustomResourceType($extension);
        }
    }

    private function isCustomResourceType(string $extension): bool
    {
        return in_array($extension, array_keys($this->config->customResourceTypes));
    }

    private function getExtension(string $path): string
    {
        $splittedPath = explode('.', $path);
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
        header("Expires: " . gmdate('D, d-M-Y H:i:s \G\M\T', time() + 60));
        header("Accept-Ranges: bytes");
        header("Content-type: " . $mime);
        header('X-Content-Type-Options: nosniff');
        header("Content-Disposition: inline");
        header("Content-Length: " . filesize($filename));
        $this->getResourceData($filename);
        return new Response();
    }

    private function getResourceData(string $filename): void
    {
        if (filesize($filename) < $this->config->fileGetContentMaxBytesLimit) {
            echo file_get_contents($filename);
        } elseif (filesize($filename) < $this->config->readfileMaxBytesLimit) {
            readfile($filename);
        } else {
            $stream = fopen($filename, 'rb');
            fpassthru($stream);
        }
    }

    private function downloadResource(string $filename, string $mime): Response
    {
        header("Expires: " . gmdate('D, d-M-Y H:i:s \G\M\T', time() + 60));
        header("Accept-Ranges: bytes");
        header("Content-type: " . $mime);
        header('X-Content-Type-Options: nosniff');
        header("Content-Disposition: attachment; filename=\"" . basename($filename) . "\"");
        header("Content-Length: " . filesize($filename));
        $this->getResourceData($filename);
        return new Response();
    }

    private function makeCustomResource(string $filename, $forceDownload = false): Response
    {
        $mime = $this->config->customResourceTypes[$this->getExtension($filename)];
        if ($forceDownload) {
            return $this->downloadResource($filename, $mime);
        } else {
            return $this->viewResource($filename, $mime);
        }
    }
}
