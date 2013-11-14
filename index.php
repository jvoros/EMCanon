<?
session_start();
date_default_timezone_set('America/Denver');

require 'vendor/autoload.php';

// initialize RedBean
R::setup('sqlite:emcanon.sqlite');

// initialize Opauth


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
$app->view->parserExtensions = array(new \Slim\Views\TwigExtension());


// ROUTES
$app->get('/', function() use($app) {
    $test = R::findAll('writeup');
    $app->render('index.html');
});

$app->get('/review/:id', function($id) use($app) {
    $test = R::load('review', $id);
    $data = $test->export();
    $app->render('review.html', $data);
});

$app->get('/auth/login(/:strategy(/:token))', function($action, $strategy = '', $token = '') use ($app) {  
        require 'opauth.conf.php';
        $opauth = new Opauth($config);
        $opauth->run();
});

$app->post('/auth/response', function() use ($app) {
           $response = unserialize(base64_decode($_POST['opauth']));
        echo "<pre>";
        print_r($response);
});


$app->run();

// initialize Opauth
