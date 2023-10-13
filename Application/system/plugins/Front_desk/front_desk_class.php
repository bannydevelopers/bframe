<?php
class Front_desk{
    public $name = 'eTours';
    public $version = 1.0;
    public $desc = 'Plugin dedicated for complete tours management as a drop in';

    private static $instance = null;
    
    public function __construct(){
        // Not applicable, may throw exception in future releases
    }

    public static function init(){
        // Hook to the system
        system::add_event_listener('exec_end', 'etours::load_page', $_SERVER['REQUEST_URI']);
        // Hook to admin dashboard
        system::add_event_listener('admin_plugin_load', 'etours::load_admin_dashboard');
    }
    public static function load_admin_dashboard($args){
        $registry = storage::init();
        $myURL = "{$registry->request[0]}/{$registry->request[1]}/{$registry->request[2]}";
        $_this = new static();
        $user = user::init()->get_session_user();

        $titles = [
            'packages'=>'eTours packages',
            'orders'=>'eTours orders',
            'Configuration'=>'eTours configuration'
        ];
        
        $conf = $registry->system_config;
        $db = db::get_connection($conf->db_configs);
        $pages = $db->select('pages', 'page_id, page_name')
                    ->where(['page_type'=>0, 'page_special'=>0])
                    ->fetchAll();
        
        if(!isset($registry->request[3])){
            $body = "How did you end up here?";
            $title = 'Isn\'t it weird?';
        }
        else{
            if($registry->request[3] == 'packages'){
                $photo_dir = "{$registry->install_dir}app/system/plugins/Etours/uploads/packages";

                if(!isset($registry->request[4])){
                    $packages = $db->select('pages','pages.*,user_accounts.full_name')
                                ->join('user_accounts', 'user_id=page_author','LEFT')
                                ->where(['page_type'=>'etours::etours_package'])
                                ->fetchAll();

                    ob_start();
                    $dir = __DIR__.'/uploads/packages';
                    foreach($packages as $pack) {
                        //mkdir("{$dir}/preview_{$pack['page_id']}");
                        $photos = is_readable("{$dir}/preview_{$pack['page_id']}") ? scandir("{$dir}/preview_{$pack['page_id']}") : [];
                        array_shift($photos);
                        array_shift($photos);
                        $src = empty($photos) ? "{$photo_dir}" : "{$photo_dir}/preview_{$pack['page_id']}";
                        if(empty($photos)) $photos[] = '/../default.jpg';
                        shuffle($photos);
                        $pack['photos'] = $photos;
                        if($pack['page_extras']) $pack['page_extras'] = json_decode($pack['page_extras'], true);
                        else $pack['page_extras'] = [];
                        include realpath(__DIR__)."/assets/package_card.html";
                    }
                    $body = ob_get_clean();
                    $template = __DIR__.'/assets/packages.html';
                    ob_start();
                    include $template;
                    $body = ob_get_clean();
                }
                else{
                    if(isset($_POST['page_title'])) {
                        $data = $_POST;
                        unset($data['tabset']);
                        unset($data['page_id']);
                        $data['page_special'] = array_sum($_POST['page_special']);
                        $data['page_extras'] = json_encode($data['page_extras']);
                        $data['page_author'] = $user['user_id'];
                        if(intval($_POST['page_id'])) {
                            $db->update('pages', $data)->where(['page_id'=>$_POST['page_id']])->commit();
                            if(!$db->error()) $pid = $_POST['page_id'];
                            else $pid = $db->error()['message'];
                        }
                        else{
                            $data['create_date'] = date('Y-m-d H:i:s');
                            $pid = $db->insert('pages', $data);
                        }
                        //var_dump($db->error(), $pid);die;
                        if(intval($pid)){
                            $dir = __DIR__.'/uploads/packages';
                            
                            foreach($_FILES['preview_photos']['name'] as $k=>$v){
                                if(!is_readable($_FILES['preview_photos']['tmp_name'][$k])) continue;
                                if(!is_readable("{$dir}/preview_{$pid}")) mkdir("{$dir}/preview_{$pid}");
                                $dst = "{$dir}/preview_{$pid}/{$_FILES['preview_photos']['name'][$k]}";
                                system::upload_image( $_FILES['preview_photos']['tmp_name'][$k], $dst, ['width'=>600, 'height'=>400]);
                            }
                            // Send to editing page
                            header("Location: /{$myURL}/packages/edit/{$pid}");
                        }
                        else print_r($db->error());
                    }
                    $cats = $db->select('etours_package_categories')->fetchAll();
                    if($registry->request[4] == 'edit'){
                        if(!isset($registry->request[4]) or !intval($registry->request[5])){
                            $body = '<p>Messing with URL never had <b>happily ever after</b>, believe me!</p>';
                        }
                        else{
                            $page =  $db->select('pages','pages.*,user_accounts.full_name')
                                        ->join('user_accounts', 'page_author=user_id', 'LEFT')
                                        ->where(['page_type'=>'etours::etours_package', 'page_id'=>$registry->request[5]])
                                        ->fetch();
                                        
                            if($page['page_extras']) $page['page_extras'] = json_decode($page['page_extras'], true);
                            else $page['page_extras'] = [];

                            $dir = __DIR__.'/uploads/packages';
                            $photos = is_readable("{$dir}/preview_{$page['page_id']}") ? scandir("{$dir}/preview_{$page['page_id']}") : [];
                            array_shift($photos);
                            array_shift($photos);
                            $src = "{$photo_dir}/preview_{$page['page_id']}";
                            $page['photos'] = $photos;

                            ob_start();
                            include 'assets/page_create_edit.html';
                            $body = ob_get_clean();
                            $title = "Tweaking '{$page['page_title']}'";
                        }
                    }
                    elseif($registry->request[4] == 'add'){
                        $page = [
                            'page_id'=>'',
                            'page_extras'=>[],
                            'page_title'=>'', 
                            'page_name'=>'', 
                            'page_type'=>'etours::etours_package', 
                            'page_parent'=>'0', 
                            'page_content'=>'', 
                            'page_icon'=>'&#xf24d;', 
                            'page_order'=>'0', 
                            'page_description'=>'', 
                            'page_keywords'=>'', 
                            'full_name'=>$user['full_name'], 
                            'create_date'=>date('Y-m-d H:i:s'), 
                            'page_special'=>'2',
                            'photos'=>[]
                        ];
                        ob_start();
                        include 'assets/page_create_edit.html';
                        $body = ob_get_clean();
                        $title = "Add a package";
                    }
                    else $body = "Looks like you got lost into wild! Such address got you nowhere, sorry!{$registry->request[3]}";
                }
            }
            elseif($registry->request[3] == 'orders'){
                $body = 'Orders coming soon...';
                $title = 'eTours orders';
            }
            elseif($registry->request[3] == 'Configuration'){
                $body = 'Configuration on their way here...';
                $title = 'eTours configuration';
            }
            else{
                $body = "How did you end up here?";
                $title = 'Isn\'t it weird?';
            }
        }
        if(!isset($title)) $title = @$titles[$args[1]];
        return ['title'=>$title,'body'=>$body];
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
            $supported = ['etours_packages'];
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
        
        if($page['page_type'] == 'etours::etours_package'){
            if(isset($_POST['fullname']) && isset($_POST['requests'])){
                // Gabbage collection
                $interval = time() - (1*60*60*3); // 3 hours
                $now = date('Y-m-d H:i:s', $interval);
                $db->delete('etours_orders')->where(['order_status'=>'pending'])->and("order_date < '{$now}'")->commit();
                // Order details
                $price = intval($page['page_extras']['price']);
                $adults_price = intval($_POST['adults']) * $price;
                $kidos_price = intval($_POST['children']) * ($price - ($price * intval($page['page_extras']['kido_discount'])));
                $amount = $adults_price + $kidos_price;
                $order_data = [
                    'order_customer'=>$_POST['fullname'], 
                    'billing_country'=>$_POST['country'], 
                    'billing_phone'=>$_POST['phone'], 
                    'billing_email'=>$_POST['email'], 
                    'adults_count'=>intval($_POST['adults']), 
                    'kidos_count'=>intval($_POST['children']), 
                    'special_requests'=>$_POST['requests'], 
                    'order_amount'=>$amount, 
                    'order_status'=>'pending', 
                    'order_date'=>date('Y-m-d H:i:s'), 
                    'payment_gateway'=>$registry->system_config->gateways->payment,
                    'order_reference'=>$page['page_id']
                ];
                
                $k = $db->insert('etours_orders', $order_data);
                
                $name = explode(' ',$_POST['fullname']);
                $fn = $name[0];
                $ln = end($name);
                $post_data = [
                    'first_name'=>$fn,
                    'last_name'=>$ln,
                    'phone'=>system::format_phone($_POST['phone']),
                    'email'=>system::format_email($_POST['email']),
                    'order_amount'=>$amount,
                    'order_description'=>$page['page_desc'],
                    'order_reference'=>$k,
                    'country'=>$_POST['country']
                ];
                $gateway = system::init_gateway('payment');
                if($gateway) {
                    ob_start();
                    //save order
                    $response = $gateway->send_request($post_data);
                    if(is_string($response)) include 'assets/payment_iframe.html';
                    else {
                        //var_dump($response);
                        include 'assets/payment_complete.html';
                    }
                    $page['page_content'] = ob_get_clean();
                    storage::init()->page = $page;
                    template::display();
                    exit;
                }
                die;
            }
            //OrderTrackingId=9dcec6ce-6709-4720-9f07-df5bc644bd3e&OrderMerchantReference=38
            if(isset($_REQUEST['OrderTrackingId'])){
                $oid = $_REQUEST['OrderMerchantReference'];
                ob_start();
                include 'assets/order_complete.html';
                $page['page_content'] = ob_get_clean();
                storage::init()->page = $page;
                template::display();
                exit;
            }
            $photo_dir = "{$registry->install_dir}app/system/plugins/Etours/uploads/packages";
            $dir = __DIR__.'/uploads/packages';
                    
            $photos = is_readable("{$dir}/preview_{$page['page_id']}") ? scandir("{$dir}/preview_{$page['page_id']}") : [];
            array_shift($photos);
            array_shift($photos);
            $src = empty($photos) ? "{$photo_dir}" : "{$photo_dir}/preview_{$page['page_id']}";
            if(empty($photos)) $photos[] = 'default.jpg';
            $href = $registry->install_dir.str_replace(' ','_',$page['page_name']);
            ob_start();
            ob_clean();
            include __DIR__.'/assets/package_preview.html';
            $page['page_content'] = ob_get_clean();
            storage::init()->page = $page;
            template::display();
            exit;
        }
    }
    protected function etours_packages($args){
        $registry = storage::init();
        unset($args[0]);
        $supported = ['cards','list'];
        $count = 9;
        $type = 'cards';
        if(isset($args[0]) && in_array($args[0], $supported)) $type = $args[0];
        if(isset($args[1]) && intval($args[1])) $count = intval($args[1]);
        $conf = storage::get_instance()->system_config;
        $db = db::get_connection($conf->db_configs);
        $parent = storage::init()->page ? storage::init()->page['page_id'] : 0;
        $packages = $db->select('pages','*')
                       ->where(['page_type'=>'etours::etours_package','page_parent'=>$parent])
                       //->limit($count)
                       ->fetchAll();
        
        $dir = __DIR__.'/uploads/packages';
        $photo_dir = "{$registry->install_dir}app/system/plugins/Etours/uploads/packages";
        $par_sql = "SELECT T2.page_id, T2.page_name,T2.page_parent
                    FROM (
                        SELECT
                            @r AS parent_id,
                            (SELECT @r := page_parent FROM pages WHERE page_id = parent_id) AS page_parent,
                            @l := @l + 1 AS lvl
                        FROM
                            (SELECT @r := {$parent}, @l := 0) vars,
                            pages m
                        WHERE @r <> 0) T1
                    JOIN pages T2
                    ON T1.parent_id = T2.page_id
                    ORDER BY T1.lvl DESC";
        $parents = $db->query($par_sql)->fetchAll();
        $url_prefix = [];
        foreach($parents as $par) $url_prefix[] = str_replace(' ','-',$par['page_name']);
        $url_prefix = implode('/',$url_prefix);
        $url_prefix = $url_prefix ? "{$url_prefix}/" : '';
        $url_prefix = $registry->install_dir.$url_prefix;
        ob_start();
        foreach($packages as $pack) {
            if($pack['page_extras']) $pack['page_extras'] = json_decode($pack['page_extras'], true);
            else $pack['page_extras'] = [];
            $photo_list = is_readable("{$dir}/preview_{$pack['page_id']}") ? scandir("{$dir}/preview_{$pack['page_id']}") : [];
            array_shift($photo_list);
            array_shift($photo_list);
            shuffle($photo_list);

            if(empty($photo_list)){
                $src = "{$photo_dir}";
                $photo_list[] = 'default.jpg';
            }
            else  $src = "{$photo_dir}/preview_{$pack['page_id']}";
            $pack['photos'] = $photo_list;
            $href = $url_prefix.str_replace(' ','-',$pack['page_name']);
            include realpath(__DIR__)."/assets/package_{$type}.html";
        }
        $package = ob_get_clean();
        return $package;
    }
    public static function etours_package($args){
        $obj = new admin();
        
        extract($args);
        if($page['page_extras']) $extras = json_decode($page['page_extras'],true);
        else $extras = [];
        $page['page_extras'] = $extras;
        $obj::$data['page'] = $page;

        $db = db::get_connection(storage::get_instance()->system_config->db_configs);

        if(isset($_POST['page_name'])){
            unset($_POST['tabset']);
            $pdata = $_POST;
            unset($pdata['page_id']);

            if(!isset($pdata['page_special'])) $pdata['page_special'] = [];
            $pdata['page_special'] = array_sum($pdata['page_special']);

            $pdata['page_extras'] = json_encode($pdata['page_extras']);

            $k = $db->update('pages', $pdata)->where(['page_id'=>$_POST['page_id']])->commit();
            print_r($db->error());
        }

        if($addr[2] == 'delete'){
            return $obj->display('pages');
        }
        elseif($addr[2] == 'edit'){
            $template = __DIR__.'/assets/page_create_edit.html';
            return $obj->display($template);
        }
        return $obj->display('my form');
    }
    /*protected function find_bbcode($content){
        $html = htmlspecialchars_decode(stripslashes($content));
		preg_match_all('/(\{\$)(.*?)(\})/i', $html, $pg_matches);
		if(isset($pg_matches[2])){
			$searches = $replacement = [];
			foreach($pg_matches[2] as $k=>$v){
				$searches[] = $pg_matches[0][$k];
                $replacement[] = $v;
			}
		}
		$html = str_replace($searches, $replacement, $html);
        return $html;
    }*/
}