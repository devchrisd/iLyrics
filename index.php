<html>
<head>
    <title>iLyrics</title>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type">

    <script type="text/javascript" src='script/jquery-2.1.0.js'></script>
    <script type="text/javascript" src="script/jquery-ui-1.10.4.custom.min.js"></script>

    <script language = "javascript" src="script/ilyrics.js"></script>
    <script language = "javascript" src="script/lrc.js"></script>

    <link rel="stylesheet" type="text/css" href="css/common.css">
    <link rel="stylesheet" type="text/css" href="css/lyrics.css">
</head>

<body>
<div class='main'>
    <header class='top'>
        <h1>iLyrics</h1>
    </header>

    <div id='container'>
    <section class='container-left'>

        <div id='div-player'>
            <audio id='player' controls playbackrate='1.0' autoplay>
                <p>Your browser does not support the audio element.</p>
            </audio>
            <figure><img id='artist_avatar' alt='artist' src='' /></figure>
        </div>

        <div class='div-list'>
            <header>
                <h3>Playlist
                <span class='float_right_button'>
                <img class='show_pl' src='images/file_load.png' title='load playlist' alt='load' />
                <img class='save_pl' src='images/file_save.png' title='save playlist' alt='save' />
                <img id='loop_icon' class='loop' alt='O' src='images/loop_on.png' title='repeat on' />
                </span>
                </h3>
                <div id='div-pl'></div>
                <div id='div-save-pl'></div>

            </header>
            <ul id='playlist' class='list' p_id=''>
            </ul>
        </div>

        <div class='div-list'>
            <header>
                <h3>Library</h3>
            </header>
            <ul id='filelist' class='list'>
            </ul>
            <div id='div-tag'></div>
        </div>
    </section>

    <section class='container-right'>
        <div id='lyrics_edit' class='right' title='Edit lyrics'>[E]</div>
        <div id='lyrics'>Fetching lyrics ...</div>
        <form id='form-raw-lyrics' action='#'>
            <input type='hidden' id='lyrics_s_id' name='lyrics_s_id' value='' />
            <textarea id='raw_lyrics' name='raw_lyrics'></textarea> <br />
            <input type='submit' value='Save' />
        </form>
    </section>
    </div>

    <footer>
        <h4>2014 <a href='mailto:devchrisd@gmail.com'>Chris Ding</a></h4>
    </footer>
</div>
</body>
</html>