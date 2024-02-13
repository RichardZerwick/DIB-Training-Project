<?php 

class RegisterController extends Controller {

    function registerUser($containerName, $clientData) {
        // Business Logic 
        try{
            DibApp::load('dibAuthenticate', 'Authenticator.php', 'components');

            $username = $clientData['alias_self']['username'];
            $firstname = $clientData['alias_self']['first_name'];
            $lastname = $clientData['alias_self']['last_name'];
            $password = $clientData['alias_self']['password'];
            $email1 = $clientData['alias_self']['email1'];
            $url = '/nav/products';

            if(empty($username))
                return $this->failure('Please provide a username and try again.','dialog');
            
            if(!is_string($username) || strlen($username)>120)  {
                Log::setMsg("Please provide a valid username/email and try again.");
                return $this->failure('Please provide a valid username/email and try again.','dialog');        
            }

            if(empty($firstname))
                return $this->failure('Please provide a firstname and try again.','dialog');
            
            if(!is_string($firstname) || strlen($firstname)>120)  {
                Log::setMsg("Please provide a valid firstname and try again.");
                return $this->failure('Please provide a valid firstname and try again.','dialog');        
            }

            if(empty($lastname))
                return $this->failure('Please provide a lastname and try again.','dialog');
            
            if(!is_string($lastname) || strlen($lastname)>120)  {
                Log::setMsg("Please provide a valid lastname and try again.");
                return $this->failure('Please provide a valid lastname and try again.','dialog');        
            }

            $sql = "SELECT `username`, `email`, `first_name`, `last_name` 
                    FROM pef_login WHERE `username`=:username OR `email`=:username";
            $login = Database::fetch($sql, array(':username' => $username), DIB::LOGINDBINDEX);
            
            if (Database::count()>0) {
                // username already exist... try again with a blank form
                Log::setMsg("Username already exists. Please use a different username and try again.");
                return $this->failure('Username already exists. Please use a different username and try again.','dialog');
            }

            $sql = "INSERT INTO pef_login (username, first_name, last_name, password, email, default_url) VALUES (:username, :first_name, :last_name, :password, :email, :default_url)";

            $result = Database::execute($sql, [
                'username' => $username,
                'first_name' => $firstname,
                'last_name' => $lastname,
                'password' => Authenticator::encryptPassword($password),
                'email' => $email1,
                'default_url' => $url
            ], DIB::LOGINDBINDEX);

            Log::w($result);

            $sql = "INSERT INTO pef_login_group (pef_login_id, pef_perm_group_id, start_datetime, expiry_datetime) VALUES (:pef_login_id, :pef_perm_group_id, '2024-02-07 02:00:00', '2039-02-07 02:00:00')";

            $result1 = Database::execute($sql, [
                'pef_login_id' => $result,
                'pef_perm_group_id' => 4,
                //'start_datetime' => '2024-02-07 02:00:00',
                //'expiry_datetime' => '2039-02-07 02:00:00'
            ], DIB::LOGINDBINDEX);

            Log::w($result);
            if ($result) {
                ClientFunctions::addAction(DibApp::$clientActions, 'open-url', array('url' => '/login'));
                return $this->success('User registered successfully','dialog');
                
            } else { 
                return $this->failure('User registration failed','dialog');
            }

        }catch (Exception $e) {
            Log::setMsg('Apologies, this function is not available at present. Please try again later.');
            Log::err('*** WARNING! The register user function code is not working. Please check /runtime/error.log and PHP error logs for details.');
            return false;
        }
        
    }

}