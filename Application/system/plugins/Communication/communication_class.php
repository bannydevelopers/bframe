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
        //system::add_event_listener('add_bank', 'communication::service_add_bank');
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
        
        $conf = $registry->system_config;
        $db = db::get_connection($conf->db_configs);
        
        $page = $registry->page;
        if(!intval($page['page_id'])) return;

        if($page['page_extras'] && is_string($page['page_extras'])) $extras = json_decode($page['page_extras']);
        else $extras = [];
        $page['page_extras'] = (array)$extras;

        $obj = new static();
        $bbcodes = template::find_bbcode($page['page_content']);
        if(is_array($bbcodes)){
            $supported = [''];
            $searches = $replacement = [];
            foreach($bbcodes as $bbc){
                $bparts = explode('/', trim($bbc,' {$}'));
                if(in_array($bparts[0], $supported)) {
                    $searches[] = $bbc;
                    $replacement[] = $obj->{$bparts[0]}($bparts);
                }
            }
            
            $page['page_content'] = str_replace($searches, $replacement, $page['page_content']);
            storage::init()->page = $page;
        }
    }
    public static function service_add_bank(){
        return '<form>Adding bank<button>Send</button></form>';
    }
}