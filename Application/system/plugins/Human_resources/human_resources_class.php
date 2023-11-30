<?php
class human_resources{

    private static $instance = null;
    
    public function __construct(){
        // Not applicable, may throw exception in future releases
    }

    public static function init(){
        // Hook to the system
        system::add_event_listener('exec_end', 'human_resources::load_page', $_SERVER['REQUEST_URI']);
        // Hook to admin dashboard
        system::add_event_listener('admin_plugin_load', 'human_resources::load_admin_dashboard');
        // Hook to admin card
        system::add_event_listener('admin_widgets_load', 'human_resources::load_admin_dashboard_cards');
        // Respond to service requests
        system::add_event_listener('add_staff', 'human_resources::service_add_staff');
        system::add_event_listener('add_designation', 'human_resources::service_add_designation');
        system::add_event_listener('add_department', 'human_resources::service_add_department');
        system::add_event_listener('add_bank', 'human_resources::service_add_bank');
        system::add_event_listener('add_branch', 'human_resources::service_add_branch');
    }
    public static function load_admin_dashboard_cards($args){
        // fetch info
        ob_start();
        include __DIR__.'/modules/html/card.html';
        return ob_get_clean();
    }
    public static function load_admin_dashboard($args){
        if(str_replace('-', '_', strtolower($args[0])) == __CLASS__) {
            $me = human_resources::get_staff();
            if($me){
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
    public static function get_headquarters_branch(){
        $moduleconfig = json_decode(file_get_contents(__DIR__.'/config.json'));
        return $moduleconfig->headquarters_branch;
    }
    public static function service_add_staff($data){
        if(user::init()->user_can('view_staff')){
            $registry = storage::init();
            $db = db::get_connection($registry->system_config->db_configs);

            if(isset($_POST['full_name']) && isset($_POST['date_employed'])){
                $userdata = [
                    'full_name'=>addslashes( $_POST['full_name'] ),
                    'email'=>system::format_email( $_POST['email']), 
                    'phone'=>system::format_phone( $_POST['phone']), 
                    'passcode'=>system::create_hash( $_POST['email']), 
                    'status'=>addslashes( $_POST['status']),
                    'system_role'=>intval( $_POST['system_role'])
                ];
                
                $staffdata = [
                    'bank'=>intval( @$_POST['bank']), 
                    'bank_account_number'=>addslashes( $_POST['bank_account_number']),
                    'registration_number'=>addslashes( $_POST['registration_number']), 
                    'residence_address'=>addslashes( $_POST['residence_address']), 
                    'designation'=>intval( $_POST['designation']),
                    'work_location'=>intval( $_POST['work_location']), 
                    'department'=>intval( $_POST['department']),
                    'date_employed'=>$_POST['date_employed'],
                    //'employment_length'=>intval( $_POST['designation']), 
                    //'employment_status'=>addslashes( $_POST['designation']), 
                    //'employment_last_renewal'=>addslashes( $_POST['designation']),
                    //'employment_end_date'=>addslashes( $_POST['designation'])
                ];
                //return json_encode(['status'=>'info', 'message'=>json_encode($staffdata)]);
                if(isset($_POST['user_id'])){
                    $staffdata['user_reference'] = $_POST['user_id'];
                    if(user::init()->user_can('edit_staff')){
                        $db->update('user_accounts', $userdata)
                            ->where(['user_id'=>intval($_POST['user_id'])])
                            ->commit();
                        if(!$db->error()){
                            $db->update('staff', $staffdata)
                                ->where(['user_reference'=>intval($_POST['user_id'])])
                                ->commit();
                        }
                    }
                    else return json_encode(['status'=>'error', 'message'=>'Access denied']);
                }
                else{
                    if(user::init()->user_can('add_staff')){
                        $k = $db->insert('user_accounts', $userdata);
                        if(!$db->error() && intval($k)){
                            $staffdata['user_reference'] = $k; 
                            $ks = $db->insert('staff', $staffdata);
                            if($db->error() or !intval($ks)){
                                $db->delete('user_accounts')->where(['user_id'=>$k])->commit();
                            }
                        }
                    }
                    else return json_encode(['status'=>'error', 'message'=>'Access denied']);
                }
                if(!$db->error())
                    return json_encode(['status'=>'success', 'message'=>'Staff saved successful']);
                else 
                    return json_encode(['status'=>'error', 'message'=>json_encode($db->error())]);
            }
            ob_start();
            $moduleconfig = json_decode(file_get_contents(__DIR__.'/config.json'));
            $mestaff = self::get_staff('work_location');
            if($mestaff == $moduleconfig->headquarters_branch) $whr = 1;
            else $whr = ['branch_id'=>$mestaff];

            $designations = $db->select('designations')->fetchAll();
            $departments = $db->select('departments')->fetchAll();
            $branches = $db->select('branches')->where($whr)->fetchAll();
            $banks = $db->select('banks')->fetchAll();
            $roles = $db->select('roles')->fetchAll();

            include __DIR__.'/modules/html/add_staff.html';
            return ob_get_clean();
        }
        else return 'Access denied, no enough permission!';
    }
    public static function service_add_designation($data){
        if(user::init()->user_can('add_designations')){
            $registry = storage::init();
            $db = db::get_connection($registry->system_config->db_configs);

            if(isset($_POST['designation_name'])){

                $data = [
                    'designation_name'=>addslashes( $_POST['designation_name'] ),
                    'designation_description'=>addslashes( $_POST['designation_description'] ),
                    'created_by'=>user::init()->get_session_user('user_id'),
                    'create_date'=>date('Y-m-d')
                ];
                $db->insert('designations', $data);
                if(!$db->error()) return json_encode(['status'=>'success', 'message'=>'Designation added successful']);
                else return json_encode(['status'=>'error', 'message'=>$db->error()['message']]);
            }
            ob_start();
            $designations = $db->select('designations')->fetchAll();
            include __DIR__.'/modules/html/add_designation.html';
            return ob_get_clean();
        }
        else return 'Access denied!';
    }
    public static function service_add_department($data){
        $registry = storage::init();
        $db = db::get_connection($registry->system_config->db_configs);

        if(isset($_POST['dept_name'])){
            $fd = [
                'dept_name'=>addslashes($_POST['dept_name']),
                'dept_desc'=>addslashes($_POST['dept_desc'])
            ];
            if(isset($_POST['dept_id']) && intval($_POST['dept_id'])) {
                $k = intval($_POST['dept_id']);
                if(user::init()->user_can('edit_departments')){
                    $db->update('departments', $fd)->where("dept_id={$k}")->commit();
                }
                else return 'Access denied!';
            }
            else{
                if(user::init()->user_can('add_departments')){
                    $k = $db->insert('departments', $fd);
                }
                else return 'Access denied!';
            }
            if($db->error()) {
                return json_encode(['message'=>$db->error()['message'],'status'=>'error']);
            }
            else {
                return json_encode(['message'=>'Department added successful', 'status'=>'success']);
            }
        }
        else{
            ob_start();
            $departments = $db->select('departments')->fetchAll();
            $faculties = $db->select('faculties')->fetchAll();
            include __DIR__.'/modules/html/add_department.html';
            return ob_get_clean();
        }
    }
    public static function service_add_bank($data){
        return 'no bank to add';
        
    }
    public static function service_add_branch($data){
        $registry = storage::init();
        $db = db::get_connection($registry->system_config->db_configs);
        if(isset($_POST['branch_name'])){
            $fd = [
                'branch_name'=>addslashes($_POST['branch_name']),
                'branch_address'=>addslashes($_POST['branch_address']),
                'branch_location'=>addslashes($_POST['branch_location']), 
                'is_headquarters'=>intval($_POST['is_headquarters'])
            ];
            if(isset($_POST['branch_id']) && intval($_POST['branch_id'])) {
                $k = intval($_POST['branch_id']);
                $db->update('branches', $fd)->where("branch_id={$k}")->commit();
            }
            else{
                $k = $db->insert('branches', $fd);
            }
            if($db->error()) {
                return json_encode(['message'=>$db->error()['message'],'status'=>'error']);
            }
            else {
                return json_encode(['message'=>'Branch added successful', 'status'=>'success']);
            }
        }
        else{
            ob_start();
            include __DIR__.'/modules/html/add_branch.html';
            return ob_get_clean();
        }        
    }
    public static function get_staff($index = null){
        $config = storage::get_data('system_config')->db_configs;
        $db = db::get_connection($config);
        $me = $db->select('staff')
                 ->where(['user_reference'=>user::init()->get_session_user('user_id')])
                 ->limit(1)
                 ->fetch();
        
        if(isset($me[$index])) return $me[$index];
        else if($index == null) return $me;
        else return null;
    }
}