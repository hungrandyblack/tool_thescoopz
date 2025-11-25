<?php
require 'vendor/autoload.php';
require 'database.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// --- Lấy param ---
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 10;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'desc';
if (!in_array($sort, ['asc', 'desc'])) $sort = 'desc';

// --- Query builder ---
$query = Capsule::table('checks');

// Nếu có search
if ($search !== '') {
    $query->where(function($q) use ($search) {
        $q->where('channel_url', 'like', "%$search%")
          ->orWhere('name_channel', 'like', "%$search%");
    });
}

// Lấy tổng số item
$total = $query->count();

// Lấy dữ liệu paginate
$rows = $query->orderBy('total_views', $sort)
    ->offset(($page - 1) * $per_page)
    ->limit($per_page)
    ->get();

// Trả JSON
header('Content-Type: application/json');
echo json_encode([
    'items' => $rows,
    'total' => $total
]);
