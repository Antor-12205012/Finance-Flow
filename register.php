<?php
require_once 'includes/config.php';
if (isLoggedIn()) redirect('index.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    
    if (strlen($name) < 2) {
        $error = 'Name must be at least 2 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check email exists
        $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
        if ($check && $check->num_rows > 0) {
            $error = 'Email already registered.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $conn->query("INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$hashed')");
            if ($conn->affected_rows > 0) {
                $success = 'Account created! You can now sign in.';
            } else {
                $error = 'Something went wrong. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — FinanceFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="brand-icon"><i class="bi bi-lightning-charge-fill"></i></div>
            <span class="brand-text">FinanceFlow</span>
        </div>
        <h2 class="auth-heading">Get Started</h2>
        <p class="auth-sub">Create your free account today</p>

        <?php if ($error): ?>
        <div class="ff-alert danger"><i class="bi bi-exclamation-circle-fill"></i><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="ff-alert success"><i class="bi bi-check-circle-fill"></i><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="ff-label">Full Name</label>
                <input type="text" name="name" class="ff-input" placeholder="Enter your name" required>
            </div>
            <div class="mb-3">
                <label class="ff-label">Email Address</label>
                <input type="email" name="email" class="ff-input" placeholder="you@gmail.com" required>
            </div>
            <div class="mb-3">
                <label class="ff-label">Password</label>
                <input type="password" name="password" class="ff-input" placeholder="Min. 6 characters" required>
            </div>
            <div class="mb-4">
                <label class="ff-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="ff-input" placeholder="Repeat password" required>
            </div>
            <button type="submit" class="ff-btn ff-btn-primary w-100 justify-content-center">
                <i class="bi bi-person-plus-fill"></i> Create Account
            </button>
        </form>

        <div class="auth-link">
            Already have an account? <a href="login.php">Sign in</a>
        </div>
    </div>
</div>
</body>
</html>
