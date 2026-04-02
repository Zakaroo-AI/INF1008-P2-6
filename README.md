# PokéMart Global 🎴

> A full-stack web-based Pokémon card marketplace built with PHP, MySQL, Bootstrap 5, and the Pokémon TCG API. Developed as part of INF1005 - Web Systems & Technologies, Group P2-6.

---

## 👥 Team

| Name | Student ID |
|---|---|
| Lee Hong Yih | 2501110 |
| Tng Qi Long Felix | 2500906 |
| Zheng Yongheng | 2502762 |

---

## 🌐 Live Site

Hosted on Google Cloud Compute Engine (LAMP stack):
> http://35.212.172.68/

---

## ✨ Features

### For Trainers (Users)
- Register and log in securely
- Browse, search and filter Pokémon card listings by type, rarity, condition, language and price
- Create listings using real card data pulled from the Pokémon TCG API
- Add cards to cart and complete checkout with stock validation
- Manage wishlist, order history, and sales
- Submit disputes and reviews on completed orders
- Chat with the AI PokéTrainer chatbot (powered by OpenAI API)

### For Administrators
- Dashboard with live analytics (Chart.js): orders over time, listings by rarity, listings by type
- Manage all users (ban, unban, promote to admin)
- Moderate all listings and orders
- Resolve disputes
- Maintain the Pokémon card catalogue

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, JavaScript (ES6), Bootstrap 5, Bootstrap Icons |
| Backend | PHP 8.1, PDO |
| Database | MySQL 8.0 |
| Web Server | Apache 2.4 |
| Hosting | Google Cloud Compute Engine (Ubuntu 22.04 LTS) |
| External APIs | Pokémon TCG API (pokemontcg.io), OpenAI API |
| Analytics | Chart.js (via CDN) |
| Version Control | Git + GitHub |

---

## 🚀 Setup Guide

### Prerequisites
- Google Cloud VM (Ubuntu 22.04 LTS, e2-medium recommended)
- HTTP and HTTPS traffic enabled in firewall settings

---

### Step 1 — Install LAMP Stack

SSH into your VM and run:

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install apache2 mysql-server php php-mysql php-pdo libapache2-mod-php php-curl -y
sudo a2enmod rewrite
sudo systemctl restart apache2
```

---

### Step 2 — Set Up MySQL Database

```bash
sudo mysql
```

Inside MySQL, run:

```sql
CREATE DATABASE pokemart CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'pokemart_user'@'localhost' IDENTIFIED BY 'your_strong_password';
GRANT ALL PRIVILEGES ON pokemart.* TO 'pokemart_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Then import the schema and seed data:

```bash
sudo mysql pokemart < /var/www/html/database.sql
```

---

### Step 3 — Upload Project Files

```bash
sudo cp -r pokemart/* /var/www/html/
sudo chown -R www-data:www-data /var/www/html/
sudo chmod -R 755 /var/www/html/
```

---

### Step 4 — Configure Private Credentials

Create a private config directory **outside** the web root:

```bash
sudo mkdir -p /var/www/private
sudo nano /var/www/private/db_config.ini
```

Paste the following into `db_config.ini`:

```ini
[database]
servername = localhost
dbname     = pokemart
username   = pokemart_user
password   = your_strong_password

[pokemontcg]
api_key = your_pokemontcg_api_key_here
```

Secure the file:

```bash
sudo chmod 600 /var/www/private/db_config.ini
sudo chown www-data:www-data /var/www/private/db_config.ini
```

---

### Step 5 — Configure OpenAI API Key (AI PokéTrainer)

Create a `.env` file in the project root:

```bash
sudo nano /var/www/html/.env
```

Add:

```
OPENAI_API_KEY=your_openai_api_key_here
```

> The `.env` file is listed in `.gitignore` and will not be pushed to GitHub.

---

### Step 6 — Configure Apache

```bash
sudo nano /etc/apache2/sites-available/000-default.conf
```

Set `DocumentRoot` to `/var/www/html` and add:

