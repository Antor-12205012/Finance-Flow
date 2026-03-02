<?php
require_once '../includes/config.php';
if (!isLoggedIn()) redirect('../login.php');

$pageTitle = 'Transactions';
$basePath = '../';
$uid = $_SESSION['user_id'];

$msg = '';
$msgType = '';

// Handle Add Transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $type = sanitize($_POST['type']);
        $cat = sanitize($_POST['category']);
        $amount = (float)$_POST['amount'];
        $desc = sanitize($_POST['description']);
        $date = sanitize($_POST['date']);

        if ($amount > 0 && in_array($type, ['income','expense'])) {
            $conn->query("INSERT INTO transactions (user_id,type,category,amount,description,date) VALUES ($uid,'$type','$cat',$amount,'$desc','$date')");
            // Update streaks
            $today = date('Y-m-d');
            $streak_check = $conn->query("SELECT * FROM streaks WHERE user_id=$uid AND streak_type='daily_entry'");
            if ($streak_check->num_rows > 0) {
                $s = $streak_check->fetch_assoc();
                $last = $s['last_updated'];
                $diff = (strtotime($today) - strtotime($last)) / 86400;
                if ($diff == 1) {
                    $new_streak = $s['current_streak'] + 1;
                    $longest = max($s['longest_streak'], $new_streak);
                    $conn->query("UPDATE streaks SET current_streak=$new_streak, longest_streak=$longest, last_updated='$today' WHERE id={$s['id']}");
                } elseif ($diff > 1) {
                    $conn->query("UPDATE streaks SET current_streak=1, last_updated='$today' WHERE id={$s['id']}");
                }
            } else {
                $conn->query("INSERT INTO streaks (user_id,streak_type,current_streak,longest_streak,last_updated) VALUES ($uid,'daily_entry',1,1,'$today')");
            }
            $msg = 'Transaction added successfully!';
            $msgType = 'success';
        } else {
            $msg = 'Invalid input. Please check your values.';
            $msgType = 'danger';
        }
    } elseif ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM transactions WHERE id=$id AND user_id=$uid");
        $msg = 'Transaction deleted.';
        $msgType = 'success';
    }
}

// Filters
$filter_type = isset($_GET['type']) ? sanitize($_GET['type']) : '';
$filter_month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$filter_year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

$where = "user_id=$uid";
if ($filter_type) $where .= " AND type='$filter_type'";
if ($filter_month) $where .= " AND MONTH(date)=$filter_month";
if ($filter_year) $where .= " AND YEAR(date)=$filter_year";

$transactions = $conn->query("SELECT * FROM transactions WHERE $where ORDER BY date DESC, created_at DESC");

$income_total = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM transactions WHERE user_id=$uid AND type='income' AND MONTH(date)=$filter_month AND YEAR(date)=$filter_year")->fetch_assoc()['t'];
$expense_total = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM transactions WHERE user_id=$uid AND type='expense' AND MONTH(date)=$filter_month AND YEAR(date)=$filter_year")->fetch_assoc()['t'];

$categories = ['Food & Dining','Transport','Shopping','Bills & Utilities','Health','Entertainment','Education','Rent','Salary','Freelance','Business','Investment','Gift','Other'];

require_once '../includes/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
    <div>
        <h1>Transactions</h1>
        <p>Track all your income and expenses</p>
    </div>
    <button class="ff-btn ff-btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-lg"></i> Add Transaction
    </button>
</div>

<?php if ($msg): ?>
<div class="ff-alert <?php echo $msgType; ?>" data-auto-dismiss>
    <i class="bi bi-<?php echo $msgType === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill'; ?>"></i>
    <?php echo $msg; ?>
</div>
<?php endif; ?>

