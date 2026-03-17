-- ============================================================
-- PokéMart Global — Database Setup
-- Run this file in your MySQL / Google Cloud SQL console
-- ============================================================

CREATE DATABASE IF NOT EXISTS pokemart CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pokemart;

-- -----------------------------------------------
-- USERS
-- -----------------------------------------------
CREATE TABLE users (
    user_id     INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    email       VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role        ENUM('trainer','admin') DEFAULT 'trainer',
    avatar_url  VARCHAR(255) DEFAULT NULL,
    is_banned   TINYINT(1)   DEFAULT 0,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------
-- POKEMON CATALOGUE
-- -----------------------------------------------
CREATE TABLE pokemon (
    pokemon_id      INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    type_primary    VARCHAR(30)  NOT NULL,
    type_secondary  VARCHAR(30)  DEFAULT NULL,
    hp              INT NOT NULL DEFAULT 50,
    attack          INT NOT NULL DEFAULT 50,
    defense         INT NOT NULL DEFAULT 50,
    speed           INT NOT NULL DEFAULT 50,
    rarity          ENUM('Common','Rare','Epic','Legendary') NOT NULL DEFAULT 'Common',
    image_url       VARCHAR(255) NOT NULL DEFAULT 'assets/images/default.png',
    description     TEXT DEFAULT NULL
);

-- -----------------------------------------------
-- LISTINGS
-- -----------------------------------------------
CREATE TABLE listings (
    listing_id  INT AUTO_INCREMENT PRIMARY KEY,
    seller_id   INT NOT NULL,
    pokemon_id  INT NOT NULL,
    title       VARCHAR(150) NOT NULL,
    description TEXT DEFAULT NULL,
    price       DECIMAL(10,2) NOT NULL,
    stock       INT NOT NULL DEFAULT 1,
    status      ENUM('active','sold','removed') DEFAULT 'active',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id)  REFERENCES users(user_id)   ON DELETE CASCADE,
    FOREIGN KEY (pokemon_id) REFERENCES pokemon(pokemon_id) ON DELETE CASCADE
);

-- -----------------------------------------------
-- CART
-- -----------------------------------------------
CREATE TABLE cart (
    cart_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    listing_id  INT NOT NULL,
    quantity    INT NOT NULL DEFAULT 1,
    added_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES users(user_id)       ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (user_id, listing_id)
);

