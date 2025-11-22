<?php
// history.php
require 'vendor/autoload.php'; // Composer autoload
require 'database.php';
use Illuminate\Database\Capsule\Manager as Capsule;
$capsule = new Capsule;
$rows = Capsule::table('checks')->orderBy("id", 'desc')->get();

// Trả JSON
header('Content-Type: application/json');
echo json_encode($rows);
