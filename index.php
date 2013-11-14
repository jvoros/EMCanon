<?
session_cache_limiter(false);
session_start();
date_default_timezone_set('America/Denver');

// composer bootstrapping
require 'vendor/autoload.php';

// initialize RedBean
R::setup('sqlite:emcanon.sqlite');

// initialize Slim, use Twig to render views
$app = new \Slim\Slim(array(
    'templates.path' => 'templates',
));

// prepare Twig view
$app->view(new \Slim\Views\Twig());
$app->view->parserOptions = array(
    'charset' => 'utf-8',
    'cache' => realpath('../templates/cache'),
    'auto_reload' => true,
    'strict_variables' => false,
    'autoescape' => true
);
$app->view->getEnvironment()->addGlobal("session", $_SESSION);
$app->view->parserExtensions = array(new \Slim\Views\TwigExtension());

// prepare AuthProtect route middleware
$protect = new AuthProtect($app);

// ROUTES
$app->get('/', function() use($app) {
    $test = R::findAll('writeup');
    $app->render('index.html');
});

$app->get('/test', function() use($app) {
    echo "<pre>";
    print_r($_SESSION);
});

$app->get('/secret', $protect->protect(), function() use($app) {
    $app->render('secret.html');
});

$app->get('/review/:id', function($id) use($app) {
    $test = R::load('review', $id);
    $data = $test->export();
    $app->render('review.html', $data);
});

$app->get('/auth/login(/:strategy(/:token))', function($action, $strategy = '', $token = '') use ($app) { 
    // store page prior to login click for redirect
    $_SESSION['authredirect'] = $_SERVER['HTTP_REFERER'];
    require 'opauth.conf.php';
    $opauth = new Opauth($config);
    $opauth->run();
});

$app->post('/auth/response', function() use ($app) {
    // get response
    $response = unserialize(base64_decode($_POST['opauth']));
    
    // instantiate Opauth
    require 'opauth.conf.php';
    $Opauth = new Opauth($config, false);
    
    // custom authresponse handler
    $authresponse = new AuthResponse($response, $Opauth);
    $authresponse->login();
    
    // redirect to homepage
    //$app->response->redirect('http://localhost/emcanon/', 303);
 
});

$app->get('/auth/logout', function() use($app) {
    $_SESSION['loggedin'] = FALSE;
    $_SESSION['user'] = array();
    $app->response->redirect('http://localhost/emcanon/', 303);
});

// RUN
$app->run();

