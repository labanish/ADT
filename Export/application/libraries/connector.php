<?php

class Connector {
	const ESCM_URL = 'https://api.kenyapharma.org/';
	const NASCOP_URL = 'http://41.57.109.241/NASCOP/';
	public function __construct() {
	}

	public function upload_connect($url, $data) {
		extract($_POST);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array("data" => json_encode($data)));
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}

}
?>