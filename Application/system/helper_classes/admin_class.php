<?php
class admin{
    public static function load_dashboard(){
        $storage = storage::initialize();
        $user = user::initialize();
        if(!$user->check_session()){
            $user->login_user();
            exit;
        }
        
    }
    
}
