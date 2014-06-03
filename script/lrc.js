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
                                "<li><span class='play_song'>" + 
                                $(this).text() + 
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
                .attr('src', 'audio/' + $(this).text())
                .appendTo(ele_song);

        getLyrics();
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
            $('.current_song').removeClass('current_song');
        }

        if (next_play !== false)
        {
        log(' next_play = ' + next_play);

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
        log('result :', mp3);

        if (mp3)
        {
            $.each(mp3, function(i, val){
                $('#filelist').append("<li class='add_song' id='" + i + "'>" + val + "</li>");
            });

            // load first song
            ele_song.empty()
                    .attr('src', 'audio/' + mp3[0])
                    .appendTo(ele_song);

            // add first song to playlist
            $('#filelist>li:first').trigger( "click" );
            $('.play_song:first()').trigger('click');
        }
    });

    return;
}

// Get lyrics from server
function getLyrics ()
{
    var pos = ele_song.attr('src').lastIndexOf("/");
    // attach song_file name to the query
    var url = "get_lyrics.php?id=" + ele_song.attr('src').substring(pos+1);
    log('url', url);

    // get lyrics in json
    $.getJSON(url, function (json)
    {
        // log('result :', json);

        if (json && json.length > 0)
        {
            // show lyrics
            lrc.start(ele_song, ele_lyrics, json);
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

// object to show KaraOK style lyrics
var lrc = {

    init: true, // first time show this lyrics
    offset: 0,  // time offset from lrc file

    elementSong: null,
    elementLyrics: null,

    scrollh: 0,
    scrollInterval: 10,

    index: 0,   // for lytext and lytime
    currentLine: 0,     // lyrics line number of the current position
    lytext: new Array(),// lyrics text
    lytime: new Array(),// lyrics time
    lyricsPlayTimeout: null,

    reset: function()
    {
        this.init   = true;
        this.offset = 0;
        this.index  = 0;
        this.lytext = new Array();// lyrics text
        this.lytime = new Array();// lyrics time
        this.currentLine    = 0;
        this.scrollh = 0;

        // clear timer so it won't replace messages in lyrics block
        if (this.lyricsPlayTimeout !== null)
            clearTimeout(this.lyricsPlayTimeout);
    },

    // ele_song:   element, 
    // ele_lyrics: element
    // lrc_lyrics: lyrics content
    start: function(ele_song, ele_lyrics, lrc_lyrics)
    {
        if (ele_song.attr('src').length > 0)
        {
            this.reset();
            this.elementSong    = ele_song;
            this.elementLyrics  = ele_lyrics;
            this.processData(lrc_lyrics);
            // sort by show time
            this.sortAr();

            // add one more empty line at tail of array for easy processing in show()
            // this.index is the max value from this.processData
            this.lytext[this.index] = '';
            this.lytime[this.index] = this.lytime[this.index-1] + 5;

            this.scrollBar();
            this.setLyricsPlayTimeOut();
        }
    },

    // scroll lyrics to current position
    scrollBar: function ()
    {
        if ( this.isPlaying() === false )
        {
            window.setTimeout("lrc.scrollBar()",this.scrollInterval);
            return;
        }

        this.scrollh = this.currentLine * 25; // amount to scroll top. line-height:25px;

        if (this.elementLyrics.scrollTop() <= this.scrollh)
        {
            position = this.elementLyrics.scrollTop() + 1;
        }
        else if (this.elementLyrics.scrollTop() >= this.scrollh+50)  // padding-top: 50px
        {
            position = this.elementLyrics.scrollTop() - 5;
        }
        this.elementLyrics.scrollTop(position);

        window.setTimeout("lrc.scrollBar()",this.scrollInterval);
    },

    // get offset(time shift) from lyrics
    getOffset: function(data)
    {
        var offset = 0;

        //get offset: [offset:+/-100]
        if ( data.search(/\[offset:(\+|\-)\d+\]/ig) >= 0 )
        {
            var offset_line = data.match(/\[offset:(\+|\-)\d+\]/ig);
            if (offset_line[0].length > 0)
            {
                var sign = offset_line[0].match(/\+|\-/);
                offset = offset_line[0].match(/\d+/g);
                offset /= 100;
                if (sign == '-')
                    offset *= -1;
                log ("offset: " + offset);
            }
        }
        return offset;
    },

    // parsing the Lyrics 
    processData: function (data)
    {
        var arr_lyrics;
        var l_time,l_ww,i,ii;
        var sec, pos;

        // if offset is set in lyrics, get it.
        this.offset = this.getOffset(data);

        // parse lyrics into array by time line
        // data.split(/\r\n|\n/);
        // = inf.match(/([(d{2}:d{2}(.d{1,2}){0,1})]){1,}W*n|([(d{2}:d{2}:d{2}(.d{1,2}){0,1})]){1,}W*n/ig);
        // log("processing...", data);
        if (data.search(/(\[\d{2}:\d{2}(.\d{1,2})?\]){1,}(.*)|(\[\d{2}:\d{2}:\d{2}(.\d{1,2})?\]){1,}(.*)/ig) >= 0)
        {
            arr_lyrics = data.match(/(\[\d{2}:\d{2}(.\d{1,2})?\]){1,}(.*)|(\[\d{2}:\d{2}:\d{2}(.\d{1,2})?\]){1,}(.*)/ig);
        }

        if(! arr_lyrics || ! arr_lyrics.length)
        {
            return false;
        }

        // process each time line and separate time and text, save to lytime and lytext
        for(i=0; i<arr_lyrics.length ; i++)
        {
            // get time
            // 
            // Sometimes there are lines with 2 time setting.
            // the same lyrics is repeated in other time
            l_time = arr_lyrics[i].match(/(\[\d{2}:\d{2}(.\d{1,2})?\])|(\[\d{2}:\d{2}:\d{2}(.\d{1,2})?\])/ig);

            // get text only lyrics
            // l_ww = arr_lyrics[i].replace(/[\s+]/ig,'').replace(/n{1,}/ig,'');
            pos = arr_lyrics[i].lastIndexOf("]") + 1;
            l_ww = arr_lyrics[i].substring(pos);

            // fill time sequence to array this.lytime,
            // and fill lyrics to Array this.lytext
            // on each time setting of this line
            for(ii=0; ii<l_time.length ; ii++)
            {
                // remove '[' and ']'
                l_time[ii] = l_time[ii].replace(/\[/,'').replace(/\]/,'');
                sec = this.convert2Seconds(l_time[ii]);

                this.lytext[this.index] = l_ww;
                this.lytime[this.index] = sec + this.offset;
                this.index++;
            }
        }

        return true;
    },

    // sort lyrics array by time sequence
    sortAr: function ()
    {
        var temp = null;
        var temp1 = null;

        for(var k=0; k<this.lytime.length; k++)
        {
            for(var j=0; j<this.lytime.length-k; j++)
            {
                if(this.lytime[j] > this.lytime[j+1])
                {
                    temp = this.lytime[j];
                    temp1 = this.lytext[j];
                    this.lytime[j] = this.lytime[j+1];
                    this.lytext[j] = this.lytext[j+1];
                    this.lytime[j+1] = temp;
                    this.lytext[j+1] = temp1;
                }
            }
        }
    },

    // convert hh:mm:ss.ms into seconds
    convert2Seconds: function (t)
    {
        var h, m, s;
        var totalt = 0;

        if(t.search(/(\[\d{2}:\d{2}(.\d{1,2})?\])/g) )
        {
            h = 0;
            m = t.substring(0,t.indexOf(":"));
            s = t.substring(t.indexOf(":")+1);
        }
        else if (t.search(/(\[\d{2}:\d{2}:\d{2}(.\d{1,2})?\])/ig) )
        {
            h = t.substring(0,t.indexOf(":"));
            m = t.substring(t.indexOf(":")+1);
            s = t.substring(t.lastIndexOf(":")+1);
        }

        s = parseInt(s.replace(//b(0+)/gi,""
                ));
        if(isNaN(s)) s = 0;

        totalt = parseInt(h) * 60 * 60 + parseInt(m)*60 + s;

        if(isNaN(totalt)) return 0;

        return totalt;
    },

    // get current play position in seconds
    getcurrentTime: function ()
    {
        return this.elementSong[0].currentTime;
    },

    isPlaying: function ()
    {
        if (this.init === false &&
                (
                this.elementSong[0].ended ||
                this.elementSong[0].paused
                )
            )
        return false;
    },

    setLyricsPlayTimeOut: function()
    {
        this.lyricsPlayTimeout = window.setTimeout("lrc.lyricsPlay()",100);
        return;
    },

    // show lyrics
    lyricsPlay: function ()
    {
        if ( this.isPlaying() === false )
        {
            this.setLyricsPlayTimeOut();
            return;
        }

        this.show();
        this.init = false;

        this.setLyricsPlayTimeOut();
    },

    // fill lyrics into div
    show: function ()
    {
        var currentTime = parseInt(this.getcurrentTime());
        if(isNaN(currentTime)) currentTime = 0;

        this.elementLyrics.html(" "); // repaint

        // We appended an empty line at the end of lyrics
        // for the sake of [k+1] in 'this.lytime[k] <= currentTime && currentTime < this.lytime[k+1]'.
        // so the real text ends at this.lytext[this.lytext.length-2]
        for(var k=0; k<=this.lytext.length-2; k++)
        {
            // bold current line
            if(this.lytime[k] <= currentTime && currentTime < this.lytime[k+1])
            {
                this.currentLine = k;
                this.elementLyrics.append("<span class='current_line'>"+this.lytext[k]+"</span><br>");
                // log('current line: ' + k);
            }
            else
            {
                // normal lines
                this.elementLyrics.append(this.lytext[k]+"<br>");
            }
        }

        return;
    }

}
