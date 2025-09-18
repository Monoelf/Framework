<?php

declare(strict_types=1);

namespace Monoelf\Framework\resource\connection;

use Monoelf\Framework\http\exceptions\HttpBadRequestException;
use Monoelf\Framework\resource\query\mySQL\MysqlQueryBuilderInterface;
use Monoelf\Framework\resource\query\QueryBuilderInterface;
use PDO;
use PDOException;
use PDOStatement;

class DataBaseConnection implements DataBaseConnectionInterface
{
    private PDO $connection;
    private string $lastInsertId = '';

    public function __construct(array $config)
    {
        $dsn = sprintf(
            "mysql:host=%s;dbname=%s;charset=%s",
            $config['host'],
            $config['dbname'],
            $config['charset']
        );

        $this->connection = new PDO(
            $dsn,
            $config['username'],
            $config['password']
        );

        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * @param QueryBuilderInterface $query
     * @return array
     */
    public function select(QueryBuilderInterface $query): array
    {
        $statement = $this->executeQuery($query);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param QueryBuilderInterface $query
     * @return array|null
     */
    public function selectOne(QueryBuilderInterface $query): ?array
    {
        $statement = $this->executeQuery($query);
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * @param QueryBuilderInterface $query
     * @return array
     */
    public function selectColumn(QueryBuilderInterface $query): array
    {
        $statement = $this->executeQuery($query);
        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * @param QueryBuilderInterface $query
     * @return mixed
     */
    public function selectScalar(QueryBuilderInterface $query): mixed
    {
        $statement = $this->executeQuery($query);
        return $statement->fetchColumn();
    }

    /**
     * @param string $resource
     * @param array $data
     * @param array $condition
     * @return int
     */
    public function update(string $resource, array $data, array $condition): int
    {
        $setParts = [];
        $bindings = [];

        foreach ($data as $key => $value) {
            $param = 'set_' . count($bindings);
            $setParts[] = "$key = :$param";
            $bindings[$param] = $value;
        }

        $whereParts = [];
        foreach ($condition as $key => $value) {
            $param = 'where_' . count($bindings);
            $whereParts[] = "$key = :$param";
            $bindings[$param] = $value;
        }

        $sql = 'UPDATE ' . $resource . ' SET ' . implode(', ', $setParts);
        if (empty($whereParts) === false) {
            $sql .= ' WHERE ' . implode(' AND ', $whereParts);
        }

        $statement = $this->connection->prepare($sql);
        $statement->execute($bindings);

        return $statement->rowCount();
    }

    /**
     * @param string $resource
     * @param array $data
     * @return int
     * @throws HttpBadRequestException
     */
    public function insert(string $resource, array $data): int
    {
        $columns = array_keys($data);
        $params = array_map(fn($col) => ':' . $col, $columns);

        $sql = 'INSERT INTO ' . $resource
            . ' (' . implode(', ', $columns) . ') VALUES ('
            . implode(', ', $params) . ')';

        $bindings = array_combine($params, array_values($data));

        $statement = $this->connection->prepare($sql);

        try {
            $statement->execute($bindings);
        } catch (PDOException $e) {
            $errorInfo = $e->errorInfo;
            if (isset($errorInfo[0], $errorInfo[1])
                && $errorInfo[0] === '23000'
                && $errorInfo[1] === 1062
            ) {
                throw new HttpBadRequestException(
                    'Duplicate entry for resource `' . $resource . '`',
                    0,
                    $e
                );
            }
            throw $e;
        }

        $this->lastInsertId = $this->connection->lastInsertId();

        return $statement->rowCount();
    }

    /**
     * @param string $resource
     * @param array $condition
     * @return int
     */
    public function delete(string $resource, array $condition): int
    {
        $whereParts = [];

        $bindings = [];

        foreach ($condition as $key => $value) {
            $param = 'where_' . count($bindings);
            $whereParts[] = "$key = :$param";
            $bindings[$param] = $value;
        }

        $sql = 'DELETE FROM ' . $resource;

        if (empty($whereParts) === false) {
            $sql .= ' WHERE ' . implode(' AND ', $whereParts);
        }

        $statement = $this->connection->prepare($sql);
        $statement->execute($bindings);

        return $statement->rowCount();
    }

    /**
     * @return string
     */
    public function getLastInsertId(): string
    {
        return $this->lastInsertId;
    }

    /**
     * @param MysqlQueryBuilderInterface $query
     * @return PDOStatement
     */
    private function executeQuery(MysqlQueryBuilderInterface $query): PDOStatement
    {
        $statementParams = $query->getStatement();
        $statement = $this->connection->prepare($statementParams->sql);
        $statement->execute($statementParams->bindings);

        return $statement;
    }
}
