<?php

namespace SismaFramework\Core\Enumerations;

enum ResponseType: int
{
    case httpNull = 0;
    case httpContinue = 100;
    case httpSwitchingProtocols = 101;
    case httpProcessing = 102;
    case httpEarlyHints = 103;
    case httpOk = 200;
    case httpCreated = 201;
    case httpAccepted = 202;
    case httpNonAuthoritativeInformation = 203;
    case httpNoContent = 204;
    case httpResetContent = 205;
    case httpPartialContent = 206;
    case httpMultiStatus = 207;
    case httpAlreadyReported = 208;
    case httpImUsed = 226;
    case httpMultipleChoices = 300;
    case httpMovedPermanently = 301;
    case httpFound = 302;
    case httpSeeOther = 303;
    case httpNotModified = 304;
    case httpUseProxy = 305;
    case httpReserved = 306;
    case httpTemporaryRedirect = 307;
    case httpPermanentlyRedirect = 308;  // RFC7238
    case httpBadRequest = 400;
    case httpUnauthorized = 401;
    case httpPaymentRequired = 402;
    case httpForbidden = 403;
    case httpNotFound = 404;
    case httpMethodNotAllowed = 405;
    case httpNotAcceptable = 406;
    case httpProxyAuthenticationRequired = 407;
    case httpRequestTimeout = 408;
    case httpConflict = 409;
    case httpGone = 410;
    case httpLengthRequired = 411;
    case httpPreconditionFailed = 412;
    case httpRequestEntityTooLarge = 413;
    case httpRequestUriTooLong = 414;
    case httpUnsupportedMediaType = 415;
    case httpRequestedRangeNotSatisfiable = 416;
    case httpExpectationFailed = 417;
    case httpIAmATeapot = 418;
    case httpMisdirectedRequest = 421;
    case httpUnprocessableEntity = 422;
    case httpLocked = 423;
    case httpFailedDependency = 424;
    case httpTooEarly = 425;
    case httpUpgradeRequired = 426;
    case httpPreconditionRequired = 428;
    case httpTooManyRequests = 429;
    case httpRequestHeaderFieldsTooLarge = 431;
    case httpUnavailableForLegalReasons = 451;
    case httpInternalServerError = 500;
    case httpNotImplemented = 501;
    case httpBadGateway = 502;
    case httpServiceUnavailable = 503;
    case httpGatewayTimeout = 504;
    case httpVersionNotSupported = 505;
    case httpVariantAlsoNegotiatesExperimental = 506;
    case httpInsufficientStorage = 507;
    case httpLoopDetected = 508;
    case httpNotExtended = 510;
    case httpNetworkAuthenticationRequired = 511;
}
