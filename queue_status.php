<?php
require 'vendor/autoload.php';
require 'database.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$batch_id = $_GET['batch_id'] ?? null;

if (!$batch_id) {
    echo json_encode(["error" => "Missing batch_id"]);
    exit;
}
$running = Capsule::table('jobs')->where('batch_id', $batch_id)->whereIN('status', ['pending','processing'])->count();
echo json_encode([
    'running' => $running,
    'success' => Capsule::table('jobs')->where('batch_id', $batch_id)->where('status', 'done')->count(),
    'error'   => Capsule::table('jobs')->where('batch_id', $batch_id)->where('status', 'error')->count()
]);
