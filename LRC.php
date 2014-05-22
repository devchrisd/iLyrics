<?php
    date_default_timezone_set('America/Toronto');

    // get current playing / selected song
    $song = "NineMillionBicycles.mp3";
    $song = "test.mp3";
?>

<html>
<head>
    <title>iLyrics</title>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
    
    <script type="text/javascript" src='script/jquery-2.1.0.js'></script>
    <script type="text/javascript" src="script/jquery-ui-1.10.4.custom.min.js"></script>

    <script language = "javascript" src="script/lrc.js"></script>
    
    <link rel="stylesheet" type="text/css" href="css/common.css">
</head>

<body>
    <div id='div_song'>
        <audio id='song' src="audio/<?=$song?>" controls></audio>
    </div>
    <div id='lyrics'>Fetching lyrics ...</div> 

</body>
</html>