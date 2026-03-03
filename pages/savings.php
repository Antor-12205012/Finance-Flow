<?php
require_once '../includes/config.php';
if (!isLoggedIn()) redirect('../login.php');

$pageTitle = 'Savings Goals';
$basePath = '../';
$uid = $_SESSION['user_id'];

$msg = ''; $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    if ($action === 'add') {
        $title = sanitize($_POST['title']);
        $target = (float)$_POST['target_amount'];
        $current = (float)$_POST['current_amount'];
        $deadline = sanitize($_POST['deadline']);
        if ($target > 0) {
            $status = ($current >= $target) ? 'completed' : 'active';
            $conn->query("INSERT INTO savings_goals (user_id,title,target_amount,current_amount,deadline,status) VALUES ($uid,'$title',$target,$current,'$deadline','$status')");
            $msg = 'Savings goal added!'; $msgType = 'success';
        }
    } elseif ($action === 'add_amount') {
        $id = (int)$_POST['id'];
        $add = (float)$_POST['add_amount'];
        $goal = $conn->query("SELECT * FROM savings_goals WHERE id=$id AND user_id=$uid")->fetch_assoc();
        if ($goal) {
            $new_current = min($goal['target_amount'], $goal['current_amount'] + $add);
            $status = ($new_current >= $goal['target_amount']) ? 'completed' : 'active';
            $conn->query("UPDATE savings_goals SET current_amount=$new_current, status='$status' WHERE id=$id AND user_id=$uid");
            $msg = 'Amount updated!'; $msgType = 'success';
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM savings_goals WHERE id=$id AND user_id=$uid");
        $msg = 'Goal deleted.'; $msgType = 'success';
    }
}

$goals = $conn->query("SELECT * FROM savings_goals WHERE user_id=$uid ORDER BY status ASC, created_at DESC");

require_once '../includes/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
    <div>
        <h1>Savings Goals</h1>
        <p>Track your financial targets and milestones</p>
    </div>
    <button class="ff-btn ff-btn-primary" data-bs-toggle="modal" data-bs-target="#addGoalModal">
        <i class="bi bi-plus-lg"></i> New Goal
    </button>
</div>

<?php if ($msg): ?>
<div class="ff-alert <?php echo $msgType; ?>" data-auto-dismiss><i class="bi bi-check-circle-fill"></i> <?php echo $msg; ?></div>
<?php endif; ?>

<?php if ($goals && $goals->num_rows > 0): ?>
<div class="row g-3">
    <?php while ($g = $goals->fetch_assoc()):
        $pct = min(100, ($g['current_amount'] / max($g['target_amount'],1)) * 100);
        $remaining = $g['target_amount'] - $g['current_amount'];
    ?>
    <div class="col-md-6 col-xl-4">
        <div class="ff-card" style="<?php echo $g['status']=='completed' ? 'border-color: var(--accent-dim)' : ''; ?>">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <div class="fw-700 font-head" style="font-size:15px"><?php echo htmlspecialchars($g['title']); ?></div>
                    <?php if ($g['deadline']): ?>
                    <div style="font-size:11px;color:var(--text-muted)"><i class="bi bi-calendar3"></i> <?php echo date('d M Y', strtotime($g['deadline'])); ?></div>
                    <?php endif; ?>
                </div>
                <?php if ($g['status'] === 'completed'): ?>
                <span class="ff-badge success"><i class="bi bi-trophy-fill"></i> Done!</span>
                <?php else: ?>
                <span class="ff-badge info"><?php echo round($pct); ?>%</span>
                <?php endif; ?>
            </div>

            <div class="d-flex justify-content-between mb-2" style="font-size:13px">
                <span>Saved: <strong class="text-accent"><?php echo formatCurrency($g['current_amount']); ?></strong></span>
                <span style="color:var(--text-muted)">Target: <?php echo formatCurrency($g['target_amount']); ?></span>
            </div>

            <div class="ff-progress mb-2">
                <div class="ff-progress-bar <?php echo $pct>=100?'success':($pct>=60?'info':'warning'); ?>" 
                     data-width="<?php echo round($pct); ?>" style="width:0"></div>
            </div>

            <?php if ($g['status'] !== 'completed'): ?>
            <div style="font-size:12px;color:var(--text-muted);margin-bottom:12px">
                <?php echo formatCurrency($remaining); ?> more to go
            </div>
            <div class="d-flex gap-2">
                <button class="ff-btn ff-btn-primary ff-btn-sm flex-fill justify-content-center" 
                    onclick="openAddAmount(<?php echo $g['id']; ?>, '<?php echo htmlspecialchars($g['title']); ?>')">
                    <i class="bi bi-plus-circle"></i> Add Savings
                </button>
                <form method="POST" onsubmit="return confirm('Delete goal?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $g['id']; ?>">
                    <button type="submit" class="ff-btn ff-btn-danger ff-btn-sm"><i class="bi bi-trash3"></i></button>
                </form>
            </div>
            <?php else: ?>
            <form method="POST" onsubmit="return confirm('Delete goal?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo $g['id']; ?>">
                <button type="submit" class="ff-btn ff-btn-ghost ff-btn-sm"><i class="bi bi-trash3"></i> Remove</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endwhile; ?>
</div>
<?php else: ?>
<div class="ff-card">
    <div class="empty-state">
        <i class="bi bi-piggy-bank"></i>
        <h5>No Savings Goals</h5>
        <p>Create a goal to start tracking your savings progress.</p>
    </div>
</div>
<?php endif; ?>

<!-- Add Goal Modal -->
<div class="modal fade" id="addGoalModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-piggy-bank-fill text-accent me-2"></i>New Savings Goal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="ff-label">Goal Title *</label>
                        <input type="text" name="title" class="ff-input" placeholder="e.g. New Laptop, Emergency Fund" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="ff-label">Target Amount (৳) *</label>
                            <input type="number" name="target_amount" class="ff-input" placeholder="50000" min="1" required>
                        </div>
                        <div class="col-6">
                            <label class="ff-label">Current Amount (৳)</label>
                            <input type="number" name="current_amount" class="ff-input" placeholder="0" min="0" value="0">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="ff-label">Deadline (optional)</label>
                        <input type="date" name="deadline" class="ff-input">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="ff-btn ff-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="ff-btn ff-btn-primary"><i class="bi bi-check-lg"></i> Create Goal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Amount Modal -->
<div class="modal fade" id="addAmountModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle-fill text-accent me-2"></i>Add to Savings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_amount">
                    <input type="hidden" name="id" id="goalIdInput">
                    <p id="goalTitleDisplay" style="color:var(--text-secondary);margin-bottom:16px"></p>
                    <div>
                        <label class="ff-label">Amount to Add (৳) *</label>
                        <input type="number" name="add_amount" class="ff-input" placeholder="1000" min="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="ff-btn ff-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="ff-btn ff-btn-primary"><i class="bi bi-check-lg"></i> Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAddAmount(id, title) {
    document.getElementById('goalIdInput').value = id;
    document.getElementById('goalTitleDisplay').textContent = 'Goal: ' + title;
    new bootstrap.Modal(document.getElementById('addAmountModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
