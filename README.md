# PokéMart Global — Setup Guide

## Stack
- PHP 8.1+, MySQL 8.0, Apache 2.4 (LAMP on Google Cloud)
- Bootstrap 5, custom CSS/JS

---

## Step 1: Set Up Google Cloud VM

1. Go to Google Cloud Console → Compute Engine → Create VM
2. Choose Ubuntu 22.04 LTS, e2-medium
3. Allow HTTP and HTTPS traffic in firewall settings

## Step 2: Install LAMP Stack

SSH into your VM and run:

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install apache2 mysql-server php php-mysql php-pdo libapache2-mod-php -y
sudo a2enmod rewrite
sudo systemctl restart apache2
```

## Step 3: Set Up MySQL Database

```bash
sudo mysql
```

Then inside MySQL:
```sql
CREATE USER 'pokemart_user'@'localhost' IDENTIFIED BY 'your_strong_password';
GRANT ALL PRIVILEGES ON pokemart.* TO 'pokemart_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Then import the database:
```bash
sudo mysql < /var/www/html/database.sql
```

## Step 4: Upload Files

Upload all project files to `/var/www/html/`:
```bash
sudo cp -r pokemart/* /var/www/html/
sudo chown -R www-data:www-data /var/www/html/
sudo chmod -R 755 /var/www/html/
```

## Step 5: Configure Database Connection

Edit `config/db.php`:
```php
define('DB_HOST', 'localhost');   // or your Cloud SQL IP
define('DB_NAME', 'pokemart');
define('DB_USER', 'pokemart_user');
define('DB_PASS', 'your_strong_password');
```

## Step 6: Configure Apache

```bash
sudo nano /etc/apache2/sites-available/000-default.conf
```

Set DocumentRoot to `/var/www/html` and add:
```
<Directory /var/www/html>
    AllowOverride All
    Require all granted
</Directory>
```

Then:
```bash
sudo systemctl restart apache2
```

---

## Test Accounts

| Role    | Email                  | Password  |
|---------|------------------------|-----------|
| Admin   | admin@pokemart.com     | admin123  |
| Trainer | ash@pokemart.com       | password  |
| Trainer | misty@pokemart.com     | password  |
| Trainer | brock@pokemart.com     | password  |

---

## File Structure

```
pokemart/
├── config/
│   └── db.php              ← DB connection config
├── includes/
│   ├── header.php          ← Common header/navbar
│   ├── footer.php          ← Common footer
│   └── auth.php            ← Auth helper functions
├── assets/
│   ├── css/style.css       ← Custom CSS
│   ├── js/main.js          ← Custom JavaScript
│   └── images/
├── api/
│   ├── cart.php            ← Cart AJAX endpoint
│   ├── wishlist.php        ← Wishlist toggle endpoint
│   └── search.php          ← Autocomplete endpoint
├── admin/
│   ├── index.php           ← Admin dashboard
│   ├── users.php           ← Manage users
│   ├── listings.php        ← Manage listings
│   ├── orders.php          ← Manage orders
│   ├── pokemon.php         ← Manage Pokémon catalogue
│   └── sidebar.php         ← Admin sidebar
├── index.php               ← Home / landing page
├── browse.php              ← Browse listings (filter/search)
├── listing.php             ← Single listing detail
├── about.php               ← About Us + contact form
├── register.php            ← User registration
├── login.php               ← User login
├── logout.php              ← Logout
├── profile.php             ← User profile management
├── my-listings.php         ← Seller's own listings
├── create-listing.php      ← Create new listing
├── edit-listing.php        ← Edit existing listing
├── cart.php                ← Shopping cart (AJAX)
├── checkout.php            ← Checkout & order placement
├── orders.php              ← Order history
├── order-detail.php        ← Order detail + status timeline
├── wishlist.php            ← Wishlist management
└── database.sql            ← Full DB schema + sample data
```

---

## Security Features
- All DB queries use PDO prepared statements (SQL injection prevention)
- All output uses htmlspecialchars() (XSS prevention)
- Passwords stored with password_hash(PASSWORD_BCRYPT)
- Session ID regenerated on login (session fixation prevention)
- Ownership verified before edit/delete operations
