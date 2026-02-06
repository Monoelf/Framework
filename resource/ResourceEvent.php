<?php

declare(strict_types=1);

namespace Monoelf\Framework\resource;

final class ResourceEvent
{
    public const RESOURCE_BEFORE_CREATE = self::class . '.BEFORE_CREATE';
    public const RESOURCE_CREATED = self::class . '.CREATED';
    public const RESOURCE_BEFORE_UPDATE = self::class . '.BEFORE_UPDATE';
    public const RESOURCE_UPDATED = self::class . '.UPDATED';
    public const RESOURCE_BEFORE_DELETE = self::class . '.BEFORE_DELETE';
    public const RESOURCE_DELETED = self::class . '.DELETED';
    public const RESOURCE_LIST_REQUEST = self::class . '.LIST_REQUEST';
    public const RESOURCE_VIEW_REQUEST = self::class . '.VIEW_REQUEST';
}
