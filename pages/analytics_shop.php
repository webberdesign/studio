<?php
/*  PAGE NAME: pages/analytics_shop.php
    SECTION: Titty Bingo Shop Analytics (WooCommerce)
------------------------------------------------------------*/
require_once __DIR__ . '/../config.php';
?>
<section class="tb-section">
    <a href="index.php?page=analytics" class="tb-btn-secondary tb-back-link" data-loading-message="Loading Latest Analytics"><i class="fas fa-arrow-left"></i> Back to Analytics</a>
    <h1 class="tb-title">Titty Bingo Shop</h1>
    <p class="tb-subtitle">WooCommerce order performance and recent sales activity.</p>
    <div id="shop-loading" class="tb-loading">Loading Latest Analytics</div>
    <div id="shop-error" class="tb-alert" style="display:none;"></div>

    <div class="tb-stats-grid" id="shop-stats" style="display:none;">
        <div class="tb-stat-card">
            <h3>Last 7 Days</h3>
            <div class="tb-stat-value" id="s7">—</div>
        </div>
        <div class="tb-stat-card">
            <h3>Last 30 Days</h3>
            <div class="tb-stat-value" id="s30">—</div>
        </div>
        <div class="tb-stat-card">
            <h3>Last 90 Days</h3>
            <div class="tb-stat-value" id="s90">—</div>
        </div>
        <div class="tb-stat-card">
            <h3>Last 12 Months</h3>
            <div class="tb-stat-value" id="s365">—</div>
        </div>
        <div class="tb-stat-card tb-stat-card--highlight">
            <h3>All-Time Orders</h3>
            <div class="tb-stat-value" id="sall">—</div>
        </div>
    </div>

    <div class="tb-shop-summary" id="shop-summary" style="display:none;">
        <div class="tb-stat-card">
            <h3>All-Time Average Order</h3>
            <div class="tb-stat-value" id="savg">—</div>
        </div>
        <div class="tb-stat-card">
            <h3>Total Items Sold</h3>
            <div class="tb-stat-value" id="sitems">—</div>
        </div>
    </div>

    <div class="tb-order-section" id="shop-orders" style="display:none;">
        <h2 class="tb-section-title">Recent Orders</h2>
        <div class="tb-order-list" id="recent-orders"></div>
    </div>
</section>
<script>
const ENDPOINT = '/wc-analytics-orders.php';

function money(value, currency = 'USD') {
    const map = {USD: '$', EUR: '€', GBP: '£', AUD: 'A$', CAD: 'C$'};
    return `${map[currency] || ''}${value.toFixed(2)}`;
}

function stats(orders, days = null) {
    const since = days ? (Date.now() / 1000 - days * 86400) : 0;
    const filtered = orders.filter(order => order.date >= since);
    const revenue = filtered.reduce((total, order) => total + order.total, 0);
    const items = filtered.reduce((total, order) => total + order.items, 0);
    return {
        count: filtered.length,
        revenue,
        items,
        avg: filtered.length ? revenue / filtered.length : 0
    };
}

const loadingEl = document.getElementById('shop-loading');
const errorEl = document.getElementById('shop-error');
const statsEl = document.getElementById('shop-stats');
const summaryEl = document.getElementById('shop-summary');
const ordersEl = document.getElementById('shop-orders');

fetch(ENDPOINT)
    .then(response => response.json())
    .then(data => {
        const orders = data.orders || [];
        if (!orders.length) {
            loadingEl.textContent = 'No orders returned yet.';
            return;
        }

        const currency = orders[0].currency || 'USD';
        const s7 = stats(orders, 7);
        const s30 = stats(orders, 30);
        const s90 = stats(orders, 90);
        const s365 = stats(orders, 365);
        const all = stats(orders);

        document.getElementById('s7').innerHTML = `${s7.count} orders<br>${money(s7.revenue, currency)}`;
        document.getElementById('s30').innerHTML = `${s30.count} orders<br>${money(s30.revenue, currency)}`;
        document.getElementById('s90').innerHTML = `${s90.count} orders<br>${money(s90.revenue, currency)}`;
        document.getElementById('s365').innerHTML = `${s365.count} orders<br>${money(s365.revenue, currency)}`;
        document.getElementById('sall').innerHTML = `${all.count} orders<br>${money(all.revenue, currency)}`;
        document.getElementById('savg').textContent = money(all.avg, currency);
        document.getElementById('sitems').textContent = all.items.toLocaleString();

        const recent = orders.slice(0, 10);
        const wrap = document.getElementById('recent-orders');
        wrap.innerHTML = recent.map(order => `
            <div class="tb-order-row">
                <div>
                    <div class="tb-order-title">Order #${order.number}</div>
                    <div class="tb-order-sub">${order.customer}</div>
                </div>
                <div class="tb-order-meta">
                    <div class="tb-order-amount">${money(order.total, order.currency || currency)}</div>
                    <div class="tb-order-date">${new Date(order.date * 1000).toLocaleDateString()}</div>
                </div>
            </div>
        `).join('');

        loadingEl.style.display = 'none';
        statsEl.style.display = 'grid';
        summaryEl.style.display = 'grid';
        ordersEl.style.display = 'block';
    })
    .catch(() => {
        loadingEl.style.display = 'none';
        errorEl.style.display = 'block';
        errorEl.textContent = 'Unable to reach WooCommerce order feed.';
    });
</script>
