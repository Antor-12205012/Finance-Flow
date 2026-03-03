<?php
require_once '../includes/config.php';
if (!isLoggedIn()) redirect('../login.php');

$pageTitle = 'Cost Cutting';
$basePath = '../';
$uid = $_SESSION['user_id'];
$month = (int)date('m');
$year = (int)date('Y');

$msg = ''; $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    if ($action === 'add') {
        $cat = sanitize($_POST['category']);
        $current = (float)$_POST['current_spending'];
        $target = (float)$_POST['target_spending'];
        if ($current > 0 && $target >= 0) {
            $conn->query("INSERT INTO cost_cutting (user_id,category,current_spending,target_spending,month,year) VALUES ($uid,'$cat',$current,$target,$month,$year)");
            $msg = 'Cost cutting goal set!'; $msgType = 'success';
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM cost_cutting WHERE id=$id AND user_id=$uid");
        $msg = 'Goal removed.'; $msgType = 'success';
    }
}

$goals = $conn->query("SELECT cc.*, COALESCE((SELECT SUM(amount) FROM transactions WHERE user_id=cc.user_id AND category=cc.category AND MONTH(date)=cc.month AND YEAR(date)=cc.year AND type='expense'),0) as actual_spent FROM cost_cutting cc WHERE cc.user_id=$uid ORDER BY cc.created_at DESC");

// Auto-suggest: Find categories where spending is high
$high_spend = $conn->query("SELECT category, SUM(amount) as total FROM transactions WHERE user_id=$uid AND type='expense' AND MONTH(date)=$month AND YEAR(date)=$year GROUP BY category ORDER BY total DESC LIMIT 5");

$categories = ['Food & Dining','Transport','Shopping','Bills & Utilities','Health','Entertainment','Education','Rent','Other'];

require_once '../includes/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
    <div>
        <h1>Cost Cutting</h1>
        <p>Identify and reduce your high spending areas</p>
    </div>
    <button class="ff-btn ff-btn-primary" data-bs-toggle="modal" data-bs-target="#addGoalModal">
        <i class="bi bi-plus-lg"></i> Add Goal
    </button>
</div>

<?php if ($msg): ?>
<div class="ff-alert <?php echo $msgType; ?>" data-auto-dismiss><i class="bi bi-check-circle-fill"></i> <?php echo $msg; ?></div>
<?php endif; ?>

<!-- Smart Suggestions -->
<?php if ($high_spend && $high_spend->num_rows > 0): ?>
<div class="ff-card mb-4">
    <div class="ff-card-title"><i class="bi bi-lightbulb-fill" style="color:var(--warning)"></i> Smart Suggestions</div>
    <p style="font-size:13px;color:var(--text-secondary);margin-bottom:16px">Based on your spending this month, consider cutting costs in:</p>
    <div class="d-flex flex-wrap gap-2">
        <?php while ($hs = $high_spend->fetch_assoc()): ?>
        <div style="background:var(--bg-hover);border:1px solid var(--border);padding:10px 14px;border-radius:var(--radius-sm);font-size:13px">
            <div style="font-weight:600"><?php echo htmlspecialchars($hs['category']); ?></div>
            <div style="color:var(--danger);font-size:12px"><?php echo formatCurrency($hs['total']); ?> spent</div>
        </div>
        <?php endwhile; ?>
    </div>
</div>
<?php endif; ?>

<!-- Goals -->
<?php if ($goals && $goals->num_rows > 0): ?>
<div class="row g-3">
    <?php while ($g = $goals->fetch_assoc()):
        $saved = $g['current_spending'] - $g['actual_spent'];
        $reduction_pct = $g['current_spending'] > 0 ? (($g['current_spending'] - $g['target_spending']) / $g['current_spending']) * 100 : 0;
        $on_track = $g['actual_spent'] <= $g['target_spending'];
    ?>
    <div class="col-md-6 col-xl-4">
        <div class="ff-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <div class="fw-700 font-head"><?php echo htmlspecialchars($g['category']); ?></div>
                    <div style="font-size:11px;color:var(--text-muted)"><?php echo getMonthName($g['month']).' '.$g['year']; ?></div>
                </div>
                <?php if ($on_track): ?>
                <span class="ff-badge success"><i class="bi bi-check-circle-fill"></i> On Track</span>
                <?php else: ?>
                <span class="ff-badge danger"><i class="bi bi-x-circle-fill"></i> Over Target</span>
                <?php endif; ?>
            </div>

            <div class="row text-center g-2 mb-3">
                <div class="col-4">
                    <div style="font-size:11px;color:var(--text-muted)">Was</div>
                    <div style="font-weight:700;color:var(--danger)"><?php echo formatCurrency($g['current_spending']); ?></div>
                </div>
                <div class="col-4">
                    <div style="font-size:11px;color:var(--text-muted)">Target</div>
                    <div style="font-weight:700;color:var(--warning)"><?php echo formatCurrency($g['target_spending']); ?></div>
                </div>
                <div class="col-4">
                    <div style="font-size:11px;color:var(--text-muted)">Actual</div>
                    <div style="font-weight:700;color:<?php echo $on_track?'var(--accent)':'var(--danger)';?>"><?php echo formatCurrency($g['actual_spent']); ?></div>
                </div>
            </div>

            <div style="font-size:12px;color:var(--text-secondary);margin-bottom:12px">
                Goal: Cut <strong style="color:var(--warning)"><?php echo round($reduction_pct); ?>%</strong> 
                (save <?php echo formatCurrency($g['current_spending'] - $g['target_spending']); ?>)
            </div>

            <form method="POST" onsubmit="return confirm('Remove this goal?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo $g['id']; ?>">
                <button type="submit" class="ff-btn ff-btn-ghost ff-btn-sm"><i class="bi bi-trash3"></i> Remove</button>
            </form>
        </div>
    </div>
    <?php endwhile; ?>
</div>
<?php else: ?>
<div class="ff-card">
    <div class="empty-state">
        <i class="bi bi-scissors"></i>
        <h5>No Cost Cutting Goals</h5>
        <p>Set targets to reduce your spending in specific categories.</p>
    </div>
</div>
<?php endif; ?>

<!-- Add Modal -->
<div class="modal fade" id="addGoalModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-scissors text-accent me-2"></i>Set Cost Cutting Goal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="ff-label">Category *</label>
                        <select name="category" class="ff-select" required>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="ff-label">Current Monthly Spending *</label>
                            <input type="number" name="current_spending" class="ff-input" placeholder="5000" min="1" required>
                        </div>
                        <div class="col-6">
                            <label class="ff-label">Target Spending *</label>
                            <input type="number" name="target_spending" class="ff-input" placeholder="3500" min="0" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="ff-btn ff-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="ff-btn ff-btn-primary"><i class="bi bi-check-lg"></i> Set Goal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
