<?php

    class Device extends Model {
        
        protected $table = 'devices';
        protected $dates = true;
        
        public $displacement = 0.5;
        
        public function getList(){            
            $sql = 'SELECT * FROM `view_devices_list`
                    ORDER BY `sorter` DESC';
            $stmt = $this->database->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
            
        }
        
        public function getWeights($group = null){            
            /*
            $sql = 'SELECT
                        ROUND(SUM(`weight`), 0) AS `total_weights`,
                        ROUND(SUM(`footprint`) * ' . $this->displacement . ', 0)  AS `total_footprints`,
                        ROUND(SUM(`estimate`) * (SELECT * FROM view_weight_emission_ratio), 0) AS `estimate_emissions`
                    FROM `'.$this->table.'` AS `d` 
                    INNER JOIN `categories` AS `c` ON  `d`.`category` = `c`.`idcategories`
                    INNER JOIN `events` AS `e` ON  `d`.`event` = `e`.`idevents` 
                    WHERE `d`.`repair_status` = 1';
            */
            $sql = 'SELECT
                    ROUND(SUM(`weight`), 0) + ROUND(SUM(`estimate`), 0) AS `total_weights`,
                    ROUND(SUM(`footprint`) * ' . $this->displacement . ', 0) + (ROUND(SUM(`estimate`) * (SELECT * FROM `view_waste_emission_ratio`), 0))  AS `total_footprints`
                FROM `'.$this->table.'` AS `d` 
                INNER JOIN `categories` AS `c` ON  `d`.`category` = `c`.`idcategories`
                INNER JOIN `events` AS `e` ON  `d`.`event` = `e`.`idevents` 
                WHERE `d`.`repair_status` = 1 AND `c`.`idcategories` != 46';
                    
            if(!is_null($group) && is_numeric($group)){
                $sql .= ' AND `e`.`group` = :group';     
            }
            
            $stmt = $this->database->prepare($sql);
            if(!is_null($group) && is_numeric($group)){
                $stmt->bindParam(':group', $group, PDO::PARAM_INT);
            }
            
            
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        }
        
        public function getCounts(){
            $sql = 'SELECT
                        COUNT(`category`) AS `catcount`,
                        ROUND(SUM(`weight`), 2) AS `catcount_weight`,
                        `name`
                    FROM `' . $this->table . '` AS `d` 
                    INNER JOIN `categories` AS `c` ON `c`.`idcategories` = `d`.`category`
                    WHERE `d`.`repair_status` = 1
                    GROUP BY `category`
                    ORDER BY `catcount` DESC';
                    
            $stmt = $this->database->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);            
        }
        
        public function getByYears($repair_status){
            $sql = 'SELECT
                        COUNT(`iddevices`) AS `total_devices`,
                        YEAR(`event_date`) AS `event_year`
                    FROM `' . $this->table . '` AS `d` 
                    INNER JOIN `events` AS `e` ON `e`.`idevents` = `d`.`event`
                    WHERE `d`.`repair_status` = :rp
                    GROUP BY `event_year`
                    ORDER BY `event_year` ASC';
            $stmt = $this->database->prepare($sql);
            $stmt->bindParam(':rp', $repair_status, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);       
            
        }
        
        
        public function ofThisUser($id){
            $sql = 'SELECT * FROM `' . $this->table . '` WHERE `repaired_by` = :id';
            $stmt = $this->database->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        }
        
        public function ofThisEvent($event){
            $sql = 'SELECT * FROM `' . $this->table . '` AS `d`
                    INNER JOIN `categories` AS `c` ON `c`.`idcategories` = `d`.`category` 
                    WHERE `event` = :event';
            $stmt = $this->database->prepare($sql);
            $stmt->bindParam(':event', $event, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        }
        
        public function ofThisGroup($group){
            $sql = 'SELECT * FROM `' . $this->table . '` AS `d`
                    INNER JOIN `categories` AS `c` ON `c`.`idcategories` = `d`.`category`
                    INNER JOIN `events` AS `e` ON `e`.`idevents` = `d`.`event` 
                    WHERE `group` = :group';
            $stmt = $this->database->prepare($sql);
            $stmt->bindParam(':group', $group, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        }
        
        public function ofAllGroups() {
            $sql = 'SELECT * FROM `' . $this->table . '` AS `d`
                    INNER JOIN `categories` AS `c` ON `c`.`idcategories` = `d`.`category`
                    INNER JOIN `events` AS `e` ON `e`.`idevents` = `d`.`event`';
            $stmt = $this->database->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        }
        
        public function statusCount($g = null, $year = null){
            $sql = 'SELECT COUNT(*) AS `counter`, `d`.`repair_status` AS `status`, `d`.`event`
                    FROM `'. $this->table .'` AS `d`';
            if( (!is_null($g) && is_numeric($g)) || (!is_null($year) && is_numeric($year))){
                $sql .= ' INNER JOIN `events` AS `e` ON `e`.`idevents` = `d`.`event` ';
            }
            
            $sql .= ' WHERE `repair_status` > 0 ';
            
            if(!is_null($g) && is_numeric($g)){
                $sql .= ' AND `group` = :g ';
            }
            if(!is_null($year) && is_numeric($year)){
                $sql .= ' AND YEAR(`event_date`) = :year ';
            }
            
            $sql .= ' GROUP BY `status`';

            $stmt = $this->database->prepare($sql);
            if(!is_null($g) && is_numeric($g)){
                $stmt->bindParam(':g', $g, PDO::PARAM_INT);
            }
            if(!is_null($year) && is_numeric($year)){
                $stmt->bindParam(':year', $year, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        }
        
        public function countByCluster($cluster, $group = null, $year = null){
            $sql = 'SELECT COUNT(*) AS `counter`, `repair_status` FROM `' . $this->table . '` AS `d` 
                    INNER JOIN `events` AS `e`
                        ON `d`.`event` = `e`.`idevents`
                    INNER JOIN `categories` AS `c`
                        ON `d`.`category` = `c`.`idcategories`
                    WHERE `c`.`cluster` = :cluster AND `d`.`repair_status` > 0 ';
                    
            if(!is_null($group)){
                $sql.=' AND `e`.`group` = :group ';
            }
            if(!is_null($year)){
                $sql.=' AND YEAR(`e`.`event_date`) = :year ';
            }
            
            $sql.= ' GROUP BY `repair_status` 
                    ORDER BY `repair_status` ASC
                    ';
            
                        
                    
            $stmt = $this->database->prepare($sql);
            
            $stmt->bindParam(':cluster', $cluster, PDO::PARAM_INT);
            
            if(!is_null($group) && is_numeric($group)){
                $stmt->bindParam(':group', $group, PDO::PARAM_INT);
            }
            if(!is_null($year) && is_numeric($year)){
                $stmt->bindParam(':year', $year, PDO::PARAM_INT);
            }
            
            $q = $stmt->execute();
            if(!$q){
                dbga($stmt->errorCode()); dbga($stmt->errorInfo() );
            }
            return $stmt->fetchAll(PDO::FETCH_OBJ);
            
        }
        
        public function countCO2ByYear($group = null, $year = null) {
            $sql = 'SELECT
                        (ROUND(SUM(`c`.`footprint`), 0) * ' . $this->displacement . ') + (IFNULL(ROUND(SUM(`estimate`) * (SELECT * FROM `view_waste_emission_ratio`), 0),0)) AS `co2`,
                        YEAR(`e`.`event_date`) AS `year`
                    FROM `' . $this->table . '` AS `d` 
                    INNER JOIN `events` AS `e`
                        ON `d`.`event` = `e`.`idevents`
                    INNER JOIN `categories` AS `c`
                        ON `d`.`category` = `c`.`idcategories`
                    WHERE `d`.`repair_status` = 1 ';
                    
            if(!is_null($group)){
                $sql.=' AND `e`.`group` = :group ';
            }
            if(!is_null($year)){
                $sql.=' AND YEAR(`e`.`event_date`) = :year ';
            }
            $sql.= ' GROUP BY `year` 
                    ORDER BY `year` DESC';
            //echo $sql;
            $stmt = $this->database->prepare($sql);
            
            if(!is_null($group) && is_numeric($group)){
                $stmt->bindParam(':group', $group, PDO::PARAM_INT);
            }
            if(!is_null($year) && is_numeric($year)){
                $stmt->bindParam(':year', $year, PDO::PARAM_INT);
            }
            
            $q = $stmt->execute();
            if(!$q){
                dbga($stmt->errorCode()); dbga($stmt->errorInfo() );
            }
            return $stmt->fetchAll(PDO::FETCH_OBJ);
            
            
        }
        
        public function countWasteByYear($group = null, $year = null) {
            $sql = 'SELECT
                        ROUND(SUM(`c`.`weight`), 0) + IFNULL( ROUND(SUM(`d`.`estimate`), 0), 0) AS `waste`,
                        YEAR(`e`.`event_date`) AS `year`
                    FROM `' . $this->table . '` AS `d` 
                    INNER JOIN `events` AS `e`
                        ON `d`.`event` = `e`.`idevents`
                    INNER JOIN `categories` AS `c`
                        ON `d`.`category` = `c`.`idcategories`
                    WHERE `d`.`repair_status` = 1 ';
                    
            if(!is_null($group)){
                $sql.=' AND `e`.`group` = :group ';
            }
            if(!is_null($year)){
                $sql.=' AND YEAR(`e`.`event_date`) = :year ';
            }
            $sql.= ' GROUP BY `year` 
                    ORDER BY `year` DESC';
            $stmt = $this->database->prepare($sql);
            //echo $sql;
            if(!is_null($group) && is_numeric($group)){
                $stmt->bindParam(':group', $group, PDO::PARAM_INT);
            }
            if(!is_null($year) && is_numeric($year)){
                $stmt->bindParam(':year', $year, PDO::PARAM_INT);
            }
            
            $q = $stmt->execute();
            if(!$q){
                dbga($stmt->errorCode()); dbga($stmt->errorInfo() );
            }
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        }
        
        public function findMostSeen($status = null, $cluster = null, $group = null){
            
            $sql = 'SELECT COUNT(`d`.`category`) AS `counter`, `c`.`name` FROM `' . $this->table . '` AS `d`
                    INNER JOIN `events` AS `e`
                        ON `d`.`event` = `e`.`idevents`
                    INNER JOIN `categories` AS `c`
                        ON `d`.`category` = `c`.`idcategories`
                    WHERE 1=1 ';
                                
            if(!is_null($status) && is_numeric($status)){                    
                $sql .= ' AND `d`.`repair_status` = :status ';
            }                    
            if(!is_null($cluster) && is_numeric($cluster)){
                $sql .= ' AND `c`.`cluster` = :cluster ';
            }
            if(!is_null($group) && is_numeric($group)){
                $sql .= ' AND `e`.`group` = :group ';
            }
            
            $sql.= ' GROUP BY `d`.`category`
                     ORDER BY `counter` DESC';
                     
            $sql .= (!is_null($cluster) ? '  LIMIT 1' : '');    
                    
                     
            $stmt = $this->database->prepare($sql);
            
            if(!is_null($status) && is_numeric($status)){
                $stmt->bindParam(':status', $status, PDO::PARAM_INT);
            }
            if(!is_null($group) && is_numeric($group)){
                $stmt->bindParam(':group', $group, PDO::PARAM_INT);
            }
            if(!is_null($cluster) && is_numeric($cluster)){
                $stmt->bindParam(':cluster', $cluster, PDO::PARAM_INT);
            }
            
            $q = $stmt->execute();
            if(!$q){
                dbga($stmt->errorCode()); dbga($stmt->errorInfo() );
            }
            return $stmt->fetchAll(PDO::FETCH_OBJ);
            
            
        }
        public function successRates($year = null, $threshold = 10, $direction = 'DESC'){
            $sql .=     'SELECT 
                            COUNT(repair_status) AS fixed, 
                            total_devices, 
                            categories.name AS category_name,
                            clusters.name AS cluster_name,
                            (COUNT(repair_status) * 100 / total_devices) AS success_rate ';
            if(!is_null($year)){ $sql .= ', YEAR(events.event_date) AS eventyear '; }
                            
            $sql .=     ' FROM devices 
                            INNER JOIN categories ON categories.idcategories = devices.category 
                            INNER JOIN (
                                SELECT
                                    COUNT(iddevices) AS total_devices,
                                    devices.category
                                FROM devices ';
            if(!is_null($year)){ $sql .= '
                                INNER JOIN events ON events.idevents=devices.event 
                                WHERE YEAR(events.event_date) = :year1 ';
            }
            $sql .=             'GROUP BY devices.category
                                ) AS totals ON totals.category = devices.category  
                            INNER JOIN clusters ON clusters.idclusters = categories.cluster ';
            if(!is_null($year)){ $sql .= 'INNER JOIN events ON events.idevents = devices.event '; }                
            $sql .=     'WHERE
                            repair_status = 1 AND
                            total_devices > ' . $threshold . ' ';
                        
            if(!is_null($year)){ $sql .= ' AND YEAR(events.event_date) = :year2 '; }            
            $sql .=     'GROUP BY devices.category
                        ORDER BY cluster ASC, success_rate ' . $direction;
            
            $stmt = $this->database->prepare($sql);
            //$stmt->bind(':direction', $direction, PDO::PARAM_STR);
            if(!is_null($year)){
                $bind = $stmt->bindParam(':year1', $year, PDO::PARAM_INT);
                $bind = $stmt->bindParam(':year2', $year, PDO::PARAM_INT);
            }
            
            $q = $stmt->execute();
            $r = $stmt->fetchAll(PDO::FETCH_OBJ);
            return $r;
        }
        
       
        
        
        
        public function guesstimates() {
            $sql .= 'SELECT COUNT(*) AS guesstimates FROM `' . $this->table . '` WHERE `category` = 46';
            $stmt = $this->database->prepare($sql);
            $q = $stmt->execute();
            $r = $stmt->fetch(PDO::FETCH_OBJ);
            return $r->guesstimates;
        }
     
        public function export() {
            $sql = 'SELECT 
                        `c`.`name` AS `category`, 
                        `problem`, 
                        `repair_status`, 
                        `spare_parts`, 
                        `e`.`location`, 
                        UNIX_TIMESTAMP( CONCAT(`e`.`event_date`, " ", `e`.`start`) ) AS `event_timestamp`, 
                        `g`.`name` AS `group_name`
                    
                    FROM `devices` AS `d` 
                    INNER JOIN `categories` AS `c` ON `c`.`idcategories` = `d`.`category`  
                    INNER JOIN `events` AS `e` ON `e`.`idevents` = `d`.`event`
                    INNER JOIN `groups` AS `g` ON `g`.`idgroups` = `e`.`group`';
                    
            $stmt = $this->database->prepare($sql);
            $q = $stmt->execute();
            $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $r;
        }   
    }