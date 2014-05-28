<?php
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
    <link rel="stylesheet" type="text/css" href="css/lyrics.css">
</head>

<body>
<div class='container'>
    <section class='container-left'>
        <header>
            <h1>iLyrics</h1>
        </header>

        <div id='div_player'>
            <audio id='player' src="" controls></audio>
            <figure><img id='artist_avatar' alt='artist' src='images/dou.jpg' /></figure>
        </div>
        <div id='lyrics'>Fetching lyrics ...</div> 
    </section>

    <section class='container-right'>
        <div class='list'>
            <ul id='filelist'>

            </ul>
        </div>

        <div class='list'>
            <ul id='playlist'>
            </ul>
        </div> 
    </section>

    <footer>
        2014 <a href='mailto:devchrisd@gmail.com'>Chris Ding</a>
    </footer>
</div>
</body>
</html>