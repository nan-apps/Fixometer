<?php

    class Party extends Model {
        
        protected $table = 'events';
        protected $dates = true;
        
        public function findAll() {
            
            $sql = 'SELECT
                        `e`.`idevents` AS `id`,
                        UNIX_TIMESTAMP(`e`.`event_date`) AS `event_date`,
                        `e`.`start` AS `start`,
                        `e`.`end` AS `end`,
                        `e`.`location`,
                        `e`.`latitude`,
                        `e`.`longitude`,
                        `e`.`pax`,
                        `e`.`hours`,
                        `g`.`name` AS `group_name`                        
                    FROM `events` AS `e`
                    INNER JOIN `groups` AS `g`
                        ON `g`.`idgroups` = `e`.`group`
                    ORDER BY `e`.`start` DESC';
                    
            $stmt = $this->database->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        }
        
        
        public function findThis($id, $devices = false) {
            
            $sql = 'SELECT
                        `e`.`idevents` AS `id`,
                        UNIX_TIMESTAMP(`e`.`event_date`) AS `event_date`,
                        `e`.`start` AS `start`,
                        `e`.`end` AS `end`,
                        `e`.`location`,
                        `e`.`latitude`,
                        `e`.`longitude`,
                        `e`.`pax`,
                        `e`.`volunteers`,
                        `e`.`hours`,
                        `g`.`name` AS `group_name`                        
                    FROM `events` AS `e`
                    INNER JOIN `groups` AS `g`
                        ON `g`.`idgroups` = `e`.`group`
                    WHERE `e`.`idevents` = :id 
                    ORDER BY `e`.`start` DESC';
                    
            $stmt = $this->database->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $q = $stmt->execute();
            
            $party =  $stmt->fetch(PDO::FETCH_OBJ);
            
            if($devices){
                $devices = new Device;
                $party->devices = $devices->ofThisEvent($party->id);
            }
            
            return $party;
        }
        
        public function createUserList($party, $users){
            /** reset user list **/
            if(!self::deleteUserList($party)){
                return false;    
            }
            $sql = 'INSERT INTO `events_users`(`event`, `user`) VALUES (:party, :user)';
            $stmt = $this->database->prepare($sql);
            $stmt->bindParam(':party', $party, PDO::PARAM_INT);
            foreach($users as $k => &$user){
                $stmt->bindParam(':user', $user, PDO::PARAM_INT);
                
                $q = $stmt->execute();
                if(!$q){
                    if(SYSTEM_STATUS == 'development'){
                        $err = $stmt->errorInfo();
                        new Error(601, $err[2]);
                    }
                }
            }
        }
        
        public function deleteUserList($party){
            $sql = 'DELETE FROM `events_users` WHERE `event` = :party';
            $stmt = $this->database->prepare($sql);
            $stmt->bindParam(':party', $party, PDO::PARAM_INT);
            return $stmt->execute();
        }
        
        public function ofThisUser($id, $only_past = false, $devices = false){
            $sql = 'SELECT *, `e`.`location` AS `venue`, UNIX_TIMESTAMP(`e`.`event_date`) AS `event_timestamp`  
                    FROM `' . $this->table . '` AS `e` 
                    INNER JOIN `events_users` AS `eu` ON `eu`.`event` = `e`.`idevents`
                    INNER JOIN `groups` as `g` ON `e`.`group` = `g`.`idgroups`
                    LEFT JOIN (
                        SELECT COUNT(`dv`.`iddevices`) AS `device_count`, `dv`.`event` 
                        FROM `devices` AS `dv` 
                        GROUP BY  `dv`.`event`
                    ) AS `d` ON `d`.`event` = `e`.`idevents` 
                    WHERE `eu`.`user` = :id';
            if($only_past == true){
                $sql .= ' AND `e`.`event_date` < NOW()';
            }
            $sql .= ' ORDER BY `e`.`event_date` DESC';
            
            $stmt = $this->database->prepare($sql);
            
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            $q = $stmt->execute();
            if(!$q){
                if(SYSTEM_STATUS == 'development'){
                    $err = $stmt->errorInfo();
                    new Error(601, $err[2]);
                }
            }
            else {
                $parties = $stmt->fetchAll(PDO::FETCH_OBJ);
                if($devices){
                    $devices = new Device;
                    foreach($parties as $i => $party){
                        $parties[$i]->devices = $devices->ofThisEvent($party->idevents);
                    }
                    
                }
                
                return $parties;
                
                
            }
        }
        
        
        public function ofThisGroup($group, $only_past = false, $devices = false){
            $sql = 'SELECT *, `e`.`location` AS `venue`, UNIX_TIMESTAMP(`e`.`event_date`) AS `event_timestamp`  
                    FROM `' . $this->table . '` AS `e` 
                    
                    INNER JOIN `groups` as `g` ON `e`.`group` = `g`.`idgroups`
                    
                    LEFT JOIN (
                        SELECT COUNT(`dv`.`iddevices`) AS `device_count`, `dv`.`event` 
                        FROM `devices` AS `dv` 
                        GROUP BY  `dv`.`event`
                    ) AS `d` ON `d`.`event` = `e`.`idevents` 
                    
                    LEFT JOIN (
                        SELECT * FROM `images`
                            INNER JOIN `xref` ON `xref`.`object` = `images`.`idimages`
                            WHERE `xref`.`object_type` = 5
                            AND `xref`.`reference_type` = 3
                            GROUP BY `images`.`path`
                    ) AS `xi` 
                    ON `xi`.`reference` = `e`.`idevents` 
                    
                    WHERE `e`.`group` = :id';
            if($only_past == true){
                $sql .= ' AND `e`.`event_date` < NOW()';
            }
            $sql .= ' ORDER BY `e`.`event_date` DESC';
            
            
            $stmt = $this->database->prepare($sql);
            
            $stmt->bindParam(':id', $group, PDO::PARAM_INT);
            
            $q = $stmt->execute();
            if(!$q){
                if(SYSTEM_STATUS == 'development'){
                    $err = $stmt->errorInfo();
                    new Error(601, $err[2]);
                }
            }
            else {
                $parties = $stmt->fetchAll(PDO::FETCH_OBJ);
                if($devices){
                    $devices = new Device;
                    foreach($parties as $i => $party){
                        $parties[$i]->devices = $devices->ofThisEvent($party->idevents);
                    }
                    
                }
                
                return $parties;
                
                
            }
        }
        
        
        
        public function findNextParties($group = null) {
            $sql = 'SELECT
                        `e`.`idevents`,
                        `e`.`location`,
                        UNIX_TIMESTAMP(`e`.`event_date`) AS `event_date`,
                        `e`.`start`,
                        `e`.`end`,
                        `e`.`latitude`,
                        `e`.`longitude`,
                        `xi`.`path` 
                    FROM `' . $this->table . '` AS `e` 
                    LEFT JOIN (
                        SELECT * FROM `images`
                            INNER JOIN `xref` ON `xref`.`object` = `images`.`idimages`
                            WHERE `xref`.`object_type` = 5
                            AND `xref`.`reference_type` = 3
                            GROUP BY `images`.`path`
                    ) AS `xi` 
                    ON `xi`.`reference` = `e`.`idevents` 
                    WHERE `e`.`event_date` >= NOW()';
                    
                    
            if(!is_null($group)){
                $sql .= ' AND `e`.`group` = :group ';    
            }
            
            $sql .= ' ORDER BY `e`.`event_date` ASC
                    LIMIT 10';
            $stmt = $this->database->prepare($sql);
            if(!is_null($group)){
                $stmt->bindParam(':group', $group, PDO::PARAM_INT);
            }
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        }
    
        public function findLatest($limit = 10) {
            $sql = 'SELECT
                        `e`.`idevents`,
                        `e`.`location`,
                        UNIX_TIMESTAMP(`e`.`event_date`) AS `event_date`,
                        `e`.`start`,
                        `e`.`end`,
                        `e`.`latitude`,
                        `e`.`longitude` 
                    FROM `' . $this->table . '` AS `e` 
                    ORDER BY `e`.`event_date` DESC 
                    LIMIT :limit';
            $stmt = $this->database->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        }
    
        
    }
    
    
    