<!-- Summary -->
<div class="row g-3 mb-4">
    <div class="col-4">
        <div class="stat-card income">
            <div class="stat-label">Total Income</div>
            <div class="stat-value text-accent" style="font-size:20px"><?php echo formatCurrency($income_total); ?></div>
        </div>
    </div>
    <div class="col-4">
        <div class="stat-card expense">
            <div class="stat-label">Total Expense</div>
            <div class="stat-value text-danger-ff" style="font-size:20px"><?php echo formatCurrency($expense_total); ?></div>
        </div>
    </div>
    <div class="col-4">
        <div class="stat-card balance">
            <div class="stat-label">Net</div>
            <div class="stat-value" style="font-size:20px; color:<?php echo ($income_total-$expense_total)>=0?'var(--accent)':'var(--danger)';?>">
                <?php echo formatCurrency($income_total - $expense_total); ?>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="ff-card mb-4">
    <form method="GET" class="row g-3">
        <div class="col-md-3 col-6">
            <label class="ff-label">Type</label>
            <select name="type" class="ff-select">
                <option value="">All Types</option>
                <option value="income" <?php echo $filter_type=='income'?'selected':''; ?>>Income</option>
                <option value="expense" <?php echo $filter_type=='expense'?'selected':''; ?>>Expense</option>
            </select>
        </div>
        <div class="col-md-3 col-6">
            <label class="ff-label">Month</label>
            <select name="month" class="ff-select">
                <?php for ($m=1; $m<=12; $m++): ?>
                <option value="<?php echo $m; ?>" <?php echo $filter_month==$m?'selected':''; ?>><?php echo getMonthName($m); ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-3 col-6">
            <label class="ff-label">Year</label>
            <select name="year" class="ff-select">
                <?php for ($y=date('Y'); $y>=date('Y')-3; $y--): ?>
                <option value="<?php echo $y; ?>" <?php echo $filter_year==$y?'selected':''; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-3 col-6 d-flex align-items-end">
            <button type="submit" class="ff-btn ff-btn-primary w-100 justify-content-center">
                <i class="bi bi-funnel-fill"></i> Filter
            </button>
        </div>
    </form>
</div>

<!-- Table -->
<div class="ff-card">
    <?php if ($transactions && $transactions->num_rows > 0): ?>
    <div class="table-responsive">
        <table class="ff-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th style="text-align:right">Amount</th>
                    <th style="text-align:center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($tx = $transactions->fetch_assoc()): ?>
                <tr>
                    <td style="color:var(--text-muted);font-size:12px;white-space:nowrap"><?php echo date('d M Y', strtotime($tx['date'])); ?></td>
                    <td><span class="ff-badge <?php echo $tx['type']; ?>"><?php echo ucfirst($tx['type']); ?></span></td>
                    <td><span class="cat-pill"><?php echo htmlspecialchars($tx['category']); ?></span></td>
                    <td style="color:var(--text-secondary)"><?php echo htmlspecialchars($tx['description'] ?: '—'); ?></td>
                    <td style="text-align:right;font-weight:700;color:<?php echo $tx['type']=='income'?'var(--accent)':'var(--danger)';?>">
                        <?php echo ($tx['type']=='income'?'+':'-') . formatCurrency($tx['amount']); ?>
                    </td>
                    <td style="text-align:center">
                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete this transaction?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $tx['id']; ?>">
                            <button type="submit" class="ff-btn ff-btn-danger ff-btn-sm" title="Delete">
                                <i class="bi bi-trash3-fill"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="bi bi-receipt"></i>
        <h5>No Transactions Found</h5>
        <p>Add your first transaction or adjust the filters.</p>
    </div>
    <?php endif; ?>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle-fill text-accent me-2"></i>Add Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="ff-label">Type *</label>
                            <select name="type" class="ff-select" required>
                                <option value="income">Income</option>
                                <option value="expense">Expense</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="ff-label">Amount (৳) *</label>
                            <input type="number" name="amount" class="ff-input" placeholder="0.00" min="0.01" step="0.01" required>
                        </div>
                        <div class="col-12">
                            <label class="ff-label">Category *</label>
                            <select name="category" class="ff-select" required>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="ff-label">Description</label>
                            <input type="text" name="description" class="ff-input" placeholder="Optional note...">
                        </div>
                        <div class="col-12">
                            <label class="ff-label">Date *</label>
                            <input type="date" name="date" class="ff-input" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="ff-btn ff-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="ff-btn ff-btn-primary"><i class="bi bi-check-lg"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
