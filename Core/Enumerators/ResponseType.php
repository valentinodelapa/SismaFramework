<?php

namespace Sisma\Core\Enumerators;

use Sisma\Core\BaseClasses\BaseEnumerator;

class ResponseType extends BaseEnumerator
{
    private const HTTP_NULL = 0;
    private const HTTP_CONTINUE = 100;
    private const HTTP_SWITCHING_PROTOCOLS = 101;
    private const HTTP_PROCESSING = 102;
    private const HTTP_EARLY_HINTS = 103;
    private const HTTP_OK = 200;
    private const HTTP_CREATED = 201;
    private const HTTP_ACCEPTED = 202;
    private const HTTP_NON_AUTHORITATIVE_INFORMATION = 203;
    private const HTTP_NO_CONTENT = 204;
    private const HTTP_RESET_CONTENT = 205;
    private const HTTP_PARTIAL_CONTENT = 206;
    private const HTTP_MULTI_STATUS = 207;
    private const HTTP_ALREADY_REPORTED = 208;
    private const HTTP_IM_USED = 226;
    private const HTTP_MULTIPLE_CHOICES = 300;
    private const HTTP_MOVED_PERMANENTLY = 301;
    private const HTTP_FOUND = 302;
    private const HTTP_SEE_OTHER = 303;
    private const HTTP_NOT_MODIFIED = 304;
    private const HTTP_USE_PROXY = 305;
    private const HTTP_RESERVED = 306;
    private const HTTP_TEMPORARY_REDIRECT = 307;
    private const HTTP_PERMANENTLY_REDIRECT = 308;  // RFC7238
    private const HTTP_BAD_REQUEST = 400;
    private const HTTP_UNAUTHORIZED = 401;
    private const HTTP_PAYMENT_REQUIRED = 402;
    private const HTTP_FORBIDDEN = 403;
    private const HTTP_NOT_FOUND = 404;
    private const HTTP_METHOD_NOT_ALLOWED = 405;
    private const HTTP_NOT_ACCEPTABLE = 406;
    private const HTTP_PROXY_AUTHENTICATION_REQUIRED = 407;
    private const HTTP_REQUEST_TIMEOUT = 408;
    private const HTTP_CONFLICT = 409;
    private const HTTP_GONE = 410;
    private const HTTP_LENGTH_REQUIRED = 411;
    private const HTTP_PRECONDITION_FAILED = 412;
    private const HTTP_REQUEST_ENTITY_TOO_LARGE = 413;
    private const HTTP_REQUEST_URI_TOO_LONG = 414;
    private const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
    private const HTTP_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    private const HTTP_EXPECTATION_FAILED = 417;
    private const HTTP_I_AM_A_TEAPOT = 418;
    private const HTTP_MISDIRECTED_REQUEST = 421;
    private const HTTP_UNPROCESSABLE_ENTITY = 422;
    private const HTTP_LOCKED = 423;
    private const HTTP_FAILED_DEPENDENCY = 424;
    private const HTTP_RESERVED_FOR_WEBDAV_ADVANCED_COLLECTIONS_EXPIRED_PROPOSAL = 425;
    private const HTTP_TOO_EARLY = 425;
    private const HTTP_UPGRADE_REQUIRED = 426;
    private const HTTP_PRECONDITION_REQUIRED = 428;
    private const HTTP_TOO_MANY_REQUESTS = 429;
    private const HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
    private const HTTP_UNAVAILABLE_FOR_LEGAL_REASONS = 451;
    private const HTTP_INTERNAL_SERVER_ERROR = 500;
    private const HTTP_NOT_IMPLEMENTED = 501;
    private const HTTP_BAD_GATEWAY = 502;
    private const HTTP_SERVICE_UNAVAILABLE = 503;
    private const HTTP_GATEWAY_TIMEOUT = 504;
    private const HTTP_VERSION_NOT_SUPPORTED = 505;
    private const HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL = 506;
    private const HTTP_INSUFFICIENT_STORAGE = 507;
    private const HTTP_LOOP_DETECTED = 508;
    private const HTTP_NOT_EXTENDED = 510;
    private const HTTP_NETWORK_AUTHENTICATION_REQUIRED = 511;
}
