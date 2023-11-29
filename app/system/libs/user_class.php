<?php
class user{
    private static $instance = null;
    public static $user = null;

    private function get_config(){
        return storage::init()->system_config;
    }
    public static function init(){
        if(self::$instance === null) self::$instance = new Static();
        return self::$instance;
    }
    public function save_user($user_data){
        $conf = storage::get_instance()->system_config;
        $db = db::get_connection($conf->db_configs);
        
        $ret = ['status'=>'unknown', 'details'=>'not set', 'user_id'=>0];
        $data = [];
        // Some fields are optional
        if(isset($user_data['full_name'])) $data['full_name'] = addslashes($user_data['full_name']);
        if(isset($user_data['system_role'])) $data['system_role'] = intval($user_data['system_role']);
        if(isset($user_data['status'])) $data['status'] = addslashes($user_data['status']);
        if(isset($user_data['phone'])) $data['phone'] = system::format_phone($user_data['phone']);
        if(isset($user_data['email'])) $data['email'] = system::format_email($user_data['email']);
        if(isset($user_data['passcode']) && trim($user_data['passcode'])) $data['passcode'] = system::create_hash($user_data['passcode']);
        //if(isset($user_data['system_role'])) $data['system_role'] = $user_data['roles'];

        if(isset($user_data['user_id']) && $user_data['user_id']){
            $id = intval($user_data['user_id']);
            $result = $db->update('user_accounts', $data)->where(['user_id'=>$id])->commit();
        }
        else{
            if(!isset($data['full_name']) or (!isset($data['phone']) && !isset($data['email']))) return;
            $user_data['created_by'] = self::get_session_user('user_id');
            $user_data['created_time'] = date('Y-m-d H:i:s');
            $id = $db->insert('user_accounts', $data);
        }
        if($db->error()) $ret = ['status'=>'fail', 'details'=>$db->error(), 'user_id'=>$id];
        else $ret = ['status'=>'ok', 'details'=>'Save was a success!', 'user_id'=>$id];

        return (object)$ret;
    }
    public function delete_user($user_id){
        $db = db::get_connection(storage::init()->db_configs);
        $ret = ['status'=>'unknown', 'details'=>'not set', 'user_id'=>0];
        $result = $db->delete('user_accounts')->where(['user_id'=>$user_id])->commit();

        if(!$db->error()) return 'ok';
        else return $db->error()['message'];
    }
    public function get_session_user($index = null){
        $conf = storage::init()->system_config;
        $user = isset($_SESSION[$conf->session_name]) ? $_SESSION[$conf->session_name] : null;
        if($user && isset($user['user'])){
            if($index == null) return $user['user'];
            elseif(isset($user['user'][$index])) return $user['user'][$index];
        }
        return null;
    }
    public function get_user_avatar($uid = null){
        if($uid == null) $uid = $this->get_session_user('user_id');
        $src = 'app/storage/uploads/avatar/';
        $root = realpath(__DIR__."/../../../{$src}");
        if(is_readable("{$root}/avatar_{$uid}.jpg")) return "{$src}/avatar_{$uid}.jpg";
        else return "{$src}/avatar_0.jpg";
    }
    public function set_session_user($user_info){
        $conf = storage::init()->system_config;
        if(!is_array($user_info)) return false;
        $_SESSION[$conf->session_name]['user'] = $user_info;
        if(is_array($_SESSION[$conf->session_name]['user'])) return true;
        else return false;
    }
    public function end_user_session(){
        $conf = storage::init()->system_config;
        $_SESSION[$conf->session_name]['user'] = null;
        unset($_SESSION[$conf->session_name]['user']);
    }
    public function get_user_permissions($role = null){
        if($role == null){
            $role = self::get_session_user('system_role');
            if(!intval($role)) return [];
        }
        $conf = storage::init()->system_config;
        $db = db::get_connection($conf->db_configs);
        
        $whr = " permission_id IN (SELECT permission_id FROM `role_permission_list` WHERE role_id = {$role})";
        $permission = $db->select('permissions','permission_name')->where($whr)->fetchAll();
        if($permission) {
            $return = [];
            foreach($permission as $v){
                $return[] = $v['permission_name'];
            }
            return $return;
        }
        else return [];
    }
    public function user_can($reference){
        if(empty(trim($reference))) return true;
        $permission = $this->get_user_permissions($this->get_session_user('system_role'));
        return in_array(trim($reference), $permission) ? true : false;
    }
    public function init_login(){
        if(isset($_POST['login']) && isset($_POST['passcode'])) {
            if(self::login_user($_POST)){
                header("Location: {$_SERVER['REQUEST_URI']}");
            }
            $msg = 'Login failed!';
        }
        $registry = storage::init();
        $root = realpath(__DIR__.'/../themes');
        $login_form = "{$root}/{$registry->system_config->theme}";
        if(is_readable("{$login_form}/login.html")) include "{$login_form}/login.html";
        else include "{$root}/Default/login.html";
        exit;
    }
    public function login_user($login_info){
        $conf = storage::init()->system_config;
        $db = db::get_connection($conf->db_configs);
        $obj = new static();
        $whr = "passcode = :passcode AND (phone = :pnumber OR email = :email) AND status = 'active'";
        if(intval($login_info['login'])){
            $login = $obj::format_phone_number($login_info['login']);
        }
        else $login = system::format_email($login_info['login']);
        if(!$login) return false;
        $pass = system::create_hash($login_info['passcode']);

        $user = $db->select('user_accounts')
                    ->join('roles','role_id=system_role')
                    ->where($whr, ['passcode'=>$pass, 'pnumber'=>$login, 'email'=>$login])
                    ->limit(1)->fetch();
        
        if(!$db->error() && isset($user['passcode'])){
            unset($user['passcode']);
            unset($user['activation_token']);
            return $obj->set_session_user($user);
        }
        return false;
    }
    public function check_user_session(){
        $obj = new static();
        $user = $obj->get_session_user();
        if($user == null) return false;
        else return true;
    }
}