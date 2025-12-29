<?php
/*  PAGE NAME: pages/analytics_shop.php
    SECTION: Titty Bingo Shop Analytics (WooCommerce)
------------------------------------------------------------*/
require_once __DIR__ . '/../config.php';

function tb_format_currency($amount, $currency) {
    $symbols = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'AUD' => 'A$',
        'CAD' => 'C$',
    ];
    $prefix = $symbols[$currency] ?? '';
    $formatted = number_format((float) $amount, 2);
    return $prefix !== '' ? $prefix . $formatted : $formatted . ($currency ? ' ' . $currency : '');
}

function tb_order_timestamp(array $order) {
    $dateString = $order['date_created']
        ?? $order['date_created_gmt']
        ?? $order['date']
        ?? $order['created_at']
        ?? null;
    if (!$dateString) {
        return null;
    }
    $timestamp = strtotime($dateString);
    return $timestamp !== false ? $timestamp : null;
}

function tb_fetch_wc_orders() {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $endpointPath = '/wc-orders.php';
    $endpoint = $host ? $scheme . '://' . $host . $endpointPath : $endpointPath;

    $context = stream_context_create([
        'http' => [
            'timeout' => 6,
        ],
    ]);

    $response = @file_get_contents($endpoint, false, $context);
    if ($response === false && $endpoint !== $endpointPath) {
        $response = @file_get_contents($endpointPath, false, $context);
    }
    if ($response === false) {
        return [
            'orders' => [],
            'error' => 'Unable to reach WooCommerce order feed.',
        ];
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return [
            'orders' => [],
            'error' => 'WooCommerce order feed returned an unexpected response.',
        ];
    }

    return [
        'orders' => $decoded['orders'] ?? $decoded,
        'error' => null,
    ];
}

function tb_collect_stats(array $orders, $sinceTimestamp = null) {
    $filtered = array_filter($orders, function ($order) use ($sinceTimestamp) {
        return $sinceTimestamp === null || $order['date'] >= $sinceTimestamp;
    });

    $count = count($filtered);
    $revenue = 0.0;
    $items = 0;
    foreach ($filtered as $order) {
        $revenue += $order['total'];
        $items += $order['items'];
    }

    $average = $count > 0 ? $revenue / $count : 0.0;

    return [
        'count' => $count,
        'revenue' => $revenue,
        'items' => $items,
        'average' => $average,
    ];
}

$payload = tb_fetch_wc_orders();
$rawOrders = is_array($payload['orders']) ? $payload['orders'] : [];
$error = $payload['error'];

$excludedStatuses = ['failed', 'refunded', 'cancelled', 'canceled', 'reversed'];
$orders = [];
$defaultCurrency = 'USD';

foreach ($rawOrders as $order) {
    if (!is_array($order)) {
        continue;
    }

    $status = strtolower($order['status'] ?? '');
    if ($status && in_array($status, $excludedStatuses, true)) {
        continue;
    }

    $timestamp = tb_order_timestamp($order);
    if (!$timestamp) {
        continue;
    }

    $lineItems = $order['line_items'] ?? [];
    $itemCount = 0;
    if (is_array($lineItems)) {
        foreach ($lineItems as $item) {
            if (!is_array($item)) {
                continue;
            }
            $itemCount += (int) ($item['quantity'] ?? $item['qty'] ?? 0);
        }
    }

    $billing = $order['billing'] ?? [];
    $customerName = trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? ''));
    if ($customerName === '') {
        $customerName = $order['customer_name'] ?? 'Guest';
    }

    $currency = $order['currency'] ?? $order['currency_code'] ?? $defaultCurrency;

    $orders[] = [
        'number' => $order['number'] ?? $order['id'] ?? '—',
        'date' => $timestamp,
        'total' => (float) ($order['total'] ?? $order['order_total'] ?? 0),
        'currency' => $currency,
        'customer' => $customerName,
        'items' => $itemCount,
    ];
}

usort($orders, function ($a, $b) {
    return $b['date'] <=> $a['date'];
});

$now = time();
$stats = [
    '7' => tb_collect_stats($orders, strtotime('-7 days', $now)),
    '30' => tb_collect_stats($orders, strtotime('-30 days', $now)),
    '90' => tb_collect_stats($orders, strtotime('-90 days', $now)),
    '365' => tb_collect_stats($orders, strtotime('-365 days', $now)),
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
            <div class="tb-stat-meta"><?php echo tb_format_currency($stats['7']['revenue'], $defaultCurrency); ?> revenue</div>
        </div>
        <div class="tb-stat-card">
            <h3>Last 30 Days</h3>
            <div class="tb-stat-value"><?php echo number_format($stats['30']['count']); ?> orders</div>
            <div class="tb-stat-meta"><?php echo tb_format_currency($stats['30']['revenue'], $defaultCurrency); ?> revenue</div>
        </div>
        <div class="tb-stat-card">
            <h3>Last 90 Days</h3>
            <div class="tb-stat-value"><?php echo number_format($stats['90']['count']); ?> orders</div>
            <div class="tb-stat-meta"><?php echo tb_format_currency($stats['90']['revenue'], $defaultCurrency); ?> revenue</div>
        </div>
        <div class="tb-stat-card">
            <h3>Last 12 Months</h3>
            <div class="tb-stat-value"><?php echo number_format($stats['365']['count']); ?> orders</div>
            <div class="tb-stat-meta"><?php echo tb_format_currency($stats['365']['revenue'], $defaultCurrency); ?> revenue</div>
        </div>
        <div class="tb-stat-card tb-stat-card--highlight">
            <h3>All-Time Orders</h3>
            <div class="tb-stat-value"><?php echo number_format($stats['all']['count']); ?> total</div>
            <div class="tb-stat-meta"><?php echo tb_format_currency($stats['all']['revenue'], $defaultCurrency); ?> total revenue</div>
        </div>
    </div>

    <div class="tb-shop-summary">
        <div class="tb-stat-card">
            <h3>All-Time Average Order</h3>
            <div class="tb-stat-value"><?php echo tb_format_currency($stats['all']['average'], $defaultCurrency); ?></div>
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
                    <div class="tb-order-row">
                        <div>
                            <div class="tb-order-title">Order #<?php echo htmlspecialchars($order['number']); ?></div>
                            <div class="tb-order-sub"><?php echo htmlspecialchars($order['customer']); ?></div>
                        </div>
                        <div class="tb-order-meta">
                            <div class="tb-order-amount"><?php echo tb_format_currency($order['total'], $order['currency'] ?? $defaultCurrency); ?></div>
                            <div class="tb-order-date"><?php echo date('M j, Y', $order['date']); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
