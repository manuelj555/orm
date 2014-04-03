<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Manuelj555\ORM;

/**
 *
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
interface ConnectionInterface
{

    public function quote($input, $type = \PDO::PARAM_STR);

    public function lastInsertId($name = null);

    public function beginTransaction();

    public function commit();

    public function rollBack();

    public function errorCode();

    public function errorInfo();
}
