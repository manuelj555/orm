<?php

/*
 * This file is part of the Manuel Aguirre Project.
 *
 * (c) Manuel Aguirre <programador.manuel@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Manuelj555\ORM\Schema;

use Manuelj555\ORM\Connection;

/**
 * Description of Table
 *
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
class Table implements \Serializable
{

    public $name;
    public $columns;
    public $primaryKey;
    public $auto;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function serialize()
    {
        return serialize(array(
            $this->name
            , $this->columns
            , $this->primaryKey
            , $this->auto
        ));
    }

    public function unserialize($serialized)
    {
        list($this->name, $this->columns,
                $this->primaryKey, $this->auto) = unserialize($serialized);
    }

}
