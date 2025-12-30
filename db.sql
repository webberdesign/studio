/* PAGE NAME: db.sql
   SECTION: Schema
-----------------------------------------*/

CREATE TABLE tb_admin_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL
);

INSERT INTO tb_admin_users (username, password_hash)
VALUES ('tbadmin', SHA2('changeme123', 256));

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
  body TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (post_id) REFERENCES tb_feed_posts(id) ON DELETE CASCADE
);

/*
 * Video comments for in-production videos.
 */
CREATE TABLE tb_video_comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  video_id INT NOT NULL,
  author_name VARCHAR(100) DEFAULT NULL,
  body TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (video_id) REFERENCES tb_videos(id) ON DELETE CASCADE
);

/*
 * Song comments for unreleased tracks.
 */
CREATE TABLE tb_song_comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  song_id INT NOT NULL,
  author_name VARCHAR(100) DEFAULT NULL,
  body TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (song_id) REFERENCES tb_songs(id) ON DELETE CASCADE
);

/*
 * App open logs.
 */
CREATE TABLE tb_app_opens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  opened_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
