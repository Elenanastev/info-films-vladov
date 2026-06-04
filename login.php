<?php
// login.php
require_once 'includes/config.php';

if (isLoggedIn()) redirect(BASE_URL);

// Гост вход
if (isset($_GET['guest'])) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'guest' LIMIT 1");
    $stmt->execute();
    $guestUser = $stmt->fetch();
    if ($guestUser) {
        $_SESSION['user_id']  = $guestUser['id'];
        $_SESSION['is_guest'] = true;
        redirect(BASE_URL . 'index.php');
    }
}

$errors   = [];
$redirect = $_GET['redirect'] ?? 'index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $errors[] = 'Моля, попълни всички полета.';
    } else {
        $pdo  = getDB();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            unset($_SESSION['is_guest']);
            redirect(BASE_URL . $redirect);
        } else {
            $errors[] = 'Грешен имейл или парола.';
        }
    }
}

$pageTitle = 'Вход';
require_once 'includes/header.php';
?>

<div class="auth-page">
    <div class="auth-box">
        <h2>🔑 Вход</h2>
        <p class="auth-sub">Нямаш акаунт? <a href="register.php" class="auth-link">Регистрирай се</a></p>

        <?php foreach ($errors as $err): ?>
            <div class="alert alert-error"><?= e($err) ?></div>
        <?php endforeach; ?>

        <form method="POST">
            <div class="form-group">
                <label>Имейл</label>
                <input type="email" name="email" placeholder="тво@имейл.bg" required
                       value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Парола</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:8px;">
                Влез →
            </button>
        </form>

        <div class="guest-box">
            <p>Искаш само да разгледаш? Влез като гост.</p>
            <a href="login.php?guest=1" class="btn btn-ghost" style="width:100%;justify-content:center;">
                👁️ Продължи като Гост
            </a>
        </div>

        <p style="text-align:center;margin-top:20px;color:var(--text-muted);font-size:.82rem;">
            Демо: <strong>admin@infofilms.bg</strong> / <strong>password</strong>
        </p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
