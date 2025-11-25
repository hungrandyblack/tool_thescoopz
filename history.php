<?php
require 'vendor/autoload.php';
require 'database.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Lấy param sort từ URL: ?sort=asc hoặc ?sort=desc
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'desc';

// Validate giá trị sort
if (!in_array($sort, ['asc', 'desc'])) {
    $sort = 'desc';
}

// Query
$rows = Capsule::table('checks')
    ->orderBy('total_views', $sort)  
    ->get();

// Trả JSON
header('Content-Type: application/json');
echo json_encode($rows);
