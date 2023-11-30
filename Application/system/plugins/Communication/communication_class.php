<?php
class Communication{

    private static $instance = null;
    
    public function __construct(){
        // Not applicable, may throw exception in future releases
    }

    public static function init(){
        // Hook to the system
        system::add_event_listener('exec_end', 'communication::load_page', $_SERVER['REQUEST_URI']);
        // Hook to admin dashboard
        system::add_event_listener('admin_plugin_load', 'communication::load_admin_dashboard');
        // Respond to service requests
        system::add_event_listener('add_bank', 'communication::service_add_bank');
    }
    public static function load_admin_dashboard($args){
        if(strtolower($args[0]) == __CLASS__) {
            $registry = storage::init();
            $myURL = "{$registry->request[0]}/{$registry->request[1]}/{$registry->request[2]}";
            $_this = new static();
            $user = user::init()->get_session_user();

            $title = $args[0];
            $body = 'Loading...';
            return ['title'=>$title,'body'=>$body];
        }
    }
    public static function load_page($request){
        $registry = storage::init();
        
        $conf = $registry->system_config;
        $db = db::get_connection($conf->db_configs);
        
        $page = $registry->page;
    }
    public static function service_add_bank(){
        return '<form>Adding bank<button>Send</button></form>';
    }
}