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

use Manuelj555\ORM\Cache\TableInfo;
use Manuelj555\ORM\Driver\AbstractDriver;
use Manuelj555\ORM\Query\QueryBuilder;
use Manuelj555\ORM\Schema\Table;
use Manuelj555\ORM\Util\ModelUtil;
use PDO;
use PDOStatement;

/**
 * Description of Connection
 *
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
class Connection extends PDO
{

    /**
     *
     * @var AbstractDriver
     */
    protected $driver;
    protected $config;
    protected $tableInfo;

    public function __construct(array $config)
    {
        $this->config = $config = array_replace(array(
            'driver' => null,
            'host' => '127.0.0.1',
            'dbname' => null,
            'username' => null,
            'password' => null,
            'options' => array(),
            'debug' => true,
            'cache' => false,
                ), $config);

        $dsn = "{$config['driver']}:host={$config['host']};dbname={$config['dbname']}";

        parent::__construct($dsn, $config['username'], $config['password'], $config['options']);

        $this->setDriver($this->createDriver($config['driver']));
    }

    protected function createDriver($driverName)
    {
        switch (strtolower($driverName)) {
            case 'mysql':
                return new Driver\Mysql($this);
        }
    }

    public function setDriver(AbstractDriver $driver)
    {
        $this->driver = $driver;
    }

    public function getDriver()
    {
        return $this->driver;
    }

    public function getConfig($key = null)
    {
        if ($key) {
            return array_key_exists($key, $this->config) ? $this->config[$key] : null;
        }

        return $this->config;
    }

    /**
     * 
     * @param type $sql
     * @param array $parameters
     * @return PDOStatement
     */
    public function createQuery($sql, array $parameters = array())
    {
        $statement = $this->prepare($sql);
        $statement->setFetchMode(self::FETCH_ASSOC);
        $statement->execute($parameters);
//        var_dump($statement->queryString);
        return $statement;
    }

    /**
     * 
     * @param string $class
     * @return QueryBuilder
     */
    public function createQueryBuilder($class = null, $alias = null)
    {
        return new QueryBuilder($this, $class, $alias);
    }

    public function save($object)
    {
        if (!$this->inTransaction()) {
            $this->beginTransaction();
        }

        $table = $this->getTableFor($object);
        $pk = ModelUtil::getPK($table, $object);

        if ($pk) {
            //update
            $this->update($table, $object, $pk);
        } else {
            //insert
            $this->create($table, $object);
        }

        return $object;
    }

    public function remove($object)
    {
        if (!$this->inTransaction()) {
            $this->beginTransaction();
        }

        $table = $this->getTableFor($object);
        $pk = ModelUtil::getPK($table, $object);

        $this->getDriver()->delete($table->name
                , "{$table->primaryKey} = ?", array($pk));
    }

    public function flush()
    {
        return $this->commit();
    }

    public function find($class, $id, $fetch = null)
    {
        $table = $this->getTableFor($class);

        return $this->prepareFind($class, array(
                    $table->primaryKey => $id,
                ))->fetch($fetch);
    }

    public function findBy($class, array $by, $fetch = null)
    {
        return $this->prepareFind($class, $by)->fetch($fetch);
    }

    public function findAll($class, array $by = array(), $fetch = null)
    {
        return $this->prepareFind($class, $by)->fetchAll($fetch);
    }

    protected function prepareFind($class, array $by)
    {
        $builder = $this->createQueryBuilder($class)->select('*');

        $index = 0;

        $method = 'where';

        foreach ($by as $key => $value) {
            $builder->{$method}("{$key} = ?");
            $method = 'andWhere';
        }

        $builder->setParameters(array_values($by));

        return $builder->execute();
    }

    /**
     * 
     * @return Table
     */
    protected function getTableFor($object)
    {
        if (!$this->tableInfo) {
            $this->tableInfo = new TableInfo($this);
        }

        $tableName = ModelUtil::getTableName($object);
        return $this->tableInfo->getTable($tableName);
    }

    protected function create(Table $table, $object)
    {
        $values = ModelUtil::getValues($table, $object);

        $this->driver->insert($table->name, $values);
        ModelUtil::setPK($table, $object, $this->lastInsertId());
    }

    protected function update(Table $table, $object, $pk)
    {
        $values = ModelUtil::getValues($table, $object);
        $where = "{$table->primaryKey} = ?";

        $originals = $this->find(get_class($object), $pk, self::FETCH_ASSOC);

        foreach ($originals as $column => $value) {
            if (array_key_exists($column, $values) and $value === $values[$column]) {
                unset($values[$column]);
            }
        }

        if (count($values)) {
            $this->driver->update($table->name, $values, $where, array($pk));
        }
    }

}
