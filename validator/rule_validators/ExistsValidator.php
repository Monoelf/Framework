<?php

declare(strict_types=1);

namespace Monoelf\Framework\validator\rule_validators;

use Monoelf\Framework\resource\connection\DataBaseConnectionInterface;
use Monoelf\Framework\resource\query\QueryBuilderInterface;
use Monoelf\Framework\validator\RuleValidatorInterface;
use Monoelf\Framework\validator\ValidationException;

final class ExistsValidator implements RuleValidatorInterface
{
    public function __construct(
        private readonly DataBaseConnectionInterface $connection,
        private readonly QueryBuilderInterface $queryBuilder,
    ) {}

    public function validate(mixed $value, array $options = []): void
    {
        $this->queryBuilder
            ->reset()
            ->select('*')
            ->from($options['resource'])
            ->where([$options['target'] => $value]);

        if (is_null($this->connection->selectOne($this->queryBuilder)) === true) {
            $message = $options['errorMessage']
                ?? 'Не найдено значение [' . $value
                    . '] для [' . $options['target'] . '] в ' . $options['resource'];

            throw new ValidationException($message);
        }
    }
}
