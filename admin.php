<?php
// admin.php — Панел за администратори
require_once 'includes/config.php';

if (!isAdmin()) {
    redirect(BASE_URL . 'login.php');
}

$pdo    = getDB();
$action = $_GET['action'] ?? 'dashboard';
$msg    = '';
$error  = '';

// ─── ИЗТРИВАНЕ НА ФИЛМ ───
if ($action === 'delete_movie' && isset($_GET['id'])) {
    $delId = (int)$_GET['id'];
    $pdo->prepare('DELETE FROM movies WHERE id = ?')->execute([$delId]);
    redirect(BASE_URL . 'admin.php?action=movies&msg=deleted');
}

// ─── ЗАПИС НА ФИЛМ (нов или редакция) ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_movie'])) {
    $editId      = (int)($_POST['movie_id'] ?? 0);
    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $genreId_m   = (int)($_POST['genre_id']   ?? 0);
    $year        = (int)($_POST['year']        ?? 0);
    $director    = trim($_POST['director']     ?? '');
    $duration    = (int)($_POST['duration']    ?? 0);
    $posterUrl_m = trim($_POST['poster_url']   ?? '');
    $trailerUrl  = trim($_POST['trailer_url']  ?? '');

    if (empty($title)) {
        $error = 'Заглавието е задължително!';
    } else {
        // Ако е качен файл
        $posterFile = null;
        if (!empty($_FILES['poster']['name']) && $_FILES['poster']['error'] === 0) {
            $ext    = strtolower(pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($ext, $allowed)) {
                $fname = uniqid('poster_') . '.' . $ext;
                $dest  = 'uploads/posters/' . $fname;
                if (!is_dir('uploads/posters')) mkdir('uploads/posters', 0755, true);
                if (move_uploaded_file($_FILES['poster']['tmp_name'], $dest)) {
                    $posterFile = $fname;
                }
            } else {
                $error = 'Позволени формати: JPG, PNG, WEBP';
            }
        }

        if (empty($error)) {
            if ($editId > 0) {
                // Update
                $sql = 'UPDATE movies SET title=?, description=?, genre_id=?, year=?, director=?, duration_min=?, trailer_url=?, poster_url=?';
                $params_m = [$title, $description, $genreId_m ?: null, $year ?: null, $director, $duration ?: null, $trailerUrl, $posterUrl_m ?: null];
                if ($posterFile) {
                    $sql .= ', poster=?';
                    $params_m[] = $posterFile;
                }
                $sql .= ' WHERE id=?';
                $params_m[] = $editId;
                $pdo->prepare($sql)->execute($params_m);
                redirect(BASE_URL . 'admin.php?action=movies&msg=updated');
            } else {
                // Insert
                $pdo->prepare('INSERT INTO movies (title, description, genre_id, year, director, duration_min, poster, poster_url, trailer_url) VALUES (?,?,?,?,?,?,?,?,?)')
                    ->execute([$title, $description, $genreId_m ?: null, $year ?: null, $director, $duration ?: null, $posterFile ?? 'default_poster.jpg', $posterUrl_m ?: null, $trailerUrl]);
                redirect(BASE_URL . 'admin.php?action=movies&msg=added');
            }
        }
    }
}

// ─── Данни ───
$movieCount  = $pdo->query('SELECT COUNT(*) FROM movies')->fetchColumn();
$userCount   = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$reviewCount = $pdo->query('SELECT COUNT(*) FROM reviews')->fetchColumn();
$genres      = $pdo->query('SELECT * FROM genres ORDER BY name')->fetchAll();

