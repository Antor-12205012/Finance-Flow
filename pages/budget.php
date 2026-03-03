<?php
require_once '../includes/config.php';
if (!isLoggedIn()) redirect('../login.php');

$pageTitle = 'Budget';
$basePath = '../';
$uid = $_SESSION['user_id'];
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

$msg = ''; $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'save') {
        $cat = sanitize($_POST['category']);
        $amount = (float)$_POST['amount'];
        if ($amount > 0) {
            $conn->query("INSERT INTO budgets (user_id,category,amount,month,year) VALUES ($uid,'$cat',$amount,$month,$year)
                ON DUPLICATE KEY UPDATE amount=$amount");
            $msg = 'Budget saved!'; $msgType = 'success';
        }
    } elseif ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM budgets WHERE id=$id AND user_id=$uid");
        $msg = 'Budget deleted.'; $msgType = 'success';
    }
}

$budgets = $conn->query("SELECT b.*, COALESCE((SELECT SUM(amount) FROM transactions WHERE user_id=b.user_id AND category=b.category AND MONTH(date)=b.month AND YEAR(date)=b.year AND type='expense'),0) as spent FROM budgets b WHERE b.user_id=$uid AND b.month=$month AND b.year=$year ORDER BY b.category");

$categories = ['Food & Dining','Transport','Shopping','Bills & Utilities','Health','Entertainment','Education','Rent','Other'];

require_once '../includes/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
    <div>
        <h1>Budget Planner</h1>
        <p>Set monthly spending limits per category</p>
    </div>
    <button class="ff-btn ff-btn-primary" data-bs-toggle="modal" data-bs-target="#addBudgetModal">
        <i class="bi bi-plus-lg"></i> Set Budget
    </button>
</div>

<?php if ($msg): ?>
<div class="ff-alert <?php echo $msgType; ?>" data-auto-dismiss><i class="bi bi-check-circle-fill"></i> <?php echo $msg; ?></div>
<?php endif; ?>

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
                <?php for ($y=date('Y'); $y>=date('Y')-2; $y--): ?>
                <option value="<?php echo $y; ?>" <?php echo $year==$y?'selected':''; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <button type="submit" class="ff-btn ff-btn-primary"><i class="bi bi-funnel-fill"></i> View</button>
    </form>
</div>

<?php if ($budgets && $budgets->num_rows > 0): ?>
<div class="row g-3">
    <?php while ($b = $budgets->fetch_assoc()):
        $pct = min(100, ($b['spent'] / max($b['amount'],1)) * 100);
        $barClass = $pct >= 100 ? 'danger' : ($pct >= 80 ? 'warning' : 'success');
        $remaining = $b['amount'] - $b['spent'];
    ?>
    <div class="col-md-6 col-xl-4">
        <div class="ff-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <div class="fw-700" style="font-family:var(--font-head)"><?php echo htmlspecialchars($b['category']); ?></div>
                    <div style="font-size:12px;color:var(--text-muted)">Budget: <?php echo formatCurrency($b['amount']); ?></div>
                </div>
                <div class="d-flex gap-1">
                    <?php if ($pct >= 100): ?>
                    <span class="ff-badge danger"><i class="bi bi-exclamation-triangle-fill"></i> Over!</span>
                    <?php elseif ($pct >= 80): ?>
                    <span class="ff-badge warning"><i class="bi bi-exclamation-circle-fill"></i> Near</span>
                    <?php else: ?>
                    <span class="ff-badge success"><i class="bi bi-check-circle-fill"></i> OK</span>
                    <?php endif; ?>
                    <form method="POST" onsubmit="return confirm('Delete budget?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
                        <button type="submit" class="ff-btn ff-btn-danger ff-btn-sm" style="padding:3px 8px;font-size:11px">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </form>
                </div>
            </div>
            <div class="d-flex justify-content-between mb-2" style="font-size:13px">
                <span>Spent: <strong style="color:<?php echo $pct>=100?'var(--danger)':'var(--text-primary)';?>"><?php echo formatCurrency($b['spent']); ?></strong></span>
                <span style="color:<?php echo $remaining<0?'var(--danger)':'var(--accent)';?>"><?php echo $remaining>=0?'Left: '.formatCurrency($remaining):'Over: '.formatCurrency(abs($remaining)); ?></span>
            </div>
            <div class="ff-progress">
                <div class="ff-progress-bar <?php echo $barClass; ?>" data-width="<?php echo round($pct); ?>" style="width:0"></div>
            </div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:6px;text-align:right"><?php echo round($pct); ?>% used</div>
        </div>
    </div>
    <?php endwhile; ?>
</div>
<?php else: ?>
<div class="ff-card">
    <div class="empty-state">
        <i class="bi bi-pie-chart"></i>
        <h5>No Budgets Set</h5>
        <p>Set a monthly budget for each spending category to track your limits.</p>
    </div>
</div>
<?php endif; ?>

<!-- Add Modal -->
<div class="modal fade" id="addBudgetModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pie-chart-fill text-accent me-2"></i>Set Budget</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="save">
                    <div class="mb-3">
                        <label class="ff-label">Category *</label>
                        <select name="category" class="ff-select" required>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="ff-label">Budget Amount (৳) *</label>
                        <input type="number" name="amount" class="ff-input" placeholder="5000" min="1" step="0.01" required>
                    </div>
                    <p style="font-size:12px;color:var(--text-muted)">Setting for: <?php echo getMonthName($month).' '.$year; ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="ff-btn ff-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="ff-btn ff-btn-primary"><i class="bi bi-check-lg"></i> Save Budget</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
