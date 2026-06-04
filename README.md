[README.md](https://github.com/user-attachments/files/28581057/README.md)
# 🎬 InfoFilms – Контейнеризация с Docker Compose

Уеб приложение за преглед и оценка на филми, изградено с PHP 8.2, Apache и MySQL 8.0. Целият проект се стартира с единствена команда чрез Docker Compose.

---

## 📁 Структура на проекта

```
infofilms/
├── Dockerfile              # Образ за PHP + Apache уеб сървъра
├── compose.yml             # Оркестрация на всички услуги
├── database.sql            # Схема и начални данни (зарежда се автоматично)
├── includes/
│   └── config.php          # Конфигурация – чете env vars (DB_HOST, DB_NAME…)
├── index.php               # Начална страница – списък с филми, филтри
├── movie.php               # Детайлна страница на филм + ревюта
├── login.php               # Вход (вкл. гост вход)
├── register.php            # Регистрация
├── logout.php              # Изход
├── admin.php               # Административен панел
├── profile.php             # Профил на потребителя
└── uploads/
    └── posters/            # Качени постери (монтиран volume)
```

---

## 🐳 Услуги (Services)

| Услуга | Образ | Порт | Описание |
|---|---|---|---|
| `app` | `elena22209/infofilms-app` | `8080` | PHP 8.2 + Apache уеб сървър |
| `db` | `mysql:8.0` | вътрешен | MySQL база данни |
| `phpmyadmin` | `phpmyadmin:latest` | `8081` | Уеб интерфейс за управление на БД |
e-
### Комуникация между услугите

Всички контейнери са свързани чрез обща Docker мрежа `infofilms_net` (bridge driver). PHP приложението достига до MySQL като използва hostname `db` – това е името на услугата в `compose.yml`, което Docker автоматично резолвира към IP адреса на контейнера. Портовете на базата данни **не са** изложени навън за по-голяма сигурност.

```
Браузър → localhost:8080 → [app контейнер] → db:3306 → [db контейнер]
Браузър → localhost:8081 → [phpmyadmin]    → db:3306 → [db контейнер]
```

---

## 🚀 Стартиране

### Изисквания

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (включва Docker Compose)

### Стъпки

**1. Клонирай хранилището**
```bash
git clone https://github.com/Elenanastev/infofilms.git
cd infofilms
```

**2. (Веднъж) Обнови Docker Hub потребителското си име в `compose.yml`**
```yaml
image: elena22209/infofilms-app:latest
```

**3. Стартирай всички контейнери**
```bash
docker compose up -d
```

Флагът `-d` пуска контейнерите във фонов режим. При първо стартиране Docker автоматично:
- изгражда образа на PHP приложението
- изтегля `mysql:8.0` и `phpmyadmin`
- зарежда `database.sql` в MySQL (схема + примерни данни)

**4. Отвори в браузъра**

| URL | Какво е |
|---|---|
| http://localhost:8080 | InfoFilms приложението |
| http://localhost:8081 | phpMyAdmin |

**Демо акаунти** (паролата е `password` за всички):
| Имейл | Роля |
|---|---|
| admin@infofilms.bg | Администратор |
| ivan@example.com | Потребител |
| guest@infofilms.bg | Гост |

---

## 🔨 Изграждане и публикуване на Docker образ

```bash
# Изгради образа локално
docker build -t elena22209/infofilms-app:latest .

# Влез в Docker Hub
docker login

# Публикувай образа
docker push elena22209/infofilms-app:latest
```

---

## 🛠 Полезни команди

```bash
# Спри всички контейнери
docker compose down

# Спри и изтрий базата данни (volume)
docker compose down -v

# Провери логовете на PHP приложението
docker compose logs app

# Провери логовете на MySQL
docker compose logs db

# Рестартирай само уеб сървъра
docker compose restart app

# Влез в контейнера на приложението
docker compose exec app bash

# Влез в MySQL конзолата
docker compose exec db mysql -u infofilms_user -pinfofilms_pass infofilms
```

---

## ⚙️ Environment Variables

Дефинирани в `compose.yml` и четени от `includes/config.php`:

| Променлива | Стойност | Описание |
|---|---|---|
| `DB_HOST` | `db` | Hostname на MySQL (Docker service name) |
| `DB_NAME` | `infofilms` | Име на базата данни |
| `DB_USER` | `infofilms_user` | MySQL потребител |
| `DB_PASS` | `infofilms_pass` | MySQL парола |

---

## 💾 Постоянни данни (Volumes)

| Volume | Описание |
|---|---|
| `db_data` | MySQL данни – оцеляват при `docker compose restart` |
| `./uploads` | Качени постери – монтирани директно от хост машината |

---

## 🔗 Линкове

- **GitHub хранилище:** `https://github.com/твоето-потребителско-име/infofilms`
- **Docker Hub образ:** `https://hub.docker.com/r/твоето-потребителско-име/infofilms-app`
