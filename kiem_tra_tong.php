<?php
require 'vendor/autoload.php';
require 'database.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use GuzzleHttp\Client;

// =================== INIT HTTP CLIENT ===================
$client = new Client([
    'timeout' => 10,
    'headers' => [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    ],
]);

// =================== LẤY CHANNEL URL UNIQUE ===================
$channelUrls = Capsule::table('checks')
    ->distinct()
    ->pluck('channel_url');

foreach ($channelUrls as $channelUrl) {
    $channelUrl = trim($channelUrl);

    if (!filter_var($channelUrl, FILTER_VALIDATE_URL)) {
        echo "Invalid URL: $channelUrl\n";
        continue;
    }

    // =================== FETCH HTML ===================
    try {
        $response = $client->get($channelUrl);
        $html = (string)$response->getBody();
    } catch (\Exception $e) {
        echo "Error fetching $channelUrl: " . $e->getMessage() . "\n";
        continue;
    }

    // =================== PARSE HTML ===================
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);

    // Followers
    $followersNode = $xpath->query("//span[text()='Followers']/preceding-sibling::span[1]")->item(0);
    $followers = $followersNode ? parseCount($followersNode->textContent) : 0;

    // Following
    $followingNode = $xpath->query("//span[text()='Following']/preceding-sibling::span[1]")->item(0);
    $following = $followingNode ? parseCount($followingNode->textContent) : 0;

    // Posts / Video Count
    $postsNode = $xpath->query("//span[text()='Posts']/preceding-sibling::span[1]")->item(0);
    $view_count = $postsNode ? parseCount($postsNode->textContent) : 0;

    // Name channel
    $nameNode = $xpath->query("//h1[contains(@class,'font-extrabold')]")->item(0);
    $name_channel = $nameNode ? trim($nameNode->textContent) : '';

    // Tổng views tất cả video
    $totalViews = 0;
    $viewNodes = $xpath->query("//div[contains(@class,'flex') and contains(@class,'items-center') and contains(@class,'gap-1')]");
    foreach ($viewNodes as $div) {
        $img = $div->getElementsByTagName("img")->item(0);
        if (!$img) continue;
        $src = $img->getAttribute("src");

        if (strpos($src, "card-play.svg") !== false) {
            $text = trim($div->textContent);
            $totalViews += parseCount($text);
        }
    }

    // =================== UPDATE DATABASE ===================
    Capsule::table('checks')
        ->where('channel_url', $channelUrl)
        ->update([
            'followers'    => $followers,
            'total_views'  => $totalViews,
            'video_count'  => $view_count,
            'following'    => $following,
            'name_channel' => $name_channel
        ]);

    echo "Updated: $channelUrl\n";
}

// =================== HÀM HỖ TRỢ ===================
function parseCount($text) {
    $text = str_replace(',', '', trim($text));
    if (stripos($text, 'K') !== false) {
        return floatval(str_replace('K', '', $text)) * 1000;
    } elseif (stripos($text, 'M') !== false) {
        return floatval(str_replace('M', '', $text)) * 1000000;
    } else {
        return (int) $text;
    }
}
header("Location: index.html");
exit;
