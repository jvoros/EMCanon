<?
/*********************************
    INITIALIZE COMPONENTS
*********************************/

session_cache_limiter(false);
session_start();
date_default_timezone_set('America/Denver');

// composer bootstrapping
require 'vendor/autoload.php';

// initialize RedBean
R::setup('sqlite:emcanon.sqlite');
R::freeze(true);

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
    'autoescape' => true,
    'debug' => true
);
// give Twig templates access to session variables, dump() function, Slim View Extras
$app->view->getEnvironment()->addGlobal("session", $_SESSION); 
$app->view->getEnvironment()->addExtension(new \Twig_Extension_Debug());
$app->view->parserExtensions = array(new \Slim\Views\TwigExtension(), new \Twig_Extension_Debug());


// prepare AuthProtect route middleware
$protect = new AuthProtect($app);


/*********************************
    ROUTES
*********************************/

$app->get('/', function() use($app) {
    $recent = R::findAll('review', ' draft = "false" ORDER BY published DESC LIMIT 5');
    R::preload($recent, 'article,article.sharedTag|tag,user');
    foreach ($recent as $wu) {
        $wu->voteup = $wu->article->countOwn('voteup');
        $wu->votedown = $wu->article->countOwn('votedown');
    }
    $latest = array_shift($recent);
    $app->render('index.html', array('latest' => $latest, 'recent' => $recent));
});

$app->get('/review/:id', function($id) use($app) {
    $wu = R::load('review', $id);
    R::preload($wu, 'article,article.sharedTag|tag,user');
    $wu->voteup = $wu->article->countOwn('voteup');
    $wu->votedown = $wu->article->countOwn('votedown');
    $app->render('review.html', array('latest' => $wu));
});

$app->get('/reviews(/:page)', function($page) use($app) {
    $limit = 5;
    $totalpages = ceil(R::count('review')/$limit);
    if ($page < $totalpages) { $next = $page+1; } else { $next = FALSE; }
    $all = R::findAll('review', ' draft = "false" ORDER BY published DESC LIMIT ?,?', array((($page-1)*$limit), $limit));
    R::preload($all, 'article,article.sharedTag|tag,user');
    foreach ($all as $wu) {
        $wu->voteup = $wu->article->countOwn('voteup');
        $wu->votedown = $wu->article->countOwn('votedown');
    }
    $app->render('writeups.html', array('all' => $all, 'next' => $next));
});


// TEST ROUTES

$app->get('/test', function() use($app) {
    echo "<pre>";
    print_r($_SESSION);
});

$app->get('/secret', $protect->protect(), function() use($app) {
    $app->render('secret.html');
});

$app->get('/bs', function() use($app) {
    $app->render('bs_base.html');
});

// AUTHORIZATION HANDLING

$app->get('/auth/login(/:strategy(/:token))', function($action, $strategy = '', $token = '') use ($app) { 
    // because of Opauth redirects, need to only store referring URL on first visit to the login page, set flag to ensure this is the case
    if (empty($_SESSION['authredirect_flag'])) { 
        // store page prior to login click for redirect
        $_SESSION['authredirect'] = $_SERVER['HTTP_REFERER'];
        // set flag
        $_SESSION['authredirect_flag'] = 1;
    }
    
    // Opauth library for external provider authentication
    require 'opauth.conf.php';
    $opauth = new Opauth($config);
});

$app->post('/auth/response', function() use ($app) {
    // get Opauth response
    $re = unserialize(base64_decode($_POST['opauth']));
    
    // instantiate Opauth
    require 'opauth.conf.php';
    $Opauth = new Opauth($config, false);
    
    // custom authresponse handler
    $authresponse = new AuthResponse($re, $Opauth);
    $authresponse->login();
    
    // reset session variables
    unset($_SESSION['opauth']);
    unset($_SESSION['authredirect_flag']);
    
    // redirect to homepage
    $app->response->redirect('http://localhost/emcanon/', 303); 
    
});

$app->get('/auth/logout', function() use($app) {
    unset($_SESSION['loggedin']);
    unset($_SESSION['user']);
    $app->redirect('http://localhost/emcanon/', 303);
});

// RUN

$app->run();

