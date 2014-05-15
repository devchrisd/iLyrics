<?php
    $song = "audio/test.mp3";
    $song = "audio/NineMillionBicycles.mp3";

    date_default_timezone_set('America/Toronto');
    require_once ('id3_tag.php');

    $tag = get_ID3($song);
?>

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
    <div id='div_song'>
        <audio id='song' src="<?=$song?>" controls></audio>
    </div>
    <div id='lyrics'>Fetching lyrics ...</div> 

    <div id='tag'>
<?php

    if ($tag !== null)
    {
        // echo '<h2>' . $tag['song'] .'</h2>';
        foreach ($tag as $tag_id => $value) 
        {
            echo "<input type='hidden' id='" . $tag_id . "' value='" . $value ."' />";
        }
    }
    // else
    //     echo 'no ID3 tag available';


    // check if Lyrics available locally, or try to fetch one.
?>
    </div>
</body>
</html>