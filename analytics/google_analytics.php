<?php
/*  FILE: analytics/google_analytics.php
    SECTION: Google Analytics Data Fetch & Render
------------------------------------------------------------*/

$apiKey = getenv('GA_API_KEY') ?: (defined('GA_API_KEY') ? GA_API_KEY : null);
$propertyId = getenv('GA_PROPERTY_ID') ?: (defined('GA_PROPERTY_ID') ? GA_PROPERTY_ID : null);
$gaClientId = getenv('GA_CLIENT_ID') ?: (defined('GA_CLIENT_ID') ? GA_CLIENT_ID : null);
$gaClientSecret = getenv('GA_CLIENT_SECRET') ?: (defined('GA_CLIENT_SECRET') ? GA_CLIENT_SECRET : null);
$gaRefreshToken = getenv('GA_REFRESH_TOKEN') ?: (defined('GA_REFRESH_TOKEN') ? GA_REFRESH_TOKEN : null);

function ga_fetch_access_token($clientId, $clientSecret, $refreshToken) {
    if (!$clientId || !$clientSecret || !$refreshToken) {
        return ['token' => null, 'error' => 'Missing Google OAuth credentials.'];
    }

    $postFields = http_build_query([
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'refresh_token' => $refreshToken,
        'grant_type' => 'refresh_token',
    ]);

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    $response = curl_exec($ch);
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['token' => null, 'error' => $error];
    }
    $data = json_decode($response, true);
    curl_close($ch);

    if (!isset($data['access_token'])) {
        return ['token' => null, 'error' => $data['error_description'] ?? 'Unable to refresh access token.'];
    }

    return ['token' => $data['access_token'], 'error' => null];
}

function ga_fetch_report($propertyId, array $metrics, array $dimensions, $startDate, $endDate, $accessToken = null, $apiKey = null, $limit = null, $orderBys = [], $dimensionFilter = null) {
    $apiUrl = "https://analyticsdata.googleapis.com/v1beta/properties/{$propertyId}:runReport";
    if ($apiKey) {
        $apiUrl .= '?key=' . urlencode($apiKey);
    }
    $payload = [
        'dateRanges' => [
            [
                'startDate' => $startDate,
                'endDate' => $endDate,
            ]
        ],
        'metrics' => array_map(fn($metric) => ['name' => $metric], $metrics),
    ];

    if (!empty($dimensions)) {
        $payload['dimensions'] = array_map(fn($dimension) => ['name' => $dimension], $dimensions);
    }

    if ($limit) {
        $payload['limit'] = (string) $limit;
    }

    if (!empty($orderBys)) {
        $payload['orderBys'] = $orderBys;
    }

    if ($dimensionFilter) {
        $payload['dimensionFilter'] = $dimensionFilter;
    }

    $payload = json_encode($payload);

    $headers = ['Content-Type: application/json'];
    if ($accessToken) {
        $headers[] = 'Authorization: Bearer ' . $accessToken;
    }

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
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

    return ['rows' => $data['rows'] ?? [], 'error' => null];
}

function ga_fetch_metric($propertyId, $metric, $startDate, $endDate, $accessToken = null, $apiKey = null) {
    $result = ga_fetch_report($propertyId, [$metric], [], $startDate, $endDate, $accessToken, $apiKey);
    if ($result['error']) {
        return ['value' => null, 'error' => $result['error']];
    }
    $value = $result['rows'][0]['metricValues'][0]['value'] ?? null;
    return ['value' => $value, 'error' => null];
}

if (!$propertyId) {
    echo '<p class="tb-error">Google Analytics is not configured.</p>';
    echo '<p class="tb-coming-soon">Set GA_PROPERTY_ID in your environment or config.php.</p>';
    return;
}

$accessToken = null;
$tokenError = null;
if ($gaClientId || $gaClientSecret || $gaRefreshToken) {
    $tokenResult = ga_fetch_access_token($gaClientId, $gaClientSecret, $gaRefreshToken);
    $accessToken = $tokenResult['token'];
    $tokenError = $tokenResult['error'];
}

if (!$accessToken && !$apiKey) {
    echo '<p class="tb-error">Google Analytics credentials are missing.</p>';
    echo '<p class="tb-coming-soon">Provide GA_CLIENT_ID, GA_CLIENT_SECRET, GA_REFRESH_TOKEN (OAuth) or GA_API_KEY.</p>';
    return;
}

$today = new DateTimeImmutable('today');
$since2021 = '2021-01-01';
$lastNinetyDays = $today->modify('-90 days')->format('Y-m-d');

$lastMonthStart = (new DateTimeImmutable('first day of last month'))->format('Y-m-d');
$lastMonthEnd = (new DateTimeImmutable('last day of last month'))->format('Y-m-d');

