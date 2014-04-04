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

use Manuelj555\ORM\Connection;
use PDO;
use Symfony\Component\EventDispatcher\Event;

/**
 * Description of ConnectionEvent
 *
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
class ConnectionEvent extends Event
{

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var PDO
     */
    protected $pdo;

    function __construct(Connection $connection, PDO $pdo)
    {
        $this->connection = $connection;
        $this->pdo = $pdo;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function getPdo()
    {
        return $this->pdo;
    }

}
