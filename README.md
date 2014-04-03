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

Consultas
----

```php
<?php

use Manuelj555\ORM\Db;

$conection = Db::get(); //por defecto devuelve la conexión 'default';
$conection = Db::get('otra');

$conection->createQuery("SELECT * FROM usuarios")->fetchAll();
$conection->createQuery("SELECT * FROM usuarios WHERE nombre = :nom", array(':nombre' => 'Manuel')->fetchAll();
$conection->createQuery("SELECT * FROM usuarios WHERE nombre = ?", array('Manuel')->fetchAll();

//Usando Db directo:

Db::get('otra')->createQuery("SELECT * FROM usuarios")->fetchAll();
Db::get()->createQuery("SELECT * FROM usuarios WHERE nombre = ?", array('Manuel')->fetchAll();

//QueryBuilder

Db::get()->createQueryBuilder()
         ->select('*')
         ->from('usuarios', 'u')
         ->join('compras', 'c', 'c.usuarios_id = u.id')
         ->where('nombre = ?')
         ->setParameters(array('Manuel'))
         ->fetchAll();

```

Devolviendo Clases
----

```php
<?php

use Manuelj555\ORM\Db;

class Usuario
{
    const TABLE = 'user'; //opcional, por defecto la clase en small_case
    
    protected id;
    protected name;
    
    public function getId(){ return $this->id; }
    
    public function getName(){ return $this->name; }
    
    public function setName($name){ $this->name = $name; }
}

Db::get()->find('Usuario', 2); //busca en la tabla user por id = 2, devuelve una instancia de Usuario.

Db::get()->findBy('Usuario', array('name' => 'Manuel'));

Db::get()->findAll('Usuario');
Db::get()->findAll('Usuario', array('name' => 'Manuel'));

//Query Builder

Db::get()->createQueryBuilder('Usuario', 'u')
         ->where('name = :n')
         ->setParameter(':n' => 'Manuel')
         ->execute()->fetch();


```
