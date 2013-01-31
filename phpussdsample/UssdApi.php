<?php

/*
 * Shuttle Service Schedule Provider
 * $LastChangedDate: (Tuesday, 13 Novmber 2012) $
 * $LastChangedBy: leyonlloyd@gmail.com $
 */


define("NORMAL_MESSAGE", "X-USSD-Message");
define("TERMINATE_MESSAGE", "X-USSD-Terminate-Message");
define("ALIVE_MESSAGE", "X-USSD-Alive-Message");

class UssdApi {

    var $ussdOp;

    public function __construct() {
        $a = func_get_args();
        $i = func_num_args();
        if (method_exists($this, $f = '__construct' . $i)) {
            call_user_func_array(array($this, $f), $a);
        }
    }

    public function __construct0() {
        $arrHeaders = $this->getHeaders();
        $this->messageType = $arrHeaders['X-Message-Type'];
        $this->conversationId = $arrHeaders['X-Requested-Conversation-Id'];

        ///read the request body
        $body = @file_get_contents('php://input');

        $json = json_decode($body);

        if (isset($json->{'ussdOperation'})) {
            $this->messageType = NORMAL_MESSAGE;
        }

        //If the message type is ALIVE sends the Alive message
        if ($this->messageType == ALIVE_MESSAGE) {
            header("HTTP/1.1 202 Accepted");
            return;
        }

        $this->address = $json->{'sourceAddress'};
        $this->message = $json->{'message'};
        $this->correlationId = $json->{'sessionId'};
        $this->ussdOp = $json->{'ussdOperation'};

        if (!((isset($this->address) && isset($this->correlationId)))) {
            throw new Exception("Some of the required parameters are not provided");
        }
    }

    /*
     * Creating the sender object
     * $url - sender url
     * $username
     * $password
     */

    public function __construct3($url, $username, $password) {
        $this->url = $url;
        $this->username = $username;
        $this->password = $password;
    }

    /*
     * Read the request Header
     */

    function getHeaders() {
        $headers = array();
        foreach ($_SERVER as $k => $v) {
            if (substr($k, 0, 5) == "HTTP_") {
                $k = str_replace(' ', '-', ucwords(strtolower($k)));
                $headers[$k] = $v;
            }
        }
        return $headers;
    }


    public function sendUssd($address, $message, $conversationId, $sessionTermination = false, $ussdop) {
        $headers = array(
            'Content-type: application/json',
            'X-Requested-Encoding: UTF-8',
            'X-Requested-Conversation-ID:' . $conversationId,
            'X-Requested-Version: 1.0');
		    //$this->getAuthHeader());

        $postData = array('applicationId' => $this->username,'password' => $this->password,'message' => $message,'sessionId' => $conversationId,'ussdOperation' => 'mt-cont','destinationAddress' => $address);

        return $this->sendRequest($postData, $headers);
    }

    public function ackUssd($address, $message, $conversationId, $sessionTermination = false) {
        $headers = array(
            'Content-type: application/json',
            'X-Requested-Encoding: UTF-8',
            'X-Requested-Conversation-ID:' . $conversationId,
            'X-Requested-Version: 1.0',
            $this->getAuthHeader());

        $postData = array('statusCode' => 'S1000', 'statusDetail' => 'Success');

        return $this->sendRequest($postData, $headers);
    }

    private function getAuthHeader() {
        $auth = $this->username . ':' . $this->password;
        $auth = base64_encode($auth);
        return 'Authorization: Basic ' . $auth;
    }

    /* create shedule */

    public function getShedule($pfrom, $pto) {
        include 'reader.php';
        $excel = new Spreadsheet_Excel_Reader();

        /* table1 */
        $shedule = '';
        $excel->read('sample.xls');
        $x = 1;

        if ($pfrom < $pto) {

            while ($x <= $excel->sheets[0]['numRows']) {

                $shedule = $shedule . "\n";

                $fromcell = isset($excel->sheets[0]['cells'][$x][$pfrom]) ? $excel->sheets[0]['cells'][$x][$pfrom] : '';
                $tocell = isset($excel->sheets[0]['cells'][$x][$pto]) ? $excel->sheets[0]['cells'][$x][$pto] : '';
                $shuttle = isset($excel->sheets[0]['cells'][$x][9]) ? $excel->sheets[0]['cells'][$x][9] : '';

                $shedule = $shedule . "$shuttle". " "."$fromcell" . " --> " . "$tocell" . "  ";

                $x++;
            }
        } else {


            /* table2 */

            while ($x <= $excel->sheets[1]['numRows']) {

                $shedule = $shedule . "\n";

                $fromcell = isset($excel->sheets[1]['cells'][$x][$pfrom]) ? $excel->sheets[1]['cells'][$x][$pfrom] : '';
                $tocell = isset($excel->sheets[1]['cells'][$x][$pto]) ? $excel->sheets[1]['cells'][$x][$pto] : '';
                 $shuttle = isset($excel->sheets[1]['cells'][$x][9]) ? $excel->sheets[1]['cells'][$x][9] : '';


                //$shedule = $shedule . "$fromcell" . " --> " . "$tocell" . "  ";
                $shedule = $shedule . "$shuttle". " "."$fromcell" . " --> " . "$tocell" . "  ";

                $x++;
            }
        }

        //driver read
        $shedule = $shedule . "\n";
        $x = 1;
        while ($x <= $excel->sheets[2]['numRows']) {
            $shedule = $shedule . "\n";
            $y = 1;
            while ($y <= $excel->sheets[2]['numCols']) {
                $cell = isset($excel->sheets[2]['cells'][$x][$y]) ? $excel->sheets[2]['cells'][$x][$y] : '';
                $shedule = $shedule . "$cell ";
                $y++;
            }

            $x++;
        }

        return $shedule;
    }

    /*
     * Creates the JSON object that's sent using cURL
     * $postData - request body
     * $header - request header
     */

    private function sendRequest($postData, $header) {
        $ch = curl_init($this->url);

        // Configuring curl options
        $options = array(
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true
        );

        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        curl_close($ch);
        return $this->handleResponse($result);
    }

    /*
     * Handles the response message
     */

    private function handleResponse($result) {
        $resp = json_decode($result);
        if ($result == "") {
            throw new AppZoneException
                    ("Server URL is invalid", '500');
        } else if ($resp->{'statusCode'} == 'S1000') {
            return $resp;
        } else {
            throw new AppZoneException($resp->{'statusDescription'}, $resp->{'statusCode'}, $resp);
        }
    }

    public function getAddress() {
        return $this->address;
    }

    public function getMessage() {
        return $this->message;
    }

    public function getMessageType() {
        return $this->messageType;
    }

    public function getUssdOP() {
        return $this->ussdOp;
    }

    public function getCorrelationId() {
        return $this->correlationId;
    }

    public function getConversationId() {
        return $this->conversationId;
    }

}

class AppZoneException extends Exception {

    var $code;
    var $response;
    var $statusMessage;

    public function __construct($message, $code, $response = null) {
        parent::__construct($message);
        $this->statusMessage = $message;
        $this->code = $code;
        $this->response = $response;
    }

    public function getStatusCode() {
        return $this->code;
    }

    public function getStatusMessage() {
        return $this->statusMessage;
    }

    public function getRawResponse() {
        return $this->response;
    }

}

