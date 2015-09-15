CREATE DATABASE IF NOT EXISTS `media` default CHARACTER SET utf8;

USE `media`;

CREATE TABLE IF NOT EXISTS `song` (
  `s_id` int(5) unsigned NOT NULL AUTO_INCREMENT,
  `song_file` varchar(250) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `title` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `album` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `artist` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `year` varchar(38) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `genre` varchar(60) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `lyrics_file` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `cover_file` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`s_id`),
  UNIQUE KEY `idx_song_file` (`song_file`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


CREATE TABLE IF NOT EXISTS `playlist` (
  `p_id` int(5) unsigned NOT NULL AUTO_INCREMENT,
  `p_name` varchar(255) NOT NULL DEFAULT '',
  `user_id` int(5) NOT NULL DEFAULT '0',
  PRIMARY KEY (`p_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `playlist_item` (
  `pi_id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `p_id` int(5) unsigned NOT NULL DEFAULT '0',
  `s_id` int(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`pi_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
