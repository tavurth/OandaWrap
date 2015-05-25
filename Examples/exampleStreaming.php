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

if (defined('TAVURTH_OANDAWRAP_EXAMPLE_STREAMING') === FALSE) {
	define('TAVURTH_OANDAWRAP_EXAMPLE_STREAMING', TRUE);
	
	//This example should be run from a console.
	//HTML will not show the output without a flush command
	
	//Include OandaWrap
	require '../OandaWrap.php';
	require 'config.php';
	
	//Check to see that OandaWrap is setup correctly.
	//Arg1 can be 'Demo', 'Live', or 'Sandbox';
	if (OandaWrap::setup('Live', $apiKey, $accountId) === FALSE)
        throw new Exception('contact Tavurth@gmail.com to submit a bug report.');
    
    function callback_func($jsonObject) {
        var_dump($jsonObject);
    }

    OandaWrap::stream(callback_func, array('EUR_USD'), array('AN ACCOUNT ID'));
}