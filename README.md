ORM
===

Simple ORM para persistencia de Objetos en Base de Datos.

Configuración
----

Para establecer la Configuración de conexión se debe hacer de la siguiente manera:

```php
<?php

use Manuelj555\ORM\Db;

// Usando un array

Db::factory(array(
    'default' => array(
        'driver' => 'mysql',
        'dbname' => 'test',
        'username' => 'root',
        'password' => null,
        'cache' => __DIR__ . '/cache/', //directorio donde se cachea la info de las tablas.
        'debug' => true, //opcional, por defecto true
        'options' => array( //opciones para la conexión PDO
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ),
    ),
    'otra' => array(
        'driver' => 'sqlite',
        'dbname' => 'test',
        'username' => 'root',
        'password' => null,
        'cache' => __DIR__ . '/cache/',
    )
));


// Usando una función

Db::factory(function(){
    //debe retornar un arreglo de conexiones.
    return array(
        'default' => array(
            'driver' => 'mysql',
            'dbname' => 'test',
            'username' => 'root',
            'password' => null,
            'cache' => __DIR__ . '/cache/', //directorio donde se cachea la info de las tablas.
            'debug' => true, //opcional, por defecto true
            'options' => array( //opciones para la conexión PDO
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ),
        )
    );

});

```
