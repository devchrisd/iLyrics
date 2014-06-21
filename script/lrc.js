
// object to show KaraOK style lyrics
var lrc = {

    init: true, // first time show this lyrics
    offset: 0,  // time offset from lrc file
    islrc: true,  // song has lrc style lyrics

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
        this.init       = true;
        this.offset     = 0;
        this.index      = 0;
        this.lytext     = new Array();// lyrics text
        this.lytime     = new Array();// lyrics time
        this.currentLine= 0;
        this.scrollh    = 0;
        this.islrc      = true;
        this.elementLyrics.empty();

        // clear timer so it won't replace messages in lyrics block
        if (this.lyricsPlayTimeout !== null)
            clearTimeout(this.lyricsPlayTimeout);
    },

    setLyrics: function(ele_lyrics, lrc_lyrics)
    {
        this.elementLyrics  = ele_lyrics;
        this.reset();
        this.elementLyrics.append(lrc_lyrics);
    },

    // ele_song:   element, 
    // ele_lyrics: element
    // lrc_lyrics: lyrics content
    start: function(ele_song, ele_lyrics, lrc_lyrics)
    {
        if (ele_song.attr('src').length > 0)
        {
            this.elementSong    = ele_song;
            this.elementLyrics  = ele_lyrics;
            this.reset();

            if (lrc_lyrics !== null && this.processData(lrc_lyrics))
            {
                // sort by show time
                this.sortAr();

                // add one more empty line at tail of array for easy processing in show()
                // this.index is the max value from this.processData
                this.lytext[this.index] = '';
                this.lytime[this.index] = this.lytime[this.index-1] + 5;
                if (this.islrc === true) this.scrollBar();
                this.setLyricsPlayTimeOut();
            }
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

        line_height = this.elementLyrics.css('line-height');
        line_height = parseInt(line_height.substring(0, line_height.lastIndexOf('p')));
        this.scrollh = this.currentLine * (line_height+1); // amount to scroll top. line-height:25px;
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
        var arr_lyrics = null;
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
            // the lyrics is not lrc-formatted
            this.lytext[this.index++] = '';
            this.lytime[this.index] = 0;
            this.lytext[this.index++] = data.replace(/\r\n|\n/g, '<br>');
            this.lytime[this.index] = 1;
            this.islrc = false;
            return true;
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
                this.elementLyrics.append("<span>" + this.lytext[k]+"</span><br>");
            }
        }

        return;
    }

}
