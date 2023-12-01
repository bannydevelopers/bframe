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
<<<<<<< HEAD
        system::add_event_listener('add_bank', 'communication::service_add_bank');
=======
        //system::add_event_listener('add_bank', 'communication::service_add_bank');
>>>>>>> 7ad5bc4fd04c8d8b1aeb35d0b9809ac3950dbaca
    }
    public static function load_admin_dashboard($args){
        if(str_replace('-', '_', strtolower($args[0])) == strtolower(__CLASS__)) {
            $me = human_resources::get_staff();
            if($me){
                $moduleconfig = json_decode(file_get_contents(__DIR__.'/config.json'));
                $config = storage::init();
                $db = db::get_connection($config->system_config->db_configs);
                $myURL = "{$config->request[0]}/{$config->request[1]}/{$config->request[2]}";
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
        $config = storage::init();
        
        $conf = $config->system_config;
        $db = db::get_connection($conf->db_configs);
        
<<<<<<< HEAD
        $page = $registry->page;
=======
        $page = $config->page;
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
>>>>>>> 7ad5bc4fd04c8d8b1aeb35d0b9809ac3950dbaca
    }
    public static function service_add_bank(){
        return '<form>Adding bank<button>Send</button></form>';
    }
}