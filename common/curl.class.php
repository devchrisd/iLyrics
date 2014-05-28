<?php

class curl_out
{
    private $post_data;
    private $response;
    private $response_meta_info;
    private $url;
    private $curl_send;

    function __construct($url, $request='', $curl_send='get')
    {
        $this->url       = $url;
        $this->curl_send = $curl_send;
        $this->post_data = $request;
        $this->response  = false;
    }

    function set_para($url, $request='', $curl_send='get')
    {
        $this->url       = $url;
        $this->curl_send = $curl_send;
        $this->post_data = $request;
        $this->response  = false;
    }

    function send_request()
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_TIMEOUT,           120);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,    60);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,    1);

        if ($this->curl_send == 'post')
            curl_setopt($ch, CURLOPT_POST,              1);
        else
            curl_setopt($ch, CURLOPT_HTTPGET,           1);


        curl_setopt($ch, CURLOPT_HEADER,            0);
//        curl_setopt($ch, CURLOPT_HEADER,            1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,        $this->post_data);
        curl_setopt($ch, CURLOPT_URL,               $this->url);

        //register a callback function which will process the headers
        //this assumes your code is into a class method, and uses $this->readHeader as the callback
        //function
        curl_setopt($ch, CURLOPT_HEADERFUNCTION,    array(&$this,'readHeader'));

        //Some servers (like Lighttpd) will not process the curl request without this header and will return error code 417 instead.
        //Apache does not need it, but it is safe to use it there as well.
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Expect:"));

        //Response will be read in chunks of 64000 bytes
        curl_setopt($ch, CURLOPT_BUFFERSIZE, 64000);

        $this->response = curl_exec($ch);

        //get the default response headers
        $headers = curl_getinfo($ch);

        debug( __METHOD__ . " header = ". print_r($headers,1));

        //add the headers from the custom headers callback function
        $this->response_meta_info = array_merge($headers, $this->response_meta_info);

        if (($errno = curl_errno($ch)) > 0)
        {
             print "ERROR: $errno: " . curl_error($ch) . "\n";
        }
        curl_close($ch);

        //catch the case where no response is actually returned
        //but curl_exec returns true (on no data) or false (if cannot connect)
        if (is_bool($this->response)) {
            if ($this->response === false){
                throw new Exception('No connection');
            } else {
                //false the response, because there are actually no data
                $this->response = false;
            }

        }
        debug(__METHOD__. ',response :' . print_r($this->response,1));

        return $this->response;
    }

    /**
     * CURL callback function for reading and processing headers
     * Override this for your needs
     *
     * @param object $ch
     * @param string $header
     * @return integer
     */
    private function readHeader($ch, $header)
    {
        debug( "readHeader, header = ". print_r($header,1));
        //extracting example data: filename from header field Content-Disposition
        $filename = $this->extractCustomHeader('Content-Disposition: attachment; filename=', '\n', $header);
        if ($filename) {
            $this->response_meta_info['content_disposition'] = trim($filename);
        }
        return strlen($header);
    }

    private function extractCustomHeader($start,$end,$header)
    {
        $pattern = '/'. $start .'(.*?)'. $end .'/';
        if (preg_match($pattern, $header, $result)) {
            return $result[1];
        } else {
            return false;
        }
    }

    function getHeaders()
    {
        return $this->response_meta_info;
    }
}