<?php
require 'vendor/autoload.php';
require 'database.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use GuzzleHttp\Client;

// Worker chạy mãi mãi
while (true) {

    // Lấy 1 job đang pending
    $job = Capsule::table('jobs')
        ->where('status', 'pending')
        ->orderBy('id')
        ->first();

    if (!$job) {
        echo "Không có job. Đợi 3s...\n";
        sleep(3);
        continue;
    }

    echo "Đang xử lý: {$job->channel_url}\n";

    // Update status
    Capsule::table('jobs')->where('id', $job->id)->update([
        'status' => 'processing',
        'updated_at' => date('Y-m-d H:i:s')
    ]);

    try {
        $client = new Client([
            'timeout' => 10,
            'headers' => ['User-Agent' => 'Mozilla/5.0'],
        ]);

        $response = $client->get($job->channel_url);
        $html = (string) $response->getBody();

        // Parse HTML
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        // Followers
        $followersNode = $xpath->query("//span[text()='Followers']/preceding-sibling::span[1]")->item(0);
        $followers = parseCount($followersNode ? $followersNode->textContent : '0');

        // Following
        $followingNode = $xpath->query("//span[text()='Following']/preceding-sibling::span[1]")->item(0);
        $following = parseCount($followingNode ? $followingNode->textContent : '0');

        // Posts
        $postsNode = $xpath->query("//span[text()='Posts']/preceding-sibling::span[1]")->item(0);
        $view_count = parseCount($postsNode ? $postsNode->textContent : '0');

        // Name
        $nameNode = $xpath->query("//h1[contains(@class,'font-extrabold')]")->item(0);
        $name_channel = $nameNode ? trim($nameNode->textContent) : '';

        // Total views
        $totalViews = 0;
        $viewNodes = $xpath->query("//div[contains(@class,'flex')]");
        foreach ($viewNodes as $div) {
            $img = $div->getElementsByTagName("img")->item(0);
            if (!$img) continue;

            if (strpos($img->getAttribute("src"), "card-play.svg") !== false) {
                $totalViews += parseCount($div->textContent);
            }
        }

        // Update vào bảng checks
        Capsule::table('checks')->where('channel_url', $job->channel_url)->update([
            'followers' => $followers,
            'total_views' => $totalViews,
            'video_count' => $view_count,
            'following' => $following,
            'name_channel' => $name_channel
        ]);

        // Done
        Capsule::table('jobs')->where('id', $job->id)->update([
            'status' => 'done',
            'updated_at' => date('Y-m-d H:i:s')
        ]);

    } catch (\Exception $e) {

        Capsule::table('jobs')->where('id', $job->id)->update([
            'status' => 'error',
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        echo "Lỗi job {$job->channel_url}: " . $e->getMessage() . "\n";
    }

    sleep(1); // nghỉ tránh quá tải server
}

function parseCount($txt)
{
    $txt = str_replace(',', '', trim($txt));
    if (stripos($txt, 'K') !== false) return floatval($txt) * 1000;
    if (stripos($txt, 'M') !== false) return floatval($txt) * 1000000;
    return (int) $txt;
}
