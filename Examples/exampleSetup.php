<?php

/*

Copyright 2014 William Whitty
Tavurth@gmail.com

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

if (defined('TAVURTH_OANDAWRAP_EXAMPLE_SETUP') === FALSE) {
	define('TAVURTH_OANDAWRAP_EXAMPLE_SETUP', TRUE);
	
	//Include OandaWrap
	require '../OandaWrap.php';
	require 'config.php';

	//Check to see that OandaWrap is setup correctly.
	//Arg1 can be 'Demo', 'Live', or Sandbox;
	if (OandaWrap::setup('Demo', $apiKey, $accountId) === FALSE) {
		echo 'OandaWrap failed to initialize, ';
		echo 'contact Tavurth@gmail.com to submit a bug report.';
		exit(1);
	}
	
	//Html initiation
	echo '<html>';
	echo '<body>';
	
	//Style our body
	echo '<style> 
	
		body { font-size: 18px; color:#222222; }
		p { position:relative; left:10%; margin: 0; padding: 0; } 
		p.indent { position:relative; left:30%; }
	
	</style>';
	//Our header
	echo '<p><h2>OandaWrap quotes test:</h2></p>';
	
	//Save the requested pairs as an array
	$pairs = array('EUR_USD', 'EUR_AUD', 'EUR_JPY', 'EUR_CAD');

	//Loop through the array
	foreach ($pairs as $pair)	//Check for valid quote
		if ($quote = OandaWrap::price($pair))
			echo '<p>Price of ' . $pair . ' is: </p><p class="indent"> ' .$quote->bid . ' => ' . $quote->ask . '</p>';
	
	//End the html
	echo '</body>';
	echo '</html>';
	
}

?>