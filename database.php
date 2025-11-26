<?php
use Illuminate\Database\Capsule\Manager as Capsule;

// =================== CÀI DATABASE ===================
$capsule = new Capsule;

$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => 'localhost',
    'database'  => 'tool_thescoopz',
    'username'  => 'root',
    'password'  => '',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
    'options'   => [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ],
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();
$capsule->getConnection()->statement("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");