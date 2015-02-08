<?php

/*

Copyright 2015 William Whitty
will.whitty.arbeit@gmail.com

Licensed under the Apache License, Version 2.0 (the 'License');
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an 'AS IS' BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.

*/

if (defined('TAVURTH_OANDAWRAP_EXAMPLE_TRADE') == FALSE) {
	define('TAVURTH_OANDAWRAP_EXAMPLE_TRADE', TRUE);
	
	//Include OandaWrap
	require '../OandaWrap.php';
	
	//apiKey can be found inside your account information 
	//screen and requires a one time generation
	$apiKey 	= 'REPLACE THIS TEXT';
	
	//AccountId is the Id of one of your accounts
	//To later change this use OandaWrap::nav_account_set($accountId)
	$accountId 	= 'REPLACE THIS TEXT';
	
	//Check to see that OandaWrap is setup correctly.
	//Arg1 can be 'Demo', 'Live', or Sandbox;
	if (OandaWrap::setup('Demo', $apiKey, $accountId) == FALSE) {
		echo 'OandaWrap failed to initialize, ';
		echo 'contact will.whitty.arbeit@gmail.com to submit a bug report.';
		exit(1);
	} 
	
	echo '<h3><b>Buy with a market order and included stopLoss:<br></h3></b>';
	OandaWrap::format(OandaWrap::buy_market(10, 'EUR_USD', array('stopLoss' => 1.0243)));
	
	echo '<h3><b>Set buy limit order with included takeProfit:<br></h3></b>';
	OandaWrap::format(OandaWrap::buy_limit(10, 'EUR_USD', 1.0243, OandaWrap::expiry_day(10), array('takeProfit' => 1.032)));
	
	echo '<h3><b>Set market if touched buy order with included trailingStop of 10 pips:<br></h3></b>';
	OandaWrap::format(OandaWrap::buy_limit(10, 'EUR_USD', 1.0243, OandaWrap::expiry_hour(), array('trailingStop' => 10)));
	
	echo '<h3><b>Buy at market, limiting size so that 2% of account is risked over 20 pips, the set stop 20 pips from current price:<br></h3></b>';
	OandaWrap::format(OandaWrap::buy_bullish('EUR_USD', 2, 20));
	
}

?>