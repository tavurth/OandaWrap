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

if (defined('TAVURTH_OANDAWRAP') == FALSE) {
	define('TAVURTH_OANDAWRAP', TRUE);
	
	//////////////////////////////////////////////////////////////////////////////////
	//
	//	OANDAWRAP API WRAPPER FOR OANDAS 'REST'
	//
	//	Written by William Whitty July 2014
	//	Questions, comments or bug reports?
	//
	//		Tavurth@gmail.com
	//
	//	I am in no way responsible for any of your losses incurred
	//	while trading forex. 
	//	I take my trades off the table if they become losers.
	//
	//
	//	Best,
	//
	//		Tavurth
	//
	//////////////////////////////////////////////////////////////////////////////////
	
	class OandaWrap {
		protected static $baseUrl;
		protected static $account;
		protected static $apiKey;
		protected static $instruments;
		protected static $socket;
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	VARIABLE DECLARATION AND HELPER FUNCTIONS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		protected static function check_name($name, $printValue, $verbose=TRUE) {
		//Check if an argument was correctly passed.
			if (!isset($name) || $name === FALSE) {	//Failure
				if ($verbose)
					echo $printValue . '<br>';
				return FALSE;
			}
			else //Valid name
				return $name;
		}
		
		protected static function setup_account($baseUrl, $apiKey = FALSE, $accountId = FALSE) {
		//Generic account setup program, prints out errors in the html if incomplete
			//Set the url
			
			self::$instruments = array();
			self::$baseUrl = $baseUrl;
			//Checking our login details
			if (strpos($baseUrl, 'https') !== FALSE || strpos($baseUrl, 'fxpractice') !== FALSE) {
				//Check that we have specified an accountId
				if (! self::check_name($accountId, 'Invalid $baseUrl accountId: $accountId.'))
					return FALSE;
				
				//Check that we have specified an API key
				if (! self::check_name($apiKey, 'Must provide API key for $baseUrl server.'))
					return FALSE;
				
				//Set the API key
				self::$apiKey = $apiKey;
				self::$account = self::account($accountId);
			}
			//Completed
			return TRUE;
		}
		
		public static function setup($server=FALSE, $apiKey=FALSE, $accountId=FALSE) {
		//Setup our enviornment variables
			if (isset(self::$account))
				if (self::$account->id == $accountId)
					return;
			//'Live', 'Demo' or the default 'Sandbox' servers.
			switch (ucfirst(strtolower($server))) { //Set all to lowercase except the first character
				case 'Live':
					return self::setup_account('https://api-fxtrade.oanda.com/v1/', $apiKey, $accountId);
				case 'Demo':
					return self::setup_account('https://api-fxpractice.oanda.com/v1/', $apiKey, $accountId);
				case 'Sandbox':
					return self::setup_account('http://api-sandbox.oanda.com/v1/');
				default:
					echo 'User must select: \'Live\', \'Demo\', or \'Sandbox\' server for OandaWrap setup.';
					return FALSE;
			}
		}
		
		protected static function index() {
		//Return a formatted string for more concise code
			if (isset(self::$account->accountId))
				return 'accounts/' . self::$account->accountId . '/';
			return 'accounts/0/';
		}
		protected static function position_index() {
		//Return a formatted string for more concise code
			return self::index() . 'positions/';
		}
		protected static function trade_index() {
		//Return a formatted string for more concise code
			return self::index() . 'trades/';
		}
		protected static function order_index() {
		//Return a formatted string for more concise code
			return self::index() . 'orders/';
		}
		protected static function transaction_index() {
		//Return a formatted string for more concise code
			return self::index() . 'transactions/';
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	DIRECT NETWORK ACCESS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		protected static function data_decode($data) {
		//Return decoded data
			return (($decoded = @gzdecode($data)) ? $decoded : $data);
		}
		protected static function authenticate($ch) {
		//Authenticate our curl object
			$headers = array('X-Accept-Datetime-Format: UNIX',			//Milliseconds since epoch
								'Accept-Encoding: gzip, deflate',		//Compress data
								'Connection: Keep-Alive');				//Persistant http connection
								
			if (isset(self::$apiKey)) {    								//Add our login hash
				array_push($headers, 'Authorization: Bearer ' . self::$apiKey);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);			//Verify Oanda
				// TODO: get CA cert for this to be able to set to true
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);			//Verify Me
			}
			//Set the sockets headers
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}
		protected static function configure($ch) {
		//Configure default connection settings
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);				//We want the data returned as a variable
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);						//Maximum wait before timeout
			self::authenticate($ch);									//Authenticate our socket
		}
		protected static function socket() {
		//Return our active socket for reuse
			if (isset(self::$socket) == FALSE)
				self::configure(self::$socket = curl_init());
			return self::$socket;
		}
		protected static function get($index, $query_data=FALSE) {
		//Send a GET request to Oanda											
			$ch = self::socket();
					
			curl_setopt($ch, CURLOPT_URL, //Url setup
				self::$baseUrl . $index . ($query_data ? '?' : '') . ($query_data ? http_build_query($query_data) : ''));
			if( ! $result = curl_exec($ch))
			{
				trigger_error(curl_error($ch));
			}
			$data = json_decode(self::data_decode($result)); 		//Launch and store decrypted data
			return $data;
		}
		protected static function post($index, $query_data) {
		//Send a POST request to Oanda
			$ch = self::socket();
																	
			curl_setopt($ch, CURLOPT_URL, self::$baseUrl . $index);		//Url setup
			curl_setopt($ch, CURLOPT_POST, 1);							//Tell curl we want to POST
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query_data));  //Include the POST data
			return json_decode(self::data_decode(curl_exec($ch))); 		//Launch and return decrypted data
		}
		protected static function patch($index, $query_data) {
		//Send a PATCH request to Oanda
			$ch = self::socket();
											
			curl_setopt($ch, CURLOPT_URL, self::$baseUrl . $index);		//Url setup
			curl_setopt($ch, CURLOPT_POST, 1);							//Tell curl we want to POST
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');			//PATCH request setup
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query_data));  //Include the POST data
			return json_decode(self::data_decode(curl_exec($ch))); 		//Launch and return decrypted data
		}
		protected static function delete($index) {
		//Send a DELETE request to Oanda
			$ch = self::socket();
			
			curl_setopt($ch, CURLOPT_URL, self::$baseUrl . $index);		//Url setup
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');			//DELETE request setup
			return json_decode(self::data_decode(curl_exec($ch))); 		//Launch and return decrypted data
		}
		protected static function stream($url, $callback){
		//Open a stream to Oanda 
		//$callback = function ($ch, $str) {
					// /* { YOUR CODE } */
					// return strlen($str); }
			self::authenticate(($ch = curl_init()));
			curl_setopt($ch, CURLOPT_URL, $url);						//Url setup
			curl_setopt($ch, CURLOPT_WRITEFUNCTION, $callback);			//Our callback, called for every new data packet
			return (curl_exec($ch));									//Launch
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	ACCOUNT WRAPPERS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public static function account($accountId) {
		//Return the information for $accountId
			return self::get('accounts/' . $accountId);
		}
		
		public static function accounts($username) {
		//Return an array of the accounts for $username 
			$accounts = self::get('accounts', array('username' => $username));
			return (isset($accounts->accounts) ? $accounts->accounts : array());
		}
		
		public static function account_id($accountName, $uName) {
		//Return the accountId for $accountName
			return self::account_named($accountName, $uName)->accountId;
		}
		
		public static function account_named($accountName, $uName) {
		//Return the information for $accountName
			foreach (self::accounts($uName) as $account)
				if ($account->accountName == $accountName) 
					return $account;
			return FALSE;
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	INSTRUMENT WRAPPERS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public static function instruments() {
		//Return a list of tradeable instruments for $accountId
			if (empty(self::$instruments))
				self::$instruments = self::get('instruments', array('accountId' => self::$account->accountId));
			//If fetch failed return an empty array
			return (self::$instruments ? self::$instruments : array());
		}
		public static function instrument($pair) {
		//Return instrument for named $pair
			foreach(self::instruments()->instruments as $instrument)
				if ($pair == $instrument->instrument)
					return $instrument;
			return false;
		}
		public static function instrument_pairs($currency) {
		//Return instruments for that correspond to $currency
			$pairs = array();
			foreach(self::instruments()->instruments as $instrument) {
				if (strpos($instrument->instrument, $currency))
					array_push($pairs, $instrument->instrument);
			}
			return $pairs;
		}
		
		public static function instrument_name($home, $away) {
		//Return a proper instrument name for two currencies
			//Example: OandaWrap::instrument_name('AUD', 'CHF') returns 'AUD_CHF'
			//Example: OandaWrap::instrument_name('USD', 'EUR') returns 'EUR_USD' 
			if (self::instrument($home . '_' . $away))
				return $home . '_' . $away;
			if (self::instrument($away . '_' . $home))
				return $away . '_' . $home;
			return FALSE;
		}
		
		public static function instrument_split($pair) {
		//Split an instrument into two currencies and return an array of them both
			$currencies = array();
			$dividerPos = strpos($pair, '_');
			//Failire
			if ($dividerPos === FALSE) return FALSE;
			//Building array
			array_push($currencies, substr($pair, 0, $dividerPos));
			array_push($currencies, substr($pair, $dividerPos+1));
			return $currencies;
		}
		
		public static function instrument_pip($pair) {
		//Return a floating point number declaring the pip size of $pair
			return self::instrument($pair)->pip;
		}
		
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	CALCULATOR FUNCTIONS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public static function convert($pairHome, $pairAway, $amount) {
		//Convert $amount of $pairHome to $pairAway
			if ($pair = self::instrument_name($pairHome, $pairAway)) {
				if ($price 		= self::price($pair)) {
					//Use the $baseIndex currency of $pair (AUD_JPY = Aud or Jpy)
					$reverse	= (strpos($pair, $pairHome) > strpos($pair, '_') ? FALSE : TRUE);
					
					//Which way to convert 
					return ($reverse ? $amount * $price->ask : $amount / $price->ask);
				}
			}
			return FALSE;
		}
		public static function convert_pair($pair, $amount, $home) {
		//Convert $amount of $pair from $home 
		//	i.e. OandaWrap::convert_pair('EUR_USD', 500, 'EUR'); Converts 500 EUR to USD
		//	i.e. OandaWrap::convert_pair('EUR_USD', 500, 'USD'); Converts 500 USD to EUR
			if ($price 		= self::price($pair)) {
				$pairNames  = self::instrument_split($pair);
				$homeFirst  = $home == $pairNames[0] ? TRUE : FALSE;
				return ($homeFirst ? 
						self::convert($pairNames[0], $pairNames[1], $amount) :
						self::convert($pairNames[1], $pairNames[0], $amount));
			}
			return FALSE;
		}
		
		public static function calc_pips($pair, $open, $close) {
		//Return the pip difference between prices $open and $close for $pair
			return round(($open - $close)/self::instrument_pip($pair), 2);
		}
		
		public static function calc_pip_price($pair, $size, $side=1) {
		//Return the cost of a single pip of $pair when $size is used
			if ($price = self::price(self::nav_instrument_name($pair, 1))) 
				return (self::instrument_pip($pair)/($side ? $price->bid : $price->ask))*$size;
			return FALSE;
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	NAV (NET ACCOUNT VALUE) WRAPPERS
		//
		////////////////////////////////////////////////////////////////////////////////// 
		
		public static function nav_account_set($accountId) {
		//Set our environment variable $account
			self::$account = self::account($accountId);
		}
		
		public static function nav_account() {
		//Return our environment variable account
			return self::$account;
		}
		
		public static function nav_instrument_name($pair, $index=0) {
		//Return the instrument name used to convert currency for the NAV
			return self::instrument_name(self::$account->accountCurrency, self::instrument_split($pair)[$index]);
		}
		
		public static function nav_size_percent($pair, $percent, $leverage = 50) {
		//Return the value of a percentage of the NAV (Net account value)
			$baseSize	= self::convert_pair(self::nav_instrument_name($pair), self::$account->balance*($percent/100), self::$account->accountCurrency);
			//Calculate our leveraged size
			return floor($baseSize * $leverage);
		}
		
		public static function nav_size_percent_per_pip($pair, $riskPerPip, $leverage = 50) {
		//Return the size for $pair that risks $riskPerPip every pip
			//@ maximum 50:1 leverage, risk is 0.5% per pip
			$baseSize = ($riskPerPip/0.5)*self::nav_size_percent($pair, 100, $leverage);
			//Calculate our leveraged size
			return floor(($leverage/50)*$baseSize);
		}
		
		public static function nav_pnl($dollarValue=FALSE) {
		//Return the pnl for account, if $dollarValue is set TRUE, return in base currency, else as %.
			if ($dollarValue == FALSE)
				return round((self::$account->unrealizedPl / self::$account->balance) * 100, 2);
			return (isset(self::$account) ? self::$account->unrealizedPl : FALSE);
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	TRANSACTION WRAPPERS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public static function transaction($transactionId) {
		//Get information on a single transaction
			return self::get(self::transaction_index() . $transactionId);
		}
		public static function transactions($number=50, $pair='all') {
		//Return an object with all transactions (max 50)
			$transactions = self::get(self::transaction_index(), array('count' => $number, 'instrument' => $pair));
			return (isset($transactions->transactions) ? $transactions->transactions : FALSE);
		}
		
		public static function transactions_types($types, $number=50, $pair='all') {
		//Return an array with all transactions conforming to one of $types which is an array of strings
			$array = array(); 
			//Return all transactions
			if ($transactions = self::transactions($number, $pair)) {
				foreach ($transactions as $transaction)
					//If the type is valid
					if (in_array($transaction->type, $types))
						//Buffer it
						array_push($array, $transaction);
			}
			//If we had a problem retrieving transactions
			else return false;
			//Return the buffer
			return $array;
		}
		public static function transactions_type($type, $number=50, $pair='all') {
		//Return up to 50 transactions of $type
			return self::transactions_types(array($type), $number, $pair);
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	! LIVE FUNCTIONS !
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		// TIME FUNCTIONS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public static function time_seconds($time) {
		//Convert oanda time from microseconds to seconds
			return floor($time/1000000);
		}
		
		public static function gran_seconds($gran) {
		//Return a the number of seconds per Oandas 'granularity'
			switch (strtoupper($gran)) {
				case 'S5': return 5;
				case 'S10': return 10;
				case 'S15': return 15;
				case 'S30': return 30;
				case 'M1': return 60;
				case 'M2': return 2*60;
				case 'M3': return 3*60;
				case 'M4': return 4*60;
				case 'M5': return 5*60;
				case 'M10': return 10*60;
				case 'M15': return 15*60;
				case 'M30': return 30*60;
				case 'H1': return 60*60;
				case 'H2': return 2*60*60;
				case 'H3': return 3*60*60;
				case 'H4': return 4*60*60;
				case 'H6': return 6*60*60;
				case 'H8': return 8*60*60;
				case 'H12': return 12*60*60;
				case 'D' : return 24*60*60;
				case 'W' : return 7*24*60*60;
				case 'M' : return (365*24*60*60)/12;
			}
		}
		
		public static function expiry($seconds=5) {
		//Return the Oanda compatible timestamp of time() + $seconds
			return time()+$seconds;
		}
		public static function expiry_min($minutes=5) {
		//Return the Oanda compatible timestamo of time() + $minutes
			return self::expiry($minutes*60);
		}
		public static function expiry_hour($hours=1) {
		//Return the Oanda compatible timestamp of time() + $hours
			return self::expiry_min($hours*60);
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	BIFUNCTIONAL MODIFICATION WRAPPERS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		//$type in all cases for bidirectional is either 'order' or 'trade'
		
		protected static function set_($type, $id, $args) {
		//Macro function for setting attributes of both orders and trades
			switch ($type) {
				case 'order':
					return self::order_set($id, $args);
				case 'trade':
					return self::trade_set($id, $args);
			}
		}
		public static function set_stop($type, $id, $price) {
		//Set the stopLoss of an order or trade
			return self::set_($type, $id, array('stopLoss' => $price));
		}
		public static function set_tp($type, $id, $price) {
		//Set the takeProfit of an order or trade
			return self::set_($type, $id, array('takeProfit' => $price));
		}
		public static function set_trailing_stop($type, $id, $distance) {
		//Set the trailingStop of an order or trade
			return self::set_($type, $id, array('trailingStop' => $distance));
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	ORDER WRAPPERS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public static function order($orderId) {
		//Return an object with the information about $orderId
			return self::get(self::order_index() . $orderId);
		}
		public static function order_pair($pair, $number=50) {
		//Get an object with all the orders for $pair
			return self::get(self::order_index(), array('instrument' => $pair, 'count' => $number));
		}
		public static function order_open($side, $units, $pair, $type, $rest = FALSE) {
		//Open a new order
			return self::post(self::order_index(), array_merge(array('instrument' => $pair, 'units' => $units, 'side' => $side, 'type' => $type), (is_array($rest) ? $rest : array())));
		}
		public static function order_open_extended($side, $units, $pair, $type, $price, $expiry, $rest = FALSE) {
		//Open a new order, expanded for simplified limit order processing
			return self::order_open($side, $units, $pair, $type, array_merge(array('price' => $price, 'expiry' => $expiry), (is_array($rest) ? $rest : array())));
		}
		public static function order_close($orderId) {
		//Close an order by Id
			return self::delete(self::order_index() . $orderId);
		}
		public static function order_close_all($pair) {
		//Close all orders in $pair
			foreach (self::order_pair($pair)->orders as $order)
				if (isset($order->id))
					self::delete(self::order_index() . $order->id);
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	ORDER MODIFICATION WRAPPERS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public static function order_set($orderId, $options) {
		//Modify the parameters of an order
			return self::patch(self::order_index() . $orderId, $options);
		}
		public static function order_set_stop($id, $price) {
		//Set the stopLoss of an order
			return self::set_stop('order', $id, $price);
		}
		public static function order_set_tp($id, $price) {
		//Set the takeProfit of an order
			return self::set_tp('order', $id, $price);
		}
		public static function order_set_trailing_stop($id, $distance) {
		//Set the trailingStop of an order
			return self::set_trailing_stop('order', $id, $distance);
		}
		public static function order_set_expiry($id, $time) {
		//Set the expiry of an order
			return self::set_('order', $id, array('expiry' => $time));
		}
		public static function order_set_units($id, $units) {
		//Set the units of an order
			return self::set_('order', $id, array('units' => $units));
		}
		
		public static function order_set_all($pair, $options) {
		//Modify all orders on $pair
			foreach (self::order_pair($pair)->orders as $order)
				if (isset($order->id))
					self::set_('order', $order->id, $options);
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	TRADE WRAPPERS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public static function trade($tradeId) {
		//Return an object containing information on a single pair
			return self::get(self::trade_index() . $tradeId);
		}
		public static function trade_pair($pair, $number=50) {
		//Return an object with all the trades on $pair
			$trades = self::get(self::trade_index(), array('instrument' => $pair, 'count' => $number));
			if (isset($trades->trades))
				return $trades->trades;
			return array();
		}
		public static function trade_close($tradeId) {
		//Close trade referenced by $tradeId
			return self::delete(self::trade_index() . $tradeId);
		}
		public static function trade_close_all($pair) {
		//Close all trades on $pair
			self::position_close($pair);
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	TRADE MODIFICATION WRAPPERS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public static function trade_set($tradeId, $options) {
		//Modify attributes of a trade referenced by $tradeId
			return self::patch(self::trade_index() . $tradeId, $options);
		}
		public static function trade_set_stop($id, $price) {
		//Set the stopLoss of a trade
			return self::set_stop('trade', $id, $price);
		}
		public static function trade_set_tp($id, $price) {
		//Set the takeProfit of a trade
			return self::set_tp('trade', $id, $price);
		}
		public static function trade_set_trailing_stop($id, $distance) {
		//Set the trailingStop of a trade
			return self::set_trailing_stop('trade', $id, $distance);
		}
		
		public static function trade_set_all($pair, $options) {
		//Modify all trades on $pair
			$result = array();
			foreach (self::trade_pair($pair) as $trade)
				if (isset($trade->id))
					array_push($result, self::set_('trade', $trade->id, $options));
			return $result;
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	POSITION WRAPPERS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public static function position($pair) {
		//Return an object with the information for a single $pairs position
			return self::get(self::position_index() . $pair);
		}
		public static function position_all() {
		//Return an object with all the positions for the account
			return self::get(self::position_index());
		}
		public static function position_close($pair) {
		//Close the position for $pair
			return self::delete(self::position_index() . $pair);
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	BIDIRECTIONAL WRAPPERS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public static function market($side, $units, $pair, $rest = FALSE) {
		//Open a new @ market order
			return self::order_open($side, $units, $pair, 'market', $rest);
		}
		public static function limit($side, $units, $pair, $price, $expiry, $rest = FALSE) {
		//Open a new limit order
			return self::order_open_extended($side, $units, $pair, 'limit', $price, $expiry, $rest);
		}
		public static function stop($side, $units, $pair, $price, $rest = FALSE) {
		//Open a new stop order
			return self::order_open_extended($side, $units, $pair, 'stop', $price, $expiry, $rest);
		}
		public static function mit($side, $units, $pair, $price, $expiry, $rest = FALSE) {
		//Open a new marketIfTouched order
			return self::order_open_extended($side, $units, $pair, 'marketIfTouched', $price, $expiry, $rest);
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	BUYING WRAPPERS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public static function buy_market($units, $pair, $rest = FALSE) {
		//Buy @ market
			return self::market('buy', $units, $pair, $rest);
		}
		public static function buy_limit($units, $pair, $price, $expiry, $rest = FALSE) {
		//Buy limit with expiry
			return self::limit('buy', $units, $pair, $price, $expiry, $rest);
		}
		public static function buy_stop($units, $pair, $price, $rest = FALSE) {
		//Buy stop with expiry
			return self::stop('buy', $units, $pair, $price, $expiry, $rest);
		}
		public static function buy_mit($units, $pair, $price, $expiry, $rest = FALSE) {
		//Buy marketIfTouched with expiry
			return self::mit('buy', $units, $pair, $price, $expiry, $rest);
		}
		public static function buy_bullish($pair, $risk, $stop, $leverage=50) {
		//Macro: Buy $pair and limit size to equal %NAV loss over $stop pips. Then set stopLoss
			
			if ($price = self::price($pair)) {
				//Buy, sizing so that we $risk / $stop
				$newTrade = self::buy_market(self::nav_size_risk_per_pip($pair, ($risk/$stop)));
				//Check our trade
				if (self::check_name($newTrade->tradeId, $newTrade->message, FALSE))
					//Set the stoploss
					self::trade_set_stop($newTrade->tradeId, $price->ask + (self::instrument_pip($pair) * $stop));
				//Pass back the new trade, or FALSE on failure
				return (isset($newTrade->tradeId) ? $newTrade : FALSE);
			}
			return FALSE;
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	SELLING WRAPPERS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public static function sell_market($units, $pair, $rest = FALSE) {
		//Sell @ market
			return self::market('sell', $units, $pair, $rest);
		}
		public static function sell_limit($units, $pair, $price, $rest = FALSE) {
		//Sell limit with expiry
			return self::limit('sell', $units, $pair, $price, $expiry, $rest);
		}
		public static function sell_stop($units, $pair, $price, $rest = FALSE) {
		//Sell stop with expiry
			return self::stop('sell', $units, $pair, $price, $expiry, $rest);
		}
		public static function sell_mit($units, $pair, $price, $expiry, $rest = FALSE) {
		//Sell marketIfTouched with expiry
			return self::mit('sell', $units, $pair, $price, $expiry, $rest);
		}
		public static function sell_bearish($pair, $risk, $stop, $leverage=50) {
		//Macro: Sell $pair and limit size to equal %NAV loss over $stop pips. Then set stopLoss
			
			if ($price = self::price($pair)) {
				//Sell, sizing so that we $risk / $stop
				$newTrade = self::sell_market(self::nav_size_risk_per_pip($pair, ($risk/$stop)));
				//Check our trade
				if (self::check_name($newTrade->tradeId, $newTrade->message, FALSE))
					//Set the stoploss
					self::trade_set_stop($newTrade->tradeId, $price->bid - (self::instrument_pip($pair) * $stop));
				//Pass back the new trade, or FALSE on failure
				return (isset($newTrade->tradeId) ? $newTrade : FALSE);
			}
			return FALSE;
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	PRICE WRAPPERS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		protected static function candles_times_to_seconds($candles) {
		//Convert the times of $candles from microseconds to seconds
			if ($candles != FALSE) {
				foreach ($candles->candles as $candle)
					$candle->time = self::time_seconds($candle->time);
				return $candles;
			}
			return FALSE;
		}
		
		public static function price($pair) {
		//Wrapper, return the current price of '$pair'
			return (is_object($prices = self::prices(array($pair)))) ? $prices->prices[0] : FALSE;
		}
		
		public static function prices($pairs) {
		//Return an array {prices} for {$pairs}
			if ($prices = self::get('prices', array('instruments' => implode(',', $pairs))))
				return $prices;
			return FALSE;
		}
		
		public static function price_time($pair, $date) {
		//Wrapper, return the current price of '$pair'
			$candlesDated = self::candles_time($pair, 'S5', ($time=strtotime($date)), $time+10);
			if (count($candlesDated) > 0)
				return $candlesDated[0];
			return FALSE;
		}
		
		public static function candles($pair, $gran, $rest = FALSE) {
		//Return a number of candles for '$pair'
			
			//Defaults for $rest
			if (!is_array($rest))
				$rest = array('count' => 1);
			else if (!isset($rest['count']) && !isset($rest['start']))
				$rest['count'] = 1;
			
			//Retrieve our candles
			$candles = self::get('candles', array_merge(array('instrument' => $pair, 'granularity' => strtoupper($gran)), $rest));
			//Check the object
			if (isset($candles->candles) == FALSE)
				return FALSE;
			
			//Convert from microseconds and return
			return self::candles_times_to_seconds($candles);
		}
		
		public static function candles_time($pair, $gran, $start, $end) {
		//Return candles for '$pair' between $start and $end
			return self::candles($pair, $gran, array('start' => $start, 'end' => $end));
		}
		
		public static function candles_count($pair, $gran, $count) {
		//Return $count of the previous candles for '$pair'
			return self::candles($pair, $gran, array('count' => $count));
		}
	}
}

?>