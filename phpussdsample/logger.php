<?php
function logFile($rtn){
	$f=fopen("/tmp/ussd","a+");
	fwrite($f, $rtn . "\n");
	fclose($f);
}