if ($action === 'movies' || $action === 'add_movie' || $action === 'edit_movie') {
    $movies = $pdo->query("
        SELECT m.*, g.name AS genre_name,
               ROUND(AVG(r.rating),1) AS avg_r,
               COUNT(r.id) AS review_count
        FROM movies m
        LEFT JOIN genres g ON m.genre_id = g.id
        LEFT JOIN reviews r ON m.id = r.movie_id
        GROUP BY m.id
        ORDER BY m.id DESC
    ")->fetchAll();
}

$editMovie = null;
if ($action === 'edit_movie' && isset($_GET['id'])) {
    $stmt = $pdo->prepare('SELECT * FROM movies WHERE id = ?');
    $stmt->execute([(int)$_GET['id']]);
    $editMovie = $stmt->fetch();
    if (!$editMovie) redirect(BASE_URL . 'admin.php?action=movies');
}

if (isset($_GET['msg'])) {
    $msgs = [
        'added'   => '✅ Филмът е добавен успешно!',
        'updated' => '✅ Филмът е обновен успешно!',
        'deleted' => '🗑️ Филмът е изтрит.',
    ];
    $msg = $msgs[$_GET['msg']] ?? '';
}

$pageTitle = 'Администрация';
require_once 'includes/header.php';
?>

<div class="admin-layout">
    <!-- SIDEBAR -->
    <aside class="admin-sidebar">
        <div class="admin-sidebar-title">АДМИН</div>
        <a href="admin.php?action=dashboard" class="admin-nav-item <?= $action === 'dashboard' ? 'active' : '' ?>">
            📊 Табло
        </a>
        <a href="admin.php?action=movies" class="admin-nav-item <?= in_array($action, ['movies','add_movie','edit_movie']) ? 'active' : '' ?>">
            🎬 Управление на филми
        </a>
        <a href="admin.php?action=add_movie" class="admin-nav-item">
            ➕ Добави филм
        </a>
        <a href="admin.php?action=users" class="admin-nav-item <?= $action === 'users' ? 'active' : '' ?>">
            👥 Потребители
        </a>
        <a href="<?= BASE_URL ?>" class="admin-nav-item">
            🏠 Към сайта
        </a>
    </aside>

    <!-- CONTENT -->
    <main class="admin-content">
        <?php if ($msg): ?>
            <div class="alert alert-success"><?= e($msg) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <!-- DASHBOARD -->
        <?php if ($action === 'dashboard'): ?>
        <div class="admin-header">
            <h1>📊 Табло</h1>
        </div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= $movieCount ?></div>
                <div class="stat-label">🎬 Филми</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $userCount ?></div>
                <div class="stat-label">👥 Потребители</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $reviewCount ?></div>
                <div class="stat-label">💬 Ревюта</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $genres ? count($genres) : 0 ?></div>
                <div class="stat-label">🎭 Жанра</div>
            </div>
        </div>
        <div style="margin-top:24px;">
            <a href="admin.php?action=add_movie" class="btn btn-primary">➕ Добави нов филм</a>
            <a href="admin.php?action=movies" class="btn btn-secondary" style="margin-left:12px;">🎬 Всички филми</a>
        </div>

        <!-- MOVIES LIST -->
        <?php elseif ($action === 'movies'): ?>
        <div class="admin-header">
            <h1>🎬 Филми</h1>
            <a href="admin.php?action=add_movie" class="btn btn-primary">➕ Добави нов</a>
        </div>
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Постер</th>
                        <th>Заглавие</th>
                        <th>Жанр</th>
                        <th>Година</th>
                        <th>Рейтинг</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movies as $m):
                        $p = getPosterUrl($m);
                    ?>
                    <tr>
                        <td>
                            <?php if ($p): ?>
                                <img src="<?= $p ?>" alt="<?= e($m['title']) ?>">
                            <?php else: ?>
                                <span style="color:var(--text-muted);">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= e($m['title']) ?></strong><br>
                            <span style="color:var(--text-muted);font-size:.82rem;"><?= e($m['director'] ?? '') ?></span>
                        </td>
                        <td><?= e($m['genre_name'] ?? '—') ?></td>
                        <td><?= $m['year'] ?? '—' ?></td>
                        <td>
                            <?= $m['avg_r'] ? '⭐ ' . number_format($m['avg_r'], 1) : '—' ?>
                            <br><span style="color:var(--text-muted);font-size:.8rem;"><?= $m['review_count'] ?> ревюта</span>
                        </td>
                        <td style="white-space:nowrap;">
                            <a href="movie.php?id=<?= $m['id'] ?>" class="btn btn-ghost" style="padding:6px 10px;font-size:.82rem;">👁️</a>
                            <a href="admin.php?action=edit_movie&id=<?= $m['id'] ?>" class="btn btn-secondary" style="padding:6px 10px;font-size:.82rem;">✏️</a>
                            <a href="admin.php?action=delete_movie&id=<?= $m['id'] ?>"
                               class="btn btn-danger" style="padding:6px 10px;font-size:.82rem;"
                               onclick="return confirm('Изтрий <?= e(addslashes($m['title'])) ?>?')">🗑️</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- ADD / EDIT MOVIE FORM -->
        <?php elseif ($action === 'add_movie' || $action === 'edit_movie'): ?>
        <div class="admin-header">
            <h1><?= $editMovie ? '✏️ Редактирай Филм' : '➕ Добави Нов Филм' ?></h1>
            <a href="admin.php?action=movies" class="btn btn-ghost">← Назад</a>
        </div>
        <div class="admin-form">
            <form method="POST" enctype="multipart/form-data">
                <?php if ($editMovie): ?>
                    <input type="hidden" name="movie_id" value="<?= $editMovie['id'] ?>">
                <?php endif; ?>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Заглавие *</label>
                        <input type="text" name="title" required
                               value="<?= e($editMovie['title'] ?? $_POST['title'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Режисьор</label>
                        <input type="text" name="director"
                               value="<?= e($editMovie['director'] ?? $_POST['director'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Жанр</label>
                        <select name="genre_id">
                            <option value="0">— Без жанр —</option>
                            <?php foreach ($genres as $g): ?>
                                <option value="<?= $g['id'] ?>"
                                    <?= ($editMovie['genre_id'] ?? 0) == $g['id'] ? 'selected' : '' ?>>
                                    <?= e($g['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Година</label>
                        <input type="number" name="year" min="1900" max="2030"
                               value="<?= e($editMovie['year'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Времетраене (мин)</label>
                        <input type="number" name="duration" min="1"
                               value="<?= e($editMovie['duration_min'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>YouTube Трейлър URL</label>
                        <input type="url" name="trailer_url" placeholder="https://www.youtube.com/embed/..."
                               value="<?= e($editMovie['trailer_url'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Описание</label>
                    <textarea name="description" rows="5"><?= e($editMovie['description'] ?? '') ?></textarea>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Постер URL (онлайн изображение)</label>
                        <input type="url" name="poster_url"
                               placeholder="https://image.tmdb.org/t/p/w500/..."
                               value="<?= e($editMovie['poster_url'] ?? '') ?>">
                        <small style="color:var(--text-muted);font-size:.8rem;">
                            Препоръчително: URL от TMDB или друг сайт
                        </small>
                    </div>
                    <div class="form-group">
                        <label>Или качи постер (JPG/PNG/WEBP)</label>
                        <input type="file" name="poster" accept="image/*" style="padding:8px;">
                        <?php if (!empty($editMovie['poster']) && $editMovie['poster'] !== 'default_poster.jpg'): ?>
                            <small style="color:var(--text-muted);">Текущ: <?= e($editMovie['poster']) ?></small>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($editMovie['poster_url'])): ?>
                <div style="margin-bottom:20px;">
                    <p style="color:var(--text-muted);font-size:.88rem;margin-bottom:8px;">Текущ постер:</p>
                    <img src="<?= e($editMovie['poster_url']) ?>" style="width:100px;border-radius:8px;">
                </div>
                <?php endif; ?>

                <button type="submit" name="save_movie" class="btn btn-primary" style="padding:12px 32px;">
                    <?= $editMovie ? '💾 Запази промените' : '🎬 Добави Филм' ?>
                </button>
            </form>
        </div>

        <!-- USERS -->
        <?php elseif ($action === 'users'): ?>
        <div class="admin-header">
            <h1>👥 Потребители</h1>
        </div>
        <?php $users = $pdo->query('SELECT * FROM users ORDER BY created_at DESC')->fetchAll(); ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Потребител</th>
                    <th>Имейл</th>
                    <th>Роля</th>
                    <th>Регистриран</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><strong><?= e($u['username']) ?></strong></td>
                    <td><?= e($u['email']) ?></td>
                    <td>
                        <?php
                        $roles = ['admin' => '⚙️ Администратор', 'user' => '👤 Потребител', 'guest' => '👁️ Гост'];
                        echo $roles[$u['role']] ?? $u['role'];
                        ?>
                    </td>
                    <td><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </main>
</div>

<?php require_once 'includes/footer.php'; ?>
