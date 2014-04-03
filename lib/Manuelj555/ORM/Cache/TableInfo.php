<?php

/*
 * This file is part of the Manuel Aguirre Project.
 *
 * (c) Manuel Aguirre <programador.manuel@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Manuelj555\ORM\Cache;

use Manuelj555\ORM\Connection;
use Manuelj555\ORM\Driver\AbstractDriver;
use Manuelj555\ORM\Schema\Table;
use RuntimeException;

/**
 * Description of TableInfo
 *
 * @author Manuel Aguirre <programador.manuel@gmail.com>
 */
class TableInfo
{

    /**
     *
     * @var AbstractDriver
     */
    protected $driver;
    protected $debug;
    protected $cache;
    protected static $tables = array();

    public function __construct(Connection $conn)
    {
        $this->driver = $conn->getDriver();
        $this->cache = $conn->getConfig('cache');
        $this->debug = $this->cache ? $conn->getConfig('debug') : true;
    }

    /**
     * 
     * @param type $table
     * @return Table
     */
    public function getTable($table)
    {
        if (!isset(self::$tables[$table])) {
            self::$tables[$table] = $this->load($table);
        }

        return self::$tables[$table];
    }

    protected function load($tableName)
    {
        $file = $this->cachedName($tableName);
        if (false !== $file) {
            if (!is_file($file) or !$this->debug) {
                $table = $this->driver->describeTable($tableName);
                $this->writeCacheFile($file, serialize($table));
            }

            $table = unserialize(file_get_contents($file));
        } else {
            $table = $this->driver->describeTable($tableName);
        }

        return $table;
    }

    protected function cachedName($table)
    {
        if (!$this->cache) {
            return false;
        }

        return rtrim($this->cache, '/') . '/orm/' . $table;
    }

    protected function writeCacheFile($file, $content)
    {
        $dir = dirname($file);
        if (!is_dir($dir)) {
            if (false === @mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new RuntimeException(sprintf("Unable to create the cache directory (%s).", $dir));
            }
        } elseif (!is_writable($dir)) {
            throw new RuntimeException(sprintf("Unable to write in the cache directory (%s).", $dir));
        }

        $tmpFile = tempnam($dir, basename($file));
        if (false !== @file_put_contents($tmpFile, $content)) {
            if (@rename($tmpFile, $file) || (@copy($tmpFile, $file) && unlink($tmpFile))) {
                @chmod($file, 0666 & ~umask());

                return;
            }
        }

        throw new RuntimeException(sprintf('Failed to write cache file "%s".', $file));
    }

}
