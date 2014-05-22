CREATE DATABASE IF NOT EXISTS `song`;

USE `song`;

CREATE TABLE IF NOT EXISTS `lyrics` (
  `l_id` SMALLINT(3) unsigned NOT NULL auto_increment,
  `title` varchar(128) NOT NULL DEFAULT '',
  `album` varchar(128) NOT NULL DEFAULT '',
  `artist` varchar(128) NOT NULL DEFAULT '',
  `year` SMALLINT(4) NOT NULL DEFAULT 0,
  `genre` char(20) NOT NULL DEFAULT '',
  `lyrics_path` varchar(128) NOT NULL DEFAULT '',
  `song_path`   varchar(128) NOT NULL DEFAULT '',
  PRIMARY KEY  (`l_id`)
) ENGINE=InnoDB DEFAULT CHARSET=ucs2;

