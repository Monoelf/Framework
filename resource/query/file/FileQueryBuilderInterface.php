<?php

declare(strict_types=1);

namespace Monoelf\Framework\resource\query\file;

use Monoelf\Framework\resource\query\QueryBuilderInterface;

interface FileQueryBuilderInterface extends QueryBuilderInterface
{
    public function getStatement(): StatementParameters;
}
