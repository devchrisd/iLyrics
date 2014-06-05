<html>
<head>
    <title>iLyrics</title>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
    
    <script type="text/javascript" src='script/jquery-2.1.0.js'></script>
    <script type="text/javascript" src="script/jquery-ui-1.10.4.custom.min.js"></script>

    <script language = "javascript" src="script/iLyrics.js"></script>
    <script language = "javascript" src="script/lrc.js"></script>
    
    <link rel="stylesheet" type="text/css" href="css/common.css">
    <link rel="stylesheet" type="text/css" href="css/lyrics.css">
</head>

<body>
<div class='container'>
    <header>
        <h1>iLyrics</h1>
    </header>

    <section class='container-left'>
        <div id='div_player'>
            <audio id='player' controls playbackrate='1.0' autoplay>
                <p>Your browser does not support the audio element.</p>
            </audio>
            <figure><img id='artist_avatar' alt='artist' src='images/dou.jpg' /></figure>
        </div>
        <div class='div-list'>
            <h3>
                Playlist
                <img class='loop' alt='O' src='images/loop-square_25.jpg' />
                <span id='loop_status'></span>
            </h3>
            <ul id='playlist' class='list'>
            </ul>
        </div>
    </section>

    <section class='container-right'>
        <div id='lyrics'>Fetching lyrics ...</div> 
        <div class='div-list'>
            <h3>Library</h3>
            <ul id='filelist' class='list'>

            </ul>
        </div>
    </section>

    <footer>
        2014 <a href='mailto:devchrisd@gmail.com'>Chris Ding</a>
    </footer>
</div>
</body>
</html>