```apache
<Directory /var/www/html>
    AllowOverride All
    Require all granted
</Directory>
```

Then restart Apache:

```bash
sudo systemctl restart apache2
```

---

## 🔑 Test Accounts

| Role | Email | Password | Access |
|---|---|---|---|
| Administrator | admin@pokemart.com | password | Full admin panel |
| Trainer | ash@pokemart.com | password | Buyer/seller workflows |
| Trainer | pine@pokemart.com | 12345678 | Additional testing account |

> All accounts use password-only login. No 2FA or email verification required.

---

## 📁 File Structure

```
pokemart/
├── config/
│   └── db.php                  ← Database connection (reads from /var/www/private/)
├── includes/
│   ├── header.php              ← Shared navbar + session start
│   ├── footer.php              ← Shared footer
│   └── auth.php                ← Auth helpers, requireLogin(), requireAdmin(), e()
├── assets/
│   ├── css/style.css           ← Custom styles
│   ├── js/main.js              ← Custom JavaScript (cart, wishlist, search)
│   └── images/
├── api/
│   ├── cart.php                ← Cart AJAX endpoint
│   ├── wishlist.php            ← Wishlist toggle endpoint
│   ├── search.php              ← Navbar autocomplete endpoint
│   ├── card-search.php         ← Pokémon TCG API proxy
│   └── trainer-chat.php        ← OpenAI chatbot backend
├── admin/
│   ├── index.php               ← Admin dashboard (Chart.js analytics)
│   ├── users.php               ← Manage users (ban/unban/promote)
│   ├── listings.php            ← Moderate listings
│   ├── orders.php              ← Manage all orders
│   ├── disputes.php            ← Resolve disputes
│   ├── pokemon.php             ← Manage card catalogue
│   └── sidebar.php             ← Admin sidebar
├── index.php                   ← Home / landing page
├── browse.php                  ← Browse listings (filter, search, sort)
├── listing.php                 ← Single listing detail
├── about.php                   ← About Us + contact form
├── register.php                ← User registration
├── login.php                   ← User login
├── logout.php                  ← Session logout
├── profile.php                 ← Profile management
├── my-listings.php             ← Seller's own listings
├── create-listing.php          ← Create new listing (TCG API search)
├── edit-listing.php            ← Edit existing listing
├── cart.php                    ← Shopping cart
├── checkout.php                ← Checkout & order placement (DB transaction)
├── orders.php                  ← Buyer order history
├── order-detail.php            ← Order detail, dispute & review submission
├── seller-orders.php           ← Seller's incoming orders
├── wishlist.php                ← Wishlist management
├── trainer.php                 ← AI PokéTrainer chatbot UI
├── database.sql                ← Full DB schema + seed data
└── .env                        ← API keys (not committed to GitHub)
```

---

## 🔒 Security

| Measure | Implementation |
|---|---|
| SQL Injection | PDO prepared statements throughout |
| XSS | `htmlspecialchars()` via `e()` helper on all output |
| Password storage | `password_hash(PASSWORD_BCRYPT)` with salting |
| Session fixation | `session_regenerate_id(true)` on login |
| Access control | `requireLogin()` and `requireAdmin()` on every protected page |
| Credential storage | DB credentials and API keys stored outside web root in `/var/www/private/` |
| Git security | `.env` and `vendor/` excluded via `.gitignore` |

---

## 📊 Database Tables

| Table | Purpose |
|---|---|
| `users` | User accounts, roles, ban status |
| `cards` | Pokémon card catalogue |
| `listings` | Marketplace listings |
| `cart` | Active cart items per user |
| `orders` | Order records |
| `order_items` | Individual items within each order |
| `wishlist` | Saved listings per user |
| `reviews` | Post-delivery buyer ratings |
| `disputes` | Buyer dispute submissions |

---

## 📄 License

This project was developed for educational purposes as part of INF1005 at Singapore Institute of Technology (SIT). Not intended for commercial use.
