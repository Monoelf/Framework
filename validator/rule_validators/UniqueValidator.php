<?php

declare(strict_types=1);

namespace Monoelf\Framework\validator\rule_validators;

use Monoelf\Framework\resource\connection\DataBaseConnectionInterface;
use Monoelf\Framework\resource\query\QueryBuilderInterface;
use Monoelf\Framework\validator\RuleValidatorInterface;
use Monoelf\Framework\validator\ValidationException;

final class UniqueValidator implements RuleValidatorInterface
{
    public function __construct(
        private readonly DataBaseConnectionInterface $connection,
        private readonly QueryBuilderInterface $queryBuilder,
    ) {}

    public function validate(mixed $value, array $options = []): void
    {
        if (is_array($value) === false) {
            $value = (array)$value;
        }

        if (is_array($options['target']) === false) {
            $options['target'] = (array)$options['target'];
        }

        $this->queryBuilder
            ->reset()
            ->select('*')
            ->from($options['resource'])
            ->where(array_combine($options['target'], $value));

        if (is_null($this->connection->selectOne($this->queryBuilder)) === false) {
            $message = $options['errorMessage']
                ?? 'Значение [' . implode(', ', $value) . '] для [' . implode(', ', $options['target'])
                    . '] уже существует в ' . $options['resource'];

            throw new ValidationException($message);
        }
    }
}
