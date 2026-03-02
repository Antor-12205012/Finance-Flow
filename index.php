<?php
require_once 'includes/config.php';
if (!isLoggedIn()) redirect('login.php');

$pageTitle = 'Dashboard';
$basePath = '';
$uid = $_SESSION['user_id'];
$month = date('m');
$year = date('Y');

// Summary stats
$income = $conn->query("SELECT COALESCE(SUM(amount),0) as total FROM transactions WHERE user_id=$uid AND type='income' AND MONTH(date)=$month AND YEAR(date)=$year")->fetch_assoc()['total'];
$expense = $conn->query("SELECT COALESCE(SUM(amount),0) as total FROM transactions WHERE user_id=$uid AND type='expense' AND MONTH(date)=$month AND YEAR(date)=$year")->fetch_assoc()['total'];
$balance = $income - $expense;
$savings_total = $conn->query("SELECT COALESCE(SUM(current_amount),0) as total FROM savings_goals WHERE user_id=$uid AND status='active'")->fetch_assoc()['total'];

// Recent transactions
$recent = $conn->query("SELECT * FROM transactions WHERE user_id=$uid ORDER BY date DESC, created_at DESC LIMIT 8");

// Monthly income/expense for chart (last 6 months)
$chart_labels = [];
$chart_income = [];
$chart_expense = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('m', strtotime("-$i months"));
    $y = date('Y', strtotime("-$i months"));
    $label = date('M', strtotime("-$i months"));
    $chart_labels[] = $label;
    $inc = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM transactions WHERE user_id=$uid AND type='income' AND MONTH(date)=$m AND YEAR(date)=$y")->fetch_assoc()['t'];
    $exp = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM transactions WHERE user_id=$uid AND type='expense' AND MONTH(date)=$m AND YEAR(date)=$y")->fetch_assoc()['t'];
    $chart_income[] = (float)$inc;
    $chart_expense[] = (float)$exp;
}

// Top expense categories
$top_cats = $conn->query("SELECT category, SUM(amount) as total FROM transactions WHERE user_id=$uid AND type='expense' AND MONTH(date)=$month AND YEAR(date)=$year GROUP BY category ORDER BY total DESC LIMIT 5");

require_once 'includes/header.php';
?>

<div class="page-header">
    <h1>Dashboard</h1>
    <p>Here's your financial overview for <?php echo date('F Y'); ?></p>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card income">
            <div class="stat-icon income"><i class="bi bi-arrow-down-circle-fill"></i></div>
            <div class="stat-label">Income</div>
            <div class="stat-value text-accent"><?php echo formatCurrency($income); ?></div>
            <div class="stat-sub">This month</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card expense">
            <div class="stat-icon expense"><i class="bi bi-arrow-up-circle-fill"></i></div>
            <div class="stat-label">Expenses</div>
            <div class="stat-value text-danger-ff"><?php echo formatCurrency($expense); ?></div>
            <div class="stat-sub">This month</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card balance">
            <div class="stat-icon balance"><i class="bi bi-wallet2"></i></div>
            <div class="stat-label">Net Balance</div>
            <div class="stat-value" style="color: <?php echo $balance >= 0 ? 'var(--accent)' : 'var(--danger)'; ?>">
                <?php echo formatCurrency($balance); ?>
            </div>
            <div class="stat-sub"><?php echo $balance >= 0 ? 'Looking good!' : 'Overspent'; ?></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card savings">
            <div class="stat-icon savings"><i class="bi bi-piggy-bank-fill"></i></div>
            <div class="stat-label">Savings</div>
            <div class="stat-value text-info-ff"><?php echo formatCurrency($savings_total); ?></div>
            <div class="stat-sub">Active goals</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Chart -->
    <div class="col-12 col-lg-8">
        <div class="ff-card h-100">
            <div class="ff-card-title">6-Month Overview</div>
            <div class="chart-container">
                <canvas id="overviewChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Top categories -->
    <div class="col-12 col-lg-4">
        <div class="ff-card h-100">
            <div class="ff-card-title">Top Expenses</div>
            <?php if ($top_cats && $top_cats->num_rows > 0):
                $total_exp = max($expense, 1);
                while ($cat = $top_cats->fetch_assoc()):
                    $pct = min(100, ($cat['total'] / $total_exp) * 100);
            ?>
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span style="font-size:13px; font-weight:500"><?php echo htmlspecialchars($cat['category']); ?></span>
                    <span style="font-size:12px; color:var(--text-muted)"><?php echo formatCurrency($cat['total']); ?></span>
                </div>
                <div class="ff-progress">
                    <div class="ff-progress-bar danger" data-width="<?php echo round($pct); ?>" style="width:0"></div>
                </div>
            </div>
            <?php endwhile; else: ?>
            <div class="empty-state" style="padding:24px">
                <i class="bi bi-bar-chart" style="font-size:32px"></i>
                <p>No expenses this month</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="col-12">
        <div class="ff-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="ff-card-title mb-0">Recent Transactions</div>
                <a href="pages/transactions.php" class="ff-btn ff-btn-ghost ff-btn-sm">View All <i class="bi bi-arrow-right"></i></a>
            </div>

            <?php if ($recent && $recent->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="ff-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Type</th>
                            <th style="text-align:right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($tx = $recent->fetch_assoc()): ?>
                        <tr>
                            <td style="color:var(--text-muted);font-size:12px"><?php echo date('d M Y', strtotime($tx['date'])); ?></td>
                            <td><span class="cat-pill"><?php echo htmlspecialchars($tx['category']); ?></span></td>
                            <td><?php echo htmlspecialchars($tx['description'] ?: '-'); ?></td>
                            <td><span class="ff-badge <?php echo $tx['type']; ?>"><?php echo ucfirst($tx['type']); ?></span></td>
                            <td style="text-align:right;font-weight:600;color:<?php echo $tx['type'] == 'income' ? 'var(--accent)' : 'var(--danger)'; ?>">
                                <?php echo ($tx['type'] == 'income' ? '+' : '-') . formatCurrency($tx['amount']); ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h5>No Transactions Yet</h5>
                <p>Start by adding your first income or expense.</p>
                <a href="pages/transactions.php" class="ff-btn ff-btn-primary mt-2">Add Transaction</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$extraScripts = '<script>
const ctx = document.getElementById("overviewChart").getContext("2d");
new Chart(ctx, {
    type: "bar",
    data: {
        labels: ' . json_encode($chart_labels) . ',
        datasets: [
            {
                label: "Income",
                data: ' . json_encode($chart_income) . ',
                backgroundColor: "rgba(0,230,118,0.7)",
                borderRadius: 6
            },
            {
                label: "Expense",
                data: ' . json_encode($chart_expense) . ',
                backgroundColor: "rgba(255,77,109,0.7)",
                borderRadius: 6
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: { color: "#8888a8", font: { size: 12 } }
            }
        },
        scales: {
            x: { grid: { color: "rgba(255,255,255,0.04)" }, ticks: { color: "#8888a8" } },
            y: { grid: { color: "rgba(255,255,255,0.04)" }, ticks: { color: "#8888a8", callback: v => "৳" + v.toLocaleString() } }
        }
    }
});
</script>';

require_once 'includes/footer.php';
?>
