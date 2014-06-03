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
 - Mac OSX 10.9.3
 - Apache 2.2.26
 - MySQL 5.6.16
 - PHP 5.4.24,
 - getID3 from https://github.com/JamesHeinrich/getID3
 // *REMOVED* - PEAR/PECL with ID3 installed


USAGE:

- Put mp3 files in folder "audio/"

- Lyrics will be saved in "lyric/"
    - Apache or the webserver user/group must have permission to create/modify file in this folder
    - Lyrics file name has the format of ARTIST_SONG.lrc (with no space)
        If this file is not availble, it will try the name SONG.lrc. 
        If this name SONG.lrc is available, and artist name is known, it will rename SONG.lrc to ARTIST_SONG.lrc.

    - If lyrics is not available locally, it will try to search and download online.

- Build up database with sql script in config/database_setup.sql

