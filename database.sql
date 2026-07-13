-- =========================================================
-- Dhaa Baja | Ancestral Rhythms
-- Full database schema + seed data
-- Import with: mysql -u root -p < database.sql
-- =========================================================

DROP DATABASE IF EXISTS dhaa_baja;
CREATE DATABASE dhaa_baja CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dhaa_baja;

-- ---------------------------------------------------------
-- Users (site members + admins share one table via `role`)
-- ---------------------------------------------------------
CREATE TABLE users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name      VARCHAR(80)  NOT NULL,
    last_name       VARCHAR(80)  NOT NULL,
    email           VARCHAR(150) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    role            ENUM('user','admin') NOT NULL DEFAULT 'user',
    status          ENUM('active','suspended') NOT NULL DEFAULT 'active',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- PHP sessions (database-backed so login/CSRF work correctly on
-- serverless platforms like Vercel, where each request can hit a
-- different instance and local disk session files aren't shared).
-- ---------------------------------------------------------
CREATE TABLE sessions (
    id              VARCHAR(128) PRIMARY KEY,
    data             MEDIUMTEXT NOT NULL,
    last_access      INT UNSIGNED NOT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Rhythm Categories (fully admin-managed, replaces the old ENUM)
-- ---------------------------------------------------------
CREATE TABLE categories (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    slug            VARCHAR(120) NOT NULL UNIQUE,
    description     VARCHAR(255) DEFAULT NULL,
    is_enabled      TINYINT(1) NOT NULL DEFAULT 1,
    visibility      ENUM('public','restricted') NOT NULL DEFAULT 'public',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Per-user override for a category: a row here means "force enabled" (enabled=1)
-- or "force disabled" (enabled=0) for that specific user, regardless of the
-- category's own default visibility. No row = fall back to the default.
CREATE TABLE category_access (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id     INT UNSIGNED NOT NULL,
    user_id         INT UNSIGNED NOT NULL,
    enabled         TINYINT(1) NOT NULL DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_category_user (category_id, user_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Rhythms (the "Library" catalogue of tracks)
-- ---------------------------------------------------------
CREATE TABLE rhythms (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(150) NOT NULL,
    slug            VARCHAR(170) NOT NULL UNIQUE,
    category_id     INT UNSIGNED DEFAULT NULL,
    description     TEXT,
    duration_seconds INT UNSIGNED DEFAULT 0,
    image_url       VARCHAR(500),
    audio_url       VARCHAR(500),
    sheet_data      LONGBLOB DEFAULT NULL,
    sheet_mime      VARCHAR(100) DEFAULT NULL,
    sheet_original_name VARCHAR(255) DEFAULT NULL,
    is_featured     TINYINT(1) NOT NULL DEFAULT 0,
    is_enabled      TINYINT(1) NOT NULL DEFAULT 1,
    visibility      ENUM('public','restricted') NOT NULL DEFAULT 'public',
    play_count      INT UNSIGNED NOT NULL DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Per-user override for a rhythm: enabled=1 forces it ON for that user,
-- enabled=0 forces it OFF for that user, no row = fall back to the default.
CREATE TABLE rhythm_access (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rhythm_id       INT UNSIGNED NOT NULL,
    user_id         INT UNSIGNED NOT NULL,
    enabled         TINYINT(1) NOT NULL DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rhythm_id) REFERENCES rhythms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_rhythm_user (rhythm_id, user_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Events (Gatherings & Echoes / Events page)
-- ---------------------------------------------------------
CREATE TABLE events (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(150) NOT NULL,
    category        ENUM('Workshop','Live Performance','Community Circle','Exhibition') NOT NULL DEFAULT 'Workshop',
    description     TEXT,
    event_date      DATE NOT NULL,
    event_time      TIME DEFAULT NULL,
    location        VARCHAR(200),
    price           DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    is_free         TINYINT(1) NOT NULL DEFAULT 0,
    capacity        INT UNSIGNED DEFAULT NULL,
    image_url       VARCHAR(500),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- RSVPs / Reservations for events
-- ---------------------------------------------------------
CREATE TABLE rsvps (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id        INT UNSIGNED NOT NULL,
    user_id         INT UNSIGNED DEFAULT NULL,
    name            VARCHAR(150) NOT NULL,
    email           VARCHAR(150) NOT NULL,
    status          ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'confirmed',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Purchases / Downloads of rhythms (kept for historical logging)
-- ---------------------------------------------------------
CREATE TABLE purchases (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rhythm_id       INT UNSIGNED NOT NULL,
    user_id         INT UNSIGNED DEFAULT NULL,
    email           VARCHAR(150) NOT NULL,
    amount          DECIMAL(8,2) NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rhythm_id) REFERENCES rhythms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Newsletter subscribers
-- ---------------------------------------------------------
CREATE TABLE newsletter_subscribers (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email           VARCHAR(150) NOT NULL UNIQUE,
    subscribed_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================================
-- SEED DATA
-- =========================================================

-- Default admin login: admin@dhaabaja.com / Admin@12345
-- Default user login:  guest@dhaabaja.com / Guest@12345
INSERT INTO users (first_name, last_name, email, password_hash, role) VALUES
('Heritage', 'Admin', 'admin@dhaabaja.com', '$2y$10$YOabnoio3AHN9vR01c5A2uLOq8dGY20QBnj.UHhw7F7LszCKRWZ2C', 'admin'),
('Guest', 'User', 'guest@dhaabaja.com', '$2y$10$RuKjWIUvVKadlNutMTLNDexIk.ShWfZ7UGme01Xkctizb6zUpydoe', 'user');

INSERT INTO categories (name, slug, description, is_enabled, visibility) VALUES
('Ceremonial', 'ceremonial', 'Ritual and temple rhythms.', 1, 'public'),
('Folk', 'folk', 'Traditional community and labor rhythms.', 1, 'public'),
('Fusion', 'fusion', 'Modern reinterpretations blending old and new.', 1, 'public'),
('Ambient', 'ambient', 'Slow, atmospheric textures.', 1, 'public');

INSERT INTO rhythms (title, slug, category_id, description, duration_seconds, image_url, is_featured, play_count) VALUES
('The Dawn Prayer Rhythm', 'dawn-prayer-rhythm', 1, 'A complex 16-beat cycle traditionally played at the stroke of sunrise in the Himalayan temples, designed to align the spirit with the day''s first light.', 525, 'https://lh3.googleusercontent.com/aida-public/AB6AXuD-quB-7usSAQDZr507QajHbr2tMlKa-IA0FLT7SmmJ7KjaQkn_ImUHsIipl_4MEE1rQsXgVBFgJkrD6xiEROkArE6641_LLHL9ZOu-euCkJLDYjuux4qJ9gwnApghsMU6FKaU3fexdmNOeTx4R5iQzqM7oThTAVVqn6hJ6Z_Dic-xa-PgjZ3bABWou76KitA2aU87SbERjqwmb9mkI-dCYnzKJGOTdDzo4CaGwYUoU-re-onjXMgD_yPobWhhU6odqiyqur5-broA', 1, 245),
('Harvest Echoes', 'harvest-echoes', 2, 'Energetic 6/8 patterns traditionally used to synchronize labor in the fields.', 260, 'https://lh3.googleusercontent.com/aida-public/AB6AXuBKGs0eZRaxkLsDJ-3wHM2tzMqWDU7qG1zpw_G-5hQG6UmYkAMFb8Tzq9fqY4Ce7OOu7hSEpgQ89h0H_SZY8RX6OVGLijeeBYdOiDD3pnif7yqjJJI7CvJCQn7SQEjKgiziU6qk3eF4H0ddx5UqgR6Yv78tOV55ivsn3agIqZf1mt-19rEHuNcBY8rxH4cr8ydpvarQdZ-qok4QLoHUMv1yqDfoFnFyMZwvcwOKabxR1uAxbKhwd_43ohaf88RgpVhx6eQXz4uEgQI', 1, 132),
('Monsoon Thrum', 'monsoon-thrum', 4, 'A slow, atmospheric pattern that mirrors the falling rhythm of monsoon rains.', 300, NULL, 0, 88),
('Vajrayana Pulse', 'vajrayana-pulse', 1, 'A rhythmic foundation for high-altitude meditation and ritual processions.', 340, NULL, 1, 190),
('Street Resonator', 'street-resonator', 3, 'A contemporary blend of Dhaa textures with modern electronic syncopation.', 275, NULL, 0, 310);

INSERT INTO events (title, category, description, event_date, event_time, location, price, is_free, capacity, image_url) VALUES
('Kathmandu Resonance', 'Live Performance', 'An open-air percussion showcase in the heart of Patan Durbar Square.', '2026-10-12', '18:00:00', 'Patan Durbar Square, Nepal', 25.00, 0, 200, 'https://lh3.googleusercontent.com/aida-public/AB6AXuDmq6dkD_6AgQN0NwJVMWyBgsAZTVmkLwuFp8_M0XKHjyydlWOuvn5ZjQFf1f4yghPTKHhIaNAH6vzF5jf-Q0XjDFAumDgJah8m8TAdnDI12KVDlMW89Dz-A8S9CAY3M00L3Jo0OsA1R_2YeOnvXBE4lP8GoM-69nqMXS0fckbh4_2VcZ-jgox4pPBeiyEY_Ypgdw3e8LKOmCY6CLLohz_tvC6N5GF6NGlnLZuUJRzgA0sgB-EvFME43sPigDCPDwM31c79P49Ir2M'),
('Rhythmic Archeology', 'Workshop', 'A livestreamed masterclass tracing the origins of Himalayan percussion notation.', '2026-10-28', '10:00:00', 'Digital Streaming', 40.00, 0, 500, NULL),
('Metals & Membranes', 'Exhibition', 'An exhibition exploring the metallurgy and skinwork behind the Dhaa Baja.', '2026-11-05', '11:00:00', 'Museum of Sound', 0.00, 1, 150, NULL),
('Sacred Woodcarving & Resonator Craft', 'Workshop', 'A 3-day immersive masterclass on the geometry and physics of the Dhaa Baja resonator.', '2026-10-14', '09:00:00', 'Bhaktapur Heritage Square, Nepal', 120.00, 0, 30, NULL),
('Full Moon Rhythm & Meditation', 'Community Circle', 'Open to all skill levels. Bring your own instrument or use one of our house resonators.', '2026-11-15', '19:30:00', 'Central Park Conservatory, NYC', 0.00, 1, 80, NULL);

INSERT INTO newsletter_subscribers (email) VALUES
('early.listener@example.com'),
('rhythm.fan@example.com');
