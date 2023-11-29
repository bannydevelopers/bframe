<?php
class Acadermic{

    private static $instance = null;
    
    public function __construct(){
        // Not applicable, may throw exception in future releases
    }

    public static function init(){
        // Hook to the system
        system::add_event_listener('exec_end', 'acadermic::load_page', $_SERVER['REQUEST_URI']);
        // Hook to admin dashboard
        system::add_event_listener('admin_plugin_load', 'acadermic::load_admin_dashboard');
        //widgets
        system::add_event_listener('admin_profile_load', 'acadermic::load_admin_widgets');
    }
    public static function load_admin_dashboard($args){
        if(str_replace('-', '_', strtolower($args[0])) == strtolower(__CLASS__)) {
            $moduleconfig = json_decode(file_get_contents(__DIR__.'/config.json'));
            $registry = storage::init();
            $myURL = "{$registry->request[0]}/{$registry->request[1]}/{$registry->request[2]}";
            $_this = new static();
            $db = db::get_connection($registry->db_configs);
            $user = user::init()->get_session_user();
            $staff = human_resources::get_staff();
            if($staff){
                $owner_school['id'] = $staff['work_location'];
                $return = ['title'=>"Module '{$args[1]}' not found",'body'=>'Request not supported'];
                if(is_readable(__DIR__."/modules/{$args[1]}.php")) {
                    include __DIR__."/modules/{$args[1]}.php";
                }
                return $return;
            }
            else $return = ['title'=>'Access denied', 'body'=>'You need to register as staff to continue'];
        }
    }
    public static function load_page($request){
        $registry = storage::init();
        
        $conf = $registry->system_config;
        $myconf = json_decode(file_get_contents(__DIR__.'/config.json'));
        $db = db::get_connection($conf->db_configs);
        
        $page = $registry->page;
        if(!intval($page['page_id'])) return;
        if($page['page_extras'] && is_string($page['page_extras'])) $extras = json_decode($page['page_extras']);
        else $extras = [];
        $page['page_extras'] = (array)$extras;

        if($page['page_id'] == $myconf->signup_page){
            // Save request
            if(isset($_POST['school_name'])){
                $extras = [
                    'registration_number'=>addslashes($_POST['registration_number']),
                    'subscription_package'=>intval($_POST['subscription_package']),
                    'address'=>addslashes($_POST['address']),
                    'phone'=>system::format_phone($_POST['phone']),
                    'email'=>system::format_email($_POST['email']),
                    'currency'=>addslashes($_POST['currency']),
                    'currency_symbol'=>addslashes($_POST['currency_symbol']),
                    'final_result_type'=>addslashes($_POST['final_result_type']),
                    'enable_online_admission'=>intval($_POST['enable_online_admission'])
                ];
                $data = [
                    'page_name'=>addslashes($_POST['school_name']),
                    'page_title'=>"{$_POST['school_name']} - Home",
                    'page_type'=>'acadermic::school_package', 
                    'page_parent'=>0, 
                    'page_content'=>'', 
                    'page_icon'=>'', 
                    'page_order'=>0, 
                    'page_desc'=>'', 
                    'page_keywords'=>'', 
                    'page_author'=>0, 
                    'create_date'=>date('Y-m-d'), 
                    'page_special'=>2, 
                    'page_extras'=>json_encode($extras)
                ];
                $k = $db->insert('pages', $data);
                if(!$db->error()) {
                    if(intval($k) && is_readable($_FILES['logo']['tmp_name'])){
                        $root_dir = realpath(__DIR__.'/../../../../storage/');
                        $dest = "{$root_dir}/school_uploads";
                        if(!is_readable($dest)) mkdir($dest);
                        $dest .= "/school_{$k}";
                        if(!is_readable($dest)) mkdir($dest);
                        $dest .= '/logo.png';
                        $logo = system::upload_image($_FILES['logo']['tmp_name'], $dest, ['width'=>240, 'height'=>240]);
                    }
                    $role = $db->select('subscription_packages','sub_role as role_id')
                               ->where(['sub_id'=>$extras['subscription_package']])->fetch();
            
                    $user = [
                        'full_name'=>$data['page_name'],
                        'email'=>$extras['email'],
                        'phone'=>$extras['phone'],
                        'passcode'=>system::create_hash($extras['email']),
                        'status'=>'active',
                        'system_role'=>$role['role_id'],
                        'create_date'=>date('Y-m-d')
                    ];
                    $db->insert('user_accounts', $user);
                    $msg = 'School added successful';
                }
                else $msg = $db->error()['message'];
                if(!$db->error()) {
                    $url = "{$registry->install_dir}{$registry->system_config->dashboard_url}";
                    header("Location: {$url}");
                }
            }
            $obj = new static();
            $bbcodes = template::find_bbcode($page['page_content']);
            $subs = $db->select('subscription_packages')->fetchAll();
            ob_start();
            include __DIR__.'/modules/html/schools_list_add.html';
            $body = ob_get_clean();
            $page['page_content'] .= "<div style=\"max-width:600px;margin:1em auto\" class=\"content box-shadow\">{$body}</div>";
            storage::init()->page = $page;
        }
    }
    public static function load_admin_widgets(){
        $registry = storage::init();
        $db = db::get_connection($registry->system_config->db_configs);

        $owner_school = user::init()->get_session_user('myschool');
        ob_start();
        //include __DIR__.'/modules/html/widgets.html';
        return ob_get_clean();
    }
}