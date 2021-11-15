<?php

namespace Sisma\Core\Enumerators;

use Sisma\Core\BaseClasses\BaseEnumerator;

class FilterType extends BaseEnumerator
{

    private const NO_FILTER = 'noFilter';
    private const STRING = 'isString';
    private const ALPHABETIC_STRING = 'isAlphabeticString';
    private const ALPHANUMERIC_STRING = 'isAlphanumericString';
    private const SECURE_PASSWORD = 'isSecurePassword';
    private const EMAIL = 'isEmail';
    private const NUMERIC = 'isNumeric';
    private const INTEGER = 'isInteger';
    private const FLOAT = 'isFloat';
    private const BOOLEAN = 'isBoolean';
    private const ARRAY = 'isArray';
    private const UPLOADED_FILE = 'isUploadedFile';
    private const ENUMERATOR = 'isEnumerator';
    private const ENTITY = 'isEntity';

}
