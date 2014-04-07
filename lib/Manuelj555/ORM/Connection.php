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

use Exception;
use InvalidArgumentException;
use Manuelj555\ORM\Cache\TableInfo;
use Manuelj555\ORM\Driver\AbstractDriver;
use Manuelj555\ORM\Event\ConnectionEvent;
use Manuelj555\ORM\Event\DeleteEvent;
use Manuelj555\ORM\Event\InsertEvent;
use Manuelj555\ORM\Event\QueryEvent;
use Manuelj555\ORM\Event\UpdateEvent;
use Manuelj555\ORM\Schema\Table;
use Manuelj555\ORM\Util\ModelUtil;
use PDO;
use PDOStatement;
use ReflectionClass;
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
    protected $repositories = array();

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

        if ($this->dispatcher->hasListeners(Events::CONNECT)) {
            $event = new ConnectionEvent($this, $this->pdo);
            $this->dispatcher->dispatch(Events::CONNECT, $event);
        }
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
     * @param string $class
     * @return Repository
     */
    public function getRepository($class)
    {
        if (isset($this->repositories[$class])) {
            return $this->repositories[$class];
        }

        if (defined("$class::REPOSITORY")) {

            $repositoryClass = constant("$class::REPOSITORY");

            if (!class_exists($repositoryClass)) {
                $reflection = new ReflectionClass($class);
                $repositoryClass = $reflection->getNamespaceName() . '\\' . $repositoryClass;
                
                if(!class_exists($repositoryClass)){
                    throw new Exception(sprintf('Class "%s" not exists', $repositoryClass));
                }
            }

            $repository = new $repositoryClass($this, $class);

            if (!($repository instanceof Repository)) {
                throw new InvalidArgumentException(sprintf('The Repository "%s" for class "%s" must be instance of Manuelj555\\ORM\\Repository', $repositoryClass, $class));
            }
        } else {
            $repository = new Repository($this, $class);
        }
        
        return $this->repositories[$class] = $repository;
    }

    public function find($class, $id, $fetch = null)
    {
        return $this->getRepository($class)->find($id, $fetch);
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
            $event = new QueryEvent($statement, $parameters);
            $this->dispatcher->dispatch(Events::QUERY, $event);
        }

        return $statement;
    }

    public function save($object)
    {
        if (!$this->getPDO()->inTransaction()) {
            $this->beginTransaction();
        }

        $table = $this->getTable($object);
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

        $event = new DeleteEvent($object);
        $this->dispatcher->dispatch(Events::PRE_DELETE, $event);

        if ($event->isStopped()) {
            return;
        }

        $table = $this->getTable($object);
        $pk = ModelUtil::getPK($table, $object);

        $this->driver->delete($table->name, "{$table->primaryKey} = ?", array($pk));

        $this->dispatcher->dispatch(Events::POST_DELETE, $event);
    }

    public function flush()
    {
        return $this->commit();
    }

    /**
     * 
     * @return Table
     */
    public function getTable($object)
    {
        if (!$this->tableInfo) {
            $this->tableInfo = new TableInfo($this);
        }

        $tableName = ModelUtil::getTableName($object);
        return $this->tableInfo->getTable($tableName);
    }

    protected function create(Table $table, $object)
    {
        $event = new InsertEvent($object, array());
        $this->dispatcher->dispatch(Events::PRE_INSERT, $event);

        if ($event->isStopped()) {
            return;
        }

        $event->setData(ModelUtil::getValues($table, $object));

        $this->dispatcher->dispatch(Events::INSERT, $event);

        if ($event->isStopped()) {
            return;
        }

        $this->driver->insert($table->name, $event->getData());
        ModelUtil::setPK($table, $object, $this->lastInsertId());

        $this->dispatcher->dispatch(Events::POST_INSERT, $event);
    }

    protected function update(Table $table, $object, $pk)
    {
        $event = new UpdateEvent($object, array(), array());
        $this->dispatcher->dispatch(Events::PRE_UPDATE, $event);

        if ($event->isStopped()) {
            return;
        }

        $values = ModelUtil::getValues($table, $object);
        $where = "{$table->primaryKey} = ?";

        $originals = $this->find(get_class($object), $pk, PDO::FETCH_ASSOC);

        foreach ($originals as $column => $value) {
            if (array_key_exists($column, $values) and $value === $values[$column]) {
                unset($values[$column]);
            }
        }

        if (count($values)) {
            $event = new UpdateEvent($object, $values, $originals);
            $this->dispatcher->dispatch(Events::UPDATE, $event);

            if ($event->isStopped()) {
                return;
            }

            $this->driver->update($table->name, $values, $where, array($pk));

            $this->dispatcher->dispatch(Events:: POST_UPDATE, $event);
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
