<?php

/*
 * The MIT License
 *
 * Copyright (c) 2024-present Valentino de Lapa.
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

namespace SismaFramework\Core\CustomTypes;

/**
 * @author Valentino de Lapa
 */
class FormFilterError
{
    const ERROR_PLACEHOLDER = 'Error';
    const CUSTOM_MESSAGE_PLACEHOLDER = 'CustomMessage';
    const COLLECTION_PLACEHOLDER = 'Collection';

    private array $errors = [];
    private array $customMessages = [];
    private array $formFilterErrorFromForm = [];

    public function generateFormFilterErrorFromForm(array $entityFromForm): void
    {
        foreach ($entityFromForm as $key => $value) {
            if (is_array($value)) {
                $this->formFilterErrorFromForm[$key] = $this->generateFormFilterErrorCollectionFromForm($value);
            } else {
                $this->formFilterErrorFromForm[$key] = $value->getFilterErrors();
            }
        }
    }

    private function generateFormFilterErrorCollectionFromForm(array $formArray): FormFilterErrorCollection
    {
        $formFilterErrorCollection = new FormFilterErrorCollection();
        foreach ($formArray as $form) {
            $formFilterErrorCollection->append($form->getFilterErrors());
        }
        return $formFilterErrorCollection;
    }

    public function __set($name, $value)
    {
        if (array_key_exists($name, $this->formFilterErrorFromForm)) {
            $this->formFilterErrorFromForm[$name] = $value;
        } else {
            $propertyName = str_replace([self::ERROR_PLACEHOLDER, self::CUSTOM_MESSAGE_PLACEHOLDER], '', $name);
            if (str_contains($name, self::ERROR_PLACEHOLDER)) {
                $this->errors[$propertyName] = $value;
            } elseif (str_contains($name, self::CUSTOM_MESSAGE_PLACEHOLDER)) {
                $this->customMessages[$propertyName] = $value;
            } else {
                
            }
        }
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->formFilterErrorFromForm)) {
            return $this->formFilterErrorFromForm[$name];
        } else {
            $propertyName = str_replace([self::ERROR_PLACEHOLDER, self::CUSTOM_MESSAGE_PLACEHOLDER], '', $name);
            if (str_contains($name, self::ERROR_PLACEHOLDER)) {
                return $this->errors[$propertyName] ?? false;
            } elseif (str_contains($name, self::CUSTOM_MESSAGE_PLACEHOLDER)) {
                return $this->customMessages[$propertyName] ?? false;
            } elseif (str_contains($name, self::COLLECTION_PLACEHOLDER)) {
                return new FormFilterErrorCollection();
            } else {
                return new FormFilterError();
            }
        }
    }

    public function getErrorsToArray(): array
    {
        $parsedErrors = [];
        foreach ($this->errors as $key => $value) {
            $parsedErrors[$key . self::ERROR_PLACEHOLDER] = $value;
        }
        foreach ($this->customMessages as $key => $value) {
            $parsedErrors[$key . self::CUSTOM_MESSAGE_PLACEHOLDER] = $value;
        }
        foreach ($this->formFilterErrorFromForm as $key => $value) {
            if ($value instanceof FormFilterError) {
                $parsedErrors[$key] = $value->getErrorsToArray();
            } elseif ($value instanceof FormFilterErrorCollection) {
                $parsedErrors[$key] = $this->getErrorCollectionToArray($value);
            }
        }
        return $parsedErrors;
    }

    private function getErrorCollectionToArray(FormFilterErrorCollection $errorCollection): array
    {
        $parsedErrors = [];
        foreach ($errorCollection as $key => $value) {
            $parsedErrors[$key] = $value->getErrorsToArray();
        }
        return $parsedErrors;
    }
}
