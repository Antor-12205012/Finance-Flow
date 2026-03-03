<?php
require_once '../includes/config.php';
if (!isLoggedIn()) redirect('../login.php');

$pageTitle = 'Streaks';
$basePath = '../';
$uid = $_SESSION['user_id'];

// Calculate various streaks
$streaks = $conn->query("SELECT * FROM streaks WHERE user_id=$uid");
$streak_data = [];
while ($s = $streaks->fetch_assoc()) {
    $streak_data[$s['streak_type']] = $s;
}

// Budget adherence streak: months where expense < income
$months_data = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('m', strtotime("-$i months"));
    $y = date('Y', strtotime("-$i months"));
    $inc = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM transactions WHERE user_id=$uid AND type='income' AND MONTH(date)=$m AND YEAR(date)=$y")->fetch_assoc()['t'];
    $exp = $conn->query("SELECT COALESCE(SUM(amount),0) as t FROM transactions WHERE user_id=$uid AND type='expense' AND MONTH(date)=$m AND YEAR(date)=$y")->fetch_assoc()['t'];
    $months_data[] = [
        'label' => date('M', strtotime("-$i months")),
        'income' => (float)$inc,
        'expense' => (float)$exp,
        'positive' => (float)$inc > (float)$exp,
        'has_data' => ((float)$inc + (float)$exp) > 0
    ];
}

// Calculate budget positive streak
$positive_streak = 0;
$budget_streak_count = 0;
foreach (array_reverse($months_data) as $md) {
    if (!$md['has_data']) break;
    if ($md['positive']) {
        $budget_streak_count++;
    } else {
        break;
    }
}

// Daily entry streak
$daily_streak = isset($streak_data['daily_entry']) ? $streak_data['daily_entry']['current_streak'] : 0;
$longest_daily = isset($streak_data['daily_entry']) ? $streak_data['daily_entry']['longest_streak'] : 0;
$last_entry = isset($streak_data['daily_entry']) ? $streak_data['daily_entry']['last_updated'] : null;

// Savings streak: months where savings goals were contributed
$savings_streak = 0;

// Achievements
$total_transactions = $conn->query("SELECT COUNT(*) as c FROM transactions WHERE user_id=$uid")->fetch_assoc()['c'];
$total_saved = $conn->query("SELECT COALESCE(SUM(current_amount),0) as t FROM savings_goals WHERE user_id=$uid")->fetch_assoc()['t'];
$goals_completed = $conn->query("SELECT COUNT(*) as c FROM savings_goals WHERE user_id=$uid AND status='completed'")->fetch_assoc()['c'];

require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>Streaks & Achievements</h1>
    <p>Stay consistent and earn rewards for your financial discipline</p>
</div>

<!-- Streak Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="ff-card text-center">
            <div style="font-size:48px;margin-bottom:8px">🔥</div>
            <div class="streak-badge mb-2"><?php echo $daily_streak; ?> Day<?php echo $daily_streak!=1?'s':''; ?></div>
            <div style="font-weight:700;font-family:var(--font-head);font-size:15px;margin-top:10px">Daily Entry Streak</div>
            <div style="font-size:12px;color:var(--text-muted);margin-top:4px">Log transactions every day</div>
            <?php if ($last_entry): ?>
            <div style="font-size:11px;color:var(--text-muted);margin-top:8px">Last: <?php echo date('d M Y', strtotime($last_entry)); ?></div>
            <?php endif; ?>
            <div class="mt-3" style="font-size:12px;color:var(--text-secondary)">
                Best: <strong style="color:var(--warning)"><?php echo $longest_daily; ?> days</strong>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="ff-card text-center">
            <div style="font-size:48px;margin-bottom:8px">💚</div>
            <div class="streak-badge mb-2" style="background:linear-gradient(135deg,var(--accent),var(--info))"><?php echo $budget_streak_count; ?> Month<?php echo $budget_streak_count!=1?'s':''; ?></div>
            <div style="font-weight:700;font-family:var(--font-head);font-size:15px;margin-top:10px">Positive Balance Streak</div>
            <div style="font-size:12px;color:var(--text-muted);margin-top:4px">Income > Expenses every month</div>
            <div class="mt-3" style="font-size:12px;color:var(--text-secondary)">
                <?php echo $budget_streak_count > 0 ? 'Keep it up!' : 'Start this month!'; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="ff-card text-center">
            <div style="font-size:48px;margin-bottom:8px">🏆</div>
            <div class="streak-badge mb-2" style="background:linear-gradient(135deg,#c77dff,#7c3aed)"><?php echo $goals_completed; ?> Goal<?php echo $goals_completed!=1?'s':''; ?></div>
            <div style="font-weight:700;font-family:var(--font-head);font-size:15px;margin-top:10px">Savings Goals Completed</div>
            <div style="font-size:12px;color:var(--text-muted);margin-top:4px">Successfully hit your targets</div>
            <div class="mt-3" style="font-size:12px;color:var(--text-secondary)">
                Total saved: <strong style="color:var(--accent)"><?php echo formatCurrency($total_saved); ?></strong>
            </div>
        </div>
    </div>
