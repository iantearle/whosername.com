<?php

if($_POST) {

	$error = '';
	$success = '';

	if(isset($_POST['login'])) {

		if(!empty($login->errors)) {

			foreach($login->errors as $error) {

				OutputMessages::setMessage($error, 'danger');

			}

			header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 303);
			exit;

		} else {

			header('Location: http://' . $_SERVER['HTTP_HOST'], true, 303);
			exit;

		}

	}

	if(isset($_POST['register'])) {
	    if($login->errors) {
	        foreach($login->errors as $error) {
	        	OutputMessages::setMessage($error, 'danger');
	        }
	    }
	    if($login->messages) {
	        foreach ($login->messages as $message) {
	            OutputMessages::setMessage($message, 'success');
	        }
	    }
	}

	if(isset($_POST['subscribe'])) {

		try {
			if(!isset($_POST['stripeToken'])) {

				throw new Exception("The Stripe Token was not generated correctly");

			}

			$token = $_POST['stripeToken'];

			$customer = Stripe_Customer::create(array(
				"description" => "Customer for " . $login->getUserEmail(),
				"card" => $token // obtained with Stripe.js
			));

			$cu = Stripe_Customer::retrieve($customer->id);
			$cu->subscriptions->create(array("plan" => "whosername12"));

			$options->setOption('stripe_sub_customer', $customer->id);

			$app->view->user_vars['main']['payment_successful'] = true;
			header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 303);
			OutputMessages::setMessage('Your payment was successful.', 'success');
			exit;

		} catch (Exception $e) {

	 		OutputMessages::setMessage($e->getMessage(), 'danger');
	 		header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 303);
			exit;

		}

	}

	if(isset($_POST['settings'])) {

		if(isset($_POST['change_emails']) && $_POST['change_emails'] == 1) {
			$options->setOption('change_emails', 1);
		} else {
			$options->deleteOption('change_emails');
		}

		if(isset($_POST['subsribe']) && $_POST['subsribe'] == 1) {
			$options->setOption('subsribe', 1);
		} else {
			$options->deleteOption('subsribe');
		}

		header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 303);
		OutputMessages::setMessage('Settings saved successfully.', 'success');
		exit;

	}

	if(isset($_POST['add'])) {

		$url = null;
		$twitter = null;
		$facebook = null;
		$linkedin = null;
		$googleplus = null;

		function remove_http($url) {
		   $disallowed = array('http://', 'https://');
		   foreach($disallowed as $d) {
		      if(strpos($url, $d) === 0) {
		         return str_replace($d, '', $url);
		      }
		   }
		   return $url;
		}

		function validate_twitter($username) {
		    return preg_match('/^[A-Za-z0-9_]{1,15}$/', $username);
		}

		function validate_facebook($username) {
		    return preg_match('/^[a-z\d.]{5,}$/i', $username);
		}

		if(isset($_POST['website'])) {

			$url = $_POST['website'];

			if(!preg_match("~^(?:f|ht)tps?://~i", $url)) {

		        $url = "http://" . $url;

		    }

			$parse = parse_url($url);

			$url = $parse['host'];

		}

		if(isset($_POST['twitter'])) {

			if(validate_twitter($_POST['twitter'])) {

				$twitter = $_POST['twitter'];

			}

		}

		if(isset($_POST['facebook'])) {

			if(validate_facebook($_POST['facebook'])) {

				$facebook = $_POST['facebook'];

			}


		}

		if(isset($_POST['linkedin'])) {

			$linkedin = $_POST['linkedin'];

		}

		if(isset($_POST['googleplus'])) {

			$googleplus = $_POST['googleplus'];

		}

		$checkAmount = 0;
		$checks = array($url, $twitter, $facebook, $linkedin, $googleplus);
		foreach($checks as $check) {
			if(trim($check) == '') {
				$checkAmount ++;
			}
		}

		if($checkAmount >= 4) {
			OutputMessages::setMessage('<b>A username has to be associated</b><br>At least one username must be entered. Having trouble? Try our support channels', 'danger');
			header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 303);
			exit;
		}

		try {
			$database->query("INSERT INTO names (user_id, names_url, names_twitter, names_facebook, names_linkedin, names_googleplus, names_created) VALUES (:user_id, :url, :twitter, :facebook, :linkedin, :googleplus, :created)");
			$database->bind(":user_id", $login->getUserId());
			$database->bind(":url", $url);
			$database->bind(":twitter", $twitter);
			$database->bind(":facebook", $facebook);
			$database->bind(":linkedin", $linkedin);
			$database->bind(":googleplus", $googleplus);
			$database->bind(":created", time());
			$database->execute();

			OutputMessages::setMessage('<b>Super</b>. That\'s another username added to the list.', 'success');
			header('Location: http://' . $_SERVER['HTTP_HOST'] . '/edit', true, 303);
			exit;

		} catch(Exception $e) {

			OutputMessages::setMessage('<b>We failed to save that, with the following error.</b><br>' . $e->getMessage(), 'danger');
			header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 303);
			exit;
		}

	}


}