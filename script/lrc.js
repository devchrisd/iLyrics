var ele_song;   // element contains the song
var ele_lyrics; // element contains the lyrics

$(function()
{
    $(window).load(function ()
    {
        ele_song = $('#song');
        ele_lyrics = $('#lyrics');

        getMp3();

    });
});

$('#filelist>ul>li').click(function(){
    log('clicked');
    ele_song.empty()
            .attr('src', 'audio/' + $(this).text())
            .appendTo(ele_song);
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
                $('#filelist>ul').append("<li id='" + i + "'>" + val + "</li>");
            });

            ele_song.empty()
                    .attr('src', 'audio/' + mp3[0])
                    .appendTo(ele_song);
            //ele_song[0].load();
            log('song: ' + ele_song.attr('src'));

            getLyrics();
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
        log('result :', json);

        if (json)
        {
            // show lyrics
            lrc.start(ele_song, ele_lyrics, json);
        }
        else
        {
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
    // var ly = "[ti:Santa Claus Is Coming To Town][ar:Hilary Duff][al:Santa Claus Lane][offset:500][00:00.00]Hilary Duff - Santa Claus Is Coming To Town[00:02.15]Album: Santa Claus Lane[00:03.49][02:44.13](Santa Claus is coming)[00:11.71][02:47.35](Santa Claus is coming to town)[00:22.95][02:25.34]Oh! You better watch out,[00:24.59][02:27.21]You better not cry,[00:26.27][02:28.95]You better not pout,[00:27.78][02:30.40]I'm telling you why,[00:30.34][03:07.46]Santa Claus is coming to town,[00:43.71]He's making a list checkin it twice,[00:47.34]He's gonna find out whos naughty or nice,[01:04.22]He sees you when you're sleeping,[01:07.61]He knows when you're awake,[01:11.13]He knows when you've been bad or good,[01:14.30]So be good for goodness sake![01:17.81]Oh! You better watch out,[01:19.66]You better not cry,[01:21.31]You better not pout,[02:11.85]The kids in Girl and Boy Land[02:15.15]will have a jubilee.[02:18.69]They're gonna build a toyland town[02:22.21]all around the Christmas tree.[02:50.64][02:57.53](Santa Claus come, Santa Claus come, Santa Claus come, come, coming to town)[03:10.45][03:13.90]Santa Claus is coming (He's coming to town)[03:17.38]Santa Claus is coming,[03:21.78]He's coming to town.[03:26.37]<END>"
    // return ly;
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

    // ele_song:   element, 
    // ele_lyrics: element
    // lrc_lyrics: lyrics content
    start: function(ele_song, ele_lyrics, lrc_lyrics)
    {
        if (ele_song[0].currentSrc.length > 0)
        {
            this.init   = true;
            this.offset = 0;
            this.index  = 0;
            this.lytext = new Array();// lyrics text
            this.lytime = new Array();// lyrics time

            this.currentLine    = 0;
            this.elementSong    = ele_song;
            this.elementLyrics  = ele_lyrics;

            this.scrollh = 0;

            this.processData(lrc_lyrics);
            // sort by show time
            this.sortAr();

            // add one more empty line at tail of array for easy processing in show()
            // this.index is the max value from this.processData
            this.lytext[this.index] = '';
            this.lytime[this.index] = this.lytime[this.index-1] + 5;

            this.scrollBar();
            window.setTimeout("lrc.lyricsPlay()",100);
            // this.elementLyrics.draggable();
            // this.elementSong.draggable();
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

    // show lyrics
    lyricsPlay: function ()
    {
        if ( this.isPlaying() === false )
        {
            window.setTimeout("lrc.lyricsPlay()",100);
            return;
        }

        this.show();
        this.init = false;

        window.setTimeout("lrc.lyricsPlay()",100);
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
                log('current line: ' + k);
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
