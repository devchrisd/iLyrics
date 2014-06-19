<?php

if (isset($_GET['s_id']) === true && empty($_GET['s_id']) !== true)
    $s_id =  $_GET['s_id'];
else
    exit;

require_once('lib/audio/mp3_lib.class.php');

$title = $artist = $album = $year = $genre = '';

if (isset($_GET['title']) === true && empty($_GET['title']) !== true)
    $title =  $_GET['title'];

if (isset($_GET['artist']) === true && empty($_GET['artist']) !== true)
    $artist =  $_GET['artist'];

if (isset($_GET['album']) === true && empty($_GET['album']) !== true)
    $album =  $_GET['album'];

if (isset($_GET['year']) === true && empty($_GET['year']) !== true)
    $year =  $_GET['year'];

if (isset($_GET['genre']) === true && empty($_GET['genre']) !== true)
    $genre =  $_GET['genre'];

$result = mp3_lib::set_song_id3(
            $s_id,
            $title,
            $artist,
            $album,
            $year,
            $genre
            );
echo json_encode($result);

return;