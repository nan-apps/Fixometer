<?php

    class Device extends Model {

        protected $table = 'devices';
        protected $dates = true;

        public $displacement = 0.5;

        public function getList($params = null){
            $sql = 'SELECT * FROM `view_devices_list`';

            if(!is_null($params)){

              $sql .= ' WHERE 1=1 AND ';

              $params = array_filter($params);
              foreach($params as $field => $value){
                if($field == 'brand' || $field == 'model' || $field == 'problem'){
                  $params[$field] = '%' . strtolower($value) . '%';
                }
                elseif($field == 'event_date') {
                  $params[$field] = implode(' AND ', $value);
                }
              }

              $clauses = array();

              foreach($params as $f => $v){
                if($f == 'event_date'){
                  $clauses[] = 'event_date BETWEEN ' . $v;
                }
                if($f == 'category' || $f == 'group'){
                  $clauses[] = 'id' . $f . ' IN (' . $v . ')';
                }
                elseif( $f == 'brand' || $f == 'model' || $f == 'problem') {
                  $clauses[] = $f . ' LIKE :' . $f;
                }
              }

              $sql .= implode(' AND ', $clauses);
            }

            $sql .= ' ORDER BY `sorter` DESC';
            $stmt = $this->database->prepare($sql);

            if(!is_null($params)){
              if(isset($params['brand']))     {
                $brand = $params['brand'];
                $stmt->bindParam(':brand', $brand, PDO::PARAM_STR);
              }
              if(isset($params['model']))     {
                $model = $params['model'];
                $stmt->bindParam(':model', $model, PDO::PARAM_STR);
              }
              if(isset($params['problem']))   { $stmt->bindParam(':problem', $params['problem'], PDO::PARAM_STR); }
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        }

        public function getWasteEmissionRatio()
        {
            $sql = 'SELECT Ratio
                    FROM `view_waste_emission_ratio`'
            ;

            $stmt = $this->database->prepare($sql);
            $stmt->execute();
            $ratio = $stmt->fetchColumn(0);

            return $ratio;
        }

        /**
         * Get the total values for waste weight and CO2 diverted due to repairs.
         * For categorised devices, the figure for weight is the sum of all category weights,
         * and for CO2, it is the sum of all category CO2 estimates, multiplied by the displacement rate.
         * For misc devices, the figure for weights is the sum of all estimated weights for misc devices,
         * and for CO2, it is the sum of all estimated weights multipled by the average CO2 per kilo.
         * NOTE: for some reason, the Misc category has a weight of 1 in the DB.  This makes it tricky
         * to achieve both categorised and misc calculations in one query.
         * TODO: NGM: don't feel that we should be rounding here, we should only round immediately prior to display.  Leaving for now.
         */
        public function getWeights($group = null)
        {
            $categorisedDevicesSql = 'SELECT
                    ROUND(SUM(`c`.`weight`), 0) AS `total_weights`,
                    ROUND(SUM(`c`.`footprint`) * ' . $this->displacement . ', 0)  AS `total_footprints`
                FROM `'.$this->table.'` AS `d`
                INNER JOIN `categories` AS `c` ON  `d`.`category` = `c`.`idcategories`
                INNER JOIN `events` AS `e` ON  `d`.`event` = `e`.`idevents`
                WHERE `d`.`repair_status` = 1 AND `c`.`idcategories` != 46';

            $miscDevicesSql = 'SELECT
                    ROUND(SUM(ifnull(`d`.`estimate`,0)), 0) AS `total_misc_weights`,
                    ROUND(SUM(ifnull(`estimate`,0)) * (SELECT * FROM `view_waste_emission_ratio`) * ' . $this->displacement . ', 0) AS `total_misc_footprints`
                FROM `'.$this->table.'` AS `d`
                INNER JOIN `categories` AS `c` ON  `d`.`category` = `c`.`idcategories`
                INNER JOIN `events` AS `e` ON  `d`.`event` = `e`.`idevents`
                WHERE `d`.`repair_status` = 1 AND `c`.`idcategories` = 46';

            if(!is_null($group) && is_numeric($group)){
                $categorisedDevicesSql .= ' AND `e`.`group` = :group';
                $miscDevicesSql .= ' AND `e`.`group` = :group';
            }

            $categorisedStmt = $this->database->prepare($categorisedDevicesSql);
            $miscStmt = $this->database->prepare($miscDevicesSql);

            if(!is_null($group) && is_numeric($group)){
                $categorisedStmt->bindParam(':group', $group, PDO::PARAM_INT);
                $miscStmt->bindParam(':group', $group, PDO::PARAM_INT);
            }

            $categorisedStmt->execute();
            $categorisedValues = $categorisedStmt->fetchAll();
            $miscStmt->execute();
            $miscValues = $miscStmt->fetchAll();

            // TODO: converting to array of 1 anonymous object to match previous return type.
            // Should just be 1 object (or array) though.
            $totalsObj = new stdClass;
            $totalsObj->total_weights = $categorisedValues[0]['total_weights'] + $miscValues[0]['total_misc_weights'];
            $totalsObj->total_footprints = $categorisedValues[0]['total_footprints'] + $miscValues[0]['total_misc_footprints'];
            $totals = array(0 => $totalsObj);

            return $totals;
        }


        public function getPartyWeights($party){
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
                WHERE `d`.`repair_status` = 1 AND `c`.`idcategories` != 46 AND `e`.`idevents` = :id';
           // echo $sql;

            $stmt = $this->database->prepare($sql);
            $stmt->bindParam(':id', $party, PDO::PARAM_INT);
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
                    LEFT JOIN (
                      SELECT * FROM xref
                        INNER JOIN images ON images.idimages = xref.object
                        WHERE object_type = ' . TBL_IMAGES . ' AND reference_type = ' . TBL_DEVICES . '
                      ) AS i ON i.reference = d.iddevices

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

            if(!is_null($year) && is_numeric($year)){
              $sql .= ', `event`';
            }


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

        public function clusterCount($cluster){
            $sql = 'select count(devices.iddevices) as counter, repair_status from devices
                    inner join categories on categories.idcategories = devices.category
                    inner join clusters on clusters.idclusters = categories.cluster
                    where categories.cluster = :cluster and repair_status > 0 group by repair_status ORDER BY repair_status ASC';
            $stmt = $this->database->prepare($sql);
            $bind = $stmt->bindParam(':cluster', $cluster, PDO::PARAM_INT);

            $q = $stmt->execute();
            $r = $stmt->fetchAll(PDO::FETCH_OBJ);
            return $r;
        }

        public function countCO2ByYear($group = null, $year = null) {
            $sql = 'SELECT
                        (ROUND(SUM(`c`.`footprint`), 0) * ' . $this->displacement . ') + ( CASE WHEN `d`.`category` = 46 THEN IFNULL(ROUND(SUM(`estimate`) * (SELECT * FROM `view_waste_emission_ratio`), 0),0) ELSE 0 END) AS `co2`,
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
                    ORDER BY `year` DESC'; // was grouped by category too at some point
          //  echo $sql;
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
                    WHERE 1=1 and `c`.`idcategories` <> ' . MISC_CATEGORY_ID;

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
        public function successRates($cluster = null, $direction = 'DESC', $threshold = 10){
            $sql .=     'SELECT
                            COUNT(repair_status) AS fixed,
                            total_devices,
                            categories.name AS category_name,
                            clusters.name AS cluster_name,
                            ROUND( (COUNT(repair_status) * 100 / total_devices), 1) AS success_rate ';
            if(!is_null($cluster)){ $sql .= ', clusters.idclusters AS cluster '; }

            $sql .=     ' FROM devices
                            INNER JOIN categories ON categories.idcategories = devices.category
                            INNER JOIN (
                                SELECT
                                    COUNT(iddevices) AS total_devices,
                                    devices.category
                                FROM devices
                                GROUP BY devices.category
                                ) AS totals ON totals.category = devices.category
                            INNER JOIN clusters ON clusters.idclusters = categories.cluster ';
            // if(!is_null($year)){ $sql .= 'INNER JOIN events ON events.idevents = devices.event '; }
            $sql .=     'WHERE
                            repair_status = 1 AND
                            total_devices > ' . $threshold . ' ';

            if(!is_null($cluster)){ $sql .= ' AND cluster = :cluster '; }
            $sql .=     'GROUP BY devices.category
                        ORDER BY cluster ASC, success_rate ' . $direction . ' LIMIT 1';

            $stmt = $this->database->prepare($sql);
            //$stmt->bind(':direction', $direction, PDO::PARAM_STR);
            if(!is_null($cluster)){
                $bind = $stmt->bindParam(':cluster', $cluster, PDO::PARAM_INT);
                //$bind = $stmt->bindParam(':year2', $year, PDO::PARAM_INT);
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
                        `brand`,
                        `model`,
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
