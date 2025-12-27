<?php
/*  PAGE NAME: analytics/youtube_analytics.php
    SECTION: YouTube Analytics Grid
------------------------------------------------------------*/

include __DIR__ . '/refreshToken.php';  // include refresh token script

$userId = 1;
$accessToken = refreshToken($userId);

$apiKey     = 'AIzaSyBhWFgxvpgiXJguMbxRS6leAMKAclgR5Vc';
$channelId  = 'UCAk3B7M6cxXZl9hUBhp0u1g';

function tb_fetch_all_videos($apiKey, $channelId) {
    $channelInfoUrl = "https://www.googleapis.com/youtube/v3/channels?part=contentDetails&id=$channelId&key=$apiKey";
    $channelInfo    = json_decode(file_get_contents($channelInfoUrl), true);
    $uploadsPlaylistId = $channelInfo['items'][0]['contentDetails']['relatedPlaylists']['uploads'];

    $videosUrl = "https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&maxResults=50&playlistId=$uploadsPlaylistId&key=$apiKey";
    $videos    = json_decode(file_get_contents($videosUrl), true);

    return $videos;
}

// Fetch basic channel statistics (subscribers, total views, video count)
$channelStatsUrl = "https://www.googleapis.com/youtube/v3/channels?part=statistics&id={$channelId}&key={$apiKey}";
$channelStatsResponse = @json_decode(file_get_contents($channelStatsUrl), true);
// Ensure we have stats before referencing
$channelStats = $channelStatsResponse['items'][0]['statistics'] ?? null;

/*
 * Channel‑level analytics helpers.
 *
 * The YouTube Analytics API supports reports that aggregate user activity
 * by geographic dimensions such as country and city.  For channel reports
 * (as opposed to video reports), the `ids` parameter should be set to
 * `channel==MINE` (or a specific channel ID if you have permission).  See
 * Google's documentation for details on the available dimensions and the
 * required sort/maxResults parameters【148652400450713†L339-L361】【148652400450713†L387-L410】.
 */

/**
 * Fetches the top countries by views for the entire channel.
 * Returns an array of [countryCode, viewCount] pairs.
 *
 * @param string $accessToken OAuth2 access token for YouTube Analytics
 * @param int    $maxResults  Maximum number of results (<=250)
 * @return array
 */
function fetchChannelTopCountries($accessToken, $maxResults = 10) {
    $startDate = '2000-01-01';
    $endDate   = date('Y-m-d');
    // Build the API URL for a country‑level report.  We request views
    // aggregated by country and sort descending by views.  See docs for
    // required sort options and maxResults restrictions【148652400450713†L339-L361】.
    $apiUrl = "https://youtubeanalytics.googleapis.com/v2/reports"
            . "?dimensions=country"
            . "&metrics=views"
            . "&ids=channel==MINE"
            . "&startDate={$startDate}"
            . "&endDate={$endDate}"
            . "&sort=-views"
            . "&maxResults=" . intval($maxResults);
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $accessToken));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);
    $results = [];
    if (isset($response['rows'])) {
        foreach ($response['rows'] as $row) {
            $country  = $row[0];
            $views    = $row[1];
            $results[] = [$country, $views];
        }
    }
    return $results;
}

/**
 * Fetches the top cities by views for the entire channel.  Note that the
 * YouTube Analytics API requires the `maxResults` parameter for city
 * reports to be 250 or less and a sort order to be specified【148652400450713†L387-L410】.
 * Returns an array of [cityName, viewCount] pairs.
 *
 * @param string $accessToken OAuth2 access token for YouTube Analytics
 * @param int    $maxResults  Maximum number of results (<=250)
 * @return array
 */
function fetchChannelTopCities($accessToken, $maxResults = 10) {
    $startDate = '2000-01-01';
    $endDate   = date('Y-m-d');
    $maxResults = min(250, intval($maxResults));
    $apiUrl = "https://youtubeanalytics.googleapis.com/v2/reports"
            . "?dimensions=city"
            . "&metrics=views"
            . "&ids=channel==MINE"
            . "&startDate={$startDate}"
            . "&endDate={$endDate}"
            . "&sort=-views"
            . "&maxResults={$maxResults}";
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $accessToken));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);
    $results = [];
    if (isset($response['rows'])) {
        foreach ($response['rows'] as $row) {
            $city  = $row[0];
            $views = $row[1];
            $results[] = [$city, $views];
        }
    }
    return $results;
}

/**
 * Fetches demographic breakdowns (age group or gender) by viewer percentage.
 *
 * @param string $accessToken OAuth2 access token for YouTube Analytics
 * @param string $dimension   Demographic dimension (ageGroup|gender)
 * @param int    $maxResults  Maximum rows to return
 * @return array
 */
function fetchChannelDemographics($accessToken, $dimension, $maxResults = 6) {
    $startDate = '2021-01-01';
    $endDate   = date('Y-m-d');
    $apiUrl = "https://youtubeanalytics.googleapis.com/v2/reports"
        . "?dimensions={$dimension}"
        . "&metrics=viewerPercentage"
        . "&ids=channel==MINE"
        . "&startDate={$startDate}"
        . "&endDate={$endDate}"
        . "&sort=-viewerPercentage"
        . "&maxResults=" . intval($maxResults);
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $accessToken));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $response['rows'] ?? [];
}

