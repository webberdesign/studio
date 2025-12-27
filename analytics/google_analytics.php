<?php
/*  FILE: analytics/google_analytics.php
    SECTION: Google Analytics Data Fetch & Render
------------------------------------------------------------*/

$apiKey = getenv('GA_API_KEY');
if (!$apiKey && defined('GA_API_KEY')) {
    $apiKey = GA_API_KEY;
}

$propertyId = getenv('GA_PROPERTY_ID');
if (!$propertyId && defined('GA_PROPERTY_ID')) {
    $propertyId = GA_PROPERTY_ID;
}

function ga_fetch_metric($apiKey, $propertyId, $metric, $startDate, $endDate) {
    $apiUrl = "https://analyticsdata.googleapis.com/v1beta/properties/{$propertyId}:runReport?key={$apiKey}";
    $payload = json_encode([
        'dateRanges' => [
            [
                'startDate' => $startDate,
                'endDate' => $endDate,
            ]
        ],
        'metrics' => [
            ['name' => $metric],
        ],
    ]);

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        return ['value' => null, 'error' => $err];
    }

    $data = json_decode($response, true);
    if (isset($data['error'])) {
        return ['value' => null, 'error' => $data['error']['message'] ?? 'Unknown API error'];
    }

    $value = $data['rows'][0]['metricValues'][0]['value'] ?? null;
    return ['value' => $value, 'error' => null];
}

if (!$apiKey || !$propertyId) {
    echo '<p class="tb-error">Google Analytics is not configured.</p>';
    echo '<p class="tb-coming-soon">Set GA_API_KEY and GA_PROPERTY_ID in your environment or config.php.</p>';
    return;
}

$today = new DateTimeImmutable('today');
$lastFiveYears = $today->modify('-5 years')->format('Y-m-d');
$lastNinetyDays = $today->modify('-90 days')->format('Y-m-d');

$lastMonthStart = (new DateTimeImmutable('first day of last month'))->format('Y-m-d');
$lastMonthEnd = (new DateTimeImmutable('last day of last month'))->format('Y-m-d');

$stats = [
    [
        'label' => 'Total Views (Last 5 Years)',
        'start' => $lastFiveYears,
        'end' => $today->format('Y-m-d'),
    ],
    [
        'label' => 'Total Views (Last 90 Days)',
        'start' => $lastNinetyDays,
        'end' => $today->format('Y-m-d'),
    ],
    [
        'label' => 'Total Views (Last Month)',
        'start' => $lastMonthStart,
        'end' => $lastMonthEnd,
    ],
];

$errors = [];
$values = [];
foreach ($stats as $stat) {
    $result = ga_fetch_metric($apiKey, $propertyId, 'screenPageViews', $stat['start'], $stat['end']);
    if ($result['error']) {
        $errors[] = $result['error'];
        $values[] = null;
    } else {
        $values[] = $result['value'];
    }
}
?>

<?php if (!empty($errors)): ?>
    <p class="tb-error">Unable to fetch Google Analytics data.</p>
    <p class="tb-coming-soon">Check the GA API key and property ID, then refresh.</p>
<?php endif; ?>

<div class="tb-stats-grid">
    <?php foreach ($stats as $index => $stat): ?>
        <div class="tb-stat-card">
            <h3><?php echo htmlspecialchars($stat['label']); ?></h3>
            <div class="tb-stat-value">
                <?php if ($values[$index] !== null): ?>
                    <?php echo number_format((int) $values[$index]); ?>
                <?php else: ?>
                    —
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
