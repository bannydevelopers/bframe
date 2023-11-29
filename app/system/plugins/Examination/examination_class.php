<?php
class examination{

    private static $instance = null;
    
    public function __construct(){
        // Not applicable, may throw exception in future releases
    }

    public static function init(){
        // Hook to the system
        system::add_event_listener('exec_end', 'examination::load_page', $_SERVER['REQUEST_URI']);
        // Hook to admin dashboard
        system::add_event_listener('admin_plugin_load', 'examination::load_admin_dashboard');
        // Hook to admin welcome cards
        system::add_event_listener('admin_profile_load', 'examination::load_admin_dashboard_cards');
    }
    public static function load_admin_dashboard($args){
        if(str_replace('-', '_', strtolower($args[0])) == __CLASS__) {
            $user = user::init()->get_session_user();
            //$owner_school = $user['myschool'];
            $staff = human_resources::get_staff();
            $owner_school['id'] = 0;//$staff['work_location'];
            if(!$owner_school) $owner_school = ['id'=>0, 'name'=>'','email'=>$user['email']];
            
            $moduleconfig = json_decode(file_get_contents(__DIR__.'/config.json'));
            $registry = storage::init();
            $myURL = "{$registry->request[0]}/{$registry->request[1]}/{$registry->request[2]}";
            $_this = new static();
            $user = user::init()->get_session_user();
            $return = ['title'=>"Module '{$args[1]}' not found",'body'=>'Request not supported'];
            $db = db::get_connection($registry->system_config->db_configs);
            if(is_readable(__DIR__."/modules/{$args[1]}.php")) {
                include __DIR__."/modules/{$args[1]}.php";
            }
            return $return;
        }
    }
    public static function load_admin_dashboard_cards($args){
        return '<div class="content" style="min-width:95%">hr cards</div>';
    }
}