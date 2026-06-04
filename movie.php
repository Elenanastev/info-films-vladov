<?php
// movie.php
require_once 'includes/config.php';

$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect(BASE_URL);

$stmt = $pdo->prepare("
    SELECT m.*, g.name AS genre_name,
           ROUND(AVG(r.rating), 1) AS avg_r,
           COUNT(r.id) AS review_count
    FROM movies m
    LEFT JOIN genres g ON m.genre_id = g.id
    LEFT JOIN reviews r ON m.id = r.movie_id
    WHERE m.id = ?
    GROUP BY m.id
");
$stmt->execute([$id]);
$movie = $stmt->fetch();
if (!$movie) redirect(BASE_URL . '?error=not_found');

$revStmt = $pdo->prepare("
    SELECT r.*, u.username
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.movie_id = ?
    ORDER BY r.created_at DESC
");
$revStmt->execute([$id]);
$reviews = $revStmt->fetchAll();

$userReview = null;
if (isLoggedIn() && !isGuest()) {
    $checkStmt = $pdo->prepare('SELECT * FROM reviews WHERE movie_id = ? AND user_id = ?');
    $checkStmt->execute([$id, $_SESSION['user_id']]);
    $userReview = $checkStmt->fetch();
}

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isLoggedIn() || isGuest()) {
        redirect(BASE_URL . 'login.php?redirect=movie.php?id=' . $id);
    }
    $rating  = (int)($_POST['rating']  ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    if ($rating < 1 || $rating > 5) $errors[] = 'Моля, избери оценка от 1 до 5 звезди.';
    if (empty($errors)) {
        try {
            if ($userReview) {
                $pdo->prepare('UPDATE reviews SET rating = ?, comment = ? WHERE movie_id = ? AND user_id = ?')
                    ->execute([$rating, $comment, $id, $_SESSION['user_id']]);
            } else {
                $pdo->prepare('INSERT INTO reviews (movie_id, user_id, rating, comment) VALUES (?, ?, ?, ?)')
                    ->execute([$id, $_SESSION['user_id'], $rating, $comment]);
            }
            redirect('movie.php?id=' . $id . '&success=1');
        } catch (PDOException $e) {
            $errors[] = 'Грешка при запис. Опитай отново.';
        }
    }
}

// Подобни филми
$similarStmt = $pdo->prepare("
    SELECT m.*, g.name AS genre_name,
           ROUND(AVG(r.rating), 1) AS avg_r
    FROM movies m
    LEFT JOIN genres g ON m.genre_id = g.id
    LEFT JOIN reviews r ON m.id = r.movie_id
    WHERE m.genre_id = ? AND m.id != ?
    GROUP BY m.id
    ORDER BY avg_r DESC
    LIMIT 4
");
$similarStmt->execute([$movie['genre_id'], $id]);
$similar = $similarStmt->fetchAll();

$avgRating   = $movie['avg_r']      ?? 0;
$reviewCount = $movie['review_count'] ?? 0;
$posterUrl   = getPosterUrl($movie);
$pageTitle   = $movie['title'];
require_once 'includes/header.php';
?>

<div class="detail-hero">
    <div class="container">
        <div class="detail-layout">
            <div class="detail-poster">
                <?php if ($posterUrl): ?>
                    <img src="<?= $posterUrl ?>" alt="<?= e($movie['title']) ?>">
                <?php else: ?>
                    <div class="movie-poster-placeholder" style="height:100%;min-height:360px;">
                        <span class="poster-icon">🎬</span>
                        <span>ФИЛМА</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="detail-info">
                <?php if ($movie['genre_name']): ?>
                    <span class="detail-genre-badge"><?= e($movie['genre_name']) ?></span>
                <?php endif; ?>

                <h1 class="detail-title"><?= e($movie['title']) ?></h1>

                <div class="detail-stars-row">
                    <div class="detail-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star <?= $i <= round($avgRating) ? 'filled' : '' ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <?php if ($avgRating > 0): ?>
                        <span class="detail-rating-text"><?= number_format($avgRating, 1) ?>/5</span>
                        <span class="detail-review-count">(<?= $reviewCount ?> <?= $reviewCount === 1 ? 'ревю' : 'ревюта' ?>)</span>
                    <?php else: ?>
                        <span class="detail-review-count">Без оценки все още</span>
                    <?php endif; ?>
                </div>

                <div class="detail-meta-grid">
                    <?php if ($movie['year']): ?>
                        <div class="detail-meta-item">
                            <span class="detail-meta-label">📅 Година</span>
                            <span class="detail-meta-value"><?= $movie['year'] ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($movie['director']): ?>
                        <div class="detail-meta-item">
                            <span class="detail-meta-label">🎥 Режисьор</span>
                            <span class="detail-meta-value"><?= e($movie['director']) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($movie['duration_min']): ?>
                        <div class="detail-meta-item">
                            <span class="detail-meta-label">⏱ Времетраене</span>
                            <span class="detail-meta-value"><?= $movie['duration_min'] ?> мин</span>
                        </div>
                    <?php endif; ?>
                    <?php if ($movie['genre_name']): ?>
                        <div class="detail-meta-item">
                            <span class="detail-meta-label">🎭 Жанр</span>
                            <span class="detail-meta-value"><?= e($movie['genre_name']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($movie['description']): ?>
                    <p class="detail-desc"><?= nl2br(e($movie['description'])) ?></p>
                <?php endif; ?>

                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <?php if ($movie['trailer_url']): ?>
                        <a href="#trailer" class="btn btn-primary">▶ Гледай Трейлър</a>
                    <?php endif; ?>
                    <a href="#reviews" class="btn btn-secondary">💬 Ревюта (<?= $reviewCount ?>)</a>
                    <a href="index.php" class="btn btn-ghost">← Назад</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($movie['trailer_url']): ?>
<div class="trailer-section" id="trailer">
    <div class="container">
        <h2 class="section-title">🎞 Официален Трейлър</h2>
        <div class="trailer-wrap">
            <iframe src="<?= e($movie['trailer_url']) ?>?rel=0"
                    title="Трейлър — <?= e($movie['title']) ?>"
                    allowfullscreen loading="lazy"></iframe>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="reviews-section" id="reviews">
    <div class="container">
        <h2 class="section-title">💬 Ревюта</h2>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">✅ Ревюто е записано успешно!</div>
        <?php endif; ?>

        <?php if (!empty($reviews)): ?>
            <div class="reviews-list">
                <?php foreach ($reviews as $rev): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="review-avatar"><?= strtoupper(substr($rev['username'], 0, 1)) ?></div>
                            <strong class="review-username"><?= e($rev['username']) ?></strong>
                            <div class="stars" style="margin-left:8px;">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="star <?= $i <= $rev['rating'] ? 'filled' : '' ?>" style="font-size:.85rem;">★</span>
                                <?php endfor; ?>
                            </div>
                            <span class="review-date"><?= date('d.m.Y', strtotime($rev['created_at'])) ?></span>
                        </div>
                        <?php if ($rev['comment']): ?>
                            <p class="review-text"><?= nl2br(e($rev['comment'])) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color:var(--text-muted);margin-bottom:24px;">Бъди първият, който остави ревю за този филм!</p>
        <?php endif; ?>

        <?php if (isLoggedIn() && !isGuest()): ?>
            <div class="review-form-box">
                <h3><?= $userReview ? '✏️ Редактирай Ревюто Си' : '✍️ Остави Ревю' ?></h3>
                <?php foreach ($errors as $err): ?>
                    <div class="alert alert-error"><?= e($err) ?></div>
                <?php endforeach; ?>
                <form method="POST">
                    <div class="form-group">
                        <label>Оценка</label>
                        <div class="star-picker" id="starPicker">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="sp-star <?= ($userReview && $userReview['rating'] >= $i) ? 'active' : '' ?>"
                                      data-val="<?= $i ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="rating" id="ratingInput"
                               value="<?= $userReview ? $userReview['rating'] : 0 ?>">
                    </div>
                    <div class="form-group">
                        <label for="comment">Коментар (незадължителен)</label>
                        <textarea name="comment" id="comment" rows="4"
                                  placeholder="Сподели мнението си..."><?= e($userReview['comment'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" name="submit_review" class="btn btn-primary">
                        <?= $userReview ? '💾 Запази промените' : '📨 Публикувай ревю' ?>
                    </button>
                </form>
            </div>
        <?php elseif (isGuest()): ?>
            <div class="review-form-box" style="text-align:center;">
                <div class="alert alert-info" style="justify-content:center;">
                    👁️ Гостите не могат да оставят ревюта.
                </div>
                <a href="login.php" class="btn btn-primary">🔑 Вход</a>
                <a href="register.php" class="btn btn-secondary" style="margin-left:10px;">📝 Регистрация</a>
            </div>
        <?php else: ?>
            <div class="review-form-box" style="text-align:center;">
                <p style="color:var(--text-muted);margin-bottom:16px;">Трябва да влезеш в профила си, за да оставиш ревю.</p>
                <a href="login.php?redirect=movie.php?id=<?= $id ?>" class="btn btn-primary">🔑 Вход</a>
                <a href="register.php" class="btn btn-secondary" style="margin-left:10px;">📝 Регистрация</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($similar)): ?>
<div class="container" style="padding-bottom:48px;">
    <h2 class="section-title">🎬 Подобни Филми</h2>
    <div class="movies-grid" style="grid-template-columns:repeat(auto-fill,minmax(180px,1fr));">
        <?php foreach ($similar as $sm):
            $smPoster = getPosterUrl($sm);
        ?>
        <a href="movie.php?id=<?= $sm['id'] ?>" class="movie-card">
            <div class="movie-poster">
                <?php if ($smPoster): ?>
                    <img src="<?= $smPoster ?>" alt="<?= e($sm['title']) ?>" loading="lazy">
                <?php else: ?>
                    <div class="movie-poster-placeholder"><span class="poster-icon">🎬</span></div>
                <?php endif; ?>
                <div class="card-overlay"><button class="btn-play">▶ Детайли</button></div>
            </div>
            <div class="movie-info">
                <div class="movie-title"><?= e($sm['title']) ?></div>
                <div class="movie-meta"><span>📅 <?= $sm['year'] ?></span></div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<button class="scroll-top" id="scrollTop" title="Нагоре">↑</button>

<?php require_once 'includes/footer.php'; ?>
