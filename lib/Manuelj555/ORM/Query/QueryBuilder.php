<?php

/*
 * This file is part of the Manuel Aguirre Project.
 *
 * (c) Manuel Aguirre <programador.manuel@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Manuelj555\ORM\Query;

use Manuelj555\ORM\Connection;
use Manuelj555\ORM\Util\ModelUtil;
use PDO;

/**
 * Description of QueryBuilder
 *
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
class QueryBuilder
{

    const SELECT = 0;
    const INSERT = 1;
    const UPDATE = 2;
    const DELETE = 3;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var array The array of SQL parts collected.
     */
    private $sqlParts = array(
        'select' => array(),
        'from' => array(),
        'join' => array(),
        'set' => array(),
        'where' => null,
        'groupBy' => array(),
        'having' => null,
        'orderBy' => array()
    );
    private $params = array();
    private $paramTypes = array();
    private $type = self::SELECT;
    private $class;

    public function __construct(Connection $conn, $class = null, $alias = null)
    {
        $this->connection = $conn;
        $this->class = $class;

        if ($class) {
            return $this->from(ModelUtil::getTableName($class), $alias);
        }
    }

    /**
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @return \PDOStatement
     */
    public function execute()
    {
        $statement = $this->connection->createQuery($this->getSQL(), $this->params);

        if ($this->class) {
            $statement->setFetchMode(PDO::FETCH_CLASS, $this->class);
        }

        return $statement;
    }

    /**
     * @return string The sql query string.
     */
    public function getSQL()
    {
        $sql = '';

        switch ($this->type) {
            case self::INSERT:
                $sql = $this->getSQLForInsert();
                break;
            case self::DELETE:
                $sql = $this->getSQLForDelete();
                break;

            case self::UPDATE:
                $sql = $this->getSQLForUpdate();
                break;
            case self::SELECT:
            default:
                $sql = $this->getSQLForSelect();
        }

        return $sql;
    }

    /**
     * Sets a query parameter for the query being constructed.
     *
     * <code>
     *     $qb = $conn->createQueryBuilder()
     *         ->select('u')
     *         ->from('users', 'u')
     *         ->where('u.id = :user_id')
     *         ->setParameter(':user_id', 1);
     * </code>
     *
     * @param string|integer $key The parameter position or name.
     * @param mixed $value The parameter value.
     * @param string|null $type PDO::PARAM_*
     * @return QueryBuilder This QueryBuilder instance.
     */
    public function setParameter($key, $value, $type = null)
    {
        if ($type !== null) {
            $this->paramTypes[$key] = $type;
        }

        $this->params[$key] = $value;

        return $this;
    }

    public function setParameters(array $params, array $types = array())
    {
        $this->paramTypes = $types;
        $this->params = $params;

        return $this;
    }

    public function getParameters()
    {
        return $this->params;
    }

    public function getParameter($key)
    {
        return isset($this->params[$key]) ? $this->params[$key] : null;
    }

    public function add($sqlPartName, $sqlPart, $append = false)
    {
        $isArray = is_array($sqlPart);
        $isMultiple = is_array($this->sqlParts[$sqlPartName]);

        if ($isMultiple && !$isArray) {
            $sqlPart = array($sqlPart);
        }

        if ($append) {
            if ($sqlPartName == "orderBy" || $sqlPartName == "groupBy" || $sqlPartName == "select" || $sqlPartName == "set") {
                foreach ($sqlPart as $part) {
                    $this->sqlParts[$sqlPartName][] = $part;
                }
            } else if ($isArray && is_array($sqlPart[key($sqlPart)])) {
                $key = key($sqlPart);
                $this->sqlParts[$sqlPartName][$key][] = $sqlPart[$key];
            } else if ($isMultiple) {
                $this->sqlParts[$sqlPartName][] = $sqlPart;
            } else {
                $this->sqlParts[$sqlPartName] = $sqlPart;
            }

            return $this;
        }

        $this->sqlParts[$sqlPartName] = $sqlPart;

        return $this;
    }

    public function select($select = null)
    {
        $this->type = self::SELECT;

        if (empty($select)) {
            return $this;
        }

        $selects = is_array($select) ? $select : func_get_args();

        return $this->add('select', $selects, false);
    }

    public function addSelect($select = null)
    {
        $this->type = self::SELECT;

        if (empty($select)) {
            return $this;
        }

        $selects = is_array($select) ? $select : func_get_args();

        return $this->add('select', $selects, true);
    }

    public function insert($insert = null)
    {
        $this->type = self::INSERT;

        if (!$insert) {
            return $this;
        }

        return $this->add('from', array(
                    'table' => $insert
        ));
    }

    public function delete($delete = null, $alias = null)
    {
        $this->type = self::DELETE;

        if (!$delete) {
            return $this;
        }

        return $this->add('from', array(
                    'table' => $delete,
                    'alias' => $alias
        ));
    }

    public function update($update = null, $alias = null)
    {
        $this->type = self::UPDATE;

        if (!$update) {
            return $this;
        }

        return $this->add('from', array(
                    'table' => $update,
                    'alias' => $alias
        ));
    }

    public function from($from, $alias)
    {
        return $this->add('from', array(
                    'table' => $from,
                    'alias' => $alias
                        ), true);
    }

    public function setValue($column, $value)
    {
        $this->sqlParts['values'][$column] = $value;

        return $this;
    }

    public function values(array $values)
    {
        return $this->add('values', $values);
    }

    public function join($join, $alias, $condition = null)
    {
        return $this->innerJoin($join, $alias, $condition);
    }

    public function innerJoin($join, $alias, $condition = null)
    {
        return $this->add('join', array(
                    'joinType' => 'inner',
                    'joinTable' => $join,
                    'joinAlias' => $alias,
                    'joinCondition' => $condition
                        ), true);
    }

    public function leftJoin($fromAlias, $join, $alias, $condition = null)
    {
        return $this->add('join', array(
                    'joinType' => 'left',
                    'joinTable' => $join,
                    'joinAlias' => $alias,
                    'joinCondition' => $condition
                        ), true);
    }

    public function rightJoin($fromAlias, $join, $alias, $condition = null)
    {
        return $this->add('join', array(
                    'joinType' => 'right',
                    'joinTable' => $join,
                    'joinAlias' => $alias,
                    'joinCondition' => $condition
                        ), true);
    }

    public function set($key, $value)
    {
        return $this->add('set', $key . ' = ' . $value, true);
    }

    public function where($predicates)
    {
        return $this->add('where', $predicates);
    }

    public function andWhere($where)
    {
        return $this->add('where', ' AND ', $where, true);
    }

    public function orWhere($where)
    {
        return $this->add('where', ' OR ', $where, true);
    }

    public function groupBy($groupBy)
    {
        if (empty($groupBy)) {
            return $this;
        }

        $groupBy = is_array($groupBy) ? $groupBy : func_get_args();

        return $this->add('groupBy', $groupBy, false);
    }

    public function addGroupBy($groupBy)
    {
        if (empty($groupBy)) {
            return $this;
        }

        $groupBy = is_array($groupBy) ? $groupBy : func_get_args();

        return $this->add('groupBy', $groupBy, true);
    }

    public function having($having)
    {
        if (!(func_num_args() == 1 && $having instanceof CompositeExpression)) {
            $having = new CompositeExpression(CompositeExpression::TYPE_AND, func_get_args());
        }

        return $this->add('having', $having);
    }

    public function andHaving($having)
    {
        $having = $this->getQueryPart('having');
        $args = func_get_args();

        if ($having instanceof CompositeExpression && $having->getType() === CompositeExpression::TYPE_AND) {
            $having->addMultiple($args);
        } else {
            array_unshift($args, $having);
            $having = new CompositeExpression(CompositeExpression::TYPE_AND, $args);
        }

        return $this->add('having', $having);
    }

    public function orHaving($having)
    {
        $having = $this->getQueryPart('having');
        $args = func_get_args();

        if ($having instanceof CompositeExpression && $having->getType() === CompositeExpression::TYPE_OR) {
            $having->addMultiple($args);
        } else {
            array_unshift($args, $having);
            $having = new CompositeExpression(CompositeExpression::TYPE_OR, $args);
        }

        return $this->add('having', $having);
    }

    public function orderBy($sort, $order = null)
    {
        return $this->add('orderBy', $sort . ' ' . (!$order ? 'ASC' : $order), false);
    }

    public function addOrderBy($sort, $order = null)
    {
        return $this->add('orderBy', $sort . ' ' . (!$order ? 'ASC' : $order), true);
    }

    /**
     * Get a query part by its name.
     *
     * @param string $queryPartName
     * @return mixed $queryPart
     */
    public function getQueryPart($queryPartName)
    {
        return $this->sqlParts[$queryPartName];
    }

    public function getQueryParts()
    {
        return $this->sqlParts;
    }

    public function resetQueryParts($queryPartNames = null)
    {
        if (is_null($queryPartNames)) {
            $queryPartNames = array_keys($this->sqlParts);
        }

        foreach ($queryPartNames as $queryPartName) {
            $this->resetQueryPart($queryPartName);
        }

        return $this;
    }

    public function resetQueryPart($queryPartName)
    {
        $this->sqlParts[$queryPartName] = is_array($this->sqlParts[$queryPartName]) ? array() : null;

        $this->state = self::STATE_DIRTY;

        return $this;
    }

    private function getSQLForSelect()
    {
        $query = 'SELECT ' . implode(', ', $this->sqlParts['select']) . ' FROM ';

        $fromClauses = array();
        $joinClauses = array();

        foreach ($this->sqlParts['from'] as $from) {
            $fromClause = $from['table'] . ' ' . $from['alias'];


            $fromClauses[$from['alias']] = $fromClause;
        }

        foreach ($this->sqlParts['join'] as $join) {
            $joinClauses[$join['joinAlias']] = ' ' . strtoupper($join['joinType'])
                    . ' JOIN ' . $join['joinTable'] . ' ' . $join['joinAlias']
                    . ' ON ' . ((string) $join['joinCondition']);
        }

        $query .= implode(', ', $fromClauses) . implode(' ', $joinClauses)
                . ($this->sqlParts['where'] !== null ? ' WHERE ' . ((string) $this->sqlParts['where']) : '')
                . ($this->sqlParts['groupBy'] ? ' GROUP BY ' . implode(', ', $this->sqlParts['groupBy']) : '')
                . ($this->sqlParts['having'] !== null ? ' HAVING ' . ((string) $this->sqlParts['having']) : '')
                . ($this->sqlParts['orderBy'] ? ' ORDER BY ' . implode(', ', $this->sqlParts['orderBy']) : '');

        return $query;
//        return ($this->maxResults === null && $this->firstResult == null) ? $query : $this->connection->getDatabasePlatform()->modifyLimitQuery($query, $this->maxResults, $this->firstResult);
    }

    private function getSQLForInsert()
    {
        return 'INSERT INTO ' . $this->sqlParts['from']['table'] .
                ' (' . implode(', ', array_keys($this->sqlParts['values'])) . ')' .
                ' VALUES(' . implode(', ', $this->sqlParts['values']) . ')';
    }

    private function getSQLForUpdate()
    {
        $table = $this->sqlParts['from']['table'] . ($this->sqlParts['from']['alias'] ? ' ' . $this->sqlParts['from']['alias'] : '');
        $query = 'UPDATE ' . $table
                . ' SET ' . implode(", ", $this->sqlParts['set'])
                . ($this->sqlParts['where'] !== null ? ' WHERE ' . ((string) $this->sqlParts['where']) : '');

        return $query;
    }

    private function getSQLForDelete()
    {
        $table = $this->sqlParts['from']['table'] . ($this->sqlParts['from']['alias'] ? ' ' . $this->sqlParts['from']['alias'] : '');
        $query = 'DELETE FROM ' . $table . ($this->sqlParts['where'] !== null ? ' WHERE ' . ((string) $this->sqlParts['where']) : '');

        return $query;
    }

    public function __toString()
    {
        return $this->getSQL();
    }

    public function fetchAll()
    {
        return $this->execute()->fetchAll();
    }

}
