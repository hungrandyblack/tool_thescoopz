<?php
// history.php

// Kết nối DB
$mysqli = new mysqli("localhost", "root", "", "tool_thescoopz");
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode(["error" => "DB connect error"]);
    exit;
}

$result = $mysqli->query("SELECT channel_url, followers, total_views, video_count, checked_at,following FROM checks ORDER BY checked_at DESC LIMIT 50");

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

$mysqli->close();

// Trả JSON
header('Content-Type: application/json');
echo json_encode($rows);
