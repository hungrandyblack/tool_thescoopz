<?php
require 'vendor/autoload.php';
require 'database.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Tạo batch_id duy nhất cho lần chạy này
$batch_id = uniqid("batch_", true);

$urls = Capsule::table('checks')->distinct()->pluck('channel_url');

foreach ($urls as $url) {
    Capsule::table('jobs')->insert([
        'channel_url' => $url,
        'status' => 'pending',
        'batch_id' => $batch_id,
        'created_at' => date('Y-m-d H:i:s'),
    ]);
}

// trả JSON cho AJAX
echo json_encode([
    "batch_id" => $batch_id,
    "total" => count($urls)
]);
