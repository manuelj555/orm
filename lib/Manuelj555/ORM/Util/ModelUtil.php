<?php

/*
 * This file is part of the Manuel Aguirre Project.
 *
 * (c) Manuel Aguirre <programador.manuel@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Manuelj555\ORM\Util;

use InvalidArgumentException;
use Manuelj555\ORM\Schema\Table;
use ReflectionObject;

/**
 * Description of Model
 *
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
class ModelUtil
{

    private static $cache = array();
    private static $tables = array();

    public static function getTableName($class)
    {
        is_object($class) && $class = get_class($class);

        if (!isset(self::$tables[$class])) {

            if (!class_exists($class)) {
                throw new InvalidArgumentException(sprintf('Invalid Class "%s"', $class));
            }

            if (defined("{$class}::TABLE")) {
                self::$tables[$class] = constant("{$class}::TABLE");
            } else {
                self::$tables[$class] = static::camelize($class);
            }
        }

        return self::$tables[$class];
    }

    protected static function camelize($string)
    {
        return strtolower(preg_replace('~(?<=\\w)([A-Z])~', '_$1', $string));
    }

    public static function setValue($object, $property, $value, &$success = false)
    {
        $success = false;
        $class = get_class($object);

        if (isset($object->$property)) {
            $success = true;
            $object->$property = $value;
        }

        //object method
        if (!isset(self::$cache[$class]['methods'])) {
            self::$cache[$class]['methods'] = array_change_key_case(array_flip(get_class_methods($object)));
        }

        $lcItem = strtolower($property);

        if (isset(self::$cache[$class]['methods']['set' . $lcItem])) {
            $method = 'get' . $lcItem;
        }

        if (isset($method)) {
            $success = true;
            call_user_func(array($object, $method), $value);
        } else {
            $reflection = new ReflectionObject($object);
            if ($reflection->hasProperty($property)) {
                $prop = $reflection->getProperty($property);
                $prop->setAccessible(true);
                $prop->setValue($object, $value);
            }
        }
    }

    public static function getValue($object, $property, &$exists = false)
    {
        $class = get_class($object);

        if (isset($object->$property)) {
            return $object->$property;
        }

        //object method
        if (!isset(self::$cache[$class]['methods'])) {
            self::$cache[$class]['methods'] = array_change_key_case(array_flip(get_class_methods($object)));
        }

        $lcItem = strtolower($property);
        if (isset(self::$cache[$class]['methods'][$lcItem])) {
            $method = (string) $lcItem;
        } elseif (isset(self::$cache[$class]['methods']['get' . $lcItem])) {
            $method = 'get' . $lcItem;
        } elseif (isset(self::$cache[$class]['methods']['is' . $lcItem])) {
            $method = 'is' . $lcItem;
        } elseif (isset(self::$cache[$class]['methods']['__call'])) {
            $method = (string) $property;
        }

        if (isset($method)) {
            $exists = true;
            return call_user_func(array($object, $method));
        }

        $exists = false;
    }

    public static function setPK(Table $table, $object, $value)
    {
        $pk = $table->primaryKey;

        if ($pk and $table->auto) {
            $reflection = new ReflectionObject($object);
            if ($reflection->hasProperty($pk)) {
                $prop = $reflection->getProperty($pk);
                $prop->setAccessible(true);
                $prop->setValue($object, $value);
            }
        }
    }

    public static function getPK(Table $table, $object)
    {
        $pk = $table->primaryKey;

        if ($pk) {
            $reflection = new ReflectionObject($object);
            if ($reflection->hasProperty($pk)) {
                $prop = $reflection->getProperty($pk);
                $prop->setAccessible(true);
                return $prop->getValue($object);
            }
        }

        return false;
    }

    public static function getValues(Table $table, $object)
    {
        $values = array();

        foreach ($table->columns as $column) {
            $value = self::getValue($object, $column, $exists);
            if ($exists) {
                $values[$column] = $value;
            }
        }

        return $values;
    }

}
