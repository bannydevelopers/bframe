<?php
class   Banks{

    private static $instance = null;
    
    public function __construct(){
        // Not applicable, may throw exception in future releases
    }

    public static function init(){
        // Hook to the system
        system::add_event_listener('exec_end', 'banks::load_page', $_SERVER['REQUEST_URI']);
        // Hook to admin dashboard
        system::add_event_listener('admin_plugin_load', 'banks::load_admin_dashboard');
        // Hook to admin card
        system::add_event_listener('admin_widgets_load', 'banks::load_admin_dashboard_cards');
        // Add service
        system::add_event_listener('add_bank', 'banks::service_add_bank');
    }
    public static function load_admin_dashboard_cards($args){
        // fetch info
        ob_start();
        include __DIR__.'/modules/html/card.html';
        return ob_get_clean();
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
    
    public static function service_add_bank($data){
        $registry = storage::init();
        $db = db::get_connection($registry->system_config->db_configs);

        if(isset($_POST['bank_name'])){
            $fd = [
                'bank_name'=>addslashes($_POST['bank_name']),
                'bank_logo'=>''
            ];
            if(isset($_POST['bank_id']) && intval($_POST['bank_id'])) {
                $k = intval($_POST['bank_id']);
                if(user::init()->user_can('edit_bank')){
                    $db->update('banks', $fd)->where("bank_id={$k}")->commit();
                }
                else return json_encode(['message'=>'Access denied!','status'=>'error']);
            }
            else{
                if(user::init()->user_can('add_bank')){
                    $k = $db->insert('banks', $fd);
                }
                else return json_encode(['message'=>'Access denied!','status'=>'error']);
            }
            if($db->error()) {
                return json_encode(['message'=>$db->error()['message'],'status'=>'error']);
            }
            else {
                return json_encode(['message'=>'Bank saved successful', 'status'=>'success']);
            }
        }
        else{
            ob_start();
            include __DIR__.'/modules/html/add_bank.html';
            return ob_get_clean();
        }        
    }
    public static function load_page($request){
        $registry = storage::init();
        return;
    }
}