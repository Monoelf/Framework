<?php

declare(strict_types=1);

namespace Monoelf\Framework\resource;

enum ResourceActionTypesEnum: string
{
    case CREATE = 'create';
    case DELETE = 'delete';
    case UPDATE = 'update';
    case PATCH = 'patch';
    case VIEW = 'view';
    case INDEX = 'index';
}
