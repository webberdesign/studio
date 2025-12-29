<?php
/*  PAGE NAME: pages/analytics_shop.php
    SECTION: Titty Bingo Shop Analytics (WooCommerce)
------------------------------------------------------------*/
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../analytics/cache_helpers.php';

$cacheKey = 'analytics_shop_overview';
$cachedOutput = tb_cache_read($cacheKey, 300);
if ($cachedOutput !== null) {
    echo $cachedOutput;
    return;
}

ob_start();
$cacheable = true;

function tb_money($value, $currency = 'USD') {
    $map = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'AUD' => 'A$',
        'CAD' => 'C$',
    ];
    $symbol = $map[$currency] ?? '';
    return $symbol . number_format((float) $value, 2);
}

function tb_collect_stats(array $orders, $days = null) {
    $since = $days ? (time() - ($days * 86400)) : 0;
    $filtered = array_filter($orders, fn($order) => $order['date'] >= $since);

    $count = count($filtered);
    $revenue = 0.0;
    $items = 0;
    foreach ($filtered as $order) {
        $revenue += $order['total'];
        $items += $order['items'];
    }

    return [
        'count' => $count,
        'revenue' => $revenue,
        'items' => $items,
        'avg' => $count ? $revenue / $count : 0,
    ];
}

function tb_fetch_shop_orders() {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $endpointPath = '/wc-analytics-orders.php';
    $endpoint = $host ? $scheme . '://' . $host . $endpointPath : $endpointPath;

    $context = stream_context_create([
        'http' => [
            'timeout' => 6,
        ],
    ]);

    $response = @file_get_contents($endpoint, false, $context);
    if ($response === false) {
        return ['orders' => [], 'error' => 'Unable to reach WooCommerce order feed.'];
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return ['orders' => [], 'error' => 'WooCommerce order feed returned an unexpected response.'];
    }

    $orders = $decoded['orders'] ?? [];
    if (!is_array($orders)) {
        $orders = [];
    }

    return ['orders' => $orders, 'error' => null];
}

$payload = tb_fetch_shop_orders();
$error = $payload['error'];
$rawOrders = $payload['orders'];
if ($error) {
    $cacheable = false;
}

