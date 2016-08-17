<?php
// Composer
require 'vendor/autoload.php';

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

require 'base/config.php';
require 'base/classes/PHPLogin.php';
require 'base/classes/Database.php';
require 'base/classes/APIRateLimit.php';
require 'base/classes/Options.php';
require 'base/classes/PrepareContent.php';
require 'base/classes/OutputMessages.php';
require 'base/classes/Serial.php';
require 'base/lib/pagination.php';

\Stripe\Stripe::setApiKey(API_KEY);

$container = new \Slim\Container([
    'settings' => [
        'displayErrorDetails' => true,
    ],
]);

$container['view'] = function ($c) {
    $view = new \Slim\Views\Ets();

    $view->user_vars['header']['date'] = time();
    $view->user_vars['main']['captcha']        = WORDING_REGISTRATION_CAPTCHA;
    $view->user_vars['main']['remember_me']    = WORDING_REMEMBER_ME;
    $view->user_vars['main']['wording_new_password']    = WORDING_NEW_PASSWORD;
    $view->user_vars['main']['wording_new_password_repeat']    = WORDING_NEW_PASSWORD_REPEAT;
    $view->user_vars['main']['wording_submit_new_password']    = WORDING_SUBMIT_NEW_PASSWORD;
    $view->user_vars['main']['wording_request_password_reset']    = WORDING_REQUEST_PASSWORD_RESET;
    $view->user_vars['main']['wording_reset_password']    = WORDING_RESET_PASSWORD;
    $view->user_vars['main']['wording_back_to_login']    = WORDING_BACK_TO_LOGIN;
    $view->user_vars['main']['output']  = OutputMessages::showMessage();

    return $view;
};

$container['notFound'] = function ($c) {
    return function ($request, $response) use ($c) {
        return $c->view->render($response, "404.tpl.html");
    };
};

$container['flash'] = function () {
    return new \Slim\Flash\Messages();
};

$app        = new \Slim\App($container);
$login      = new PHPLogin();
$database   = new Database();
$header     = new stdClass();
$footer     = new stdClass();
$content    = new stdClass();
$menu       = new stdClass();
$addExtras  = new stdClass();
$hashids 	= new Hashids\Hashids('Who am I');

$app->parserDirectory = dirname(__FILE__) . '/vendor/little-polar-apps/ets';
$app->parserCacheDirectory = dirname(__FILE__) . '/base/cache';

$options = array();

if($login->isUserLoggedIn()) {

	$user_id = $login->getUserId();

	$options = new Options(array_merge(array(array("user_id" => (int) $login->getUserId())), $database->get_user_options($user_id)));

	foreach($options as $optname => $optval) {

		$header->{$optname} = $optval;
		$footer->{$optname} = $optval;
		$menu->{$optname} = $optval;
		$user_vars['main'][$optname] = $xv;

	}

}

require 'base/functions/post.php';
$app->header         = new stdClass();
$app->menu           = new stdClass();
$app->footer         = new stdClass();
$app->main           = new stdClass();
$app->content        = PrepareContent::getInstance();
$content    		 = new stdClass();
$menu       		 = new stdClass();

$addExtras->version = '&Beta;0.2.';
$addExtras->yoursite = YOURSITE;
$addExtras->copyrightdate = isset($addExtras->copyrightdate) ? $addExtras->copyrightdate : date('Y');
$addExtras->logged_in = $login->isUserLoggedIn();
$addExtras->themetemplates = YOURSITE.'base/templates';
$addExtras->css_link = YOURSITE . 'base/css/styles.css';
$addExtras->javascript_link = YOURSITE . 'base/javascript/javascript.js';
$addExtras->images_folder = YOURSITE."base/images/";
$addExtras->templates_folder = YOURSITE."base/templates/";
$addExtras->css_folder = YOURSITE."base/css/";
$addExtras->javascript_folder = YOURSITE."base/javascript/";
$addExtras->stripe_secret_key = API_KEY;
$addExtras->stripe_publishable_key = PUBLISHABLE_KEY;
$addExtras->amount = AMOUNT;
$addExtras->currency = CURRENCY;

foreach($addExtras as $xp => $xv) {

	$app->header->{$xp} = $xv;
	$app->footer->{$xp} = $xv;
	$app->menu->{$xp} = $xv;
	$xmlvars[$xp] = $xv;
	$app->content->{$xp} = $xv;

}

// $app->view->set('options', $options);
// $app->view->set('login', $login);
// $app->view->set('database', $database);

