<?php
require_once '../includes/config.php';
if (!isLoggedIn()) redirect('../login.php');

$pageTitle = 'Reports';
$basePath = '../';
$uid = $_SESSION['user_id'];

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Summary
$income = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM transactions WHERE user_id=$uid AND type='income' AND MONTH(date)=$month AND YEAR(date)=$year")->fetch_assoc()['t'];
$expense = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM transactions WHERE user_id=$uid AND type='expense' AND MONTH(date)=$month AND YEAR(date)=$year")->fetch_assoc()['t'];
$savings_rate = $income > 0 ? (($income - $expense) / $income) * 100 : 0;

// Expense by category
$exp_cats = $conn->query("SELECT category, SUM(amount) as total FROM transactions WHERE user_id=$uid AND type='expense' AND MONTH(date)=$month AND YEAR(date)=$year GROUP BY category ORDER BY total DESC");
$cat_labels = []; $cat_data = []; $cat_colors = ['#ff4d6d','#ffd166','#4cc9f0','#c77dff','#00e676','#f77f00','#06d6a0','#ef476f','#118ab2','#073b4c'];
$ci = 0;
$cat_bg = [];
while ($cat = $exp_cats->fetch_assoc()) {
    $cat_labels[] = $cat['category'];
    $cat_data[] = (float)$cat['total'];
    $cat_bg[] = $cat_colors[$ci % count($cat_colors)];
    $ci++;
}
// Reset for table
$exp_cats = $conn->query("SELECT category, SUM(amount) as total FROM transactions WHERE user_id=$uid AND type='expense' AND MONTH(date)=$month AND YEAR(date)=$year GROUP BY category ORDER BY total DESC");

// Income by category
$inc_cats = $conn->query("SELECT category, SUM(amount) as total FROM transactions WHERE user_id=$uid AND type='income' AND MONTH(date)=$month AND YEAR(date)=$year GROUP BY category ORDER BY total DESC");

// Daily spending this month
$daily = $conn->query("SELECT DAY(date) as day, SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) as exp, SUM(CASE WHEN type='income' THEN amount ELSE 0 END) as inc FROM transactions WHERE user_id=$uid AND MONTH(date)=$month AND YEAR(date)=$year GROUP BY day ORDER BY day");
$daily_labels = []; $daily_exp = []; $daily_inc = [];
while ($d = $daily->fetch_assoc()) {
    $daily_labels[] = $d['day'];
    $daily_exp[] = (float)$d['exp'];
    $daily_inc[] = (float)$d['inc'];
}

require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>Monthly Report</h1>
    <p>Detailed financial analysis for <?php echo getMonthName($month).' '.$year; ?></p>
</div>

<!-- Month Filter -->
<div class="ff-card mb-4">
    <form method="GET" class="d-flex gap-3 flex-wrap align-items-end">
        <div>
            <label class="ff-label">Month</label>
            <select name="month" class="ff-select">
                <?php for ($m=1; $m<=12; $m++): ?>
                <option value="<?php echo $m; ?>" <?php echo $month==$m?'selected':''; ?>><?php echo getMonthName($m); ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div>
            <label class="ff-label">Year</label>
            <select name="year" class="ff-select">
                <?php for ($y=date('Y'); $y>=date('Y')-3; $y--): ?>
                <option value="<?php echo $y; ?>" <?php echo $year==$y?'selected':''; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <button type="submit" class="ff-btn ff-btn-primary"><i class="bi bi-search"></i> Generate</button>
    </form>
</div>

<!-- KPIs -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card income">
            <div class="stat-label">Total Income</div>
            <div class="stat-value text-accent"><?php echo formatCurrency($income); ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card expense">
            <div class="stat-label">Total Expense</div>
            <div class="stat-value text-danger-ff"><?php echo formatCurrency($expense); ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card balance">
            <div class="stat-label">Net Balance</div>
            <div class="stat-value" style="color:<?php echo ($income-$expense)>=0?'var(--accent)':'var(--danger)';?>"><?php echo formatCurrency($income - $expense); ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card savings">
            <div class="stat-label">Savings Rate</div>
            <div class="stat-value text-info-ff"><?php echo max(0, round($savings_rate)); ?>%</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Expense Pie Chart -->
    <div class="col-md-5">
        <div class="ff-card h-100">
            <div class="ff-card-title">Expense Breakdown</div>
            <?php if (!empty($cat_data)): ?>
            <div class="chart-container" style="height:220px">
                <canvas id="pieChart"></canvas>
            </div>
            <?php else: ?>
            <div class="empty-state" style="padding:32px"><i class="bi bi-pie-chart" style="font-size:32px"></i><p>No expenses</p></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Daily trend -->
    <div class="col-md-7">
        <div class="ff-card h-100">
            <div class="ff-card-title">Daily Transactions</div>
            <?php if (!empty($daily_labels)): ?>
            <div class="chart-container" style="height:220px">
                <canvas id="lineChart"></canvas>
            </div>
            <?php else: ?>
            <div class="empty-state" style="padding:32px"><i class="bi bi-graph-up" style="font-size:32px"></i><p>No data</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Category Tables -->
