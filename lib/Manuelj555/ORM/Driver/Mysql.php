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

use Manuelj555\ORM\Schema\Table;
use PDOException;

/**
 * Description of Mysql
 *
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
class Mysql extends AbstractDriver
{

    public function getName()
    {
        return 'mysql';
    }

    public function describeTable($name)
    {
        try {
            $result = $this->getConnection()->createQuery("DESCRIBE $name");

            if ($result) {

                $table = new Table($name);

                while ($field = $result->fetchObject()) {
                    $table->columns[] = $field->Field;

                    if (strtoupper($field->Key) === 'PRI') {
                        $table->primaryKey = $field->Field;

                        if ($field->Extra === 'auto_increment') {
                            $table->auto = true;
                        } else {
                            $table->auto = false;
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            throw $e;
        }

        return $table;
    }

}
