<?php

/*
 * This file is part of the Manuel Aguirre Project.
 *
 * (c) Manuel Aguirre <programador.manuel@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Manuelj555\ORM\Driver;

use Manuelj555\ORM\Connection;

/**
 * Description of AbstractDriver
 *
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
abstract class AbstractDriver
{

    /**
     *
     * @var Connection
     */
    protected $connection;

    function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    abstract public function getName();

    abstract function describeTable($name);

    /**
     * 
     * @param type $table
     * @param array $values
     * @return \PDOStatement
     */
    public function insert($table, array $values)
    {
        $builder = $this->getConnection()->createQueryBuilder()->insert($table);

        foreach ($values as $field => $val) {
            $builder->setValue($field, '?');
        }

        return $builder->setParameters(array_values($values))->execute();
    }

    public function update($table, array $values, $conditions, array $parameters)
    {
        $builder = $this->getConnection()->createQueryBuilder()->update($table);

        foreach ($values as $field => $val) {
            $builder->set($field, '?');
        }

        $parameters = array_merge(array_values($values), $parameters);

        return $builder->setParameters($parameters)
                        ->where($conditions)->execute();
    }

    public function delete($table, $conditions, array $parameters)
    {
        return $this->getConnection()->createQueryBuilder()
                        ->delete($table)->where($conditions)
                        ->setParameters($parameters)->execute();
    }

}
