<?php

if (isset($_POST['s_id']) === true && empty($_POST['s_id']) !== true)
    $s_id =  $_POST['s_id'];
else
    exit;

require_once('lib/audio/mp3_lib.class.php');

$lyrics = '';

if (isset($_POST['lyrics']) === true && empty($_POST['lyrics']) !== true)
    $lyrics =  $_POST['lyrics'];

$result = mp3_lib::get_song_info($s_id);

$filename = $result['lyrics_file'];

$fp = fopen($filename, 'w');
fwrite($fp, $lyrics);
fclose($fp);

echo json_encode($filename);

return;