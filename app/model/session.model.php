<?php

    class Session extends Model {
        
        protected $table = 'sessions';
        protected $dates = true;
        
        public function createSession($user){
            $session = 'noToken'.md5(substr(time(), -8));
            $sql = 'INSERT INTO `sessions`(`session`, `user`) VALUES (:session, :user)';
            
            $stmt = $this->database->prepare($sql);
            
            $stmt->bindParam(':session', $session, PDO::PARAM_STR);
            $stmt->bindParam(':user', $user, PDO::PARAM_INT);
            
            $q = $stmt->execute();
            
            if(!$q){
                new Error(601, 'Could not create user session.');
                return false;
            }
            else {
                return true;
            }
            
            
        }
        
        public function setSession($user, $sessionToken) {
            
            $sql = 'UPDATE `sessions` SET `session` = :session WHERE `user` = :user';
            
            $stmt = $this->database->prepare($sql);
            
            $stmt->bindParam(':session', $sessionToken, PDO::PARAM_STR);
            $stmt->bindParam(':user', $user, PDO::PARAM_INT);
            
            $q = $stmt->execute();
            
            if(!$q){
                new Error(601, 'Could not create user session.');
                dbga($stmt->errorInfo());
                return false;
            }
            else {
                unset($_SESSION[APPNAME]);
                $_SESSION[APPNAME][SESSIONKEY] = $sessionToken;

                return true;
            }
            
        }
        
        protected function getSession() {
            $session = $_SESSION[APPNAME][SESSIONKEY];
            
            $sql = 'SELECT users.idusers AS id, users.name, users.email, users.group, roles.role FROM users
                    INNER JOIN roles ON roles.idroles = users.role
                    INNER JOIN sessions ON sessions.user = users.idusers
                    WHERE sessions.session = :session';
            
            $stmt = $this->database->prepare($sql);
            $stmt->bindParam(':session', $session, PDO::PARAM_STR);
            $q = $stmt->execute();
            
            
            if(!$q){
                new Error(602, 'Could not retrieve user profile.');
                return false;
            }
            else {
                $objectUser = $stmt->fetch(PDO::FETCH_OBJ);
                
                if(is_object($objectUser)){
                    $User = new User;
                    $objectUser->permissions = $User->getRolePermissions($objectUser->role);    
                }
                return $objectUser;
            }
        }
        

        protected function destroySession($session, $user) { }
    }
    