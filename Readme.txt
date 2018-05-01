FEATURE:
 - Get LRC lyrics from online resource and save as local file
 - show lyrics in KaraOK style
 - playlist: add and remove songs to playlist
 - use HTML5 audio element, no plugin required
 - can read and apply offset in .lrc file:
    format:
        [offset:300]    // delay 300ms
        or
        [offset:-300]   // shift ahead 300ms
 - loop playlist



Testing Environment:
 MAMP
 - Mac OSX 10.9.3 and above
 - Apache 2.2.26
 - MySQL 5.6.16
 - PHP 5.4.24 and above
 - getID3 from https://github.com/JamesHeinrich/getID3
    https://github.com/JamesHeinrich/getID3.git
 // *REMOVED* - PEAR/PECL with ID3 installed


USAGE:

- Put mp3 files in folder "music_resource/audio/"

- Album cover is saved in "music_resource/cover/"

- Lyrics will be saved in "music_resource/lyric/"
    - Lyrics file name has the format of ARTIST_SONG.lrc (with no space)
        If this file is not availble, it will try the name SONG.lrc.
        If this name SONG.lrc is available, and artist name is known, it will rename SONG.lrc to ARTIST_SONG.lrc.

    - If lyrics is not available locally, it will try to search and download online.


SETUP:

- Apache or the webserver user/group must have permission to create/modify file in music_resource/ (lyric/, audio/, and cover/ ). Use command:
    sudo chown www:www [dir]
- Build up database with sql script in setup/database_setup.sql
- create user 'developer' in mysql:
	mysql> create user 'developer'@'localhost' identified by 'ppp';
	mysql> grant all on media.* to 'developer'@'localhost';
	mysql> flush privileges;

- Run [sitename_iLyrics]/iLyrics_controller.php?action=lib_refresh to scan the music_resource audio / lyrics and cover folder to setup database



TODO:

- save playlist