</div>

<!-- Monthly Consistency Calendar -->
<div class="ff-card mb-4">
    <div class="ff-card-title">6-Month Budget Performance</div>
    <div class="d-flex gap-3 flex-wrap">
        <?php foreach ($months_data as $md): ?>
        <div style="text-align:center;flex:1;min-width:80px">
            <div style="font-size:24px;margin-bottom:4px">
                <?php if (!$md['has_data']): ?>
                    ⬜
                <?php elseif ($md['positive']): ?>
                    ✅
                <?php else: ?>
                    ❌
                <?php endif; ?>
            </div>
            <div style="font-size:12px;font-weight:600;color:var(--text-secondary)"><?php echo $md['label']; ?></div>
            <?php if ($md['has_data']): ?>
            <div style="font-size:10px;color:<?php echo $md['positive']?'var(--accent)':'var(--danger)';?>">
                <?php echo $md['positive'] ? '+'.formatCurrency($md['income']-$md['expense']) : formatCurrency($md['expense']-$md['income']).' over'; ?>
            </div>
            <?php else: ?>
            <div style="font-size:10px;color:var(--text-muted)">No data</div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Achievements -->
<div class="ff-card">
    <div class="ff-card-title">Achievements</div>
    <div class="row g-3">
        <?php
        $achievements = [
            ['icon'=>'📝', 'title'=>'First Transaction', 'desc'=>'Added your first transaction', 'earned'=>$total_transactions >= 1],
            ['icon'=>'💯', 'title'=>'Century Club', 'desc'=>'Logged 100+ transactions', 'earned'=>$total_transactions >= 100],
            ['icon'=>'💰', 'title'=>'Saver Starter', 'desc'=>'Created first savings goal', 'earned'=>$goals_completed >= 1],
            ['icon'=>'🎯', 'title'=>'Goal Crusher', 'desc'=>'Completed 5 savings goals', 'earned'=>$goals_completed >= 5],
            ['icon'=>'🔥', 'title'=>'Week Warrior', 'desc'=>'7-day transaction streak', 'earned'=>$daily_streak >= 7],
            ['icon'=>'⚡', 'title'=>'Streak Master', 'desc'=>'30-day transaction streak', 'earned'=>$daily_streak >= 30],
            ['icon'=>'📊', 'title'=>'Budget Conscious', 'desc'=>'3 months positive balance', 'earned'=>$budget_streak_count >= 3],
            ['icon'=>'🌟', 'title'=>'Finance Pro', 'desc'=>'6 months positive balance', 'earned'=>$budget_streak_count >= 6],
        ];
        foreach ($achievements as $ach):
        ?>
        <div class="col-6 col-md-3">
            <div class="ff-card" style="text-align:center;padding:16px;opacity:<?php echo $ach['earned']?1:0.35;?>;border-color:<?php echo $ach['earned']?'var(--accent-dim)':'var(--border)';?>">
                <div style="font-size:32px;margin-bottom:6px"><?php echo $ach['icon']; ?></div>
                <div style="font-weight:700;font-size:12px;font-family:var(--font-head)"><?php echo $ach['title']; ?></div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:2px"><?php echo $ach['desc']; ?></div>
                <?php if ($ach['earned']): ?>
                <div class="ff-badge success mt-2" style="font-size:10px;justify-content:center"><i class="bi bi-check-circle-fill"></i> Earned</div>
                <?php else: ?>
                <div style="font-size:10px;color:var(--text-muted);margin-top:6px">Locked</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
