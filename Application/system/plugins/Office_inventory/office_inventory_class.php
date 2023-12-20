<?php
class Office_inventory{

    private static $instance = null;
    
    public function __construct(){
        // Not applicable, may throw exception in future releases
    }

    public static function init(){
        // Hook to the system
        system::add_event_listener('exec_end', 'office_inventory::load_page', $_SERVER['REQUEST_URI']);
        // Hook to admin dashboard
        system::add_event_listener('admin_plugin_load', 'office_inventory::load_admin_dashboard');
        // Hook to admin card
        system::add_event_listener('admin_widgets_load', 'office_inventory::load_admin_dashboard_cards');
    }
    public static function load_admin_dashboard_cards($args){
        // fetch info
        ob_start();
        include __DIR__.'/modules/html/card.html';
        return ob_get_clean();
    }
    public static function load_admin_dashboard($args){
        if(str_replace('-', '_', strtolower($args[0])) == strtolower(__CLASS__)) {
            $me = human_resources::get_staff();
            if($me){
                $headquarters = human_resources::get_headquarters_branch();
                $is_headquarters = ($headquarters == $me['work_location']);
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
            else return ['title'=>'Access denied', 'body'=>'You need to register as staff to continue'];
        }
    }
    public static function load_page($request){
        $registry = storage::init();
        return;
    }
}