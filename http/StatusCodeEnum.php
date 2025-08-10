<?php

declare(strict_types=1);

namespace Monoelf\Framework\http;

enum StatusCodeEnum: int
{
    case STATUS_OK = 200;
    case STATUS_CREATED = 201;
    case STATUS_NO_CONTENT = 204;
    case STATUS_BAD_REQUEST = 400;
    case STATUS_UNAUTHORIZED = 401;
    case STATUS_PAYMENT_REQUIRED = 402;
    case STATUS_FORBIDDEN = 403;
    case STATUS_NOT_FOUND = 404;
    case STATUS_METHOD_NOT_ALLOWED = 405;
    case STATUS_NOT_ACCEPTABLE = 406;
    case STATUS_PROXY_AUTHENTICATION_REQUIRED = 407;
    case STATUS_REQUEST_TIMEOUT = 408;
    case STATUS_CONFLICT = 409;
    case STATUS_GONE = 410;
    case STATUS_LENGTH_REQUIRED = 411;
    case STATUS_PRECONDITION_FAILED = 412;
    case STATUS_REQUEST_ENTITY_TOO_LARGE = 413;
    case STATUS_REQUEST_URI_TOO_LARGE = 414;
    case STATUS_UNSUPPORTED_MEDIA_TYPE = 415;
    case STATUS_REQUESTED_RANGE_NOT_SATISFIED = 416;
    case STATUS_EXPECTATION_FAILED = 417;
    case STATUS_PAYLOAD_TOO_LARGE = 418;
    case STATUS_UNPROCESSABLE_ENTITY = 422;
    case STATUS_LOCKED = 423;
    case STATUS_FAILED_DEPENDENCY = 424;
    case STATUS_TOO_MANY_REQUESTS = 429;
    case STATUS_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
    case STATUS_UNAVAILABLE_FOR_LEGAL_READ = 451;
    case STATUS_INTERNAL_SERVER_ERROR = 500;
    case STATUS_NOT_IMPLEMENTED = 501;
    case STATUS_BAD_GATEWAY = 502;
    case STATUS_SERVICE_UNAVAILABLE = 503;
    case STATUS_GATEWAY_TIMEOUT = 504;
    case STATUS_HTTP_VERSION_NOT_SUPPORTED = 505;
    case STATUS_VARIANT_ALSO_NEGOTIATES = 506;
    case STATUS_INSUFFICIENT_STORAGE = 507;
    case STATUS_LOOP_DETECTED = 508;
    case STATUS_NOT_EXTENDED = 510;
    case STATUS_NETWORK_AUTHENTICATION_REQUIRED = 511;

    public function reasonPhrase(): string
    {
        return match ($this) {
            self::STATUS_OK => 'OK',
            self::STATUS_CREATED => 'Created',
            self::STATUS_NO_CONTENT => 'No Content',
            self::STATUS_BAD_REQUEST => 'Bad Request',
            self::STATUS_UNAUTHORIZED => 'Unauthorized',
            self::STATUS_PAYMENT_REQUIRED => 'Payment Required',
            self::STATUS_FORBIDDEN => 'Forbidden',
            self::STATUS_NOT_FOUND => 'Not Found',
            self::STATUS_METHOD_NOT_ALLOWED => 'Method Not Allowed',
            self::STATUS_NOT_ACCEPTABLE => 'Not Acceptable',
            self::STATUS_PROXY_AUTHENTICATION_REQUIRED => 'Proxy',
            self::STATUS_REQUEST_TIMEOUT => 'Request Timeout',
            self::STATUS_CONFLICT => 'Conflict',
            self::STATUS_GONE => 'Gone',
            self::STATUS_LENGTH_REQUIRED => 'Length Required',
            self::STATUS_PRECONDITION_FAILED => 'Precondition Failed',
            self::STATUS_REQUEST_ENTITY_TOO_LARGE => 'Request Entity Too Large',
            self::STATUS_REQUEST_URI_TOO_LARGE => 'Request-URI Too Large',
            self::STATUS_UNSUPPORTED_MEDIA_TYPE => 'Unsupported',
            self::STATUS_REQUESTED_RANGE_NOT_SATISFIED => 'Requested Range Not Satisfied',
            self::STATUS_EXPECTATION_FAILED => 'Expectation Failed',
            self::STATUS_PAYLOAD_TOO_LARGE => 'Payload Too Large',
            self::STATUS_UNPROCESSABLE_ENTITY => 'Unprocessable Entity',
            self::STATUS_LOCKED => 'Locked',
            self::STATUS_FAILED_DEPENDENCY => 'Failed Dependency',
            self::STATUS_TOO_MANY_REQUESTS => 'Too Many Requests',
            self::STATUS_INTERNAL_SERVER_ERROR => 'Internal Server Error',
            self::STATUS_NOT_IMPLEMENTED => 'Not Implemented',
            self::STATUS_BAD_GATEWAY => 'Bad Gateway',
            self::STATUS_SERVICE_UNAVAILABLE => 'Service Unavailable',
            self::STATUS_GATEWAY_TIMEOUT => 'Gateway Timeout',
            default => 'Unknown Status',
        };
    }
}
