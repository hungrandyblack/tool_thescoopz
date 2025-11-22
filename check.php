<?php
header("Content-Type: application/json; charset=UTF-8");

require 'vendor/autoload.php'; // Nếu dùng Guzzle
use GuzzleHttp\Client;

// =================== LẤY URL ===================
if (!isset($_POST['channel_url'])) {
    echo json_encode(["error" => "No channel URL provided"]);
    exit;
}

$channelUrl = trim($_POST['channel_url']);
if (!filter_var($channelUrl, FILTER_VALIDATE_URL)) {
    echo json_encode(["error" => "Invalid URL"]);
    exit;
}

// =================== FETCH HTML ===================
$client = new Client([
    'timeout' => 10,
    'headers' => [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    ],
]);

try {
    $response = $client->get($channelUrl);
    $html = (string)$response->getBody();
} catch (\Exception $e) {
    echo json_encode(["error" => "Lỗi khi tải trang: " . $e->getMessage()]);
    exit;
}

// =================== PARSE HTML ===================
libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadHTML($html);
libxml_clear_errors();
$xpath = new DOMXPath($dom);

// Posts (sẽ trả về view_count)
$postsNode = $xpath->query("//span[text()='Posts']/preceding-sibling::span[1]")->item(0);
$view_count = $postsNode ? (int) trim($postsNode->textContent) : 0;

// Following
$followingNode = $xpath->query("//span[text()='Following']/preceding-sibling::span[1]")->item(0);
$following = $followingNode ? (int) trim($followingNode->textContent) : 0;

// Followers
$followersNode = $xpath->query("//span[text()='Followers']/preceding-sibling::span[1]")->item(0);
$followers = $followersNode ? (int) trim($followersNode->textContent) : 0;

// Tổng views tất cả video
$totalViews = 0;
$videoCount = 0;

$viewNodes = $xpath->query("//div[contains(@class,'flex') and contains(@class,'items-center') and contains(@class,'gap-1')]");
foreach ($viewNodes as $div) {
    $img = $div->getElementsByTagName("img")->item(0);
    if (!$img) continue;
    $src = $img->getAttribute("src");

    if (strpos($src, "card-play.svg") !== false) {
        $text = trim($div->textContent);
        $num = (int) str_replace([",", "."], "", $text);
        $totalViews += $num;
        $videoCount++;
    }
}

// =================== LƯU DATABASE ===================
$mysqli = new mysqli("localhost", "root", "", "tool_thescoopz");
if ($mysqli->connect_errno) {
    echo json_encode(["error" => "Database error: " . $mysqli->connect_error]);
    exit;
}

$stmt = $mysqli->prepare("
    INSERT INTO checks (channel_url, followers, total_views, video_count, following)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->bind_param("siiii", $channelUrl, $followers, $totalViews, $view_count, $following);
$stmt->execute();
$stmt->close();
$mysqli->close();

// =================== TRẢ JSON ===================
echo json_encode([
    "channel_url" => $channelUrl,
    "followers" => $followers,
    "following" => $following,
    "view_count" => $view_count,
    "total_views" => $totalViews,
    "video_count" => $videoCount
]);
