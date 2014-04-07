<?php

/*
 * This file is part of the Manuel Aguirre Project.
 *
 * (c) Manuel Aguirre <programador.manuel@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Manuelj555\ORM;

use Manuelj555\ORM\Query\QueryBuilder;

/**
 * Description of Repository
 *
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
class Repository
{

    /**
     * @var Connection
     */
    private $connection;
    private $class;

    final public function __construct(Connection $connection, $class)
    {
        $this->connection = $connection;
        $this->class = $class;
    }

    /**
     * 
     * @return QueryBuilder
     */
    public function createQueryBuilder($alias = null)
    {
        $query = new QueryBuilder($this->connection, $this->class, $alias);
        $query->select('*');

        return $query;
    }

    public function findAll(array $params = array(), $fetch = null)
    {
        return $this->prepare($params)->fetchAll($fetch);
    }

    public function find($id, $fetch = null)
    {
        $table = $this->connection->getTable($this->class);

        return $this->prepare(array($table->primaryKey => $id))->fetch($fetch);
    }

    public function findBy(array $by, $fetch = null)
    {
        return $this->prepare($by)->fetch($fetch);
    }

    public function __call($name, $arguments)
    {
        if (0 === strpos($name, 'findBy')) {
            $property = substr($name, 6);
            $method = 'findBy';
        } elseif (0 === strpos($name, 'findAllBy')) {
            $property = substr($name, 9);
            $method = 'findAll';
        } else {
            trigger_error(sprintf('Call to undefined method %s::%s()', __CLASS__, $name));
        }

        $property[0] = strtolower($property[0]);

        return call_user_func_array(array($this, $method)
                , array(array($property => $arguments[0])));
    }

    private function prepare(array $findBy)
    {
        $builder = $this->createQueryBuilder();
        $method = 'where';

        foreach ($findBy as $key => $value) {
            $builder->{$method}("{$key} = ?");
            $method = 'andWhere';
        }

        $builder->setParameters(array_values($findBy));

        return $builder->execute();
    }

}
