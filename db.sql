/* PAGE NAME: db.sql
   SECTION: Schema
-----------------------------------------*/

CREATE TABLE tb_admin_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  display_name VARCHAR(100) DEFAULT NULL
);

INSERT INTO tb_admin_users (username, password_hash, display_name)
VALUES ('tbadmin', SHA2('changeme123', 256), 'Admin');

CREATE TABLE tb_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  icon_path VARCHAR(255) DEFAULT NULL,
  unlock_pin VARCHAR(6) NOT NULL UNIQUE,
  theme VARCHAR(10) DEFAULT NULL,
  show_spotify TINYINT(1) DEFAULT NULL,
  show_apple TINYINT(1) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tb_user_devices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  device_token VARCHAR(64) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES tb_users(id) ON DELETE CASCADE
);

CREATE TABLE tb_user_push_subscriptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  onesignal_id VARCHAR(64) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES tb_users(id) ON DELETE CASCADE
);

CREATE TABLE tb_videos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  youtube_url VARCHAR(255) NOT NULL,
  is_released TINYINT(1) DEFAULT 0,
  position INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tb_songs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  mp3_path VARCHAR(255) DEFAULT NULL,
  cover_path VARCHAR(255) DEFAULT NULL,
  apple_music_url VARCHAR(255) DEFAULT NULL,
  spotify_url VARCHAR(255) DEFAULT NULL,
  is_released TINYINT(1) DEFAULT 0,
  position INT DEFAULT 0,
  collection_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

/*
 * Collections table to group songs into albums/collections.
 * Each collection has a title and optional cover art.
 */
CREATE TABLE tb_collections (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  cover_path VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

/*
 * Feed tables for social-style updates with media and comments.
 */
CREATE TABLE tb_feed_posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  author_name VARCHAR(100) DEFAULT NULL,
  body TEXT NOT NULL,
  youtube_url VARCHAR(255) DEFAULT NULL,
  video_path VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tb_feed_media (
  id INT AUTO_INCREMENT PRIMARY KEY,
  post_id INT NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  media_type ENUM('image','video') NOT NULL DEFAULT 'image',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (post_id) REFERENCES tb_feed_posts(id) ON DELETE CASCADE
);

CREATE TABLE tb_feed_comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  post_id INT NOT NULL,
  author_name VARCHAR(100) DEFAULT NULL,
  author_user_id INT DEFAULT NULL,
  body TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (post_id) REFERENCES tb_feed_posts(id) ON DELETE CASCADE,
  FOREIGN KEY (author_user_id) REFERENCES tb_users(id) ON DELETE SET NULL
);

/*
 * Video comments for in-production videos.
 */
CREATE TABLE tb_video_comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  video_id INT NOT NULL,
  author_name VARCHAR(100) DEFAULT NULL,
  author_user_id INT DEFAULT NULL,
  body TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (video_id) REFERENCES tb_videos(id) ON DELETE CASCADE,
  FOREIGN KEY (author_user_id) REFERENCES tb_users(id) ON DELETE SET NULL
);

/*
 * Song comments for unreleased tracks.
 */
CREATE TABLE tb_song_comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  song_id INT NOT NULL,
  author_name VARCHAR(100) DEFAULT NULL,
  author_user_id INT DEFAULT NULL,
  body TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (song_id) REFERENCES tb_songs(id) ON DELETE CASCADE,
  FOREIGN KEY (author_user_id) REFERENCES tb_users(id) ON DELETE SET NULL
);

/*
 * Collection comments for collection discussions.
 */
CREATE TABLE tb_collection_comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  collection_id INT NOT NULL,
  author_name VARCHAR(100) DEFAULT NULL,
  author_user_id INT DEFAULT NULL,
  body TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (collection_id) REFERENCES tb_collections(id) ON DELETE CASCADE,
  FOREIGN KEY (author_user_id) REFERENCES tb_users(id) ON DELETE SET NULL
);

/*
 * App open logs.
 */
CREATE TABLE tb_app_opens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  opened_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ,
  FOREIGN KEY (user_id) REFERENCES tb_users(id) ON DELETE SET NULL
);
