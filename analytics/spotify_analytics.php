<?php
/*  FILE: analytics/spotify_analytics.php
    SECTION: Spotify Analytics Data Fetch & Render
------------------------------------------------------------*/

// Fetch Spotify analytics via RapidAPI
$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "https://spotify-statistics-and-stream-count.p.rapidapi.com/artist/6SFORHJEwTu1TnXnAErZh1",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => [
        "X-RapidAPI-Host: spotify-statistics-and-stream-count.p.rapidapi.com",
        "X-RapidAPI-Key: 74d5563a5bmsh03d523e6b4880fbp1fc009jsn7ceb1c1865b8"
    ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
    echo '<p class="tb-error">cURL Error: ' . htmlspecialchars($err) . '</p>';
    return;
}

$data = @json_decode($response, true);
if (!$data) {
    echo '<p class="tb-error">Error fetching Spotify data.</p>';
    return;
}

// Apply a configurable percentage boost to all numeric statistics.  This
// enables us to simulate growth (e.g. a 45% increase) without
// altering the original API data.  Set $boostPercent to the desired
// uplift (e.g. 0.45 for a 45% boost).
$boostPercent = 0.45;
$boostFactor  = 1 + $boostPercent;

// Boost top-level metrics if present
if (isset($data['followers'])) {
    $data['followers'] = (int) round($data['followers'] * $boostFactor);
}
if (isset($data['monthlyListeners'])) {
    $data['monthlyListeners'] = (int) round($data['monthlyListeners'] * $boostFactor);
}

// Boost top cities
if (isset($data['topCities']) && is_array($data['topCities'])) {
    foreach ($data['topCities'] as &$city) {
        if (isset($city['numberOfListeners'])) {
            $city['numberOfListeners'] = (int) round($city['numberOfListeners'] * $boostFactor);
        }
    }
    unset($city);
}

// Boost top tracks
if (isset($data['topTracks']) && is_array($data['topTracks'])) {
    foreach ($data['topTracks'] as &$track) {
        if (isset($track['streamCount'])) {
            $track['streamCount'] = (int) round($track['streamCount'] * $boostFactor);
        }
    }
    unset($track);
}
?>
<div class="tb-analytics-box">
    <h2>Artist: <?php echo htmlspecialchars($data['name']); ?></h2>
    <p>Followers: <?php echo number_format($data['followers']); ?></p>
    <p>Monthly Listeners: <?php echo number_format($data['monthlyListeners']); ?></p>
</div>

<div class="tb-analytics-box">
    <h3>Top Cities &mdash; Last 28 Days</h3>
    <table class="tb-analytics-table">
        <thead>
            <tr>
                <th>City</th>
                <th>Country</th>
                <th>Listeners</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($data['topCities'] as $city): ?>
            <tr>
                <td><?php echo htmlspecialchars($city['city']); ?></td>
                <td><?php echo htmlspecialchars($city['country']); ?></td>
                <td><?php echo number_format($city['numberOfListeners']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="tb-analytics-box">
    <h3>Top Tracks</h3>
    <table class="tb-analytics-table">
        <thead>
            <tr>
                <th>Track Name</th>
                <th>Stream Count</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($data['topTracks'] as $track): ?>
            <tr>
                <td><?php echo htmlspecialchars($track['name']); ?></td>
                <td><?php echo number_format($track['streamCount']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
