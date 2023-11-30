<?php 
class admin{
    public static $data = [];
    private static function get_db(){
        $config = storage::get_data('system_config')->db_configs;
        return db::get_connection($config);
    }
    public static function load_dashboard($page = ''){
        $user = user::init();
        if(!$user->get_session_user()) $user->init_login();
        if(isset($_POST['ajax_register'])){
            $data = [
                'full_name'=>$_POST['full_name'],
                'system_role'=>0,
                'status'=>'active',
                'phone'=>system::format_phone($_POST['phone']),
                'email'=>$_POST['email'],
                'passcode'=>system::create_hash($_POST['passcode']),
                'created_by'=>0
            ];
            $k = user::init()->save_user($data);
            if($k->status == 'ok') $msg = 'Your account created successfull!';
            else $msg = 'Registration failed! May be account exists!';
            die($msg);
        }
        if(isset($_REQUEST['ajax_event'])){
            $arr = system::dispatch_event(addslashes($_REQUEST['ajax_event']), $_REQUEST);
            foreach($arr as $resp){
                if(is_string($resp)) echo $resp;
            }
            die;
        }
        // First things first
        $_this = new static();
        $_this::$data['url'] = $_SERVER['REQUEST_URI'];
        //$_this::$data['user_permissions'] = user::init()->get_user_permissions();
        $_this::$data['conf'] = storage::init()->system_config;
        $_this::$data['nav'] = $_this->get_nav();
        $_this::$data['root'] = storage::get_instance()->install_dir;
        $_this::$data['address'] = $page;
        $_this->handle_post_data();
        $_this::$data['url'] = strtok($_SERVER["REQUEST_URI"], '?');
        //var_dump($user->get_session_user());

        if(isset($page[1])){
            $method = "load_{$page[1]}";
            if(method_exists($_this, $method)){
                $_this->{$method}($page);
            }
            else $_this->load_not_found($page);
        }
        else $_this->load_home();
        exit;
    }
    protected function get_nav(){
        $conf = storage::get_instance()->system_config;
        $db = db::get_connection($conf->db_configs);
        $obj = new static();
        if(isset($obj::$data['nav'])) return $obj::$data['nav'];
        
        $pages = $db->select('pages','page_id,page_name')->where(['page_type'=>0])->fetchAll();
        $pkids = [
                    [
                        'name'=>'Create page',
                        'href'=>'/pages/add',
                        'permission'=>'add_page'
                    ]
                ];
        foreach($pages as $p){
            $pkids[] = [
                'name'=>$p['page_name'],
                'href'=>"/pages/edit/{$p['page_id']}",
                'permission'=>'edit_page'
            ];
        }
        $nav = [
            [
                'name'=>'Dashboard',
                'href'=>'/', 
                'icon'=>'f015',
                'permission'=>''
            ]
        ];
        foreach($conf->plugins as $plugin){
            $path = realpath(__DIR__.'/../');
            if(!is_file("{$path}/plugins/{$plugin}/config.json")) continue;
            $pconfig = json_decode(file_get_contents("{$path}/plugins/{$plugin}/config.json"), true);
            if(json_last_error() or !isset($pconfig['admin']['navigation'])) continue; // skip fault plugin
            $nav = array_merge($nav,$pconfig['admin']['navigation']);
        }
        $settings = 
        [
            [
                'name'=>'Users',
                'href'=>'#users', 
                'icon'=>'f509',
                'permission'=>'view_user',
                'children'=>[
                    [   
                        'name'=>'Create user',
                        'href'=>'/users/add',
                        'permission'=>'add_user'
                    ],
                    [
                        'name'=>'View users',
                        'href'=>'/users/list',
                        'permission'=>'view_user'
                    ]
                ]
            ],
            [
                'name'=>'Pages',
                'href'=>'/pages', 
                'icon'=>'f15c',
                'permission'=>'view_page',
                'children'=>$pkids
            ],
            [
                'name'=>'Setting',
                'alias'=>'configuration',
                'href'=>'#', 
                'icon'=>'f085',
                'permission'=>'view_configuration',
                'children'=>[
                    [
                        'name'=>'Widgets',
                        'href'=>'/configuration/widgets',
                        'permission'=>'view_widgets'
                    ],
                    [
                        'name'=>'Plugins',
                        'href'=>'/configuration/plugins',
                        'permission'=>'view_plugins'
                    ],
                    [
                        'name'=>'General settings',
                        'href'=>'/configuration/system',
                        'permission'=>'view_configuration'
                    ],
                    [
                        'name'=>'User permissions',
                        'href'=>'/configuration/permission',
                        'permission'=>'view_permission'
                    ]
                ]
            ]
        ];
        $nav = array_merge($nav, $settings);
        //var_dump('<pre>',$nav);
        return $nav;

    }
    public function load_home(){
        $_this = new static();
        $db = self::get_db();
        $user = user::init();
        $_this::$data['admin_widgets'] = [];
        if($user->user_can('view_user')){
            $_this::$data['admin_widgets']['users'] = $db->select('user_accounts', 'count(user_id) as total')->fetch();
        }
        if($user->user_can('view_pages')){
            $_this::$data['admin_widgets']['pages'] = $db->select('pages', 'count(page_id) as total')->fetch();
        }
        if($user->user_can('view_plugins')){
            $plugins_count = scandir(__DIR__.'/../plugins/');
            array_shift($plugins_count);
            array_shift($plugins_count);
            $_this::$data['admin_widgets']['plugins'] = ['total' => count($plugins_count)];
        }
        
        $widgets = system::dispatch_event('admin_profile_load', []);
        if($widgets && $widgets[0]){
            $_this::$data['widgets'] = '';
            foreach($widgets as $widget) $_this::$data['widgets'] = $widget;
        }

        $user = user::init()->get_session_user('full_name');
        return $this->display('dashboard',"Howdy, {$user}!");
    }
    public function load_pages($addr){
        $obj = new static();
        $db = $obj::get_db();
        if(isset($_POST['delete_page'])){
            $db->delete('pages')->where(['page_id'=>intval($_POST['delete_page'])])->commit();
            if(!$db->error()){
                die(json_encode([
                    'message'=>'Page deleted successful!',
                    'status'=>'success'
                ]));
            }
            else{
                die(json_encode([
                    'message'=>$db->error()['message'],
                    'status'=>'success'
                ]));
            }
        }
        $pages = $db->select('pages','page_id,page_icon,page_parent,page_name,page_title,page_special')
                    ->where(['page_type'=>0])->or('page_type IS NULL')
                    ->fetchAll();
        $obj::$data['pages'] = $pages;
        $data = ['address'=>$addr];
        if(!isset($addr[2])){
            $pages = template::create_array_tree($pages);
            $obj::$data['pages_list'] = $obj::array_to_list($pages);
            return $this->display('pages',' ');
        }
        else{
            system::clear_cache();
            if($addr[2] == 'add'){
                if(isset($_POST['page_name'])){
                    unset($_POST['tabset']);
                    $pdata = $_POST;
                    if(!isset($pdata['page_author'])) $pdata['page_author'] = user::init()->get_session_user('user_id');

                    if(!isset($pdata['page_special'])) $pdata['page_special'] = [];
                    $pdata['page_special'] = array_sum($pdata['page_special']);

                    $tmp = [];
                    foreach($pdata['page_extras']['name'] as $k=>$v){
                        if(empty($v) or empty($pdata['page_extras']['value'][$k])) continue;
                        $tmp[$v] = $pdata['page_extras']['value'][$k];
                    } 
                    $pdata['page_extras'] = $tmp;
                    $pdata['page_extras'] = json_encode($pdata['page_extras']);

                    $k = $db->insert('Pages', $pdata);
                    if($k) {
                        $obj::$data['message'] = 'page_added';
                        header("Location: ./edit/{$k}");
                    }
                    else $obj::$data['error'] = json_encode($db->error());
                    if($db->error()) $msg = $db->error()['message'];
                }
                $obj::$data['pages'] = $db->select('pages','page_id,page_icon,page_parent,page_name,page_title')
                                          ->where('page_special < 2')
                                          ->fetchAll();
                return $this->display('page_create', 'Create new page');
            }
            if(isset($addr[3]) && intval($addr[3])){
                if(isset($_POST['page_name'])){
                    $user = user::init()->get_session_user();
                    $data = $_POST;
                    unset($data['tabset']);
                    unset($data['page_id']);
                    
                    $data['page_special'] = isset($data['page_special']) ? array_sum(array_values($_POST['page_special'])) : 0;
                    $data['page_extras'] = isset($data['page_extras']) ? json_encode($data['page_extras']) : "{}";
                    $data['page_author'] = $user['user_id'];
                    $data['page_content'] = htmlspecialchars($data['page_content']);
                    
                    if(intval($_POST['page_id'])) {
                        $pid = $db->update('pages', $data)->where(['page_id'=>$_POST['page_id']])->commit();
                        if(!$db->error() && $pid) $pid = $_POST['page_id'];
                        else $pid = 0;
                    }
                    else{
                        $data['create_date'] = date('Y-m-d H:i:s');
                        $pid = $db->insert('pages', $data);
                    }
                    if(intval($pid)) {
                        $msg = 'Page saved successful';
                        $data['page_content'] = htmlspecialchars_decode($data['page_content']);
                    }
                    else $msg = 'Page save fail, sorry';
                    system::cache_clear();
                }
                $pg = $db->select('pages','pages.*,user_accounts.full_name')
                                         ->join('user_accounts','user_id=page_author', 'LEFT')
                                         ->where(['page_id'=>intval($addr[3])])
                                         ->fetch();
                if($pg['page_type']){
                    $args = explode('::', $pg['page_type']);
                    $obj::$data['page_type'] = $pg['page_type'];
                    if(is_callable($args)) return call_user_func($args, ['page'=>$pg,'addr'=>$addr]);
                    else return $this->display('page_not_recognized', "Required plugin not available or disabled");
                }
                if($pg['page_extras']) $extras = json_decode($pg['page_extras']);
                else $extras = [];
                $pg['page_extras'] = $extras;
                $pg['page_content'] = htmlspecialchars_decode($pg['page_content']);
                $obj::$data['page'] = $pg;

                /*if($addr[2] == 'delete'){
                    // delete then list pages with action
                    return $this->display('pages');
                }
                else*/if($addr[2] == 'edit'){
                    return $this->display('page_create_edit', $pg['page_name']);
                }
                elseif($addr[2] == 'add'){
                    return $this->display('page_create_edit', 'Create page');
                }
            }
        }
    }
    public function load_users($addr){
        if(isset($_SESSION['msg'])){
            self::$data['msg'] = $_SESSION['msg'];
            unset($_SESSION['msg']);
        }
        else self::$data['msg'] = '';
        $registry = storage::init();
        self::$data['root'] = "{$registry->install_dir}{$registry->request[0]}/{$registry->request[1]}";
        $db = self::get_db();
        $user = user::init();
        if(isset($_POST['full_name'])){
            $k = $user->save_user($_POST);
            $cls = $k->status == 'ok' ? 'msg' : 'error';
            if(!is_string($k->details)) $msg = $k->details['message'];
            else $msg = $k->details;
            if(isset($_GET['ajax']) && $_GET['ajax']) 
                die(json_encode(['status'=>$k->status, 'message'=>$msg]));
            else 
                self::$data['msg'] = "<p class=\"{$cls}\">{$msg}</p>";
            if(isset($k->user_id) && intval($k->user_id)){
                $_SESSION['msg'] = self::$data['msg'];
                $url = self::$data['root'];
                header("Location: {$url}/edit/{$k->user_id}");
            }
        }
        if(isset($_POST['uid'])){
            $k = $user->delete_user($_POST['uid']);
            $status = ($k == 'ok') ? 'ok' : 'fail';
            if(isset($_POST['ajax'])) die(json_encode(['status'=>$status,'message'=>$k]));
        }
        if(!isset($addr[2])){
            self::$data['users'] = $db->select('user_accounts')->join('roles','roles.role_id=user_accounts.role','LEFT')
                        ->where(1)->fetchAll();
            return $this->display('users_dashboard');
        }
        else{
            if(strtolower($addr[2]) == 'add'){
                self::$data['roles'] = $db->select('roles')->fetchAll();
                return $this->display('user_add');
            }
            elseif(strtolower($addr[2]) == 'edit' && intval(@$addr[3])){
                self::$data['roles'] = $db->select('roles')->fetchAll();
                self::$data['user'] = $db->select('user_accounts')
                                         ->where(['user_id'=>intval(@$addr[3])])
                                         ->fetch();
                return $this->display('user_edit', ' ');
            }
            elseif(strtolower($addr[2]) == 'list'){
                $pts = explode('/',$_SERVER['REQUEST_URI']);
                $myid = $user->get_session_user('user_id');
                $users = $db->select('user_accounts')->join('roles','roles.role_id=user_accounts.system_role','LEFT')
                            ->where("user_id != {$myid}")->fetchAll();
                
                system::add_event_listener('users_db_read', 'system::test_event_listener');
                $tmp = system::dispatch_event('users_db_read', $users);
                if(is_array($tmp)) $user = $tmp;
                self::$data['users'] = $users;
                //var_dump('<pre>',$db->error());
                return $this->display('users', ' ');
            }
        }
    }
    public function load_plugin($addr){
        $obj = new static();
        array_shift($addr);
        array_shift($addr);
        $obj::$data['body'] = '';
        $return = system::dispatch_event('admin_plugin_load', $addr);
        if($return && $return[0]){
            $obj::$data['body'] = $return[0]['body'];
            return $this->display('plugin',$return[0]['title']);
        }
        else {
            return $this->display('plugin','No data');
        }
    }
    public function load_configuration($addr){
        $db = self::get_db();
        if(isset($_GET['role_name'])){
            $k = $db->insert('roles',['role_name'=>addslashes($_GET['role_name'])]);
            $dberror = $db->error();
            if(intval($k)) $ret = ['status'=>'ok','message'=>'Role added successful'];
            else $ret = ['status'=>'fail','message'=>$dberror['message']];
            die( json_encode($ret) );
        }
        if(isset($_POST['role_perm'])){
            $role = $db->select('roles','role_id')->where(['role_name'=>trim($_POST['role_perm'])])->fetch();
            $db->delete('role_permission_list')->where(['role_id'=>$role['role_id']])->commit();
            if(isset($_POST['permission']) && is_array($_POST['permission'])){
                $list = implode("),({$role['role_id']},", $_POST['permission']);
                $qry = "INSERT INTO role_permission_list (role_id, permission_id) VALUES({$role['role_id']},{$list})";
                $k = $db->query($qry);
            }
            if(!$db->error()) die('Permission updated successful!');
            else die('Error updating prmissions');
        }
        if(isset($_POST['del_role'])){
            $role = $db->select('roles','role_id')->where(['role_name'=>trim($_POST['del_role'])])->fetch();
            $db->delete('role_permission_list')->where(['role_id'=>$role['role_id']])->commit();
            if(!$db->error()) {
                $db->delete('roles')->where(['role_id'=>$role['role_id']])->commit();
                die('The role together with permission deleted successful');
            }
            else die('Deletion failed');
        }
        if(!isset($addr[2])){
            return $this->display('404', ' ');
        }
        if(strtolower($addr[2]) == 'permission'){
            $roles = $db->select('role_permission_list')
                        ->join('permissions','permissions.permission_id=role_permission_list.permission_id')
                        ->join('roles','roles.role_id=role_permission_list.role_id')
                        ->order_by('legend')->fetchAll();
            
            $empty_roles = $db->select('roles')->order_by('role_id', 'desc')->fetchAll();

            $perms = $db->select('permissions')->fetchAll();
            $permissions = [];
            foreach($perms as $perm){
                if(!isset($permissions[$perm['legend']])) $permissions[$perm['legend']] = [];
                $permissions[$perm['legend']][] = $perm;
            }
            $role_tree = [];
            foreach($empty_roles as $er){
                $role_tree[$er['role_name']] = [];
            }
            foreach($roles as $role){
                /*if(!isset($role_tree[$role['role_name']])) {
                    $role_tree[$role['role_name']] = [];
                }*/
                if(!isset($role_tree[$role['role_name']][$role['legend']])) {
                    $role_tree[$role['role_name']][$role['legend']] = [];
                }
                $role_tree[$role['role_name']][$role['legend']][] = [
                    'role_id'=>$role['role_id'],
                    'permission_id'=>$role['permission_id'],
                    'permission_name'=>$role['permission_name']
                ];
            }
            self::$data['roles'] = $role_tree;
            self::$data['permissions'] = $permissions;
            return $this->display('roles','Roles and permission');
        }
        elseif(strtolower($addr[2]) == 'plugins'){
            $plugins_dir = realpath(__DIR__.'/../plugins');
            $plugins = scandir($plugins_dir);
            array_shift($plugins);
            array_shift($plugins);
            $active = storage::init()->system_config->plugins;
            if(isset($_POST['update_plugin'])){
                $v = $_POST['update_plugin'];
                if(in_array($v, $active)){
                    $key = array_search($_POST['update_plugin'], $active);
                    unset(storage::init()->system_config->plugins[$key]);
                }
                else{
                    // update permissions
                    $perms_file = realpath(__DIR__."/../plugins/{$v}").'/permissions.json';
                    if(is_readable($perms_file)){
                        $perms = json_decode(file_get_contents($perms_file));
                        // To do: Revise using multi insert method
                        $qry = [];
                        $whr = implode("','", $perms);
                        $existing = $db->select('permissions', 'permission_name')->where("permission_name IN ('{$whr}')")->fetchAll();
                        foreach($perms as $c) {
                            if(array_search($c, array_column($existing, 'permission_name')) !== false) continue;
                            $vx = str_replace('_', ' ', $v);
                            $qry[] = "('{$c}','{$vx}')";
                        }
                        if(count($qry) > 0){
                            // update the missisions only
                            $qry = implode(',', $qry);
                            $qry = "INSERT INTO permissions (permission_name, legend) VALUES {$qry}";
                            $db->query($qry);
                        }
                    }
                    array_unshift(storage::init()->system_config->plugins, $v);
                }
                system::config_write((array)storage::init()->system_config);
                die;
            }
            $total = count($active).'/'.count($plugins);

            $plugin_cards = ['active'=>'', 'inactive'=>''];
            foreach($plugins as $k=>$v){
                if(!is_readable("{$plugins_dir}/{$v}/config.json")) continue;
                $icon = in_array($v, $active) ? '&#xf205;' : '&#xf204;';
                $info = json_decode(file_get_contents("{$plugins_dir}/{$v}/config.json"));
                ob_start();
                include  realpath(__DIR__.'/../../').'/storage/assets/html/admin/plugin_card.html';
                if(in_array($v, $active)) $plugin_cards['active'] .= ob_get_clean();
                else $plugin_cards['inactive'] .= ob_get_clean();
            }
            self::$data['plugins'] = implode('', $plugin_cards);
            return $this->display('Plugins', "Plugins ({$total})");
        }
        elseif(strtolower($addr[2]) == 'system'){

            if(isset($_POST['site_name'])){
                $conf = storage::init()->system_config;
                foreach($_POST as $key=>$value){
                    if(isset($conf->$key)){
                        if(is_object($value)){
                            foreach($value as $k=>$v) $conf->$key->$k = $v;
                        }
                        else $conf->$key = $value;
                    }
                }
                $json = json_encode($conf, JSON_PRETTY_PRINT);
                $fn = realpath(__DIR__.'/../secure/').'/config.json';
                //die($fn);
                file_put_contents($fn, $json);
                header("Location: {$_SERVER['REQUEST_URI']}");
            }

            $conf = storage::init()->system_config;
            self::$data['configs'] = $conf;
            return $this->display('configs','System configuration');
        }
        elseif(strtolower($addr[2]) == 'widgets'){
            $configs = storage::init()->system_config;
            if(isset($_POST['ajax_delete_widget_content'])){
                $widget = $db->select('system_widgets')->where(['widget_id'=>$_POST['wid']])->fetch();
                $json = json_decode($widget['widget_content'], true);
                unset($json[$_POST['index']]);
                $db->update('system_widgets', ['widget_content'=>json_encode(array_values($json))])
                   ->where(['widget_id'=>$_POST['wid']])->commit();

                if(!$db->error()) $msg = 'ok';
                else $msg = 'Content deletion failed';
                die($msg);
            }
            if(isset($_POST['add_widget_snippet'])){
                $widget = $db->select('system_widgets')->where(['widget_id'=>$_POST['add_widget_snippet']])->fetch();
                $json = json_decode($widget['widget_content'], true);
                $json[] = [
                    'name'=>$_POST['name'],
                    'content'=>$_POST['content'],
                    'status'=>'active',
                    'visibility'=>'',
                ];
                $db->update('system_widgets', ['widget_content'=>json_encode(array_values($json))])
                   ->where(['widget_id'=>$_POST['add_widget_snippet']])->commit();

                if(!$db->error()) $msg = 'ok';
                else $msg = 'Content snippet adding failed';
                die($msg);
            }
            if(isset($_POST['ajax_update_visibility'])){
                $widget = $db->select('system_widgets')->where(['widget_id'=>$_POST['wid']])->fetch();
                $json = json_decode($widget['widget_content'], true);
                $json[$_POST['content_index']]['visibility'] = $_POST['ajax_update_visibility'];

                $db->update('system_widgets', ['widget_content'=>json_encode(array_values($json))])
                   ->where(['widget_id'=>$_POST['wid']])->commit();

                if(!$db->error()) $msg = 'ok';
                else $msg = 'Visibility pages list update failed';
                die($msg);
            }
            if(isset($_POST['ajax_update_content'])){
                $widget = $db->select('system_widgets')->where(['widget_id'=>$_POST['wid']])->fetch();
                $json = json_decode($widget['widget_content'], true);
                //var_dump($json);
                $json[$_POST['content_index']]['content'] = $_POST['ajax_update_content'];
                $json[$_POST['content_index']]['name'] = $_POST['widname'];

                $db->update('system_widgets', ['widget_content'=>json_encode(array_values($json))])
                   ->where(['widget_id'=>$_POST['wid']])->commit();

                if(!$db->error()) $msg = 'ok';
                else $msg = 'Widget content update failed';
                if(isset($_POST['ajax_request']))die($msg);
            }
            if(isset($_POST['ajax_update_status'])){
                $widget = $db->select('system_widgets')->where(['widget_id'=>$_POST['wid']])->fetch();
                $json = json_decode($widget['widget_content'], true);
                $json[$_POST['content_index']]['status'] = $_POST['ajax_update_status'];

                $db->update('system_widgets', ['widget_content'=>json_encode(array_values($json))])
                   ->where(['widget_id'=>$_POST['wid']])->commit();

                if(!$db->error()) $msg = 'ok';
                else $msg = 'Widget status update failed';
                die($msg);
            }
            $home_path = realpath(__DIR__.'/../../');
            $theme_dir = "{$home_path}/system/themes/{$configs->theme}";
            $templates = glob("{$theme_dir}/*.html");
            self::$data['active_widgets'] = template::find_funcs('get_widget', $templates);
            $widgets = $db->select('system_widgets')->where(1)->fetchAll();
            /*
                SELECT system_widgets.*, GROUP_CONCAT(system_widgets_contents.content_name) as body, 
                GROUP_CONCAT(system_widgets_contents.content_id) as wcid FROM system_widgets 
                LEFT JOIN system_widgets_contents on system_widgets.widget_id = system_widgets_contents.widget_reference
                 GROUP BY system_widgets.widget_id;
            */
            self::$data['widgets'] = '';
            foreach($widgets as $widget){
                ob_start();
                $wid = json_decode($widget['widget_content']);
                include  "{$home_path}/storage/assets/html/admin/widget_card.html";
                self::$data['widgets'] .= ob_get_clean();
            }
            self::$data['pages'] = $db->select('pages', 'page_id, page_name')->fetchAll();
            return $this->display('widgets','System widgets');
        }
        else{
            return $this->display('404', ' ');
        }
    }
    public function load_profile($addr){        

        $user_id = isset($addr[2]) && intval($addr[2]) ? intval($addr[2]) : user::init()->get_session_user('user_id');

        $widgets = system::dispatch_event('admin_profile_load', $addr);
        if($widgets && $widgets[0]){
            self::$data['widgets'] = '';
            foreach($widgets as $widget) self::$data['widgets'] .= $widget;
        }

        $db = self::get_db();
        self::$data['me'] = $db->select('user_accounts')
                               ->join('roles','role_id=system_role')
                               ->where(['user_id'=>$user_id])
                               ->fetch();
                               
        if(self::$data['me'] ){
            self::$data['me']['perms'] = user::init()->get_user_permissions(self::$data['me']['system_role']);
            unset(self::$data['me']['passcode']);
            return $this->display('profile',' ');
        }
        return $this->display('404',' ');
    }
    public function load_logout(){
        $data = ['address'=>['Logout']];
        return $this->display('login');
    }
    public function load_not_found($addr){
        
        include realpath(__DIR__.'/../../').'/storage/assets/html/admin/404.html';

        exit;
    }
    public static function array_to_list($arr){
        $storage = storage::init();
        $dl = trim($storage->install_dir."/{$storage->system_config->dashboard_url}/pages/edit",'/');
        $ul = '<ul>';
        foreach($arr as $li){
            $ul .= "<li><a href=\"/{$dl}/{$li['page_id']}\" data-icon=\"{$li['page_icon']}\">{$li['page_name']}</a>";
			if(isset($li['children'])) $ul .= self::array_to_list($li['children']);
			$ul .= '</li>';
        }
        $ul .= '</ul>';
        return $ul;
    }
    public function handle_post_data(){
        $helper = user::init();
        $registry = storage::init();
        $msg = '';
        if(isset($_POST['login']) && isset($_POST['passcode'])){
            $helper->login_user($_POST);
            if(!$helper->check_user_session()) $msg = 'Login creditial mismatch';
        }
        if(isset($_GET['logout'])){
            // Unset session
            $helper->end_user_session();
            // Remove query string
            $url = strtok($_SERVER["REQUEST_URI"], '?');
            header("Location: {$url}");
        }
        $db = db::get_connection($registry->system_config->db_configs);
        if(isset($registry->request[1]) && $registry->request[1] == 'forgot_password'){
            if(isset($_GET['recover'])){
                // verify token and let go
                $token = addslashes($_GET['recover']);
                if(isset($_POST['passcode1'])){
                    if($_POST['passcode1'] == $_POST['passcode2']){
                        $user = $db->select('user_accounts','user_id')
                           ->where(['activation_token'=>$token])
                           ->limit(1)
                           ->fetch();
                        if($user){
                            $password = system::create_hash($_POST['password1']);
                            $k = $db->update('user_accounts', ['activation_token'=>null,'password'=>$password])
                                    ->where(['user_id'=>$user['user_id']])
                                    ->commit();
                            if($k->rowCount()){
                                header("Location: {$home}?msg=password_changed");
                            }
                            else {
                                die($helper::get_sub_template('login', ['error'=>'Password change failed']));
                            }
                        }
                        else{
                            $msg = 'Token invalid or expired';
                            die($helper::get_sub_template('change-password', ['error'=>$msg]));
                        }
                    }
                    else{
                        $msg = 'Passwords do not match';
                        die($helper::get_sub_template('change-password', ['error'=>$msg]));
                    } 
                }
                $user = $db->select('user')
                           ->where(['activation_token'=>$token])
                           ->limit(1)
                           ->fetch();
                if($user){
                    die($helper::get_sub_template('change-password',['error'=>'']));
                }
                else{
                    die($helper::get_sub_template('forgot-password',['error'=>'Token verification failed!']));
                }
            }
            $msg = '';
            if(isset($_POST['login'])){
                $user = $db->select('user')
                            ->where(['email'=>helper::format_email($_POST['login'])])
                            ->or(['phone_number'=>helper::format_phone_number($_POST['login'])])
                            ->limit(1)
                            ->fetch();
                if($user){//var_dump(storage::init()->system_config);
                    $token = helper::create_hash(microtime(true));
        
                    $db->update('user', ['activation_token'=>$token])->where(['user_id'=>$user['user_id']])->commit();
        
                    $first_name = $user['first_name'];
                    $site_name = storage::init()->system_config->system_name;
                    $link = "https://{$_SERVER['HTTP_HOST']}/{$home}/forgot_password?recover={$token}";
                    $user['phone_number'] = "{$user['phone_number']}"; // stringify
                    $phone = "{$user['phone_number'][0]}{$user['phone_number'][1]}{$user['phone_number'][2]}{$user['phone_number'][3]}********";
                    
                    $template = file_get_contents(realpath(__DIR__.'/../').'/config/mails/emails/password_recovery.html');
                    $body = str_replace(['{$first_name}','{$site_name}','{$link}'],[$first_name, $site_name,$link], $template);
                    $sms_opts = [
                        'recipients'=>$user['phone_number'],
                        'body'=>strip_tags($body)
                    ];
                    if($user['phone_number']) $result = helper::send_sms($sms_opts);
        
                    $email_opts = [
                        'body'=>$body, 
                        'subject'=>'Password recovery instruction',
                        'recipient'=>$user['email']
                    ];
                    if($user['email']) $result = helper::send_email($email_opts);
                    if($result == 'mail_send_ok'){
                        die($helper::get_sub_template('token-sent',['phone'=>$phone,'email'=>'*****']));
                    }
                    else $msg = $result;
                }
                else $msg = 'Account not found. Please retry!';
            }
            die($helper::get_sub_template('forgot-password',['msg'=>$msg]));
        }
    }
    public function display($dash_name = 'dashboard', $title = ''){
        if(is_readable($dash_name)) $template = $dash_name;
        else $template = realpath(__DIR__.'/../../')."/storage/assets/html/admin/{$dash_name}.html";
        ob_start();
        extract(self::$data);
        if(is_readable($template)) include $template;
        else include realpath(__DIR__.'/../../').'/storage/assets/html/admin/404.html';
        $page_content = ob_get_clean();
        $title = $title ? $title : (isset($address[1]) ? $address[1] : $dash_name);
        include realpath(__DIR__.'/../../').'/storage/assets/html/admin/index.html';
    }
}