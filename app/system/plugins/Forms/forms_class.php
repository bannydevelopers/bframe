<?php
class forms{

    private static $instance = null;
    
    public function __construct(){
        // Not applicable, may throw exception in future releases
    }

    public static function init(){
        // Hook to the system
        system::add_event_listener('exec_end', 'forms::load_page', $_SERVER['REQUEST_URI']);
        // Hook to admin dashboard
        system::add_event_listener('admin_plugin_load', 'forms::load_admin_dashboard');
    }

    public static function load_admin_dashboard($args){
        $registry = storage::init();
        if(strtolower($args[0]) == __CLASS__) {
            $myURL = "{$registry->request[0]}/{$registry->request[1]}/{$registry->request[2]}";
            $_this = new static();
            $user = user::init()->get_session_user();
            $return = ['title'=>'Module not found','body'=>'Request not supported'];
            if(is_readable(__DIR__."/modules/{$args[1]}.php")) {
                include __DIR__."/modules/{$args[1]}.php";
            }
            return $return;
        }
    }

    public static function load_page($args){
        $registry = storage::init();
        $db = db::get_connection($registry->system_config);
        
        $page = $registry->page;
            if(is_string($page['page_extras'])) $page['page_extras'] = json_decode($page['page_extras'], true);
        if(!intval($page['page_id'])) return;
        if($page['page_type'] == 'forms::form_page'){
            if(!empty($_POST)){
                //var_dump($page['page_extras']['thanks_msg']);
                $dir = realpath(__DIR__.'/form_submits');
                if(!is_writable("{$dir}/{$page['page_name']}")) mkdir("{$dir}/{$page['page_name']}");
                $fn = time();
                file_put_contents("{$dir}/{$page['page_name']}/{$fn}.json", json_encode($_POST, JSON_PRETTY_PRINT));
                $page['page_content'] = str_replace('{$form}', $page['page_extras']['thanks_msg'], $page['page_content']);
                storage::init()->page = $page;
                return;
            }
            //$page['page_extras'] = json_decode(stripslashes(str_replace(['\n','\r'],'',$page['page_extras'])), true);
            $form_fields = "<form method=\"POST\">{$page['page_extras']['form_fields']}</form>";

            $page['page_content'] = str_replace('{$form}', $form_fields, $page['page_content']);

            storage::init()->page = $page;
        }
    }
}