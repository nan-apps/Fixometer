<?php
    session_start();
    $url =  $_GET['url'];
    
    require_once($_SERVER['DOCUMENT_ROOT'] . '/config/config.php');
    require_once(ROOT . DS . 'config' . DS . 'database.php');
    require_once(ROOT . DS . 'config' . DS . 'definitions.php');
    require_once(ROOT . DS . 'lib' . DS . 'main.php');
    
    