$orders = [];
foreach ($rawOrders as $order) {
    if (!is_array($order)) {
        continue;
    }
    $date = (int) ($order['date'] ?? 0);
    if ($date <= 0) {
        continue;
    }

    $itemList = [];
    if (!empty($order['item_list']) && is_array($order['item_list'])) {
        foreach ($order['item_list'] as $item) {
            if (!is_array($item)) {
                continue;
            }
            $name = trim((string) ($item['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $qty = (int) ($item['qty'] ?? 0);
            $itemList[] = $qty > 0 ? sprintf('%s (%d)', $name, $qty) : $name;
        }
    }

    $orders[] = [
        'number' => $order['number'] ?? '—',
        'total' => (float) ($order['total'] ?? 0),
        'items' => (int) ($order['items'] ?? 0),
        'date' => $date,
        'currency' => $order['currency'] ?? 'USD',
        'customer' => $order['customer'] ?? 'Guest',
        'city' => $order['city'] ?? '',
        'state' => $order['state'] ?? '',
        'item_list' => $itemList,
    ];
}

usort($orders, fn($a, $b) => $b['date'] <=> $a['date']);

$defaultCurrency = $orders[0]['currency'] ?? 'USD';

$stats = [
    '7' => tb_collect_stats($orders, 7),
    '30' => tb_collect_stats($orders, 30),
    '90' => tb_collect_stats($orders, 90),
    '365' => tb_collect_stats($orders, 365),
    'all' => tb_collect_stats($orders, null),
];

$recentOrders = array_slice($orders, 0, 10);
?>
<section class="tb-section">
    <a href="index.php?page=analytics" class="tb-btn-secondary tb-back-link" data-loading-message="Loading Latest Analytics"><i class="fas fa-arrow-left"></i> Back to Analytics</a>
    <h1 class="tb-title">Titty Bingo Shop</h1>
    <p class="tb-subtitle">WooCommerce order performance and recent sales activity.</p>

    <?php if ($error) : ?>
        <div class="tb-alert"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="tb-stats-grid">
        <div class="tb-stat-card">
            <h3>Last 7 Days</h3>
            <div class="tb-stat-value"><?php echo number_format($stats['7']['count']); ?> orders</div>
            <div class="tb-stat-meta"><?php echo tb_money($stats['7']['revenue'], $defaultCurrency); ?></div>
        </div>
        <div class="tb-stat-card">
            <h3>Last 30 Days</h3>
            <div class="tb-stat-value"><?php echo number_format($stats['30']['count']); ?> orders</div>
            <div class="tb-stat-meta"><?php echo tb_money($stats['30']['revenue'], $defaultCurrency); ?></div>
        </div>
        <div class="tb-stat-card">
            <h3>Last 90 Days</h3>
            <div class="tb-stat-value"><?php echo number_format($stats['90']['count']); ?> orders</div>
            <div class="tb-stat-meta"><?php echo tb_money($stats['90']['revenue'], $defaultCurrency); ?></div>
        </div>
        <div class="tb-stat-card">
            <h3>Last 12 Months</h3>
            <div class="tb-stat-value"><?php echo number_format($stats['365']['count']); ?> orders</div>
            <div class="tb-stat-meta"><?php echo tb_money($stats['365']['revenue'], $defaultCurrency); ?></div>
        </div>
        <div class="tb-stat-card tb-stat-card--highlight">
            <h3>All-Time Orders</h3>
            <div class="tb-stat-value"><?php echo number_format($stats['all']['count']); ?> total</div>
            <div class="tb-stat-meta"><?php echo tb_money($stats['all']['revenue'], $defaultCurrency); ?></div>
        </div>
    </div>

    <div class="tb-shop-summary">
        <div class="tb-stat-card">
            <h3>All-Time Average Order</h3>
            <div class="tb-stat-value"><?php echo tb_money($stats['all']['avg'], $defaultCurrency); ?></div>
        </div>
        <div class="tb-stat-card">
            <h3>Total Items Sold</h3>
            <div class="tb-stat-value"><?php echo number_format($stats['all']['items']); ?></div>
        </div>
    </div>

    <div class="tb-order-section">
        <h2 class="tb-section-title">Recent Orders</h2>
        <?php if (empty($recentOrders)) : ?>
            <p class="tb-muted">No recent orders to show yet.</p>
        <?php else : ?>
            <div class="tb-order-list">
                <?php foreach ($recentOrders as $order) : ?>
                    <?php
                    $itemsLine = !empty($order['item_list'])
                        ? implode(', ', $order['item_list'])
                        : 'Items n/a';
                    $locationLine = trim($order['city'] . ($order['state'] ? ', ' . $order['state'] : ''));
                    ?>
                    <div class="tb-order-row">
                        <div>
                            <div class="tb-order-title">Order #<?php echo htmlspecialchars($order['number']); ?></div>
                            <div class="tb-order-sub"><?php echo htmlspecialchars($order['customer']); ?></div>
                            <div class="tb-order-sub"><?php echo htmlspecialchars($itemsLine); ?></div>
                        </div>
                        <div class="tb-order-meta">
                            <div class="tb-order-amount"><?php echo tb_money($order['total'], $order['currency']); ?></div>
                            <div class="tb-order-date"><?php echo date('M j, Y', $order['date']); ?></div>
                            <?php if ($locationLine !== '') : ?>
                                <div class="tb-order-date"><?php echo htmlspecialchars($locationLine); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php
$renderedOutput = ob_get_clean();
if ($cacheable) {
    tb_cache_write($cacheKey, $renderedOutput);
}

echo $renderedOutput;
?>
