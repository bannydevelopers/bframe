<?php
class user{
    private static $instance = null;

    public static function initialize(){
        if(self::$instance === null) self::$instance = new static();
        return self::$instance;
    }
    public function check_session(){
        $storage = storage::initialize()->system_config;
        if(!isset($_SESSION[$storage->session_name])) return false;
        $session = $_SESSION[$storage->session_name];
        if(isset($session['user_id']) && intval($session['user_id'])) return true;
        else return false;
    }
    public function login_user(){
        $storage = storage::initialize();
        if(isset($_POST['fullname'])){
            return $this->save_user();
        }
        $path = realpath(__DIR__.'/../views');
        if(is_readable("{$path}/{$storage->view}/login.html")){
            $login_tpl = "{$path}/{$storage->view}/login.html";
        }
        else{
            $login_tpl = "{$path}/default/login.html";
        }
        $registry = $storage->system_config;
        $base = $storage->install_dir;
        include $login_tpl;
        return;
    }
    public function save_user(){
        //
    }
}
