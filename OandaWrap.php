<?php

/*

Copyright 2014 William Whitty
will.whitty.arbeit@gmail.com

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.

*/

if (defined("TAVURTH_OANDAWRAP") == FALSE) {
	define("TAVURTH_OANDAWRAP", TRUE);
	
	//////////////////////////////////////////////////////////////////////////////////
	//
	//	OANDA FUNCTIONAL API WRAPPER
	//
	//	Written by William Whitty July 2014
	//	Questions, comments or bug reports?
	//
	//		will.whitty.arbeit@gmail.com
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
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	VARIABLE DECLARATION AND HELPER FUNCTIONS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		private static $account;
		private static $apiKey;
		private static $server;
		private static $baseUrl;
		
		public static function setup($server=FALSE, $apiKey=FALSE, $userName=FALSE, $accountName=FALSE) {
		//Setup our enviornment variables
			self::$apiKey = self::$account = self::$server = "";
			switch (ucfirst(strtolower($server))) {
				case "Live":
					//Set the url
					self::$baseUrl = "https://api-fxtrade.oanda.com/v1/";
					//Check our account ID
					if ($userName == FALSE) {
						echo "OandaWrap: Must provide username for live server.";
						return FALSE;
					}
					//Check our API key
					if ($apiKey == FALSE) {
						echo "OandaWrap: Must provide API key for live server.";
						return FALSE;
					}
					//Set the API key
					self::$apiKey = $apiKey;
					
					if ($accountName) {
						//Find the account
						$account = self::account_named($accountName, $userName);
						//Check the account
						if (!$account) {
							echo "OandaWrap: Invalid live account name: $accountName.<br>";
							return FALSE;
						}
						//Set our current account
						self::$account = $account;
					}
					else {
						$accounts = self::accounts($userName);
						//Check the account
						if (count($accounts) < 1) {
							echo "OandaWrap: Invalid live account name: $userName.<br>";
							return FALSE;
						}
						//Set our current account
						self::$account = $account[0];
					}
					return TRUE;
				case "Demo":
					//Set the url
					self::$baseUrl = "http://api-fxgame.oanda.com/v1/";
					//Check our account ID
					if ($accountId == FALSE) {
						echo "OandaWrap: Must provide accountId for demo server."; 
						return FALSE;
					}
					//Set the account
					self::$account = self::account($accountId);
					//Check the account
					if (!isset(self::$account->balance)) {
						echo "OandaWrap: Invalid demo accountId: $accountId.";
						return FALSE;
					}
					return TRUE;
				default:
					//Set the url
					self::$baseUrl = "http://api-sandbox.oanda.com/v1/";
					return TRUE;
			}
		}
		
		private function current_account_set($accountId) {
		//Set our environment variable account
			self::$account = self::account($accountId);
		}
		
		private function current_account($accountId) {
		//Return our environment variable account
			return self::$account;
		}
		
		private static function index() {
		//Return a formatted string for more concise code
			return "accounts/" . self::$account->accountId . "/";
		}
		private static function position_index() {
		//Return a formatted string for more concise code
			return self::index() . "positions/";
		}
		private static function trade_index() {
		//Return a formatted string for more concise code
			return self::index() . "trades/";
		}
		private static function order_index() {
		//Return a formatted string for more concise code
			return self::index() . "orders/";
		}
		private static function transaction_index() {
		//Return a formatted string for more concise code
			return self::index() . "transactions/";
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	DIRECT NETWORK ACCESS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public static function authenticate($ch) {
		//Authenticate our curl object
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . self::$apiKey));  //Sending our login hash
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);				//Verify Oanda
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);				//Verify Me
		}
		public static function configure($ch) {
		//Configure default connection settings
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);				//We want the data returned as a variable
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);						//Maximum wait before timeout
			self::authenticate($ch);									//Authenticate our socket
		}
		public static function get($index, $query_data=FALSE) {
		//Send a GET request to Oanda
			self::configure(($ch = curl_init()));						//initialization
																		//Url setup
			curl_setopt($ch, CURLOPT_URL, self::$baseUrl . $index . ($query_data ? "?" : "") . ($query_data ? http_build_query($query_data) : "")); 
			return json_decode(curl_exec($ch));							//Launch
		}
		public static function post($index, $query_data) {
		//Send a POST request to Oanda
			self::configure(($ch = curl_init()));						//initialization
			curl_setopt($ch, CURLOPT_URL, self::$baseUrl . $index);		//Url setup
			curl_setopt($ch, CURLOPT_POST, 1);							//Tell curl we want to POST
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query_data));  //Include the POST data
			return json_decode(curl_exec($ch));							//Launch
		}
		public static function patch($index, $query_data) {
		//Send a PATCH request to Oanda
			self::configure(($ch = curl_init()));						//initialization
			curl_setopt($ch, CURLOPT_URL, self::$baseUrl . $index);		//Url setup
			curl_setopt($ch, CURLOPT_POST, 1);							//Tell curl we want to POST
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");			//PATCH request setup
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query_data));  //Include the POST data
			return json_decode(curl_exec($ch));							//Launch
		}
		public static function delete($index) {
		//Send a DELETE request to Oanda
			self::configure(($ch = curl_init()));						//initialization
			curl_setopt($ch, CURLOPT_URL, self::$baseUrl . $index);		//Url setup
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");			//DELETE request setup
			return json_decode(curl_exec($ch));							//Launch
		}
		public static function stream($url, $callback){
		//Open a stream to Oanda
			self::authenticate(($ch = curl_init()));
			
			curl_setopt($ch, CURLOPT_URL, $url);						//Url setup
			curl_setopt($ch, CURLOPT_WRITEFUNCTION, $callback);			//Our callback, called for every new data packet
			//curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);				//We want the data returned as a variable
			return (curl_exec($ch));									//Launch
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	ACCOUNT WRAPPERS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public static function accounts($username) {
		//Return an array of the accounts for $username 
			return self::get("accounts", array("username" => $username))->accounts;
		}
		
		public static function account($accountId) {
		//Return the information for $accountId
			return self::get("accounts/" . $accountId);
		}
		
		public static function account_named($accountName, $uName) {
		//Return the information for $accountName
			foreach (self::accounts($uName) as $account)
				if ($account->accountName == $accountName)
					return self::account($account->accountId);
			return FALSE;
		}
		
		public static function account_id($accountName, $uName) {
		//Return the accountId for $accountName
			return self::account_named($accountName, $uName)->accountId;
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	INSTRUMENT WRAPPERS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public static function instruments() {
		//Return a list of tradeable instruments for $accountId
			return self::get("instruments", array("accountId" => self::$account->accountId));
		}
		
		public static function instrument_name($home, $away) {
		//Return a proper instrument name for two currencies
			if (self::instrument($home . "_" . $away))
				return $home . "_" . $away;
			if (self::instrument($away . "_" . $home))
				return $away . "_" . $home;
		}
		
		public static function instrument($pair) {
		//Return instrument for named $pair
			foreach(self::instruments()->instruments as $instrument)
				if ($pair == $instrument->instrument)
					return $instrument;
			return false;
		}
		
		public static function instrument_split($pair) {
		//Split an instrument into two currencies and return an array of them both
			$currencies = array();
			if (strpos($pair, "_") === FALSE) return FALSE;
			array_push($currencies, substr($pair, 0, strpos($pair, "_")));
			array_push($currencies, substr($pair, strpos($pair, "_")+1));
			return $currencies;
		}
		
		public static function instrument_pip($pair) {
		//Return a floating point number declaring the pip size of $pair
			return self::instrument($pair)->pip;
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	NAV (NET ACCOUNT VALUE) WRAPPERS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public static function convert($pair, $amount, $homeIndex=0) {
			$instrument = self::instrument_name($pair[0], $pair[1]);
			$reverse	= (strpos($instrument, $pair[$homeIndex]) > strpos($instrument, "_") ? TRUE : FALSE);
			$price 		= self::price($instrument);
			
			return ($reverse ? $amount / $price->ask : $amount * $price->ask);
		}
		
		public static function percent_nav ($pair, $percent, $leverage=1) {
		//Return the value of a percentage of the NAV (Net account value)
			$amount	= self::convert(array(self::$account->accountCurrency, self::instrument_split($pair)[0]), self::$account->balance*($percent/100));
			
			return floor($amount * $leverage);
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	TRANSACTION WRAPPERS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		
		public static function transaction_all($number=50, $pair="all") {
		//Return an object with all transactions (max 50)
			return self::get(self::transaction_index(), array("count" => $number, "instrument" => $pair));
		}
		
		public static function transaction_types($types, $number=50, $pair="all") {
		//Return an array with all transactions conforming to one of {$types}
			$array = array(); 
			foreach (self::transaction_all($number, $pair)->transactions as $transaction)
				if (in_array($transaction->type, $types))
					array_push($buffer, $transaction);
			return $array;
		}
		
		public static function transaction_get($transactionId) {
		//Get information on a single transaction
			return self::get(self::transaction_index() . $transactionId);
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	ORDER WRAPPERS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public static function order_pair($pair, $number=50) {
		//Get an object with all the orders for $pair
			return self::get(self::order_index(), array("instrument" => $pair, "count" => $number));
		}
		public static function order($orderId) {
		//Return an object with the information about $orderId
			return self::get(self::order_index() . $orderId);
		}
		public static function order_open($side, $units, $pair, $type, $rest = FALSE) {
		//Open a new order
			return self::post(self::order_index(), array_merge(array("instrument" => $pair, "units" => $units, "side" => $side, "type" => $type), (is_array($rest) ? $rest : array())));
		}
		public static function order_open_extended($side, $units, $pair, $type, $price, $expiry, $rest = FALSE) {
		//Open a new order, expanded for simplified limit order processing
			return self::order_open($side, $units, $pair, $type, array_merge(array("price" => $price, "expiry" => $expiry), (is_array($rest) ? $rest : array())));
		}
		public static function order_modify($orderId, $options) {
		//Modify the parameters of an order
			return self::patch(self::order_index() . $orderId, $options);
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
		//	TRADE WRAPPERS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public static function trade_pair($pair, $number=50) {
		//Return an object with all the trades on $pair
			return self::get(self::trade_index(), array("instrument" => $pair, "count" => $number));
		}
		public static function trade($tradeId) {
		//Return an object containing information on a single pair
			return self::get(self::trade_index() . $tradeId);
		}
		public static function trade_modify($tradeId, $options) {
		//Modify attributes of a trade referenced by $tradeId
			return self::patch(self::trade_index() . $tradeId, $options);
		}
		public static function trade_close($tradeId) {
		//Close trade referenced by $tradeId
			return self::delete(self::trade_index() . $tradeId);
		}
		public static function trade_close_all($pair) {
		//Close all trades on $pair
			$closed = array();
			foreach (self::trade_pair($pair)->trades as $trade)
				if (isset($trade->id))
					array_push($closed, self::delete(self::trade_index() . $trade->id));
			return $closed;
		}
		public static function trade_modify_all($pair, $options) {
		//Modify all trades on $pair
			foreach (self::trade_pair($pair)->trades as $trade)
				if (isset($trade->id))
					self::trade_modify($trade->id, $options);
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	POSITION WRAPPERS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public static function position_all() {
		//Return an object with all the positions for the account
			return self::get(self::position_index());
		}
		public static function position($pair) {
		//Return an object with the information for a single $pairs position
			return self::get(self::position_index() . $pair);
		}
		public static function position_close($positionId) {
		//Close the position for a pair using $position Id
			return self::delete(self::position_index() . $positionId);
		}
		public static function position_close_pair($pair) {
		//Close the position for $pair
			return self::delete(self::position($pair)->id);
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	BIDIRECTIONAL WRAPPERS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public static function market($side, $units, $pair, $rest = FALSE) {
		//Open a new @ market order
			return self::order_open($side, $units, $pair, "market", $rest);
		}
		public static function limit($side, $units, $pair, $price, $expiry, $rest = FALSE) {
		//Open a new limit order
			return self::order_open_extended($side, $units, $pair, "limit", $price, $expiry, $rest);
		}
		public static function stop($side, $units, $pair, $price, $rest = FALSE) {
		//Open a new stop order
			return self::order_open_extended($side, $units, $pair, "stop", $price, $expiry, $rest);
		}
		public static function mit($side, $units, $pair, $price, $expiry, $rest = FALSE) {
		//Open a new marketIfTouched order
			return self::order_open_extended($side, $units, $pair, "marketIfTouched", $price, $expiry, $rest);
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	BUYING WRAPPERS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public static function buy_market($units, $pair, $rest = FALSE) {
		//Buy @ market
			return self::market("buy", $units, $pair, "market", $rest);
		}
		public static function buy_limit($units, $pair, $price, $expiry, $rest = FALSE) {
		//Buy limit with expiry
			return self::limit("buy", $units, $pair, "limit", $price, $expiry, $rest);
		}
		public static function buy_stop($units, $pair, $price, $rest = FALSE) {
		//Buy stop with expiry
			return self::stop("buy", $units, $pair, "stop", $price, $expiry, $rest);
		}
		public static function buy_mit($units, $pair, $price, $expiry, $rest = FALSE) {
		//Buy marketIfTouched with expiry
			return self::mit("buy", $units, $pair, "marketIfTouched", $price, $expiry, $rest);
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	SELLING WRAPPERS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public static function sell_market($units, $pair, $rest = FALSE) {
		//Sell @ market
			return self::market("sell", $units, $pair, "market", $rest);
		}
		public static function sell_limit($units, $pair, $price, $rest = FALSE) {
		//Sell limit with expiry
			return self::limit("sell", $units, $pair, "limit", $price, $expiry, $rest);
		}
		public static function sell_stop($units, $pair, $price, $rest = FALSE) {
		//Sell stop with expiry
			return self::stop("sell", $units, $pair, "stop", $price, $expiry, $rest);
		}
		public static function sell_mit($units, $pair, $price, $expiry, $rest = FALSE) {
		//Sell marketIfTouched with expiry
			return self::mit("sell", $units, $pair, "marketIfTouched", $price, $expiry, $rest);
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	EXPIRY WRAPPERS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public static function expiry($seconds=5) {
		//Return the Oanda compatible timestamp of time() + $seconds
			return date("Y-m-j\TH:i:s\Z", (time()+$seconds*60));
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
		//	MODIFICATION WRAPPERS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		//$type in all cases here is either "order" or "trade"
		
		private static function _set($type, $id, $args) {
		//Macro function for setting attributes of both orders and trades
			switch ($type) {
				case "order":
					return self::order_modify($id, $args);
				case "trade":
					return self::trade_modify($id, $args);
			}
		}
		public static function stop_set($type, $id, $price) {
		//Set the stopLoss of an order or trade
			return self::_set($type, $id, array("stopLoss" => $price));
		}
		public static function tp_set($type, $id, $price) {
		//Set the takeProfit of an order or trade
			return self::_set($type, $id, array("takeProfit" => $price));
		}
		public static function trailing_stop_set($type, $id, $distance) {
		//Set the trailingStop of an order or trade
			return self::_set($type, $id, array("trailingStop" => $distance));
		}
		public static function expiry_set($id, $time) {
		//Set the units of an order
			return self::_set("order", $id, array("expiry" => $time));
		}
		public static function units_set($id, $units) {
		//Set the units of an order
			return self::_set("order", $id, array("units" => $units));
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	PRICE WRAPPERS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public static function prices($pairs) {
		//Return an array of prices for $pairs
			return self::get("prices", array("instruments" => implode(",", $pairs)));
		}
		
		public static function price($pair) {
		//Wrapper, return the current price of $pair
			return self::prices(array($pair))->prices[0];
		}
		
		public static function candles($pair, $gran, $number) {
		//Return a number of candles for $pair
			return self::get("candles", array("instrument" => $pair, "granularity" => strtoupper($gran), "count" => $number));
		}
	}
}

?>
