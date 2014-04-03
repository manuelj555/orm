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

use InvalidArgumentException;
use Manuelj555\ORM\Cache\TableInfo;
use Manuelj555\ORM\Driver\AbstractDriver;
use Manuelj555\ORM\Query\QueryBuilder;
use Manuelj555\ORM\Schema\Table;
use Manuelj555\ORM\Util\ModelUtil;
use PDO;
use PDOStatement;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Description of Connection
 *
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
class Connection
{

    /**
     *
     * @var AbstractDriver
     */
    protected $driver;
    protected $config;
    protected $tableInfo;

    /**
     *
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     *
     * @var PDO
     */
    protected $pdo;

    public function __construct(array $config, EventDispatcherInterface $dispatcher = null)
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

        $this->setDriver($this->createDriver($config['driver']));

        $this->dispatcher = $dispatcher ? : new EventDispatcher();
    }

    public function connect()
    {
        $dsn = "{$this->config['driver']}:host={$this->config['host']};dbname={$this->config['dbname']}";

        $this->pdo = new PDO($dsn
                , $this->config['username']
                , $this->config['password']
                , $this->config['options']);

        $this->dispatcher->dispatch(Events::CONNECT);
    }

    /**
     * @return PDO
     */
    protected function getPDO()
    {
        if (!$this->pdo) {
            $this->connect();
        }

        return $this->pdo;
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
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
        $statement = $this->getPDO()->prepare($sql);
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        $statement->execute($parameters);

        if ($this->dispatcher->hasListeners(Events::QUERY)) {
            $this->dispatcher->dispatch(Events::QUERY);
        }

        return $statement;
    }

    /**
     * 
     * @param string $class
     * @return QueryBuilder
     */
    public function createQueryBuilder($class = null, $alias = null)
    {
        $query = new QueryBuilder($this, $class, $alias);

        if ($class) {
            $query->select($this->getTableFor($class)->columns);
        }

        return $query;
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
        if (!$this->getPDO()->inTransaction()) {
            $this->beginTransaction();
        }

        $table = $this->getTableFor($object);
        $pk = ModelUtil::getPK($table, $object);

        $this->driver->delete($table->name
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
        
        var_dump(get_defined_vars());

        if (count($arguments) !== 2) {
            throw new InvalidArgumentException('Invalid Number of Arguments, expected 2');
        }

        $property[0] = strtolower($property[0]);

        list($class, $value) = $arguments;

        return call_user_func_array(array($this, $method), array(
            $class, array($property => $value)
        ));
    }

    protected function prepareFind($class, array $by)
    {
        $builder = $this->createQueryBuilder($class)->select('*');
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

        if ($this->dispatcher->hasListeners(Events::PRE_INSERT)) {
            $this->dispatcher->dispatch(Events::PRE_INSERT);
        }

        $this->driver->insert($table->name, $values);
        ModelUtil::setPK($table, $object, $this->lastInsertId());

        if ($this->dispatcher->hasListeners(Events::POST_INSERT)) {
            $this->dispatcher->dispatch(Events::POST_INSERT);
        }
    }

    protected function update(Table $table, $object, $pk)
    {
        $values = ModelUtil::getValues($table, $object);
        $where = "{$table->primaryKey} = ?";

        $originals = $this->find(get_class($object), $pk, PDO::FETCH_ASSOC);

        foreach ($originals as $column => $value) {
            if (array_key_exists($column, $values) and $value === $values[$column]) {
                unset($values[$column]);
            }
        }

        if (count($values)) {

            if ($this->dispatcher->hasListeners(Events::PRE_UPDATE)) {
                $this->dispatcher->dispatch(Events::PRE_UPDATE);
            }

            $this->driver->update($table->name, $values, $where, array($pk));

            if ($this->dispatcher->hasListeners(Events::POST_UPDATE)) {
                $this->dispatcher->dispatch(Events::POST_UPDATE);
            }
        }
    }

    public function beginTransaction()
    {
        return $this->getPDO()->beginTransaction();
    }

    public function commit()
    {
        return $this->getPDO()->commit();
    }

    public function errorCode()
    {
        return $this->getPDO()->errorCode();
    }

    public function errorInfo()
    {
        return $this->getPDO()->errorInfo();
    }

    public function lastInsertId($name = null)
    {
        return $this->getPDO()->lastInsertId($name);
    }

    public function quote($input, $type = \PDO::PARAM_STR)
    {
        return $this->getPDO()->quote($input, $type);
    }

    public function rollBack()
    {
        return $this->getPDO()->rollBack();
    }

}
