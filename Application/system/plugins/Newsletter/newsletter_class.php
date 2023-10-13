<?php
class newsletter{
    public $name = 'newsletter';
    public $version = 1.0;
    public $desc = 'Plugin dedicated for complete academic management as a drop in';

    private static $instance = null;
    
    public function __construct(){
        // Not applicable, may throw exception in future releases
    }

    public static function init(){
        // Hook to the system
        system::add_event_listener('exec_end', 'newsletter::load_page', $_SERVER['REQUEST_URI']);
        // Hook to admin dashboard
        system::add_event_listener('admin_plugin_load', 'newsletter::load_admin_dashboard');
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
        $check = @file_get_contents(__DIR__.'/last_send.txt');
        if(!$check or $check != date('Y-m-d')){
            $subs = $db->select('newsletter_subscribers','subscriber_phone')->fetchAll();
            $subscribers = [];
            foreach($subscribers as $sb) $subscribers[] = $sb['subscriber_phone'];

            $conf_txt = file_get_contents(__DIR__.'/modules/alerts.json');
            $cd = json_decode($conf_txt);
            $message = $cd->updates_available;
            $sms = system::init_gateway('sms');
            $sms->send_request(['recipients'=>$subscribers, 'message'=>$message]);
            file_put_contents(__DIR__.'/last_send.txt', date('Y-m-d'));
        }
        $page = $registry->page;
        if(!intval($page['page_id'])) return;

        $bbcodes = template::find_bbcode($page['page_content']);
        if(is_array($bbcodes)){
            $obj = new static();
            $supported = ['newsletter_subscribe'];
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
       
    public static function newsletter_subscribe(){
        $msg = '';
        if(isset($_POST['subcription_phone']) && !empty($_POST['subcription_phone'])){
            $registry = storage::init();
            $db = db::get_connection($registry->system_config);

            $data = [
                'subscriber_phone'=>system::format_phone($_POST['subcription_phone']), 
                'subscription_date'=>date('Y-m-d H:i:s')
            ];
            $k = $db->insert('newsletter_subscribers', $data);
            if(!$db->error() && $k) $msg = "<script>alert('You are in the list, enjoy!');</script>";
        }
        ob_start();
        echo $msg;
        include __DIR__.'/assets/html/subscription_form.html';
        return ob_get_clean();
    }
}