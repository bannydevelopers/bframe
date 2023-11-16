<?php
class Stores{

    private static $instance = null;
    
    public function __construct(){
        // Not applicable, may throw exception in future releases
    }

    public static function init(){
        // Hook to the system
        system::add_event_listener('exec_end', 'stores::load_page', $_SERVER['REQUEST_URI']);
        // Hook to admin dashboard
        system::add_event_listener('admin_plugin_load', 'stores::load_admin_dashboard');
        // Hook to admin card
        system::add_event_listener('admin_profile_load', 'stores::load_admin_dashboard_cards');
    }
    public static function load_admin_dashboard_cards($args){
        return '<div class="content" style="min-width:95%">hr cards</div>';
    }
    public static function load_admin_dashboard($args){
        if(str_replace('-', '_', strtolower($args[0])) == strtolower(__CLASS__)) {
            $moduleconfig = json_decode(file_get_contents(__DIR__.'/config.json'));
            $registry = storage::init();
            $myURL = "{$registry->request[0]}/{$registry->request[1]}/{$registry->request[2]}";
            $_this = new static();
            $user = user::init()->get_session_user();
            $return = ['title'=>"Module '{$args[1]}' not found",'body'=>'Request not supported'];
            if(is_readable(__DIR__."/modules/{$args[1]}.php")) {
                include __DIR__."/modules/{$args[1]}.php";
            }
            return $return;
        }
    }
    public static function load_page($request){
        $registry = storage::init();
        return;
    }
}