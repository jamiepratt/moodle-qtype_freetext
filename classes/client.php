<?php

namespace qtype_freetext;

use core\session\exception;

class client {

    /**
     * Using this variable to cache the response for the server so that we can get the fraction in the question
     * grade_response method and the feedback in the renderer without repeating ws calls.
     *
     * @var array first index is ws question id, second index is student response.
     *
     */
    protected static $returned = array();


    /**
     * Get just the justification (mark) from the web service result.
     *
     * @param $questionid int web service question id
     * @param $response string student response
     * @return array zero based array of strings
     */
    public static function justification($questionid, $response) {
        $response = self::process_response_before_sending($response);
        return self::cache_returned_data($questionid, $response)->justification;
    }

    /**
     * Get just the fraction (mark) from the web service result.
     *
     * @param $questionid int web service question id
     * @param $response string student response
     * @return integer
     */
    public static function mark($questionid, $response) {
        $response = self::process_response_before_sending($response);
        return self::cache_returned_data($questionid, $response)->mark;
    }

    /**
     * Have free text web service grade student response.
     *
     * @param $questionid integer the ws question id specified in the question editing form.
     * @param $response
     * @return mixed
     */
    protected static function cache_returned_data($questionid, $response) {
        if (isset(self::$returned[$questionid])) {
            if (isset(self::$returned[$questionid][$response])) {
                return self::$returned[$questionid][$response];
            }
        } else {
            self::$returned[$questionid] = array();
        }
        self::$returned[$questionid][$response] = self::json_decode(self::send_ws_request($questionid, $response));
        return self::$returned[$questionid][$response];
    }

    /**
     * Use our own JSON decode routine so that if there are any problems with the JSON format we get a verbose human
     * readable error message.
     *
     * @param $json JSON string
     * @return mixed decoded JSON
     * @throws exception if there is an error throw an exception with a human readable description
     */
    protected static function json_decode($json) {
        $decodedcontent = json_decode($json);
        switch(json_last_error()){
            case JSON_ERROR_NONE:
                $error = '';
                break;
            case JSON_ERROR_DEPTH:
                $error = ' - Maximum stack depth exceeded';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $error = ' - Underflow or the modes mismatch';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $error = ' - Unexpected control character found';
                break;
            case JSON_ERROR_SYNTAX:
                $error = ' - Syntax error, malformed JSON';
                break;
            case JSON_ERROR_UTF8:
                $error = ' - Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
            default:
                $error = ' - Unknown error';
                break;
        }
        if (!empty($error)) {
            throw new exception('errorjson', $error);
        }
        return $decodedcontent;
    }

    /**
     * The method that actually does the ws call.
     *
     * @param $questionid
     * @param $response
     * @return mixed
     * @throws exception
     */
    protected static function send_ws_request($questionid, $response) {
        global $CFG;

        $url = get_config('qtype_freetext', 'wsurl');

        $postparams = array('q_id' => $questionid, 'answer' => $response);
        $postdata = json_encode($postparams);

        $curloptions = array(
            CURLOPT_FAILONERROR    => true,     // CURL should return an error if the http server
            // returns an error status code.
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER         => false,    // don't return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING       => "",       // handle all encodings
            CURLOPT_USERAGENT      => $CFG->wwwroot, // who am i
            CURLOPT_AUTOREFERER    => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
            CURLOPT_TIMEOUT        => 120,      // timeout on response
            CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
            CURLOPT_SSL_VERIFYPEER => true,     // Enable SSL Cert checks
            CURLOPT_POST           => 1,        // I am sending (JSON) post data
            CURLOPT_POSTFIELDS     => $postdata,
            CURLOPT_HTTPHEADER     => array('Content-Type: application/json',
                                            'Content-Length: ' . strlen($postdata))
        );

        $ch = curl_init($url);
        curl_setopt_array($ch, $curloptions);
        $content = curl_exec($ch);
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new exception('errorcurl', $error);
        }
        curl_close($ch);

        return $content;

    }

    /**
     * This is the place where we do any processing necessary of the response given by the student
     * before sending it off to the web service for grading.
     *
     * @param string $response from student
     * @return string to send to web service
     */
    protected static function process_response_before_sending($response) {
        $response = self::safe_normalize($response);
        $response = \core_text::strtolower($response);
        return $response;
    }

    /**
     * Normalise a UTf-8 string to FORM_C, avoiding the pitfalls in PHP's
     * normalizer_normalize function.
     * @param string $string the input string.
     * @return string the normalised string.
     */
    protected static function safe_normalize($string) {
        if ($string === '') {
            return '';
        }

        if (!function_exists('normalizer_normalize')) {
            return $string;
        }

        $normalised = normalizer_normalize($string, \Normalizer::FORM_C);
        if (is_null($normalised)) {
            // An error occurred in normalizer_normalize, but we have no idea what.
            debugging('Failed to normalise string: ' . $string, DEBUG_DEVELOPER);
            return $string; // Return the original string, since it is the best we have.
        }

        return $normalised;
    }
}
