<?php

    class GroupController extends Controller {
        
        public function __construct($model, $controller, $action){
            parent::__construct($model, $controller, $action);
            
            $Auth = new Auth($url);
            if(!$Auth->isLoggedIn() && $action != 'stats'){
                header('Location: /user/login');
            }
            else {
                
                $user = $Auth->getProfile();
                $this->user = $user;
                $this->set('user', $user);
                $this->set('header', true);
            }
        }
        
        public function index($response = null){
            
            $this->set('title', 'Groups');
            $this->set('list', $this->Group->findAll());
            
            if(!is_null($response)){
                $this->set('response', $response);
            }
            
        }
        
        public function create(){
            
            // Administrators can add Groups.
            if(hasRole($this->user, 'Administrator')){
                $this->set('title', 'New Group');
                $this->set('gmaps', true);
                $this->set('js', 
                            array('head' => array(
                                            '/ext/geocoder.js'
                            )));
                    
                
                if($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)) {
                    $error = array();
                    
                    // We got data! Elaborate.
                    $name       =       $_POST['name'];
                    $area       =       $_POST['area'];
                    $freq       =       $_POST['frequency'];
                    $location   =       $_POST['location'];
                    $latitude   =       $_POST['latitude'];
                    $longitude  =       $_POST['longitude'];
                    $text       =       $_POST['free_text'];
                    
                   
                    
                    
                    if(empty($name)){
                        $error['name'] = 'Please input a name.';
                    }
                    if(!empty($latitude) || !empty($longitude)) {
                        // check that these values are floats.
                        $check_lat = filter_var($latitude, FILTER_VALIDATE_FLOAT);
                        $check_lon = filter_var($longitude, FILTER_VALIDATE_FLOAT);
                        
                        if(!$check_lat || !$check_lon){
                            $error['location'] = 'Coordinates must be in the correct format.';
                        }
                        
                    }
                    
                    
                    if(empty($error)) {
                        // No errors. We can proceed and create the User.
                        $data = array(  'name'          => $name,
                                        'area'          => $area,
                                        'frequency'     => $freq,
                                        'location'      => $location,
                                        'latitude'      => $latitude,
                                        'longitude'     => $longitude,
                                        'free_text'     => $text,
                                        );
                        $idGroup = $this->Group->create($data);
                        if($idGroup){
                            $response['success'] = 'Group created correctly.';
                            
                            if(isset($_FILES) && !empty($_FILES)){
                                $file = new File;
                                $group_avatar = $file->upload('image', 'image', $idGroup, TBL_GROUPS, false, true);    
                            }
                            
                            /** Prepare Custom Fields for WP XML-RPC - get all needed data **/
                            $Host = $Groups->findHost($idGroup);
                            
                            $custom_fields = array(
                                            array('key' => 'group_city',           'value' => $area),
                                            array('key' => 'group_host',            'value' => $Host->hostname),       
                                            array('key' => 'group_hostavatarurl',   'value' => UPLOADS_URL . 'mid_' .$Host->path),
                                            array('key' => 'group_hash',            'value' => $idGroup),
                                            array('key' => 'group_avatar_url',      'value' => UPLOADS_URL . 'mid_' . $group_avatar ),
                                            );
                            
                            
                            /** Start WP XML-RPC **/
                            $wpClient = new \HieuLe\WordpressXmlrpcClient\WordpressClient();
                            $wpClient->setCredentials(WP_XMLRPC_ENDPOINT, WP_XMLRPC_USER, WP_XMLRPC_PSWD);
                            
                            $content = array(
                                            'post_type' => 'group',
                                            'custom_fields' => $custom_fields
                                            );
                            
                            $wpid = $wpClient->newPost($data['name'], $text, $content);
                            $this->Group->update(array('wordpress_post_id' => $wpid), $idGroup);
                            
                            
                        }
                        else {
                            $response['danger'] = 'Group could <strong>not</strong> be created. Something went wrong with the database.';
                        }
                        
                    }
                    else {
                        $response['danger'] = 'Group could <strong>not</strong> be created. Please look at the reported errors, correct them, and try again.';
                    }
                    
                    
                    $this->set('response', $response);
                    $this->set('error', $error);
                    $this->set('udata', $_POST);
                    
                }
                
            }
            else {
                header('Location: /user/forbidden', true, 403);
            }
        }
        
        
    
        public function edit($id) {
            
            if(hasRole($this->user, 'Administrator') || hasRole($this->user, 'Host')){ 
                
                if($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)){
                    
                    $data = $_POST;
                    
                    // remove the extra "files" field that Summernote generates -
                    unset($data['files']);
                    unset($data['image']);
                    
                    $u = $this->Group->update($data, $id);
                    
                    if(!$u) {
                        $response['danger'] = 'Something went wrong. Please check the data and try again.';
                    }
                    else {
                        $response['success'] = 'Group updated!';
                        
                        if(isset($_FILES['image']) && !empty($_FILES['image']) && $_FILES['image']['error'] != 4){
                            
                            $existing_image = $this->Group->hasImage($id, true);
                            if(count($existing_image) > 0){
                                $this->Group->removeImage($id, $existing_image[0]);
                            }
                            $file = new File;
                            $group_avatar = $file->upload('image', 'image', $id, TBL_GROUPS, false, true);
                            
                        }
                        else {
                            $group_avatar = 'boh';
                        }
                        
                         /** Prepare Custom Fields for WP XML-RPC - get all needed data **/
                        $Host = $this->Group->findHost($id);
                        
                        $custom_fields = array(
                                        array('key' => 'group_city',           'value' => $data['area']),       
                                        array('key' => 'group_host',            'value' => $Host->hostname),       
                                        array('key' => 'group_hostavatarurl',   'value' => UPLOADS_URL . 'mid_' .$Host->path),
                                        array('key' => 'group_hash',            'value' => $id),
                                        array('key' => 'group_avatar_url',      'value' => UPLOADS_URL . 'mid_' . $group_avatar ),
                                        );
                        
                        
                        /** Start WP XML-RPC **/
                        $wpClient = new \HieuLe\WordpressXmlrpcClient\WordpressClient();
                        $wpClient->setCredentials(WP_XMLRPC_ENDPOINT, WP_XMLRPC_USER, WP_XMLRPC_PSWD);
                        
                        $content = array(
                                        'post_type' => 'group',
                                        'post_title' => $Host->groupname,
                                        'post_content' => $data['free_text'],
                                        'custom_fields' => $custom_fields
                                        );
                        
                        
                        // Check for WP existence in DB
                        $theGroup = $this->Group->findOne($id);
                        if(!empty($theGroup->wordpress_post_id)){
                            
                            // we need to remap all custom fields because they all get unique IDs across all posts, so they don't get mixed up.
                            $thePost = $wpClient->getPost($theGroup->wordpress_post_id);
                            
                            foreach( $thePost['custom_fields'] as $i => $field ){
                                foreach( $custom_fields as $k => $set_field){
                                    if($field['key'] == $set_field['key']){
                                        $custom_fields[$k]['id'] = $field['id'];
                                    }
                                }
                            }
                            
                            $content['custom_fields'] = $custom_fields;
                            $wpClient->editPost($theGroup->wordpress_post_id, $content);
                        }
                        else {
                            $wpid = $wpClient->newPost($Host->groupname, $data['free_text'], $content);
                            $this->Group->update(array('wordpress_post_id' => $wpid), $id);
                        }
                        
                        
                        if(hasRole($this->user, 'Host')){
                            header('Location: /host?action=gu&code=200');
                        }
                    }
                    
                    $this->set('response', $response);
                }
            }
            $this->set('gmaps', true);
            $this->set('js', array( 'head' => array( '/ext/geocoder.js')));
            
            $Group = $this->Group->findOne($id);
            $this->set('title', 'Edit Group ' . $Group->name );
            $this->set('formdata', $Group);
            
            
        }
        
        public function delete($id){
            if(hasRole($this->user, 'Administrator')){
                $r = $this->Group->delete($id);
                if(!$r){
                    $response = 'd:err';
                }
                else {
                    $response = 'd:ok';
                }
                header('Location: /group/index/' . $response);
            }
            else {
                header('Location: /user/forbidden');
            }
        }
        
        public function stats($id){
            
            $Party = new Party;
            $Device = new Device;
            
            $allparties = $Party->ofThisGroup($id, true, true);
            
            $participants = 0;
            $hours_volunteered = 0;
            
            $need_attention = 0;
            foreach($allparties as $i => $party){
                if($party->device_count == 0){
                    $need_attention++;    
                }
                
                $party->co2 = 0;
                $party->fixed_devices = 0;
                $party->repairable_devices = 0;
                $party->dead_devices = 0;
                
                $participants += $party->pax;
                $hours_volunteered += (($party->volunteers > 0 ? $party->volunteers * 3 : 12 ) + 9);
                
                foreach($party->devices as $device){
                    if($device->repair_status == DEVICE_FIXED){ 
                        $party->co2 += $device->footprint;
                    }
                    
                    switch($device->repair_status){
                        case 1:
                            $party->fixed_devices++;
                            break;
                        case 2:
                            $party->repairable_devices++;
                            break;
                        case 3:
                            $party->dead_devices++;
                            break;
                    }
                }
                
                $party->co2 = number_format(round($party->co2 * $Device->displacement), 0, '.' , ',');    
            }
            
            $this->set('pax', $participants);
            $this->set('hours', $hours_volunteered);
            
            $weights = $Device->getWeights($id);
            $devices = $Device->ofThisGroup($id);
            
            
        }
    }
    