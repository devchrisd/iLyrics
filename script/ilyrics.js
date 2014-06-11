var ele_song;   // element contains the song
var ele_lyrics; // element contains the lyrics
var play_index;   // current position in playlist
var loop;

$(function()
{
    $(window).load(function ()
    {
        ele_song    = $('#player');
        ele_lyrics  = $('#lyrics');
        loop        = true;
        $('#loop_status').text('On');
        // ele_song.draggable();
        // ele_lyrics.draggable();
        getMp3();
    });

/*
    (1) $(document).on('event', 'selector', handler) works for dynamically created elements, 
    (2) $('selector').on('event', handler) will not work for dynamic delegation.
     
    Should $(document).on() be used for everything?
      It will work but if you don't need the dynamic delegation, it would be more appropriate to use (2) because (1) requires slightly more work from the browser. There won't be any real impact on performance but it makes sense to use the most appropriate method for your use.

    To remove events bound with .on(), see .off(). 
    To attach an event that runs only once and then removes itself, see .one()
*/
    // add song to playlist
    $(document).on('click', '.add_song', function(){
        // add to playlist
        $('#playlist').append(
                                "<li><span class='play_song' s_id='" + $(this).attr('s_id') + "' " + 
                                "path='" + $(this).attr('path') + "'>" + $(this).text() + 
                                "</span><img class='remove' alt='X' /></li>"
                            );
    });

    // remove from list
    $(document).on('click', '.remove', function(){

        if ($(this).parent().index() == $('.current_song').parent().index())
        {
            alert('cannot remove current_song.');
            return;
        }

        $(this).parent().detach();
    });

    $(document).on('click', '.play_song', function(){

        if ($('.current_song')[0])
        {
            if ($(this).index('.play_song') == $('.current_song').index('.play_song'))
            {
                return;     // no change needed
            }

            $('.current_song').removeClass('current_song');
        }
        $(this).addClass('current_song');

        ele_song.empty()
                .attr('src', $(this).attr('path') + '/' + $(this).text())
                .attr('s_id', $(this).attr('s_id'))
                .appendTo(ele_song);

        getLyrics($(this).attr('s_id'));
    });

    $(document).on('click', '.tag', function(){
        // pop up id3 info
        var url = "get_id3.php?id=" + $(this).parent().attr('s_id');

        // get id3 in json
        $.getJSON(url, function (id3)
        {
            if(id3)
            {
                // show id3
                $.each(id3, function(key, val){
                log('got id3: ' + key + ':' + val);

                //$('#filelist').append("<li class='add_song' id='" + i + "'>" + val + "<img class='tag' alt='Tag' /></li>");
            });
            }
        });
    });

    // play next song in playlist
    $('#player').on('ended', function(){
        play_index = $('.current_song').index('.play_song');
        play_list_len = $('.play_song').length - 1;

        next_play = false;
        if ( play_list_len > 0 && play_index < play_list_len)
        {
            next_play = play_index+1;
        }
        else if (loop === true)
        {
            next_play = 0;
            $('.current_song').removeClass('current_song');     // let loop work if there is only 1 song in playlist
        }

        if (next_play !== false)
        {
            $('.play_song').slice(next_play,next_play+1).trigger('click');
        }
    });

    $('.loop').on('click', function(){
        var status = 'On';

        loop = !loop;
        if (loop === false)
        {
            status = 'Off';
        }
        $('#loop_status').text(status);

    });
});

// Get lyrics from server
function getMp3 ()
{
    var url = "get_mp3.php";

    // get mp3 file list in json
    $.getJSON(url, function (mp3)
    {
        // log('result :', mp3);

        if (mp3)
        {
            $.each(mp3, function(i, val){
                var pos = val.file.lastIndexOf("/");
                // var url = "get_lyrics.php?id=" + ele_song.attr('src').substring(pos+1);

                path = val.file.substr(0, pos);
                file = val.file.substr(pos+1);

                $('#filelist').append("<li class='add_song' s_id='" + val.s_id + "'" + 
                    "path='" + path + "''>" + file + "<img class='tag' alt='Tag' /></li>");
            });

            // default initial action:
            // - load first song
            ele_song.empty()
                    .attr('src', mp3[0]['file'])
                    .attr('s_id', mp3[0]['s_id'])
                    .appendTo(ele_song);

            // - add first song to playlist
            $('#filelist>li:first').trigger( "click" );
            $('.play_song:first()').trigger('click');
        }
    });

    return;
}

// Get lyrics from server
function getLyrics (s_id)
{
    // var pos = ele_song.attr('src').lastIndexOf("/");
    // attach song_file name to the query
    // var url = "get_lyrics.php?id=" + ele_song.attr('src').substring(pos+1);
    var url = "get_lyrics.php?id=" + s_id;

    log('url', url);

    // get lyrics in json
    $.getJSON(url, function (json)
    {
        // log('result :', json);

        if (json)
        {
            setCover(json.cover);
            // show lyrics
            lrc.start(ele_song, ele_lyrics, json.lyrics);
        }
        else
        {
            lrc.reset();
            setLyrics('No lyrics available.');
        }
    });
    return;
/*
    $.ajax(url)
    .done( function(data)
    {
        // log( "Sample of data: ", data.slice( 0, 100 ) );
        data = $.parseJSON(data);
        log( "Sample of data: ", data );

        // show lyrics
        lrc.start(ele_song, ele_lyrics, data);

    })
    .always( function()
    {

        setLyrics('Fetching lyrics ...');

    })
    .fail( function()
    {

        setLyrics('No lyrics available.');

    });
*/
}

function setLyrics(lyrics)
{
    ele_lyrics.html(lyrics);
}

function setCover(cover)
{
    if (cover && cover.length > 0)
    {
        // show cover
        $('#artist_avatar').attr('src', cover);
    }
    else
    {
        // replace with default
        $('#artist_avatar').attr('src', 'images/default.jpg');
    }
}
function getCover(s_id)
{
    // var pos = ele_song.attr('src').lastIndexOf("/");
    // attach song_file name to the query
    // var url = "get_lyrics.php?id=" + ele_song.attr('src').substring(pos+1);
    var url = "get_cover.php?id=" + s_id;

    // get lyrics in json
    $.getJSON(url, function (json)
    {
        // log('result :', json);

        if (json && json.length > 0)
        {
            // show cover
            $('#artist_avatar').attr('src', json);
        }
        else
        {
            // replace with default
            $('#artist_avatar').attr('src', 'images/default.jpg');
        }
    });
    return;
}
function log()
{
    //    var debug = false;
    var debug = true;
    if ( debug && console && console.log )
    {
        for(var i=0; i<arguments.length; i++)
        {
            console.log( arguments[i] );
        }
    }
}
