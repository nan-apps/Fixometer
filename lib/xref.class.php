<?php

    class Xref extends Model {
        
        protected $table = 'xref';
        
        protected $obj;
        protected $ref;
        protected $objType;
        protected $refType;
        
        private $index;
        private $search_id;
        private $search_type;
        
        /**
         * @ object -> can be image, link, or any other shared asset
         * @ reference -> the main App Element that needs the object (user, event, device, group)
         * */
        public function __construct($index, $object = null, $objectType = null, $reference = null, $referenceType = null){
            
            parent::__construct();
            
            $this->obj      = (integer)$object;
            $this->ref      = (integer)$reference;
            $this->objType  = (integer)$objectType;
            $this->refType  = (integer)$referenceType;
            
            $this->index    = ($index === 'object' || $index === 'reference') ? $index : false;
            if($this->index === 'object'){
                $this->search_id = $this->obj;
                $this->search_type = $this->objType;
            }
            elseif($this->index === 'reference'){
                $this->search_id = $this->ref;
                $this->search_type = $this->refType;
            }
            else {
                return new Error(500, 'Invalid Index in Xref search. (xref.class.php, line 32)');
            }
        }
        
        /**
         * returns object with cross-references to selected
         * ID and TABLE.
         * @ $index can be 'object' || 'reference'
         *   this selects in which direction to look 
         * */
        public function findXref(){
            if($this->index){
                $sql = 'SELECT * FROM `' . $this->table . '` 
                        WHERE `' . $this->index . '` = :id
                        AND `' . $this->index . '_type` = :type';
                $stmt = $this->database->prepare($sql);
                
                $stmt->bindParam(':id', $this->search_id, PDO::PARAM_INT);
                $stmt->bindParam(':type', $this->search_type, PDO::PARAM_INT);
                
                $q = $stmt->execute();
                
                if(!$q && SYSTEM_STATUS == 'development'){
                    dbga($stmt->errorInfo());
                }
                
                return $stmt->fetchAll(PDO::FETCH_OBJ);
            }
            else {
                return false;
            }
        }
        
        public function createXref(){
            if($this->index){
                
                self::deleteXref();
                
                $sql = 'INSERT INTO `' . $this->table . '`(`object`, `object_type`, `reference`, `reference_type`) VALUES (:obj, :objType, :ref, :refType)';
                
                $stmt = $this->database->prepare($sql);
                
                $stmt->bindParam(':obj', $this->obj, PDO::PARAM_INT);
                $stmt->bindParam(':objType', $this->objType, PDO::PARAM_INT);
                $stmt->bindParam(':ref', $this->ref, PDO::PARAM_INT);
                $stmt->bindParam(':refType', $this->refType, PDO::PARAM_INT);
                
                $q = $stmt->execute();
                
                if(!$q && SYSTEM_STATUS == 'development'){
                    dbga($stmt->errorInfo());
                }
                
                return $stmt->fetchAll(PDO::FETCH_OBJ);
            }
            else {
                return false;
            }
            
        }
        
        public function deleteXref() {
            
            if($this->index){                
                $sql = 'DELETE FROM `' . $this->table . '` WHERE `' . $this->index . '` = :id AND `' . $this->index . '_type` = :type';
                
                $stmt = null;        
                $stmt = $this->database->prepare($sql);
                
                $stmt->bindParam(':id', $this->search_id, PDO::PARAM_INT);
                $stmt->bindParam(':type', $this->search_type, PDO::PARAM_INT);
                
                $q = $stmt->execute();
                
                if(!$q && SYSTEM_STATUS == 'development'){
                    dbga($stmt->errorInfo());
                }
                
                return $stmt->fetchAll(PDO::FETCH_OBJ);
            }
            else {
                return false;
            }
            
        }
        
    }