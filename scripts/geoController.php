<?php
	class Geo {
		
		protected $api = "https://www.geoplugin.net/json.gp?ip=%s";
		protected $properties = [];
		
		public function __get($key) {
			if (isset($this->properties[$key])) {
				return $this->properties[$key];
			} else {
				return null;
			}
		}
		
		public function request($ip) {
			$url = sprintf($this->api, $ip);
			$data = $this->sendRequest($url);
			
			//$this->properties = json_decode($data, true);
			
			//var_dump($data);
		}
		
		protected function sendRequest($url) {
			//$curl = curl_init();
			//curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			//curl_setopt($curl, CURLOPT_URL, $url);
			//return curl_exec($curl);
			
			return unserialize(file_get_contents($url));
		}
	}
?>