-- -----------------------------------------------
-- ORDERS
-- -----------------------------------------------
CREATE TABLE orders (
    order_id    INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id    INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status      ENUM('pending','processing','shipped','delivered') DEFAULT 'pending',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- -----------------------------------------------
-- ORDER ITEMS
-- -----------------------------------------------
CREATE TABLE order_items (
    item_id     INT AUTO_INCREMENT PRIMARY KEY,
    order_id    INT NOT NULL,
    listing_id  INT NOT NULL,
    quantity    INT NOT NULL,
    unit_price  DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(order_id)     ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE CASCADE
);

-- -----------------------------------------------
-- WISHLIST
-- -----------------------------------------------
CREATE TABLE wishlist (
    wishlist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    listing_id  INT NOT NULL,
    added_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES users(user_id)       ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist_item (user_id, listing_id)
);

-- ============================================================
-- SAMPLE DATA
-- ============================================================

-- Admin account (password: admin123)
INSERT INTO users (username, email, password_hash, role) VALUES
('admin', 'admin@pokemart.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Trainer accounts (password: password)
INSERT INTO users (username, email, password_hash, role) VALUES
('AshKetchum',  'ash@pokemart.com',  '$2y$10$TKh8H1.PfbuNauTTalJMFO2bGa9OaBIFerZ2RFJFY/HHkOCCQ0Bla', 'trainer'),
('MistyWater',  'misty@pokemart.com','$2y$10$TKh8H1.PfbuNauTTalJMFO2bGa9OaBIFerZ2RFJFY/HHkOCCQ0Bla', 'trainer'),
('BrockRock',   'brock@pokemart.com','$2y$10$TKh8H1.PfbuNauTTalJMFO2bGa9OaBIFerZ2RFJFY/HHkOCCQ0Bla', 'trainer');

-- Pokémon catalogue
INSERT INTO pokemon (name, type_primary, type_secondary, hp, attack, defense, speed, rarity, image_url, description) VALUES
('Pikachu',    'Electric', NULL,      35, 55, 40, 90, 'Rare',      'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/other/official-artwork/25.png',   'The iconic Electric Mouse Pokémon. Its cheeks store electricity.'),
('Charizard',  'Fire',     'Flying',  78, 84, 78, 100,'Epic',      'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/other/official-artwork/6.png',    'A fearsome Fire/Flying type that soars the skies.'),
('Blastoise',  'Water',    NULL,      79, 83, 100, 78,'Epic',      'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/other/official-artwork/9.png',    'A powerful Water-type with cannons on its shell.'),
('Mewtwo',     'Psychic',  NULL,      106,110,90, 130,'Legendary', 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/other/official-artwork/150.png',  'A genetically engineered Legendary Pokémon of immense power.'),
('Gengar',     'Ghost',    'Poison',  60, 65, 60, 110,'Rare',      'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/other/official-artwork/94.png',   'A Shadow Pokémon that lurks in dark corners.'),
('Lucario',    'Fighting', 'Steel',   70, 110,70, 90, 'Epic',      'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/other/official-artwork/448.png',  'Masters Aura to sense and control energy.'),
('Bulbasaur',  'Grass',    'Poison',  45, 49, 49, 45, 'Common',    'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/other/official-artwork/1.png',    'A dual Grass/Poison starter with a seed on its back.'),
('Squirtle',   'Water',    NULL,      44, 48, 65, 43, 'Common',    'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/other/official-artwork/7.png',    'A tiny Water-type that hides in its shell.'),
('Eevee',      'Normal',   NULL,      55, 55, 50, 55, 'Rare',      'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/other/official-artwork/133.png',  'An Evolution Pokémon with unstable genetic makeup.'),
('Snorlax',    'Normal',   NULL,      160,110,65, 30, 'Epic',      'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/other/official-artwork/143.png',  'A massive Pokémon that sleeps most of the day.'),
('Dragonite',  'Dragon',   'Flying',  91, 134,95, 80, 'Legendary', 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/other/official-artwork/149.png',  'A friendly Dragon type said to bring good fortune.'),
('Umbreon',    'Dark',     NULL,      95, 65, 110,65, 'Rare',      'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/other/official-artwork/197.png',  'A Moonlight Pokémon that evolved under moonlight.');

-- Sample listings
INSERT INTO listings (seller_id, pokemon_id, title, description, price, stock) VALUES
(2, 1,  'Shiny Pikachu — Level 50',        'My beloved Pikachu, trained to perfection. Fast shipping!',               250.00, 1),
(2, 2,  'Charizard — Competitive Ready',   'Fully EV trained Charizard. Knows Flamethrower, Fly, Dragon Claw.',       999.00, 1),
(3, 3,  'Blastoise Tank Build',            'Defensive Blastoise with max Defense EVs. Great for ranked battles.',     750.00, 2),
(3, 5,  'Haunted Gengar',                  'Spooky Gengar perfect for Ghost-type lovers. Knows Shadow Ball.',          400.00, 3),
(4, 7,  'Starter Bulbasaur',               'Perfect for beginners. Gentle nature, easy to train.',                      80.00, 5),
(4, 8,  'Cute Squirtle',                   'Adorable Squirtle with Modest nature. Great for Water-type teams.',          75.00, 4),
(2, 9,  'Rare Eevee — Multiple Evolutions','Eevee ready to evolve into your favourite Eeveelution!',                   300.00, 2),
(3, 6,  'Lucario — Aura Master',           'Powerful Lucario with Close Combat and Aura Sphere. Tournament-ready.',    850.00, 1),
(4, 10, 'Snorlax — Heavy Hitter',          'A mighty Snorlax. Perfect for defensive teams. Very relaxed nature.',      600.00, 1),
(2, 11, 'Dragonite — Legendary Quality',   'Rare Dragonite with Multiscale ability. One of a kind find.',             1200.00, 1),
(3, 12, 'Umbreon — Moonlit Beauty',        'Graceful Umbreon with perfect IVs in HP and Defense.',                    500.00, 2),
(4, 4,  'Mewtwo — Ultimate Power',         'The rarest of listings. Psychic powerhouse. Handle with care.',           5000.00, 1);