<div class="row g-3">
    <div class="col-md-6">
        <div class="ff-card">
            <div class="ff-card-title">Expenses by Category</div>
            <?php if ($exp_cats && $exp_cats->num_rows > 0):
                $ei = 0;
                while ($cat = $exp_cats->fetch_assoc()):
                    $pct = $expense > 0 ? ($cat['total'] / $expense) * 100 : 0;
            ?>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:10px;height:10px;border-radius:50%;background:<?php echo $cat_colors[$ei % count($cat_colors)]; ?>"></div>
                    <span style="font-size:13px"><?php echo htmlspecialchars($cat['category']); ?></span>
                </div>
                <div style="text-align:right">
                    <div style="font-weight:700;font-size:13px;color:var(--danger)"><?php echo formatCurrency($cat['total']); ?></div>
                    <div style="font-size:11px;color:var(--text-muted)"><?php echo round($pct); ?>%</div>
                </div>
            </div>
            <?php $ei++; endwhile; else: ?>
            <div class="empty-state" style="padding:24px"><p>No expenses this month</p></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-md-6">
        <div class="ff-card">
            <div class="ff-card-title">Income by Category</div>
            <?php if ($inc_cats && $inc_cats->num_rows > 0):
                while ($cat = $inc_cats->fetch_assoc()):
                    $pct = $income > 0 ? ($cat['total'] / $income) * 100 : 0;
            ?>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span style="font-size:13px"><?php echo htmlspecialchars($cat['category']); ?></span>
                <div style="text-align:right">
                    <div style="font-weight:700;font-size:13px;color:var(--accent)"><?php echo formatCurrency($cat['total']); ?></div>
                    <div style="font-size:11px;color:var(--text-muted)"><?php echo round($pct); ?>%</div>
                </div>
            </div>
            <?php endwhile; else: ?>
            <div class="empty-state" style="padding:24px"><p>No income this month</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$extraScripts = '<script>
// Pie Chart
' . (!empty($cat_data) ? '
const pieCtx = document.getElementById("pieChart").getContext("2d");
new Chart(pieCtx, {
    type: "doughnut",
    data: {
        labels: ' . json_encode($cat_labels) . ',
        datasets: [{
            data: ' . json_encode($cat_data) . ',
            backgroundColor: ' . json_encode($cat_bg) . ',
            borderWidth: 2,
            borderColor: "#111118"
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: "right", labels: { color: "#8888a8", boxWidth: 12, font: { size: 11 } } }
        },
        cutout: "65%"
    }
});
' : '') . '
// Line Chart
' . (!empty($daily_labels) ? '
const lineCtx = document.getElementById("lineChart").getContext("2d");
new Chart(lineCtx, {
    type: "line",
    data: {
        labels: ' . json_encode($daily_labels) . ',
        datasets: [
            {
                label: "Income",
                data: ' . json_encode($daily_inc) . ',
                borderColor: "var(--accent)",
                backgroundColor: "rgba(0,230,118,0.08)",
                fill: true,
                tension: 0.4,
                pointRadius: 3
            },
            {
                label: "Expense",
                data: ' . json_encode($daily_exp) . ',
                borderColor: "var(--danger)",
                backgroundColor: "rgba(255,77,109,0.08)",
                fill: true,
                tension: 0.4,
                pointRadius: 3
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { labels: { color: "#8888a8", font: { size: 11 } } } },
        scales: {
            x: { grid: { color: "rgba(255,255,255,0.04)" }, ticks: { color: "#8888a8", font: { size: 10 } } },
            y: { grid: { color: "rgba(255,255,255,0.04)" }, ticks: { color: "#8888a8", callback: v => "৳" + v } }
        }
    }
});
' : '') . '
</script>';

require_once '../includes/footer.php';
?>
