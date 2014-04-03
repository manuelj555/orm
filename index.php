<?php

use Manuelj555\ORM\Db;

require './vendor/autoload.php';

class User
{

    const TABLE = 'usuarios';

    protected $id;
    protected $nombres;
    public $email;
    protected $telefono;

    public function getId()
    {
        return $this->id;
    }

    public function setNombres($nombres)
    {
        $this->nombres = $nombres;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function setTelefono($telefono)
    {
        $this->telefono = $telefono;
    }

    public function getNombres()
    {
        return $this->nombres;
    }

    public function getTelefono()
    {
        return $this->telefono;
    }

}

Db::factory(array(
    'default' => array(
        'driver' => 'mysql',
        'dbname' => 'test',
        'username' => 'root',
        'password' => null,
        'cache' => __DIR__ . '/cache/',
//    'debug' => false,
        'options' => array(
//        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ),
    )
));

$conn = Db::get();

$u = $conn->find('User', 11);

$u->setTelefono('123567004444');
$u->setEmail("JEJEJEJEJA");

$e = $conn->save($u);
//$e = $conn->remove($u);
$conn->flush();

var_dump($u);
