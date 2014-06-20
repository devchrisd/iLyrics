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
                                "</span><img class='remove' alt='[X]' title='remove from list' /></li>"
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

    var tag_arr = ['title', 'artist', 'album', 'year', 'genre'];

    $(document).on('click', '.tag', function(){
        // pop up tag info
        var url = "get_tag.php?id=" + $(this).attr('s_id');

        // Note: jQuery UI does not support positioning hidden elements.
        $('#div-tag').html('');
        $('#div-tag').show();
        $("#div-tag").position({
            my:        "left top",
            at:        "right top",
            of:        $(this), // or $("#otherdiv)
            collision: "flipfit"
        });

        // get tag in json
        $.getJSON(url, function (tag)
        {
            if(tag)
            {
                $('#div-tag').html("<span class='right'>X</span><ul id='ul-tag'></ul><form id='form-tag' method='post' action='#'> </form>");
                $('#form-tag').append("<input type='hidden' name='tag_s_id' id='tag_s_id' value='" + tag['s_id'] + "' />");

                // show tag
                $.each(tag, function(key, val){
                    if ($.inArray(key, tag_arr) >= 0)
                    {
                        $('#div-tag ul').append("<li class='tag_item' id='" + key + "'>" + key + ' : ' + val + "</li>");
                        $('#form-tag').append('<span>' + key + '</span>' + '<input class="form_item" name="' + key + '" id="' + key + '" value="' + val + '" /><br />');
                    }
                });
                $('#form-tag').append("<input type='submit' id='submit' value='Save' />");
            }
            else
            {
                $('#div-tag').hide();
            }
        });
    });

    // hide div by clicking 'X'
    $(document).on('click', '#div-tag>.right', function()
    {
        $('#div-tag').hide();
        $('#div-tag').html('');
    });

    $(document).on('click', '.tag_item', function()
    {
        $('#div-tag ul').hide();
        // show form
        $('#form-tag').show();
    });

    $(document).on('submit', '#form-tag', function()
    {
        var url = "save_tag.php?";

        title  = $('#form-tag #title').val();
        artist = $('#form-tag #artist').val();
        album  = $('#form-tag #album').val();
        year   = $('#form-tag #year').val();
        genre  = $('#form-tag #genre').val();

        url += 's_id=' + $('#tag_s_id').val() + '&title=' + title + '&artist=' + artist + '&album=' + album + '&year=' + year + '&genre=' + genre;
        // log ('save tag ' + url);
        $.ajax(url)
        .done( function(data)
        {
            $('#div-tag>.right').click();
        });

        return false;
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

    $('#lyrics_edit').on('click', function(){
        $('#lyrics').toggle();
        $('#form-raw-lyrics').toggle();
    });

    $(document).on('submit', '#form-raw-lyrics', function()
    {
        var lyrics_url = "save_lyrics.php";

        lyrics_val = $('#raw_lyrics').val();

        // lyrics_data = 's_id=' + $('#lyrics_s_id').val() + '&lyrics=' + lyrics;
        log ('save lyrics ' + lyrics_url);
        $.ajax({
            url: lyrics_url,
            type: "POST",
            data: { s_id: $('#lyrics_s_id').val(), lyrics: lyrics_val}
        })
        .done( function(result)
        {
            log(result);
            lrc.start(ele_song, ele_lyrics, lyrics_val);
            $('#lyrics_edit').click();
        });

        return false;
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

                $('#filelist').append("<li><span class='add_song' s_id='" + val.s_id + "'" + "path='" + path + "'>" + file + "</span><img s_id='" + val.s_id + "' class='tag' alt='[Tag]' title='show/edit tags' /></li>");
            });

            // default initial action:
            // - load first song
            ele_song.empty()
                    .attr('src', mp3[0]['file'])
                    .attr('s_id', mp3[0]['s_id'])
                    .appendTo(ele_song);

            // - add first song to playlist
            $('#filelist span:first').trigger( "click" );
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
        hasLyrics = false;

        if (json)
        {
            setCover(json.cover);
            if (json.lyrics)
            {
                hasLyrics = true;
                // show lyrics
                lrc.start(ele_song, ele_lyrics, json.lyrics);

                $('#raw_lyrics').val(json.lyrics);
                $('#lyrics_s_id').val(s_id);
            }
        }

        if (hasLyrics === false)
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
