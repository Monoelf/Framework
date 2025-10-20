<?php

declare(strict_types=1);

namespace Monoelf\Framework\resource\query\mySQL;

use Monoelf\Framework\resource\query\QueryBuilderInterface;

interface DataBaseQueryBuilderInterface extends QueryBuilderInterface
{
    public function getStatement(): StatementParameters;
}
