<?php

declare(strict_types=1);

namespace Monoelf\Framework\resource\query;

enum OperatorsEnum: string
{
    case EQ = '$eq';
    case NE = '$ne';
    case GT = '$gt';
    case GTE = '$gte';
    case LT = '$lt';
    case LTE = '$lte';
    case IN = '$in';
    case NIN = '$nin';
    case LIKE = '$like';
}
