<?php

namespace SismaFramework\Core\Enumerations;

enum FilterType: string
{
    case noFilter = 'noFilter';
    case isString = 'isString';
    case isAlphabeticString = 'isAlphabeticString';
    case isAlphanumericString = 'isAlphanumericString';
    case isSecurePassword = 'isSecurePassword';
    case isEmail = 'isEmail';
    case isNumeric = 'isNumeric';
    case isInteger = 'isInteger';
    case isFloat = 'isFloat';
    case isBoolean = 'isBoolean';
    case isArray = 'isArray';
    case isUploadedFile = 'isUploadedFile';
    case isEnumeration = 'isEnumeration';
    case isEntity = 'isEntity';
}