$videos = tb_fetch_all_videos($apiKey, $channelId);

// Fetch top geographic stats for the channel.  These lists show the
// countries and cities where the channel receives the most views.
$topCountries = fetchChannelTopCountries($accessToken, 10);
$topCities    = fetchChannelTopCities($accessToken, 10);
$ageBreakdown = $accessToken ? fetchChannelDemographics($accessToken, 'ageGroup') : [];
$genderBreakdown = $accessToken ? fetchChannelDemographics($accessToken, 'gender') : [];
?>

<div class="tb-yt-header">
    <h2>YouTube Channel Stats</h2>
    <?php if ($channelStats): ?>
      <?php
        $subscriberCount = (int) ($channelStats['subscriberCount'] ?? 0);
        $viewCount = (int) ($channelStats['viewCount'] ?? 0);
        $videoCount = max(1, (int) ($channelStats['videoCount'] ?? 0));
        $avgViews = $viewCount > 0 ? round($viewCount / $videoCount) : 0;
      ?>
      <div class="tb-stats-grid tb-stats-grid--three">
        <div class="tb-stat-card tb-stat-card--highlight">
          <h3>Total Subscribers</h3>
          <div class="tb-stat-value"><?php echo number_format($subscriberCount); ?></div>
        </div>
        <div class="tb-stat-card tb-stat-card--highlight">
          <h3>Total Views</h3>
          <div class="tb-stat-value"><?php echo number_format($viewCount); ?></div>
        </div>
        <div class="tb-stat-card">
          <h3>Avg Views per Video</h3>
          <div class="tb-stat-value"><?php echo number_format($avgViews); ?></div>
        </div>
      </div>
    <?php else: ?>
      <p class="tb-error">Unable to fetch channel statistics.</p>
    <?php endif; ?>

    <div class="tb-analytics-grid">
      <div class="tb-analytics-box">
        <h3>Top Countries by Views</h3>
        <?php if (!empty($topCountries)): ?>
          <ol class="tb-top-list">
            <?php foreach ($topCountries as [$country, $views]): ?>
              <li><?php echo htmlspecialchars($country); ?> – <?php echo number_format($views); ?> views</li>
            <?php endforeach; ?>
          </ol>
        <?php else: ?>
          <p class="tb-muted">No country data available yet.</p>
        <?php endif; ?>
      </div>
      <div class="tb-analytics-box">
        <h3>Top Cities by Views</h3>
        <?php if (!empty($topCities)): ?>
          <ol class="tb-top-list">
            <?php foreach ($topCities as [$city, $views]): ?>
              <li><?php echo htmlspecialchars($city); ?> – <?php echo number_format($views); ?> views</li>
            <?php endforeach; ?>
          </ol>
        <?php else: ?>
          <p class="tb-muted">No city data available yet.</p>
        <?php endif; ?>
      </div>
      <div class="tb-analytics-box">
        <h3>Viewer Age Groups</h3>
        <?php if (!empty($ageBreakdown)): ?>
          <ol class="tb-top-list">
            <?php foreach ($ageBreakdown as $row): ?>
              <li><?php echo htmlspecialchars($row[0]); ?> – <?php echo round($row[1], 2); ?>%</li>
            <?php endforeach; ?>
          </ol>
        <?php else: ?>
          <p class="tb-muted">Age group data requires OAuth access.</p>
        <?php endif; ?>
      </div>
      <div class="tb-analytics-box">
        <h3>Viewer Gender</h3>
        <?php if (!empty($genderBreakdown)): ?>
          <ol class="tb-top-list">
            <?php foreach ($genderBreakdown as $row): ?>
              <li><?php echo htmlspecialchars($row[0]); ?> – <?php echo round($row[1], 2); ?>%</li>
            <?php endforeach; ?>
          </ol>
        <?php else: ?>
          <p class="tb-muted">Gender data requires OAuth access.</p>
        <?php endif; ?>
      </div>
    </div>

    <h2 style="margin-top:1.5rem">Videos</h2>
    <?php if ($channelStats): ?>
      <p class="tb-muted">Total Published Videos: <?php echo number_format($videoCount); ?></p>
    <?php endif; ?>
</div>

<div class="video-gallery">
    <?php foreach ($videos['items'] as $item):
        $title        = $item['snippet']['title'];
        $videoId      = $item['snippet']['resourceId']['videoId'];
        $thumbnailUrl = $item['snippet']['thumbnails']['medium']['url'];
    ?>
        <div class="video">
            <a href="fetchvideo.php?videoID=<?php echo htmlspecialchars($videoId); ?>">
                <img src="<?php echo htmlspecialchars($thumbnailUrl); ?>" alt="<?php echo htmlspecialchars($title); ?>">
                <h2><?php echo htmlspecialchars($title); ?></h2>
            </a>
        </div>
    <?php endforeach; ?>
</div>
