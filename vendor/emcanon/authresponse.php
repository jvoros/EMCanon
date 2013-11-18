<?
/*

A Class to handle the Opauth response data

@author: Jeremy Voros

Requires RedBean running
Injects an Opauth object for error/auth checking
Sets Session variables

*/

class AuthResponse
{
    protected $response;
    protected $provider;
    protected $opauth;
    protected $error;
    protected $user;
    
    public function __construct($response, Opauth $opauth) {
        // assign variables
        $this->response = $response;
        $this->provider = $response['auth']['provider'];
        $this->opauth = $opauth;
        
        // run the error check on instantiation
        $this->errorCheck();
    }
    
    private function errorCheck() {
        if (array_key_exists('error', $this->response)) {
            $this->error = '<strong style="color: red;">Authentication error: </strong> Opauth returns error auth response.'."<br>\n";
        } elseif (empty($this->response['auth']) || empty($this->response['timestamp']) || empty($this->response['signature']) || empty($this->response['auth']['provider']) || empty($this->response['auth']['uid'])) {
            $this->error = '<strong style="color: red;">Invalid auth response: </strong>Missing key auth response components.'."<br>\n";
        } elseif (!$this->opauth->validate(sha1(print_r($this->response['auth'], true)), $this->response['timestamp'], $this->response['signature'], $reason)) {
            $this->error = '<strong style="color: red;">Invalid auth response: </strong>'.$reason.".<br>\n";
        }
    } // end error check
    
    private function newUser() {
        $this->user                 = R::dispense('user');
        $this->user->created       = date("Y-m-d H:i:s");
        $this->user->last_visit    = date("Y-m-d H:i:s");
        $this->user->name          = $this->response['auth']['info']['name'];
        $this->user->thumb         = $this->response['auth']['info']['image'];
        
        //provider specific
        if ($this->provider == 'Google') {
            $this->user->email     = $this->response['auth']['info']['email'];
            $this->user->google    = $this->response['auth']['info']['email'];
            $this->user->gplus     = $this->response['auth']['raw']['link'];
        }
        
        if ($this->provider == 'Twitter') {
            $this->user->twitter   = $this->response['auth']['info']['nickname'];
        }
        
        if ($this->provider == 'Facebook') {
            $this->user->facebook   = $this->response['auth']['info']['nickname'];
        }
        
        $newuser_id = R::store($this->user);   
    } // end new user
    
    public function login() {
        // check for error
        if (isset($this->error)) { echo $this->error; } 
        
        // no error
        else {
            // check for user
            switch($this->provider) {
                case 'Google':
                    $user = R::findOne('user', ' google = ?', array($this->response['auth']['info']['email']));
                    break;
                
                case 'Facebook':
                    $user = R::findOne('user', ' facebook = ?', array($this->response['auth']['info']['nickname']));
                    break;
                
                case 'Twitter':
                    $user = R::findOne('user', ' twitter = ?', array($this->response['auth']['info']['nickname']));
                    break;
            }
            
            // new user vs returning user
            if (is_null($user)) { $this->newUser(); } else { $user->last_visit = date("Y-m-d H:i:s"); $id = R::store($user); $this->user = $user; }
            $_SESSION['user'] = $this->user->export();
            $_SESSION['loggedin'] = TRUE;
        }
    } // end login 
}



