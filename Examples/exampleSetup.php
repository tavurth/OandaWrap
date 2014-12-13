<?php

/*

Copyright 2014 William Whitty
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

if (defined('TAVURTH_OANDAWRAP_EXAMPLE_SETUP') == FALSE) {
	define('TAVURTH_OANDAWRAP_EXAMPLE_SETUP', TRUE);
	
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
	
	//Html initiation
	echo '<html>';
	echo '<body>';
	
	//Style our body
	echo '<style> body { font-size: 18; color:#222222; } p { position:relative; left:10%; } </style>';
	//Our header
	echo '<p><h2>OandaWrap quotes test:</h2></p>';
	
	//Save the requested pairs as an array
	$pairs = array('EUR_USD', 'EUR_AUD', 'EUR_JPY', 'EUR_CAD');
	
	echo '<p>';
	//Loop through the array
	foreach ($pairs as $pair)	//Check for valid quote
		if ($quote = OandaWrap::price($pair))
			echo 'Price of ' . $pair . ' is bid:' .$quote->bid . ', offer:' . $quote->ask . ' <br>';
	echo '</p>';
	
	echo'<p><h2>Call to OandaWrap::price(\'EUR_USD\') returns:</h2></p><p>';
	//Display all information relating to a returned quote object
	foreach (OandaWrap::price('EUR_USD') as $key => $value)
		echo $key . ' => ' . $value . '<br>';
	echo '</p>';
	
	//End the html
	echo '</body>';
	echo '</html>';
	
}

?>