$stats = [
    [
        'label' => 'Total Views (Since 2021)',
        'start' => $since2021,
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

$analyticsStart = '2023-01-01';
$analyticsEnd = $today->format('Y-m-d');
$yearlyReport = ga_fetch_report(
    $propertyId,
    ['screenPageViews'],
    ['year'],
    $analyticsStart,
    $analyticsEnd,
    $accessToken,
    $apiKey,
    null,
    [
        [
            'dimension' => [
                'dimensionName' => 'year',
            ],
            'desc' => false,
        ],
    ]
);
$topCountryReport = ga_fetch_report(
    $propertyId,
    ['screenPageViews'],
    ['country'],
    $analyticsStart,
    $analyticsEnd,
    $accessToken,
    $apiKey,
    10,
    [
        [
            'metric' => [
                'metricName' => 'screenPageViews',
            ],
            'desc' => true,
        ],
    ]
);

$topCityReport = ga_fetch_report(
    $propertyId,
    ['screenPageViews'],
    ['city'],
    $analyticsStart,
    $analyticsEnd,
    $accessToken,
    $apiKey,
    10,
    [
        [
            'metric' => [
                'metricName' => 'screenPageViews',
            ],
            'desc' => true,
        ],
    ]
);

$topPageReport = ga_fetch_report(
    $propertyId,
    ['screenPageViews'],
    ['pagePath'],
    $analyticsStart,
    $analyticsEnd,
    $accessToken,
    $apiKey,
    15,
    [
        [
            'metric' => [
                'metricName' => 'screenPageViews',
            ],
            'desc' => true,
        ],
    ]
);

$genderReport = ga_fetch_report(
    $propertyId,
    ['screenPageViews'],
    ['sessionSourceMedium'],
    $analyticsStart,
    $analyticsEnd,
    $accessToken,
    $apiKey,
    12,
    [
        [
            'metric' => [
                'metricName' => 'screenPageViews',
            ],
            'desc' => true,
        ],
    ]
);

$pageReport = ga_fetch_report(
    $propertyId,
    ['screenPageViews'],
    ['pagePath'],
    $analyticsStart,
    $analyticsEnd,
    $accessToken,
    $apiKey,
    8,
    [
        [
            'metric' => [
                'metricName' => 'screenPageViews',
            ],
            'desc' => true,
        ],
    ],
    [
        'notExpression' => [
            'filter' => [
                'fieldName' => 'pagePath',
                'stringFilter' => [
                    'matchType' => 'EXACT',
                    'value' => '/firestone-park/firestone-pool/',
                ],
            ],
        ],
    ]
);

$deviceReport = ga_fetch_report(
    $propertyId,
    ['screenPageViews'],
    ['deviceCategory'],
    $analyticsStart,
    $analyticsEnd,
    $accessToken,
    $apiKey,
    6,
    [
        [
            'metric' => [
                'metricName' => 'screenPageViews',
            ],
            'desc' => true,
        ],
    ]
);

$browserReport = ga_fetch_report(
    $propertyId,
    ['screenPageViews'],
    ['browser'],
    $analyticsStart,
    $analyticsEnd,
    $accessToken,
    $apiKey,
    6,
    [
        [
            'metric' => [
                'metricName' => 'screenPageViews',
            ],
            'desc' => true,
        ],
    ]
);

$errors = [];
$values = [];
foreach ($stats as $stat) {
    $result = ga_fetch_metric($propertyId, 'screenPageViews', $stat['start'], $stat['end'], $accessToken, $apiKey);
    if ($result['error']) {
        $errors[] = $result['error'];
        $values[] = null;
    } else {
        $values[] = $result['value'];
    }
}

$excludedPages = [
    '/firestone-park/firestone-park-rental-rates/',
];
$topPages = [];
if (!empty($topPageReport['rows'])) {
    foreach ($topPageReport['rows'] as $row) {
        $path = $row['dimensionValues'][0]['value'] ?? '';
        if ($path === '' || in_array($path, $excludedPages, true)) {
            continue;
        }
        $topPages[] = $row;
        if (count($topPages) >= 7) {
            break;
        }
    }
}
?>

<?php if (!empty($errors)): ?>
    <p class="tb-error">Unable to fetch Google Analytics data.</p>
    <?php if ($tokenError): ?>
        <p class="tb-coming-soon"><?php echo htmlspecialchars($tokenError); ?></p>
    <?php else: ?>
        <p class="tb-coming-soon">Check the GA credentials and property ID, then refresh.</p>
    <?php endif; ?>
<?php endif; ?>

<div class="tb-stats-grid tb-stats-grid--three">
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

<?php
$yearlyViews = [
    '2021' => 0,
    '2022' => 0,
    '2023' => 0,
    '2024' => 0,
    '2025' => 0,
];
if (!empty($yearlyReport['rows'])) {
    foreach ($yearlyReport['rows'] as $row) {
        $year = $row['dimensionValues'][0]['value'] ?? null;
        if ($year && array_key_exists($year, $yearlyViews)) {
            $yearlyViews[$year] = (int) ($row['metricValues'][0]['value'] ?? 0);
        }
    }
}
?>

<div class="tb-stats-grid tb-stats-grid--three">
    <?php foreach ($yearlyViews as $year => $views): ?>
        <div class="tb-stat-card tb-stat-card--large">
            <h3><?php echo htmlspecialchars($year); ?> Views</h3>
            <div class="tb-stat-value"><?php echo number_format($views); ?></div>
        </div>
    <?php endforeach; ?>
</div>

<div class="tb-analytics-grid">
    <div class="tb-analytics-box">
        <h3>Top Pages (2023–Present)</h3>
        <?php if (!empty($topPages)): ?>
            <ol class="tb-top-list">
                <?php foreach ($topPages as $row): ?>
                    <li><?php echo htmlspecialchars($row['dimensionValues'][0]['value'] ?? 'Unknown'); ?> – <?php echo number_format((int) ($row['metricValues'][0]['value'] ?? 0)); ?> views</li>
                <?php endforeach; ?>
            </ol>
        <?php else: ?>
            <p class="tb-muted">No page data available yet.</p>
        <?php endif; ?>
    </div>
    <div class="tb-analytics-box">
        <h3>Top Countries (2023–Present)</h3>
        <?php if (!empty($topCountryReport['rows'])): ?>
            <ol class="tb-top-list">
                <?php foreach ($topCountryReport['rows'] as $row): ?>
                    <li><?php echo htmlspecialchars($row['dimensionValues'][0]['value'] ?? 'Unknown'); ?> – <?php echo number_format((int) ($row['metricValues'][0]['value'] ?? 0)); ?> views</li>
                <?php endforeach; ?>
            </ol>
        <?php else: ?>
            <p class="tb-muted">No country data available yet.</p>
        <?php endif; ?>
    </div>
    <div class="tb-analytics-box">
        <h3>Top Cities (2023–Present)</h3>
        <?php if (!empty($topCityReport['rows'])): ?>
            <ol class="tb-top-list">
                <?php foreach ($topCityReport['rows'] as $row): ?>
                    <li><?php echo htmlspecialchars($row['dimensionValues'][0]['value'] ?? 'Unknown'); ?> – <?php echo number_format((int) ($row['metricValues'][0]['value'] ?? 0)); ?> views</li>
                <?php endforeach; ?>
            </ol>
        <?php else: ?>
            <p class="tb-muted">No city data available yet.</p>
        <?php endif; ?>
    </div>
    <div class="tb-analytics-box">
        <h3>Top Referrers</h3>
        <?php if (!empty($referrerReport['rows'])): ?>
            <ol class="tb-top-list">
                <?php foreach ($referrerReport['rows'] as $row): ?>
                    <li><?php echo htmlspecialchars($row['dimensionValues'][0]['value'] ?? 'Unknown'); ?> – <?php echo number_format((int) ($row['metricValues'][0]['value'] ?? 0)); ?> views</li>
                <?php endforeach; ?>
            </ol>
        <?php else: ?>
            <p class="tb-muted">Referrer data not available for this property.</p>
        <?php endif; ?>
    </div>
    <div class="tb-analytics-box">
        <h3>Top Pages</h3>
        <?php if (!empty($pageReport['rows'])): ?>
            <ol class="tb-top-list">
                <?php foreach ($pageReport['rows'] as $row): ?>
                    <li><?php echo htmlspecialchars($row['dimensionValues'][0]['value'] ?? 'Unknown'); ?> – <?php echo number_format((int) ($row['metricValues'][0]['value'] ?? 0)); ?> views</li>
                <?php endforeach; ?>
            </ol>
        <?php else: ?>
            <p class="tb-muted">Page data not available for this property.</p>
        <?php endif; ?>
    </div>
    <div class="tb-analytics-box">
        <h3>Device Categories</h3>
        <?php if (!empty($deviceReport['rows'])): ?>
            <ol class="tb-top-list">
                <?php foreach ($deviceReport['rows'] as $row): ?>
                    <li><?php echo htmlspecialchars($row['dimensionValues'][0]['value'] ?? 'Unknown'); ?> – <?php echo number_format((int) ($row['metricValues'][0]['value'] ?? 0)); ?> views</li>
                <?php endforeach; ?>
            </ol>
        <?php else: ?>
            <p class="tb-muted">Device data not available for this property.</p>
        <?php endif; ?>
    </div>
    <div class="tb-analytics-box">
        <h3>Top Browsers</h3>
        <?php if (!empty($browserReport['rows'])): ?>
            <ol class="tb-top-list">
                <?php foreach ($browserReport['rows'] as $row): ?>
                    <li><?php echo htmlspecialchars($row['dimensionValues'][0]['value'] ?? 'Unknown'); ?> – <?php echo number_format((int) ($row['metricValues'][0]['value'] ?? 0)); ?> views</li>
                <?php endforeach; ?>
            </ol>
        <?php else: ?>
            <p class="tb-muted">Browser data not available for this property.</p>
        <?php endif; ?>
    </div>
</div>
