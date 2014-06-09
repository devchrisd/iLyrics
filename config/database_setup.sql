CREATE DATABASE IF NOT EXISTS `media`;

USE `media`;

CREATE TABLE IF NOT EXISTS `song` (
  `s_id` SMALLINT(5) unsigned NOT NULL auto_increment,
  `filename` varchar(128) NOT NULL DEFAULT '',
  `song_file` varchar(256) NOT NULL DEFAULT '',
  `title` varchar(128) NOT NULL DEFAULT '',
  `album` varchar(128) NOT NULL DEFAULT '',
  `artist` varchar(128) NOT NULL DEFAULT '',
  `year` SMALLINT(4) unsigned NOT NULL DEFAULT 0,
  `genre` char(20) NOT NULL DEFAULT '',
  `lyrics_file` varchar(256) NOT NULL DEFAULT '',
  `cover_file`  varchar(256) NOT NULL DEFAULT '',
  PRIMARY KEY  (`s_id`),
  UNIQUE INDEX `idx_song_file` (`song_file`)
) ENGINE=InnoDB DEFAULT CHARSET=ucs2;
