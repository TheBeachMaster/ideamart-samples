<?php

/*
 * Shuttle Service Schedule Provider
 * $LastChangedDate: (Tuesday, 13 Novmber 2012) $
 * $LastChangedBy: leyonlloyd@gmail.com $
 */

include_once 'UssdApi.php';
include_once 'logger.php';
//include_once 'example.php';
define("USSDSENDURL", "http://localhost:7000/ussd/send");
define("API_ID", "APP_000001");
define("API_PASSWD", "password");


try {
    define('TIMEZONE', 'Asia/Colombo');
    date_default_timezone_set(TIMEZONE);
    $receiver = new UssdApi();
    //create the receiver
    logFile("\nMessage Received...");
    //getting the message content
    $rtn = "{$receiver->getAddress()} :: {$receiver->getMessage()} :: {$receiver->getConversationId()}";
    logFile("Message: " . $rtn);


    $res = '';
    //sending a message
    if ($receiver->getMessageType() == NORMAL_MESSAGE) {

        //$sender = new MchoiceUssdApi("http://127.0.0.1:8000/ussd/", "appid", "pass");
        //$sender = new UssdApi("http://localhost:7000/ussd/send", "APP_000001", "password");
        $sender = new UssdApi(USSDSENDURL, API_ID, API_PASSWD);
        
        //send ackknowledgment
        $res = $sender->ackUssd($receiver->getAddress(), "ack", $receiver->getConversationId(), 'false');
        
        if ($receiver->getUssdOP() == 'mo-init') {
            $res = $sender->sendUssd($receiver->getAddress(), "Shuttle Service schedule provider ".date("d/m/y  g:i a")."\n\nSelect your checkin & checkout destinations[eg: '1 2' or '7 3'] \n\n(1) Location 1 \n(2) Location 2 \n(3) Location 3 \n(4) Location 4 \n(5) Location 5 \n(6) Location 6 \n(7) Location 7 \n(8) Location 8", $receiver->getConversationId(), 'false');
            
        } else if ($receiver->getUssdOP() == 'mo-cont') {
            
            list($vfrom,$vto) = split(" ", $receiver->getMessage());
           // if ( preg_match("/[0-9]+/", $vfrom) && preg_match("/[0-9]+/", $vto) )
            if ((is_numeric($vfrom)&&($vfrom>=1 && $vfrom<=8)) && (is_numeric($vto)&&($vto>=1 && $vto<=8)) ) {
              $res = $sender->sendUssd($receiver->getAddress(), $sender->getShedule($vfrom,$vto), $receiver->getConversationId(), 'false');
            } else {
                $res = $sender->sendUssd($receiver->getAddress(), "Shuttle Service schedule provider \n\nInvalid Input eg:['1 2' or '7 3']", $receiver->getConversationId(), 'false');
            }
        }
    } else if ($receiver->getMessageType() == TERMINATE_MESSAGE) {
        logFile("Terminate message received address : " . $receiver->getAddress() . " conversationId : " . $receiver->getConversationId());
    }

    logFile("\nRESPONSE::: correlationId :" . $res->{'correlationId'} . ", statusCode :" . $res->{'statusCode'} . ", statusDescription :" . $res->{'statusDescription'});
} catch (AppZoneException $ex) {
    //throws when failed sending or receiving the message
    logFile("ERROR: $ex");
}
