Testing Environment:
 MAMP
 - Mac OSX 10.9.3
 - Apache 2.2.26
 - MySQL 5.6.16
 - PHP 5.4.24, 
 - PEAR/PECL with ID3 installed



1. Build up database with sql script in config/database_setup.sql

2. Put mp3 files in folder "audio/"

3. Lyrics will be saved in "lyric/"
    - Apache or the webserver user/group must have permission to create/modify file in this folder
    - Lyrics file name has the format of ARTIST_SONG.lrc (with no space)
        If this file is not availble, it will try the name SONG.lrc. 
        If this name SONG.lrc is available, and artist name is known, it will rename SONG.lrc to ARTIST_SONG.lrc.

    - If lyrics is not available locally, it will try to search and download online.



