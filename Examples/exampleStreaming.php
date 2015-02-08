<?php

/*

Copyright 2015 William Whitty
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

if (defined('TAVURTH_OANDAWRAP_EXAMPLE_STREAMING') == FALSE) {
	define('TAVURTH_OANDAWRAP_EXAMPLE_STREAMING', TRUE);
	
	//This example should be run from a console.
	//HTML will not show the output without a flush command
	
	//Include OandaWrap
	require '../OandaWrap.php';
	
	//apiKey can be found inside your account information 
	//screen and requires a one time generation
	$apiKey 	= 'REPLACE THIS TEXT';
	
	//Check to see that OandaWrap is setup correctly.
	//Arg1 can be 'Demo', 'Live', or 'Sandbox';
	if (OandaWrap::setup('Demo', $apiKey) == FALSE) {
		echo 'OandaWrap failed to initialize, ';
		echo 'contact Tavurth@gmail.com to submit a bug report.';
		exit(1);
	}
	
	$callback = function ($jsonObject) {
		var_dump($jsonObject);
		if (FALSE /* RETURN TRUE TO EXIT THE STREAM*/)
			return TRUE;
	};
	
	OandaWrap::stream('prices', $callback, array('EUR_USD'));
	//OandaWrap::stream('events', $callback, array('ACCOUNT-ID'));
}

?>