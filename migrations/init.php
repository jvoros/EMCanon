<?
// script to load initial data into database

date_default_timezone_set('America/Denver');
require '../vendor/autoload.php';

// initialize RedBean
R::setup('sqlite:../emcanon.sqlite');

// emtpy old database
R::nuke();
echo "Nuked old dbase<br />";

// ARTICLES
// initial articles
$urls = array(
    '2386557',
    'http://www.ncbi.nlm.nih.gov/pubmed/12930925',
    'http://www.ncbi.nlm.nih.gov/pubmed/11856794',
    'http://www.ncbi.nlm.nih.gov/pubmed/20924010',
    'http://www.ncbi.nlm.nih.gov/pubmed/1309182',
    'http://www.ncbi.nlm.nih.gov/pubmed/6121481',
    'http://www.ncbi.nlm.nih.gov/pubmed/11907287',
    'http://www.ncbi.nlm.nih.gov/pubmed/18596271',
    'http://www.ncbi.nlm.nih.gov/pubmed/8204123',
    '10685714',
    '11856793'
);

// parse 'em and load 'em with EMCanon PMParser
foreach ($urls as $url) {
    $article = PMParser::getArticleFromURL($url);
    $article['type'] = 'article';
    $articleBean = R::graph($article);
    $article_id = R::store($articleBean);
}

// demo article
$article = R::load('article', 1);

echo "Loaded articles <br />";


// TAGS
R::tag($article, array('critical care', 'cardiology'));


// ROLES
// initial user roles
$user = R::dispense('role');
$user->role = 'user';
$user_id = R::store($user);

$author = R::dispense('role');
$author->role = 'author';
$author_id = R::store($author);

$editor = R::dispense('role');
$editor->role = 'editor';
$editor_id = R::store($editor);

echo "Created roles <br />";


//USERS
// initial user
$me                 = R::dispense('user');
$me->name           = "Scott Weingert";
$me->title          = "MD";
$me->email          = "swine@emcrit.com";
$me->sharedRole[]   = $editor;
$me->created        = "2013-11-01 17:30:30";
$me->last_visit     = date("Y-m-d H:i:s");
$me->twitter        = "swinemcrit"; //twitter nickname
$me->facebook       = "scotty.winegart"; //facebook nickname
$me->google         = "swine@gmail.com"; //google email
$me->gplus          = "https://plus.google.com/115814176630336921439"; //google plus
$me->thumb          = "https://lh3.googleusercontent.com/-l0uuuL-hNEk/AAAAAAAAAAI/AAAAAAAAAAA/6aSflWv5hNQ/photo.jpg";
$me->bio            = "Etsy trust fund yr hoodie selfies, beard scenester pop-up drinking vinegar pickled plaid iPhone cliche kale chips before they sold out. Hella scenester PBR&B High Life American Apparel, twee flannel. Pinterest cred chillwave, distillery food truck Bushwick locavore McSweeney's lo-fi selfies letterpress blog readymade tofu.";

$me_id = R::store($me);
echo "Created user <br />";


//WRITEUP
// initial writeup
$wu = R::dispense('writeup');
$wu->article = $article;
$wu->draft = 'false';
$wu->author = $me;
$wu->published = '2013-11-10';
$wu->text = '
<h2>Takehome</h2>
During a cardiopulmonary resuscitation, an end-tidal CO2 of less than 10mm Hg in patients with a PEA rhythm portends a poor prognosis.

<h2>Background</h2>
There are over 300,000 out-of-hospital cardiac arrests every year in the United States, with approximately 10% of patients surviving to hospital discharge. The number of patients who are admitted to the hospital but die prior to discharge is costly both to society as well as to individual families. At the time that this paper was written there were few markers of prognosis that could be used during a resuscitation to predict whether a patient would regain circulation or survive to discharge. Prior research had demonstrated that end-tidal carbon dioxide (etCO2) could be used as an indirect measure of cardiac output during a resuscitation, and these authors hypothesized that etCO2 could be used in the pre-hospital setting to predict which patient in pulseless-electrical activity (PEA) would die. 

<h2>Analysis</h2>
<p>This was a prospective study involving 154 consecutive cardiac arrest victims in a single county in the United States of America with a population of approximately 160,000 people. Between 1991-1995, patients over the age of 18 who had a cardiac arrest with associated PEA were included in the study. Patients who had arrests due to hypothermia, poisoning, trauma, hypovolemia, tension pneumothorax, and cardiac tamponade were excluded. All patients received basic ACLS interventions including intubation by the paramedics. After intubation, etCO2 was measured continuously during the remaining resuscitation. A final etCO2 measurement was taken after 20 minutes of resuscitation, and a level of 10 mmHg or less was determined as the cutoff for patients who would likely not survive. Groups were compared using a Wilcoxon-rank sum test.</p>

<p>150 patients were included in the final patient population. In an initial analysis looking at survival to hospital admission (ROSC), there were no differences between survivors and non-survivors in the initial etCO2 level recorded (non-survivors 12.3Â±6.9; survivors 12.2Â±4.6), although survivors had a statistically increased etCO2 at 20 minutes (non-survivors 4.4Â±2.9; survivors 32.8Â±7.4) (table 1, page 302). An etCO2 of 10mmHg or less had a 100% sensitivity, specificity, positive predictive value and negative predictive value in determining who would die prior to admission to the hospital. Of the patients who survived to hospital admission, 20-minute etCO2 levels failed to predict who would survive to hospital discharge (non-survivors 31.8Â±7.3; survivors 34.0Â±7.7). The authors concluded that an etCO2 less than 10mmHg measured after 20 minutes of CPR could accurately predict who would survive to hospital admission, but was not predictive of who would go on to survive to discharge. The authors recommended that in patients with an etCO2 less than 10mm Hg after 20 minutes of CPR a resuscitation could be terminated.</p>

