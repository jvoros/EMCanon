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
$Rschema = R::$duplicationManager->getSchema();
R::$duplicationManager->setTables($Rschema);
R::freeze(true);

// app wide utility functions and constants
define('BASE_URL', 'http://localhost/emcanon');
function sort_articles_by_date($a, $b) {
    $A = $a->year . $a->month . $a->day;
    $B = $b->year . $b->month . $b->day;
	if($A == $B){ return 0 ; }
	return ($A < $B) ? -1 : 1;
}

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
$app->view->getEnvironment()->addGlobal('session', $_SESSION);
$app->view->getEnvironment()->addGlobal('base_url', BASE_URL);
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
        $wu->comment = $wu->article->countOwn('comment');
        $user_voteup = $wu->article->withCondition(' user_id =  ' . $_SESSION['user']['id'])->ownVoteup;
        $user_votedown = $wu->article->withCondition(' user_id = ' . $_SESSION['user']['id'])->ownVotedown;
        if (!empty($user_voteup) || !empty($user_votedown)) { $wu->voteflag = 1; }
    }
    $latest = array_shift($recent);
    $app->render('index.html', array('latest' => $latest, 'recent' => $recent));
});

$app->get('/review/:id', function($id) use($app) {    
    $article = R::load('article', $id);
    R::preload($article, 'sharedTag|tag,ownComment|comment,ownComment.user|user,ownReview|review,ownReview.user|user');
    $article->voteup = $article->countOwn('voteup');
    $article->votedown = $article->countOwn('votedown');
    $article->comment = $article->countOwn('comment');
    $app->render('review.html', array('article' => $article));    
});

$app->get('/reviews(/:page)', function($page = 1) use($app) {
    $limit = 5;
    $totalpages = ceil(R::count('review')/$limit);
    if ($page < $totalpages) { $next = $page+1; } else { $next = FALSE; }
    $all = R::findAll('review', ' draft = "false" ORDER BY published DESC LIMIT ?,?', array((($page-1)*$limit), $limit));
    R::preload($all, 'article,article.sharedTag|tag,user');
    foreach ($all as $wu) {
        $wu->voteup = $wu->article->countOwn('voteup');
        $wu->votedown = $wu->article->countOwn('votedown');
        $wu->comment = $wu->article->countOwn('comment');
    }
    $app->render('reviews.html', array('recent' => $all, 'next' => $next, 'page' => $page));
});

$app->get('/canon', function() use($app) {
    $tags = R::findAll('tag', ' ORDER BY title ASC');
    R::preload($tags, 'sharedArticle|article,*.ownReview|review');
    foreach ($tags as $tag) { 
        foreach ($tag->sharedArticle as $article) {
            $article->voteup = $article->countOwn('voteup');
            $article->votedown = $article->countOwn('votedown');
            $article->comments = $article->countOwn('comment');
        }
    }
    $data = R::exportAll($tags, true, array('tag','article','review'));
    $app->render('canon.html', array('tags' => $data));
});

$app->get('/canon/:tag', function($tag) use($app) {
    $tags = R::find('tag', ' title = ?', array($tag));
    R::preload($tags, 'sharedArticle|article,*.ownReview|review');
    foreach ($tags as $tag) { 
        foreach ($tag->sharedArticle as $article) {
            $article->voteup = $article->countOwn('voteup');
            $article->votedown = $article->countOwn('votedown');
            $article->comments = $article->countOwn('comment');
        }
    }
    $data = R::exportAll($tags, true, array('tag','article','review'));
    $app->render('canon.html', array('tags' => $data));
}); 

// INTERACT ROUTES

$app->group('/interact', $protect->protect(), function() use($app) {
    $app->post('/vote/:dir/:id', function($dir, $id) use($app) {
        $vote = 'vote' . $dir;
        $vote           = R::dispense($vote);
        $vote->user_id = $_SESSION['user']['id'];
        $vote->article_id = $id    ;
        $vote->timestamp   = date("Y-m-d H:i:s");
        //$vote_id           = R::store($vote);
        
        $app->response()->header('Content-Type', 'application/json');
        echo json_encode(array('respond' => 'OK', 'vote_id' => $vote_id));
    });
    
    $app->post('/commentlike/:cid', function($cid) use($app) {
    });
    
    $app->post('/comment/:id', function($id) use($app) {
    });
    
    $app->post('/submit', function() use($app) {
        $pmid = $app->request->post('PMID');
        
        // check format
        if (!preg_match('/^[0-9]+$/', $pmid)) { 
            $response = array('status' => 'error', 'text' => 'wrong format for PMID');
            echo json_encode($response);
        } 
    });

});

// TEST ROUTE

$app->get('/test-articles', function() use($app) {
    $articles = R::findAll('article');
    foreach ($articles as $article) {
        $article->voteup = $article->countOwn('voteup');
        $article->votedown = $article->countOwn('votedown');
        $article->comments = $article->countOwn('comment');
    }
    $articles = R::exportAll($articles, true, array('article','review'));
    echo "<pre>";
    print_r($articles);
});

$app->get('/test', function() use($app) {
    $article = R::load('article', 9);
    $article->voteup = $article->countOwn('voteup');
    $article->votedown = $article->countOwn('votedown');
    $article->comment = $article->countOwn('comment');
    R::preload($article, 'sharedTag|tag,ownComment|comment,ownComment.user|user,ownReview|review,ownReview.user|user');
        echo "<pre>";
    print_r($article->export());
});

$app->get('/parse/:id', function($id) use($app) {
    $parse = new PMParser;
    $article = $parse->getArticle($id);
    echo var_dump($article);
});

$app->get('/secret', $protect->protect(), function() use($app) {
    $app->render('secret.html');
});


// AUTHORIZATION HANDLING

$app->get('/login', function() use($app) {
    $_SESSION['authredirect'] = $app->request->getReferer();
    // set flag
    $_SESSION['authredirect_flag'] = 1;
    $app->render('login.html');
});

$app->get('/auth/login(/:strategy(/:token))', function($action, $strategy = '', $token = '') use ($app) { 
    // because of Opauth redirects, need to only store referring URL on first visit to the login page, set flag to ensure this is the case
    if (empty($_SESSION['authredirect_flag'])) { 
        // store page prior to login click for redirect
        $_SESSION['authredirect'] = $app->request->getReferer();
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
    $app->response->redirect($_SESSION['authredirect'], 303); 
    
});

$app->get('/auth/logout', function() use($app) {
    unset($_SESSION['loggedin']);
    unset($_SESSION['user']);
    $app->redirect('http://localhost/emcanon/', 303);
});

/*********************************
    RUN
*********************************/

$app->run();

