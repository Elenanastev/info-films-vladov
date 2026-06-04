<?php
// register.php
require_once 'includes/config.php';
if (isLoggedIn()) redirect(BASE_URL);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm']  ?? '');

    if (empty($username) || strlen($username) < 3)
        $errors[] = 'Потребителското ти име трябва да е поне 3 символа.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = 'Невалиден имейл адрес.';
    if (strlen($password) < 6)
        $errors[] = 'Паролата трябва да е поне 6 символа.';
    if ($password !== $confirm)
        $errors[] = 'Паролите не съвпадат.';

    if (empty($errors)) {
        $pdo = getDB();
        $chk = $pdo->prepare('SELECT id FROM users WHERE username=? OR email=?');
        $chk->execute([$username, $email]);
        if ($chk->fetch()) {
            $errors[] = 'Потребителското им или имейлът вече съществуват.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare('INSERT INTO users (username, email, password) VALUES (?,?,?)')
                ->execute([$username, $email, $hash]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            redirect(BASE_URL . 'index.php');
        }
    }
}

$pageTitle = 'Регистрация';
require_once 'includes/header.php';
?>
<div class="auth-page">
    <div class="auth-box">
        <h2>📝 Регистрация</h2>
        <p class="auth-sub">Вече имаш акаунт? <a href="login.php" class="auth-link">Влез</a></p>
        <?php foreach ($errors as $err): ?>
            <div class="alert alert-error"><?= e($err) ?></div>
        <?php endforeach; ?>
        <form method="POST">
            <div class="form-group">
                <label>Потребителско Име</label>
                <input type="text" name="username" required value="<?= e($_POST['username'] ?? '') ?>" placeholder="ИванБГ">
            </div>
            <div class="form-group">
                <label>Имейл</label>
                <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>" placeholder="тво@имейл.bg">
            </div>
            <div class="form-group">
                <label>Парола</label>
                <input type="password" name="password" required placeholder="Минимум 6 символа">
            </div>
            <div class="form-group">
                <label>Потвърди Паролата</label>
                <input type="password" name="confirm" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:8px;">
                Създай Акаунт →
            </button>
        </form>
        <div class="guest-box">
            <p>Само разглеждаш? Влез без регистрация.</p>
            <a href="login.php?guest=1" class="btn btn-ghost" style="width:100%;justify-content:center;">
                👁️ Продължи като Гост
            </a>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
