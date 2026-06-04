-- ============================================
-- InfoFilms - База данни (Обновена версия)
-- ============================================

CREATE DATABASE IF NOT EXISTS infofilms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE infofilms;

-- ─────────────────────────────────────────────
-- Таблица: users
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    email       VARCHAR(100) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    avatar      VARCHAR(255) DEFAULT 'default.png',
    role        ENUM('user','admin','guest') DEFAULT 'user',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- Таблица: genres
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS genres (
    id    INT AUTO_INCREMENT PRIMARY KEY,
    name  VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- Таблица: movies
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS movies (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    title         VARCHAR(200) NOT NULL,
    description   TEXT,
    genre_id      INT,
    year          YEAR,
    director      VARCHAR(100),
    duration_min  INT,
    poster        VARCHAR(255) DEFAULT 'default_poster.jpg',
    poster_url    VARCHAR(500) DEFAULT NULL,
    trailer_url   VARCHAR(255),
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- Таблица: reviews
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS reviews (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    movie_id   INT NOT NULL,
    user_id    INT NOT NULL,
    rating     TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment    TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_review (movie_id, user_id),
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────
-- Примерни данни
-- ─────────────────────────────────────────────
INSERT INTO genres (name) VALUES
    ('Екшън'), ('Комедия'), ('Драма'), ('Ужаси'), ('Научна фантастика'),
    ('Романтика'), ('Трилър'), ('Анимация'), ('Документален'), ('Фентъзи');

-- Паролата за всички акаунти е: password
INSERT INTO users (username, email, password, role) VALUES
    ('admin', 'admin@infofilms.bg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
    ('ivan_bg', 'ivan@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
    ('guest', 'guest@infofilms.bg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'guest');

-- ФИЛМИ с poster_url (онлайн изображения от TMDB)
INSERT INTO movies (title, description, genre_id, year, director, duration_min, poster_url, trailer_url) VALUES
    ('Интерстелар', 'Екип от астронавти пътува през червей дупка в търсене на нова обитаема планета, докато времето и гравитацията изкривяват реалността.', 5, 2014, 'Кристофър Нолан', 169, 'https://image.tmdb.org/t/p/w500/gEU2QniE6E77NI6lCU6MxlNBvIx.jpg', 'https://www.youtube.com/embed/zSWdZVtXT7E'),
    ('Тъмният рицар', 'Батман се изправя срещу Жокера — анархистичен злодей, решен да сее хаос и страх в Готъм Сити.', 1, 2008, 'Кристофър Нолан', 152, 'https://image.tmdb.org/t/p/w500/qJ2tW6WMUDux911r6m7haRef0WH.jpg', 'https://www.youtube.com/embed/EXeTwQWrcwY'),
    ('Шосенка', 'Двама затворници изграждат дълбока приятелска връзка в продължение на две десетилетия в затвора Шошанк, намирайки утеха и надежда.', 3, 1994, 'Франк Дарабонт', 142, 'https://image.tmdb.org/t/p/w500/q6y0Go1tsGEsmtFryDOJo3dEmqu.jpg', 'https://www.youtube.com/embed/6hB3S9bIaco'),
    ('Началото', 'Крадец с умението да влиза в сънищата на хора и да краде тайни получава шанс да изтрие криминалното си минало.', 5, 2010, 'Кристофър Нолан', 148, 'https://image.tmdb.org/t/p/w500/oYuLEt3zVCKq57qu2F8dT7NIa6f.jpg', 'https://www.youtube.com/embed/YoHD9XEInc0'),
    ('Форест Гъмп', 'Историята на прост, но добросърдечен мъж от Алабама, чийто живот по чуден начин се преплита с ключови моменти от американската история.', 3, 1994, 'Робърт Земекис', 142, 'https://image.tmdb.org/t/p/w500/arw2vcBveWOVZr6pxd9XTd1TdQa.jpg', 'https://www.youtube.com/embed/bLvqoHBptjg'),
    ('Матрицата', 'Хакер открива, че реалността, в която живее, е симулация, контролирана от машини, и се присъединява към бунта на хората.', 5, 1999, 'Лана и Лили Вачовски', 136, 'https://image.tmdb.org/t/p/w500/f89U3ADr1oiB1s9GkdPOEpXUk5H.jpg', 'https://www.youtube.com/embed/vKQi3bBA1y8'),
    ('Криминале', 'Съдбата на седем герои се преплита в Лос Анджелис в истории за насилие, изкупление и откровен хумор.', 7, 1994, 'Куентин Тарантино', 154, 'https://image.tmdb.org/t/p/w500/d5iIlFn5s0ImszYzBPb8JPIfbXD.jpg', 'https://www.youtube.com/embed/s7EdQ4FqbhY'),
    ('Властелинът на пръстените: Задругата', 'Хобитът Фродо и неговите приятели поемат на епично пътешествие да унищожат Единствения Пръстен и да спасят Средната земя.', 10, 2001, 'Питър Джаксън', 178, 'https://image.tmdb.org/t/p/w500/6oom5QYQ2yQTMJIbnvbkBL9cHo6.jpg', 'https://www.youtube.com/embed/V75dMMIW2B4'),
    ('Боец', 'Разочарован от живота, служещ в компания, Джак Тачър среща загадъчния Тайлър Дюрдан и двамата основават тайна организация.', 7, 1999, 'Дейвид Финчър', 139, 'https://image.tmdb.org/t/p/w500/pB8BM7pdSp6B6Ih7QZ4DrQ3PmJK.jpg', 'https://www.youtube.com/embed/qtRKdVHc-cE'),
    ('Гладиаторът', 'Римски генерал, предаден от корумпиран принц, е превърнат в роб и гладиатор, за да спечели обратно свободата и честта си.', 1, 2000, 'Ридли Скот', 155, 'https://image.tmdb.org/t/p/w500/ty8TGRuvJLPUmAR1H1nRIsgwvim.jpg', 'https://www.youtube.com/embed/owK1qxDselE'),
    ('Spirited Away', 'Десетгодишно момиченце се озовава в магически свят, пълен с духове, след като родителите й са превърнати в прасета.', 8, 2001, 'Хаяо Миядзаки', 125, 'https://image.tmdb.org/t/p/w500/39wmItIWsg5sZMyRUHLkWBcuVCM.jpg', 'https://www.youtube.com/embed/ByXuk9QqQkk'),
    ('Жокерът', 'Провален комик от Готъм Сити бавно губи разсъдъка си и се превръща в зловещия Жокер в трагична история за обществото.', 3, 2019, 'Тод Филипс', 122, 'https://image.tmdb.org/t/p/w500/udDclJoHjfjb8Ekgsd4FDteOkCU.jpg', 'https://www.youtube.com/embed/zAGVQLHvwOY'),
    ('Avengers: Endgame', 'Оцелялите Отмъстители се събират за последна битка срещу Танос в битка за съдбата на вселената.', 1, 2019, 'Братя Русо', 181, 'https://image.tmdb.org/t/p/w500/or06FN3Dka5tukK1e9sl16pB3iy.jpg', 'https://www.youtube.com/embed/TcMBFSGVi1c'),
    ('Паразит', 'Бедно корейско семейство се инфилтрира в богато домакинство в острата социална сатира, носител на Оскар.', 7, 2019, 'Пон Джун-хо', 132, 'https://image.tmdb.org/t/p/w500/7IiTTgloJzvGI1TAYymCfbfl3vT.jpg', 'https://www.youtube.com/embed/5xH0HfJHsaY'),
    ('Дюна', 'Млад аристократ поема контрола над пустинна планета, единственият източник на най-ценната субстанция в галактиката.', 5, 2021, 'Дени Вилньов', 155, 'https://image.tmdb.org/t/p/w500/d5NXSklpcvwN3Y1bqBlMKScCFPn.jpg', 'https://www.youtube.com/embed/8g18jFHCLXk'),
    ('Опенхаймер', 'Историята на Робърт Опенхаймер — ученият, ръководил Проект Манхатан и създал първата атомна бомба в света.', 3, 2023, 'Кристофър Нолан', 180, 'https://image.tmdb.org/t/p/w500/8Gxv8gSFCU0XGDykEGv7zR1n2ua.jpg', 'https://www.youtube.com/embed/uYPbbksJxIg');

INSERT INTO reviews (movie_id, user_id, rating, comment) VALUES
    (1, 2, 5, 'Невероятен филм! Гледах го три пъти.'),
    (2, 2, 5, 'Хийт Леджър е брилянтен като Жокера.'),
    (3, 1, 5, 'Класика на киното. Никога не остарява.'),
    (4, 2, 4, 'Сложен, но страхотен. Нолан е гений.'),
    (5, 1, 5, 'Невероятно вдъхновяващ. Просто перфектен.'),
    (7, 1, 5, 'Тарантино на върха. Диалозите са шедьовър.'),
    (8, 2, 5, 'Епичен! Джаксън е създал нещо безсмъртно.'),
    (12, 1, 4, 'Хоакин Финикс е невероятен в тази роля.'),
    (14, 2, 5, 'Пон Джун-хо заслужено спечели Оскар!'),
    (16, 1, 5, 'Нолан отново надмина себе си.');

-- Изглед
CREATE OR REPLACE VIEW movies_with_rating AS
SELECT 
    m.id, m.title, m.description, g.name AS genre,
    m.year, m.director, m.duration_min, m.poster, m.poster_url, m.trailer_url,
    ROUND(AVG(r.rating), 1) AS avg_rating,
    COUNT(r.id) AS review_count
FROM movies m
LEFT JOIN genres g ON m.genre_id = g.id
LEFT JOIN reviews r ON m.id = r.movie_id
GROUP BY m.id, m.title, m.description, g.name, m.year, m.director, m.duration_min, m.poster, m.poster_url, m.trailer_url;
