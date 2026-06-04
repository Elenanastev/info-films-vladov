<?php
// profile.php
require_once 'includes/config.php';
if (!isLoggedIn() || isGuest()) redirect(BASE_URL . 'login.php');

$user = getCurrentUser();
if (!$user) redirect(BASE_URL . 'login.php');

$pdo = getDB();

$reviews = $pdo->prepare("
    SELECT r.*, m.title AS movie_title, m.id AS movie_id,
           m.poster_url, m.poster
    FROM reviews r
    JOIN movies m ON r.movie_id = m.id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$reviews->execute([$user['id']]);
$myReviews = $reviews->fetchAll();

$pageTitle = 'Профил';
require_once 'includes/header.php';
?>

<div class="profile-header">
    <div class="container">
        <div class="profile-avatar"><?= strtoupper(substr($user['username'], 0, 1)) ?></div>
        <div class="profile-name"><?= e($user['username']) ?></div>
        <span class="profile-role"><?= $user['role'] === 'admin' ? '⚙️ Администратор' : '👤 Потребител' ?></span>
        <p style="color:var(--text-muted);margin-top:8px;font-size:.9rem;"><?= e($user['email']) ?></p>
        <p style="color:var(--text-muted);font-size:.85rem;">Регистриран на <?= date('d.m.Y', strtotime($user['created_at'])) ?></p>
    </div>
</div>

<div class="container">
    <h2 class="section-title">💬 Моите Ревюта (<?= count($myReviews) ?>)</h2>

    <?php if (empty($myReviews)): ?>
        <div class="no-results">
            <div class="no-icon">📝</div>
            <h3>Все още няма ревюта</h3>
            <p>Разгледай филмите и остави мнение!</p>
            <a href="index.php" class="btn btn-primary" style="margin-top:16px;">🎬 Разгледай Филми</a>
        </div>
    <?php else: ?>
        <div class="reviews-list" style="padding-bottom:48px;">
            <?php foreach ($myReviews as $rev):
                $p = !empty($rev['poster_url']) ? $rev['poster_url']
                   : (!empty($rev['poster']) && $rev['poster'] !== 'default_poster.jpg' ? 'uploads/posters/'.$rev['poster'] : '');
            ?>
            <div class="review-card" style="display:flex;gap:16px;align-items:flex-start;">
                <?php if ($p): ?>
                    <a href="movie.php?id=<?= $rev['movie_id'] ?>">
                        <img src="<?= e($p) ?>" alt="<?= e($rev['movie_title']) ?>"
                             style="width:54px;height:80px;object-fit:cover;border-radius:6px;flex-shrink:0;">
                    </a>
                <?php endif; ?>
                <div style="flex:1;">
                    <div class="review-header">
                        <a href="movie.php?id=<?= $rev['movie_id'] ?>" style="font-weight:700;color:var(--accent);">
                            <?= e($rev['movie_title']) ?>
                        </a>
                        <div class="stars" style="margin-left:8px;">
                            <?php for ($i=1;$i<=5;$i++): ?>
                                <span class="star <?= $i<=$rev['rating'] ? 'filled' : '' ?>" style="font-size:.82rem;">★</span>
                            <?php endfor; ?>
                        </div>
                        <span class="review-date"><?= date('d.m.Y', strtotime($rev['created_at'])) ?></span>
                    </div>
                    <?php if ($rev['comment']): ?>
                        <p class="review-text"><?= nl2br(e($rev['comment'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
