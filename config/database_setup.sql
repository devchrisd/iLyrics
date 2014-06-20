CREATE DATABASE IF NOT EXISTS `media` default CHARACTER SET utf8;

USE `media`;

CREATE TABLE IF NOT EXISTS `song` (
  `s_id` SMALLINT(5) unsigned NOT NULL auto_increment,
  `song_file` varchar(250) NOT NULL DEFAULT '',
  `title` varchar(128) NOT NULL DEFAULT '',
  `album` varchar(128) NOT NULL DEFAULT '',
  `artist` varchar(128) NOT NULL DEFAULT '',
  `year` varchar(18) NOT NULL DEFAULT '',
  `genre` varchar(60) NOT NULL DEFAULT '',
  `lyrics_file` varchar(256) NOT NULL DEFAULT '',
  `cover_file`  varchar(256) NOT NULL DEFAULT '',
  PRIMARY KEY  (`s_id`),
  UNIQUE INDEX `idx_song_file` (`song_file`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