$app->map(['GET', 'POST'], '/', function ($request, $response, $args) use ($options, $login, $app) {

    $this->view->make_header($app->header);
    $this->view->make_menu($app->header);
    $this->view->make_footer($app->header);

	if($login->isUserLoggedIn()) {

		$this->view->user_vars['header']['title'] = 'Dashboard';
		$this->view->make_content($app->content->prepareContent($app->content, PrepareContent::getDetails($login)));
		$this->view->render($response, 'dashboard.tpl.html');

	} else {

		$this->view->user_vars['header']['title'] = 'Home';
		return $this->view->render($response, 'home.tpl.html');

	}

});


$app->map(['GET', 'POST'], '/login', function ($request, $response, $args) use ($app) {

    $this->view->make_header($app->header);
    $this->view->make_menu($app->header);
    $this->view->make_footer($app->header);
    $this->view->make_content($app->content);

    $app->view->user_vars['header']['title'] = 'Login';
	return $this->view->render($response, 'login.tpl.html');

})->setName('login');


$app->get('/logout', function () use ($login, $app) {

    $login->doLogout();

	header("location: ". YOURSITE);
	exit;

})->setName('logout');;

$app->map(['GET', 'POST'], '/register', function ($request, $response, $args) use ($login, $app) {

    $this->view->make_header($app->header);
    $this->view->make_menu($app->header);
    $this->view->make_footer($app->header);
    $this->view->make_content($app->content);

    $app->view->user_vars['header']['title'] = 'Register';
	$app->view->user_vars['main']['registration_successful'] = (isset($_GET['verification_code']) || $login->isRegistrationSuccessful() &&
   (ALLOW_USER_REGISTRATION || (ALLOW_ADMIN_TO_REGISTER_NEW_USER && $_SESSION['user_access_level'] == 255))) ? true : null;
	$app->view->user_vars['main']['registration_verified'] = (isset($_GET['verification_code'])) ? true : null;

	return $this->view->render($response, 'register.tpl.html');

})->setName('register');


$app->map(['GET', 'POST'], '/forgot', function ($request, $response, $args) use ($app, $login) {

    $this->view->make_header($app->header);
    $this->view->make_menu($app->header);
    $this->view->make_footer($app->header);
    $this->view->make_content($app->content);

	$this->view->user_vars['main']['password_reset_link'] = $login->isPasswordResetLinkValid();

	$this->view->user_vars['header']['title'] = 'Reset Password';
	return $this->view->render($response, 'reset_password.tpl.html');

})->setName('forgot');


$app->map(['GET', 'POST'], '/add', function ($request, $response, $args) use ($app, $login) {

    $this->view->make_header($app->header);
    $this->view->make_menu($app->header);
    $this->view->make_footer($app->header);
    $this->view->make_content($app->content);

	$this->view->user_vars['header']['title'] = 'Add a username';

	if($login->isUserLoggedIn()) {

		return $this->view->render($response, 'add.tpl.html');

	} else {

		$this->flash->addMessage('error', 'Login required');
		return $response->withRedirect($this->router->pathFor('login'));

	}

})->setName('add');

$app->get('/edit', function ($request, $response, $args) use($app, $login, $options) {

    $this->view->make_header($app->header);
    $this->view->make_menu($app->header);
    $this->view->make_footer($app->header);

	if($login->isUserLoggedIn()) {
        $this->view->user_vars['header']['title'] = 'Edit a username';
		$this->view->make_content($app->content->prepareContent($app->content, PrepareContent::getResultsForEdit()));

	} else {

		$this->flash->addMessage('error', 'Login required');
		return $response->withRedirect($this->router->pathFor('login'));

	}

	return $this->view->render($response, 'edit_full.tpl.html');

})->setName('edit');

$app->map(['GET', 'POST'], '/edit/{id}', function ($request, $response, $args) use ($app, $login) {

    $this->view->make_header($app->header);
    $this->view->make_menu($app->header);
    $this->view->make_footer($app->header);
    $this->view->make_content($app->content);

	$this->view->user_vars['header']['title'] = 'Edit a username';

	if($login->isUserLoggedIn()) {

		$this->view->make_content($app->content->prepareContent($app->content, PrepareContent::getResultsForEdit($args['id'])));
		return $this->view->render($response, 'edit.tpl.html');

	} else {

		$this->flash->addMessage('error', 'Login required');
		return $response->withRedirect($this->router->pathFor('login'));

	}

});


$app->map(['GET', 'POST'], '/subscription', function ($request, $response, $args) use($app, $login, $options) {

    $this->view->make_header($app->header);
    $this->view->make_menu($app->header);
    $this->view->make_footer($app->header);
    $this->view->make_content($app->content);

	if($login->isUserLoggedIn()) {

        if($options->getOption('stripe_sub_customer') || $login->getUserAccessLevel() >= 200) {
            return $response->withRedirect($this->router->pathFor('api'));
        } else {

    		$app->view->user_vars['header']['title'] = 'Start your subscription';
			$app->view->user_vars['main']['email'] = $login->getUserEmail();
			return $this->view->render($response, 'subscription.tpl.html');
        }

	} else {

		$this->flash->addMessage('error', 'Login required');
		return $response->withRedirect($this->router->pathFor('login'));

	}

})->setName('subscription');

