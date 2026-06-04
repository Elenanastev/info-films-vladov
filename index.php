<?php
// index.php
require_once 'includes/config.php';

$pdo = getDB();

$genres = $pdo->query('SELECT * FROM genres ORDER BY name')->fetchAll();

$search   = trim($_GET['search']   ?? '');
$genreId  = (int)($_GET['genre']  ?? 0);
$sortBy   = $_GET['sort'] ?? 'newest';

$where  = ['1=1'];
$params = [];

if ($search !== '') {
    $where[] = '(m.title LIKE ? OR m.description LIKE ? OR m.director LIKE ?)';
    $like = "%$search%";
    array_push($params, $like, $like, $like);
}
if ($genreId > 0) {
    $where[] = 'm.genre_id = ?';
    $params[] = $genreId;
}

$order = match($sortBy) {
    'rating'  => 'avg_r DESC, m.title',
    'oldest'  => 'm.year ASC',
    'alpha'   => 'm.title ASC',
    default   => 'm.id DESC',
};

$sql = "
    SELECT m.*, g.name AS genre_name,
           ROUND(AVG(r.rating), 1) AS avg_r,
           COUNT(r.id) AS review_count
    FROM movies m
    LEFT JOIN genres g ON m.genre_id = g.id
    LEFT JOIN reviews r ON m.id = r.movie_id
    WHERE " . implode(' AND ', $where) . "
    GROUP BY m.id
    ORDER BY $order
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$movies = $stmt->fetchAll();

// Top rated за featured row
$topMovies = $pdo->query("
    SELECT m.*, g.name AS genre_name,
           ROUND(AVG(r.rating), 1) AS avg_r,
           COUNT(r.id) AS review_count
    FROM movies m
    LEFT JOIN genres g ON m.genre_id = g.id
    LEFT JOIN reviews r ON m.id = r.movie_id
    GROUP BY m.id
    HAVING review_count > 0
    ORDER BY avg_r DESC, review_count DESC
    LIMIT 8
")->fetchAll();

$pageTitle = 'Начало';
require_once 'includes/header.php';
?>

<section class="hero">
    <h1>Открий <span>Следващия</span> Си Филм</h1>
    <p>Ревюта, трейлъри и оценки от общността</p>

    <form class="filter-bar" method="GET" action="index.php">
        <input type="text" name="search" placeholder="🔍 Търси по заглавие" value="<?= e($search) ?>">

        <select name="genre">
            <option value="0">Всички жанрове</option>
            <?php foreach ($genres as $g): ?>
                <option value="<?= $g['id'] ?>" <?= $genreId == $g['id'] ? 'selected' : '' ?>>
                    <?= e($g['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="sort">
            <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>>Най-нови</option>
            <option value="rating" <?= $sortBy === 'rating' ? 'selected' : '' ?>>По рейтинг</option>
            <option value="alpha"  <?= $sortBy === 'alpha'  ? 'selected' : '' ?>>А → Я</option>
            <option value="oldest" <?= $sortBy === 'oldest' ? 'selected' : '' ?>>По година</option>
        </select>

        <button type="submit" class="btn btn-primary">Филтрирай</button>
        <?php if ($search || $genreId): ?>
            <a href="index.php" class="btn btn-secondary">✕ Изчисти</a>
        <?php endif; ?>
    </form>
</section>

<?php if (empty($search) && !$genreId && !empty($topMovies)): ?>
<div class="container">
    <div class="featured-section">
        <h2 class="section-title">⭐ Топ Рейтинг</h2>
        <div class="featured-scroll" id="featuredScroll">
            <?php foreach ($topMovies as $i => $fm): ?>
            <a href="movie.php?id=<?= $fm['id'] ?>" class="featured-card">
                <div class="featured-poster">
                    <?php $posterUrl = getPosterUrl($fm); ?>
                    <?php if ($posterUrl): ?>
                        <img src="<?= $posterUrl ?>" alt="<?= e($fm['title']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="movie-poster-placeholder" style="height:150px;">
                            <span class="poster-icon" style="font-size:2rem;">🎬</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="featured-info">
                    <div class="featured-rank">#<?= $i + 1 ?></div>
                    <div class="featured-title"><?= e($fm['title']) ?></div>
                    <div class="featured-meta"><?= $fm['year'] ?> · <?= e($fm['genre_name'] ?? '') ?></div>
                    <div class="stars-row" style="margin-top:6px;">
                        <div class="stars">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                <span class="star <?= $s <= round($fm['avg_r']) ? 'filled' : '' ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <span class="rating-count"><?= number_format($fm['avg_r'], 1) ?></span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="container">
    <h2 class="section-title">
        🎬 <?= $search ? 'Резултати за "' . e($search) . '"' : ($genreId ? 'Жанр: ' . e($genres[array_search($genreId, array_column($genres, 'id'))]['name'] ?? '') : 'Всички Филми') ?>
        <span style="font-size:1rem;color:var(--text-muted);font-family:var(--font-body);letter-spacing:0;"><?= count($movies) ?> филма</span>
    </h2>

    <div class="movies-grid">
        <?php if (empty($movies)): ?>
            <div class="no-results">
                <div class="no-icon">🎭</div>
                <h3>Няма намерени филми</h3>
                <p>Опитай с различни филтри</p>
            </div>
        <?php endif; ?>

        <?php foreach ($movies as $movie): ?>
            <?php
            $avgRating   = $movie['avg_r'] ?? 0;
            $reviewCount = $movie['review_count'] ?? 0;
            $posterUrl   = getPosterUrl($movie);
            ?>
            <a href="movie.php?id=<?= $movie['id'] ?>" class="movie-card">
                <div class="movie-poster">
                    <?php if ($posterUrl): ?>
                        <img src="<?= $posterUrl ?>" alt="<?= e($movie['title']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="movie-poster-placeholder">
                            <span class="poster-icon">🎬</span>
                            <span>ФИЛМА</span>
                        </div>
                    <?php endif; ?>
                    <?php if ($movie['genre_name']): ?>
                        <span class="genre-badge"><?= e($movie['genre_name']) ?></span>
                    <?php endif; ?>
                    <div class="card-overlay">
                        <button class="btn-play">▶ Детайли</button>
                    </div>
                </div>
                <div class="movie-info">
                    <div class="movie-title"><?= e($movie['title']) ?></div>
                    <div class="movie-meta">
                        <?php if ($movie['year']): ?><span>📅 <?= $movie['year'] ?></span><?php endif; ?>
                        <?php if ($movie['director']): ?><span>🎥 <?= e($movie['director']) ?></span><?php endif; ?>
                    </div>
                    <div class="stars-row">
                        <div class="stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star <?= $i <= round($avgRating) ? 'filled' : '' ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <span class="rating-count">
                            <?= $avgRating > 0 ? number_format($avgRating, 1) : 'Без' ?>
                            <?= $reviewCount > 0 ? "($reviewCount)" : '' ?>
                        </span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<button class="scroll-top" id="scrollTop" title="Нагоре">↑</button>

<?php require_once 'includes/footer.php'; ?>
