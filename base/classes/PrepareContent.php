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

	public static function getDetails($login) {

		global $hashids, $options, $content, $database;

		if($options->getOption('stripe_sub_customer') || $login->getUserAccessLevel() >= 200) {
			$content->api_key = $hashids->encrypt($login->getUserId(), 100, 200, 300, $login->getUserId() * 3, 600, 700, 800);
		}

		// Get the largest created date (latest) as usernames and websites may be live on multiple results
		$database->query("SELECT * FROM `names` t JOIN (SELECT names_url, MAX(names_created) maxVal FROM `names` GROUP BY names_url) t2 ON t.names_created = t2.maxVal AND t.names_url = t2.names_url AND user_id = :user_id");
		$database->bind(":user_id", $login->getUserId());
		$database->execute();

		$user_names_records = $database->resultset();

		$content->saved_names = count($user_names_records);

		return $content;

	}

	public static function getSettings($user_id) {

		global $hashids, $options, $content, $database;

		$content->change_emails = $options->getOption('change_emails');
		$content->newsletter = $options->getOption('subsribe');
		$content->subscribed = (!$options->getOption('stripe_sub_customer')) ? false : true;

		return $content;

	}


	public static function getResults($hash, $url) {

		global $hashids, $content, $database;

		$page = $content;

		// Get the largest created date (latest) as usernames and websites may be live on multiple results
		$database->query("SELECT * FROM `names` t JOIN (SELECT names_url, MAX(names_created) maxVal FROM `names` WHERE names_live = 1 GROUP BY names_url) t2 ON t.names_created = t2.maxVal AND t.names_url = t2.names_url AND :url IN(t.names_url, names_twitter, names_facebook, names_linkedin, names_googleplus)");
		$database->bind(":url", $url);
		$database->execute();

		$content = $database->resultset();

		foreach($content as $k=>$itemObj) {

			$content[$k]->input = $url;
			$content[$k]->twitter = $itemObj->names_twitter;
			$content[$k]->facebook = $itemObj->names_facebook;
			$content[$k]->linkedin = $itemObj->names_linkedin;
			$content[$k]->googleplus = $itemObj->names_googleplus;
			$content[$k]->url = $itemObj->names_url;
			$content[$k]->hash = $hash;

			foreach($page as $i=>$itemObj) {
				$content[$k]->{$i} = $itemObj;
			}

		}

		return $content;

	}

	public static function getResultsForEdit($id=null) {

		global $content, $database;

		$page = &$content;

		if($id) {

			$statement = 'SELECT * FROM `names` WHERE id = :id AND names_live = 1';

		} else {

			$statement = 'SELECT * FROM `names` t JOIN (SELECT names_url, MAX(names_created) maxVal FROM `names` WHERE names_live = 1 GROUP BY names_url) t2 ON t.names_created = t2.maxVal AND t.names_url = t2.names_url';

		}

		// Get the largest created date (latest) as usernames and websites may be live on multiple results
		$database->query($statement);


		if($statement) {

			$database->bind(":id", $id);

		}

		$database->execute();

		$content = $database->resultset();

		foreach($content as $k=>$itemObj) {

			$content[$k]->url = $itemObj->names_url;
			$content[$k]->twitter = $itemObj->names_twitter;
			$content[$k]->facebook = $itemObj->names_facebook;
			$content[$k]->linkedin = $itemObj->names_linkedin;
			$content[$k]->googleplus = $itemObj->names_googleplus;
			$content[$k]->permalink = YOURSITE . 'edit/' . $itemObj->id;

		}

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
