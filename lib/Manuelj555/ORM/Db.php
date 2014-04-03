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

/**
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
class Db
{

    private static $connections = array();
    private static $configs = array();

    /**
     * 
     * @param string $name
     * @return Connection
     */
    public static function get($name = 'default')
    {
        if (isset(self::$connections[$name])) {
            return self::$connections[$name];
        }

        if (!isset(self::$configs[$name])) {
            throw new \InvalidArgumentException('Config not Exists!');
        }

        return self::$connections[$name] = new Connection(self::$configs[$name]);
    }

    public static function factory($configs)
    {
        if (is_array($configs)) {
            self::$configs = array_merge(self::$configs, $configs);
        } elseif (is_callable($configs)) {
            self::$configs = call_user_func($configs);

            if (!is_array(self::$configs)) {
                throw new \InvalidArgumentException('No Array config!');
            }
        } else {

            throw new \InvalidArgumentException(sprintf('Expected array or callable, given "%s"', (string) $configs));
        }
    }

}
