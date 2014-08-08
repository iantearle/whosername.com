<?php

class PrepareContent {

	private static $dateformat = 'l, F jS, Y';
	private static $timeformat = 'g:i a ';
	private static $timeoffset = 8;
	public static $count = -1;

	public function __construct() {

	}

	public static function get($section) {

		$database = new Database();

		$array = ['content'];

		return $array;

	}

	private static function assignContent($content, $extraVars = array()) {

		foreach($content as $k => $itemObj) {

			$content[$k] = $itemObj;

		}

		return $content;
	}

	public static function getDetails($user_id) {

		$hashids = new Hashids\Hashids('Who am I');

		$content = new stdClass();

		$content->api_key = $hashids->encrypt($user_id, 100, 200, 300, $user_id * 3, 600, 700, 800);

		return $content;

	}


	public static function getResults($hash, $url) {

		global $hashids;
		$database = new Database();

		$user_id = $hashids->decrypt($hash);

		$database->query("SELECT * FROM `names` WHERE names_url = :url  AND names_created = (SELECT MAX(names_created) FROM `names`) AND names_live = 1");
		$database->bind(":url", $url);
		$database->execute();

		$content = new stdClass();
		$content = $database->resultset();

		foreach($content as $k=>$itemObj) {
			$content[$k]->twitter = $itemObj->names_twitter;
			$content[$k]->facebook = $itemObj->names_facebook;
			$content[$k]->linkedin = $itemObj->names_linkedin;
			$content[$k]->googleplus = $itemObj->names_googleplus;
		}

		$content->hash = $hash;

		return $content;

	}

	public static function getShortCode($url) {

		$database = new Database();
		$database->query("SELECT * FROM yourls_url WHERE url = :url");
		$database->bind(":url", $url);
		$database->execute();

		$found = $database->single();

		if($found) {
			return $found->keyword;
		} else {
			$found = yourls_add_new_link( yourls_sanitize_url( $url ), "", "");
			return $found['url']['keyword'];
		}
	}

	public static function getCount($user_id) {

		$database = new Database();
		$database->query("SELECT a.* FROM feeds_items a LEFT JOIN subscription b ON a.feed = b.feed WHERE b.user_id = :user_id");
		$database->bind(":user_id", $user_id);
		$database->execute();

		return $database->rowCount();

	}

	private static function trim_excerpt($text, $len, $strip = false) {
		$excerpt_length = $len;
		$text = trim($text);
		$chars = strlen($text);
		$words = explode(' ', $text, $excerpt_length + 1);
		if($strip == true) {
			if(strlen($text) > $len) {
				$len = $len -1;
				$text = wordwrap($text, $len);
				$text = substr($text, 0, strpos($text, "\n")) . '&hellip;';
			}
		} elseif(count($words) > $excerpt_length) {
			array_pop($words);
			array_push($words, '&hellip;</p>');
			$text = implode(' ', $words);
		}
		return $text;
	}

}