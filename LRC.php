<html>
<head>
    <title>Songs with Lyrics</title>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
    
    <script type="text/javascript" src='jquery-2.1.0.js'></script>
    <script type="text/javascript" src="jquery-ui-1.10.4.custom.min.js"></script>

    <script language = "javascript" src="lrc.js"></script>
    
    <link rel="stylesheet" type="text/css" href="common.css">
</head>

<body>
<?php
    $song = "audio/test.mp3";
?>
    <div id='div_song'>
        <audio id='song' src="<?=$song?>" controls></audio>
    </div>
    <div id='lyrics'>歌词加载中……</div> 
</body>
</html>