<?php

/*
 * This file is part of the Manuel Aguirre Project.
 *
 * (c) Manuel Aguirre <programador.manuel@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Manuelj555\ORM\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Description of QueryEvent
 *
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
class QueryEvent extends Event
{

    /**
     * @var \PDOStatement
     */
    protected $statement;

    /**
     * @var array
     */
    protected $parameters = array();

    public function __construct(\PDOStatement $statement, array $parameters = array())
    {
        $this->statement = $statement;
        $this->parameters = $parameters;
    }

    public function getStatement()
    {
        return $this->statement;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function getSQL()
    {
        return $this->statement->queryString;
    }

}
