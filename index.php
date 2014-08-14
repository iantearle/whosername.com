<?php
// Composer
require 'vendor/autoload.php';

Dotenv::load(__DIR__);

require 'base/config.php';
require 'base/classes/PHPLogin.php';
require 'base/classes/Database.php';
require 'base/classes/Options.php';
require 'base/classes/PrepareContent.php';
require 'base/classes/OutputMessages.php';
require 'base/classes/Serial.php';
require 'base/lib/pagination.php';

Stripe::setApiKey(API_KEY);

$app        = new \Slim\Slim();
$login      = new PHPLogin();
$database   = new Database();
$header     = new stdClass();
$footer     = new stdClass();
$content    = new stdClass();
$menu       = new stdClass();
$addExtras  = new stdClass();
$hashids 	= new Hashids\Hashids('Who am I');

$app->config(array(
	'view' => new Ets(),
    'templates.path' => 'base/templates'
));

$app->view->parserDirectory = dirname(__FILE__) . '/vendor/little-polar-apps/ets';
$app->view->parserCacheDirectory = dirname(__FILE__) . '/cache';
$app->view->setTemplatesDirectory(dirname(__FILE__) . '/base/templates');

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
	
	$header->{$xp} = $xv;
	$footer->{$xp} = $xv;
	$menu->{$xp} = $xv;
	$xmlvars[$xp] = $xv;
	$content->{$xp} = $xv;
	
}

$app->view->set('options', $options);
$app->view->set('login', $login);
$app->view->set('database', $database);
$app->view->make_header($header);
$app->view->make_menu($header);
$app->view->make_footer($header);
$app->view->make_content($content);

$app->view->user_vars['header']['date'] = time();

$app->view->user_vars['main']['captcha'] = WORDING_REGISTRATION_CAPTCHA;
$app->view->user_vars['main']['remember_me'] = WORDING_REMEMBER_ME;
$app->view->user_vars['main']['output'] = OutputMessages::showMessage();


$app->map('/', function () use ($options, $login, $app) {


	if($login->isUserLoggedIn()) {

		$app->view->user_vars['header']['title'] = 'Dashboard';
		$app->view->set('content', PrepareContent::getDetails($login->getUserId()));
		$app->render('dashboard.tpl.html');

	} else {

		$app->view->user_vars['header']['title'] = 'Home';
		$app->render('home.tpl.html');

	}

})->via('GET', 'POST');


$app->map('/login', function () use ($app) {

	$app->render('login.tpl.html');

})->via('GET', 'POST');


$app->get('/logout', function () use ($login, $app) {

    $login->doLogout();

	header("location: ". YOURSITE);
	exit;

});


$app->map('/register', function () use ($login, $app) {

	$app->view->user_vars['main']['registration_successful'] = (isset($_GET['verification_code']) || $login->isRegistrationSuccessful() && 
   (ALLOW_USER_REGISTRATION || (ALLOW_ADMIN_TO_REGISTER_NEW_USER && $_SESSION['user_access_level'] == 255))) ? true : null;
	$app->view->user_vars['main']['registration_verified'] = (isset($_GET['verification_code'])) ? true : null;

	$app->render('register.tpl.html');

})->via('GET', 'POST');


$app->map('/forgot', function () use ($app) {

	$app->render('login.reset_password.php');

})->via('GET', 'POST');


$app->map('/add', function () use ($app, $login) {
	
	$app->view->user_vars['header']['title'] = 'Add a username';
	
	if($login->isUserLoggedIn()) {
	
		$app->render('add.tpl.html');
		
	} else {
	
		$app->flash('error', 'Login required');
		$app->redirect('/login');
        
	}

})->via('GET', 'POST');


$app->map('/subscription', function() use($app, $login, $options) {
		
	if($login->isUserLoggedIn()) {
	
		if(!$options->getOption('stripe_sub_customer')) {

			$app->view->user_vars['header']['title'] = 'Start your subscription';
			$app->render('subscription.tpl.html');

		} else {
		
			$app->redirect('/');
			
		}
		
	}
	
})->via('GET', 'POST');


$app->get('/my-api', function() use($app, $login, $options) {
		
	if($login->isUserLoggedIn()) {
	
		if($options->getOption('stripe_sub_customer')) {

			$app->view->user_vars['header']['title'] = 'My API';
			$app->render('my-api.tpl.html');

		} else {
		
			$app->redirect('/subscription');
			
		}
	}
	
});


$app->get('/api/:api_key/:url', function ($api_key, $url) use ($content, $hashids, $database, $app) {

	$hash = $hashids->decrypt($api_key);

	$database->query("SELECT * FROM users WHERE user_id = :id");
	$database->bind(":id", $hash[0]);
	$database->execute();

	$count = $database->rowCount();

	if($count) {
	
		$app->view->set('content', PrepareContent::getResults($hash, $url));
		$app->response()->header('Content-Type', 'application/json');
		$app->render(array('api.tpl.html'));
		
	} else {
	
		$app->notFound();
		
	}

});


$app->run();