<h2>Strength</h2>
Although the results of this paper have gained widespread usage within the emergency medicine and critical care fields, there are a number of methodological weaknesses. The first limitation of this study is its lack of blinding. Although patients were enrolled prospectively, it is unclear from the methods whether there was any blinding of the purpose of the study, or of the a priori determined 10mm Hg etCO2 cutoff, to the paramedics enrolling and treating patients. This lack of blinding could result in significant response bias, potentially altering providers interventions based upon the etCO2. A second weakness is that the exclusion criteria used for this study greatly limited its application during a resuscitation. Many patients were excluded based upon the mechanism of the arrest, but there was no explanation for why these criteria were used. During a resuscitation it is often difficult to know the exact cause of the arrest, and the extensive exclusion criteria may limit the cases that these results can be applied to. 

<h2>Impact</h2>
Considerable effort has been focused on determining who will survive from a cardiac arrest in order to appropriately focus interventions on salvageable patients. Epidemiological studies have identified that the use of bystander CPR, having a shockable rhythm upon presentation, and the use of a defibrillator are all predictive of improved outcomes, but prior to the publication of this manuscript there were few measurable variables that could be used during a resuscitation to predict which patients were not going to survive. This was the first study to suggest that low etCO2 was a sensitive and specific predictor of patients who would not undergo ROSC, and suggested that it be used in determining when a resuscitation could be terminated. Since the publication of this paper further research has demonstrated that these same concepts can be applied to patients with all initial rhythms (Kolar et al, Salen et al). These concepts have now been widely adopted and are described in emergency medicine textbooks (Rosen\'s).';

$wu_id = R::store($wu);

echo "Created writeup <br />";


// URLS FOR ALL THE ARTICLES
$all_urls = array(
    '2386557',
    'http://www.ncbi.nlm.nih.gov/pubmed/12930925',
    'http://www.ncbi.nlm.nih.gov/pubmed/11856794',
    'http://www.ncbi.nlm.nih.gov/pubmed/20924010',
    'http://www.ncbi.nlm.nih.gov/pubmed/1309182',
    'http://www.ncbi.nlm.nih.gov/pubmed/6121481',
    'http://www.ncbi.nlm.nih.gov/pubmed/11907287',
    'http://www.ncbi.nlm.nih.gov/pubmed/18596271',
    'http://www.ncbi.nlm.nih.gov/pubmed/8204123',
    '10685714',
    '11856793',
    '2899772',
    'http://www.ncbi.nlm.nih.gov/pubmed/14411374',
    '10486418',
    'http://www.ncbi.nlm.nih.gov/pubmed/9233867',
    'http://www.ncbi.nlm.nih.gov/pubmed/10770981',
    'http://www.ncbi.nlm.nih.gov/pubmed/8559200',
    'http://www.ncbi.nlm.nih.gov/pubmed/9171064',
    '15306666',
    '1522840',
    '12571258',
    '15657322',
    'http://www.ncbi.nlm.nih.gov/pubmed/2233916',
    '11389488',
    'http://www.ncbi.nlm.nih.gov/pubmed/12432041',
    '15726056',
    '17192537',
    '11794169',
    '2368794',
    '12724479',
    '2910162',
    '9971864',
    '20179283',
    '15286537',
    '1548720',
    '10793162',
    '7651472',
    '8995086',
    '18318689',
    '14523886',
    '13366553',
    '16549851',
    'http://www.ncbi.nlm.nih.gov/pubmed/7752753',
    'http://www.ncbi.nlm.nih.gov/pubmed/11453709',
    '14507948',
    '11103723',
    'http://www.ncbi.nlm.nih.gov/pubmed/19091393',
    '2278545',
    '7935634',
    '10789667',
    '8189470',
    '12169503',
    'http://www.ncbi.nlm.nih.gov/pubmed/2587134',
    '15385657',
    '15123483',
    '9113963',
    '10390264',
    '14757604',
    '19758692',
    '12359784',
    '1881722',
    '9606243',
    '9685461',
    '23062548',
    '16968847',
    '15459622',
    '10080845',
    '11172179',
    '11525706',
    '2859819',
    '3706169',
    '4100436',
    '12362006',
    '3059186',
    'http://www.ncbi.nlm.nih.gov/pubmed/18396107',
    'http://www.ncbi.nlm.nih.gov/pubmed/20044534',
    '11401607',
    '11742046',
    'http://www.ncbi.nlm.nih.gov/pubmed/11147987',
    '17258668',
    '7477192',
    'http://www.ncbi.nlm.nih.gov/pubmed/12239256',
    '4136544',
    'http://www.ncbi.nlm.nih.gov/pubmed/7893293',
    'http://www.ncbi.nlm.nih.gov/pubmed/10683058',
    '15258866',
    '10891517',
    '12131380',
    '10891516',
    '9498497',
    '16374287',
    '2332918',
    '11586156',
    '1554175',
    '8114236',
    '9403421',
    'http://www.ncbi.nlm.nih.gov/pubmed/11356436',
    '14695411',
    '16738268'
);