$app->map(['GET', 'POST'], '/settings', function ($request, $response, $args) use($app, $login, $options) {

    $this->view->make_header($app->header);
    $this->view->make_menu($app->header);
    $this->view->make_footer($app->header);

	if($login->isUserLoggedIn()) {

		$this->view->user_vars['header']['title'] = 'Settings';
		$this->view->make_content($app->content->prepareContent($app->content, PrepareContent::getSettings($login->getUserId())));
		return $this->view->render($response, 'settings.tpl.html');

	} else {

		$this->flash->addMessage('error', 'Login required');
		return $response->withRedirect($this->router->pathFor('login'));

	}

})->setName('settings');


$app->get('/my-api', function ($request, $response, $args) use($app, $login, $options) {

    $this->view->make_header($app->header);
    $this->view->make_menu($app->header);
    $this->view->make_footer($app->header);

	if($login->isUserLoggedIn()) {

		if($options->getOption('stripe_sub_customer') || $login->getUserAccessLevel() >= 200) {

			$this->view->user_vars['header']['title'] = 'My API';
			$this->view->make_content($app->content->prepareContent($app->content, PrepareContent::getDetails($login)));
			return $this->view->render($response, 'my-api.tpl.html');

		} else {

			return $response->withRedirect($this->router->pathFor('subscription'));

		}
	} else {

		$this->flash->addMessage('error', 'Login required');
		return $response->withRedirect($this->router->pathFor('login'));

	}

})->setName('myapi');

$app->get('/api/{api_key}/{url}/{format}', function ($request, $response, $args)  use ($content, $hashids, $database, $app) { //($api_key, $url, $format = null)

	$hash = $hashids->decrypt($args['api_key']);

    if(!$hash) {
        return $response->withStatus(403)->withHeader('AuthenticationFailed', 'Server failed to authenticate the request. Make sure the value of the Authorisation header is formed correctly including the signature.');
    }

    $database->query("SELECT * FROM users WHERE user_id = :id");
	$database->bind(":id", $hash[0]);
	$database->execute();

	$count = $database->rowCount();

	if($count) {

		switch($args['format']) {

			case 'json':

				$this->view->user_vars['main']['type'] = 'json';
				$content = PrepareContent::getResults($args['api_key'], $args['url']);
				$this->view->make_content($content);
				if(empty($content->content)) {
                    return $this->view->render($response->withStatus(404), array('api.tpl.html'))->withHeader('Content-Type', 'application/json');
				} else {
                    return $this->view->render($response, array('api.tpl.html'))->withHeader('Content-Type', 'application/json');
				}
				break;

			case 'xml':

				$this->view->user_vars['main']['type'] = 'xml';
				$content = PrepareContent::getResults($args['api_key'], $args['url']);
				$this->view->make_content($content);
				if(empty($content->content)) {
                    return $this->view->render($response->withStatus(404), array('api.tpl.html'))->withHeader('Content-Type', 'application/xml');
				} else {
                    return $this->view->render($response, array('api.tpl.html'))->withHeader('Content-Type', 'application/xml');
				}
				break;

			default:
				return $response->withStatus(400)->withHeader('InvalidUri', 'The requested URI does not represent any resource on the server.');
				break;

		}

	} else {

        return $response->withStatus(403)->withHeader('AuthenticationFailed', 'Server failed to authenticate the request. Make sure the value of the Authorisation header is formed correctly including the signature.');

	}

})->add(function ($request, $response, $next) {

    $requests = 250; // maximum number of requests
    $inmins = 60;    // in how many time (minutes)

    $APIRateLimit = new APIRateLimit($requests, $inmins);
    $mustbethrottled = $APIRateLimit();

    if ($mustbethrottled == false) {
        return $next($request, $response);
    } else {
        return $response->withStatus(429)->withHeader('RateLimit-Limit', $requests);
    }
});

$app->get('/changelog', function ($request, $response, $args) use($app) {

    $this->view->make_header($app->header);
    $this->view->make_menu($app->header);
    $this->view->make_footer($app->header);
    $this->view->make_content($app->content);

	return $this->view->render($response, 'changelog.tpl.html');

});

$app->get('/javascript/{files}', function ($request, $response, $args) use($app) {

	$_GET['type'] = 'javascript';
	$_GET['files'] = $args['files'];
	include('base/functions/combine.php');

});

$app->get('/css/{files}', function ($request, $response, $args) use($app) {

	$_GET['type'] = 'css';
	$_GET['files'] = $args['files'];
	include('base/functions/combine.php');

});


$app->run();
