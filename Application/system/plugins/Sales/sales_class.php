<?php
class Sales{

    private static $instance = null;
    
    public function __construct(){
        // Not applicable, may throw exception in future releases
    }

    public static function init(){
        // Hook to the system
        system::add_event_listener('exec_end', 'sales::load_page', $_SERVER['REQUEST_URI']);
        // Hook to admin dashboard
        system::add_event_listener('admin_plugin_load', 'sales::load_admin_dashboard');
        // Hook to admin card
        system::add_event_listener('admin_widgets_load', 'sales::load_admin_dashboard_cards');
        // Add product service
        system::add_event_listener('add_product', 'sales::add_product_service');
        // Add product category
        system::add_event_listener('add_product_category', 'sales::add_product_category');
        // Add supplier
        system::add_event_listener('add_supplier', 'sales::add_supplier');
    }
    public static function load_admin_dashboard_cards($args){
        // fetch info
        ob_start();
        include __DIR__.'/modules/html/card.html';
        return ob_get_clean();
    }
    public static function load_admin_dashboard($args){
        if(str_replace('-', '_', strtolower($args[0])) == strtolower(__CLASS__)) {
            $me = human_resources::get_staff();
            if($me){
                $headquarters = human_resources::get_headquarters_branch();
                $is_headquarters = ($headquarters == $me['work_location']);
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
            else{
                return ['title'=>'Access denied', 'body'=>'You need to register as staff to continue'];
            }
        }
    }
    public static function load_page($request){
        $registry = storage::init();
        return;
    }
    public static function add_product_service($args){
        $me = human_resources::get_staff();
        $hq = human_resources::get_headquarters_branch();
        $config = storage::get_data('system_config')->db_configs;
        $db = db::get_connection($config);
        $status = 'error';
        if(isset($_POST['product_name'])){
            $data = [
                'product_name'=>addslashes($_POST['product_name']),
                'product_price'=>intval($_POST['product_price']),
                'product_model'=>addslashes($_POST['product_model']),
                'product_category'=>intval($_POST['product_category']),
                'product_description'=>addslashes($_POST['product_description']),
                'product_unit_singular'=>addslashes($_POST['product_unit_singular']),
                'product_unit_plural'=>addslashes($_POST['product_unit_plural']),
                'owner_branch'=>intval($me['work_location'])
            ];
            if(isset($_POST['product_id'])){
                $k = intval($_POST['product_id']);
                $db->update('product', $data)->where(['product_id'=>$k])->commit();
            }
            else{
                $chk = $db->select('product', 'product_name')
                            ->where(['product_name'=>$data['product_name']])
                            ->and(['owner_branch'=>$data['owner_branch']])
                            ->limit(1)
                            ->fetch();
        
                if(!$chk) $k = $db->insert('product', $data);
                else $k = 0;
            }
        
            if(isset($_FILES['product_image']) && is_readable($_FILES['product_image']['tmp_name']) && $k){
                $dir = realpath(__DIR__.'/../../../storage/uploads/products/');
                $source = $_FILES['product_image']['tmp_name'];//product_thumb_0.jpg
                $bp = system::upload_image($source, "{$dir}/product_{$k}.jpg", ['width'=>600, 'height'=>400]);
        
                file_put_contents("{$dir}/tmp.jpg", file_get_contents($bp));
                $thumb = system::upload_image("{$dir}/tmp.jpg", "{$dir}/product_thumb_{$k}.jpg", ['width'=>200, 'height'=>170]);
            }
            if($k) {
                $msg = 'Saved successful';
                $status = 'success';
            }
            else{
                $err = $db->error();
                $msg = $k == 0 && $err ? 'Error saving product' : $err['message'];
            }
            //var_dump($db->error());
            if(isset($_POST['ajax_request'])) die(json_encode(['status'=>$status, 'message'=>$msg]));
        }

        $productCategory = $db->select('product_category', 'category_id, category_name')
                            ->where(1)
                            ->fetchAll();
        ob_start();
        include __DIR__.'/modules/html/add_product.html';
        return ob_get_clean();
    }
    public static function add_product_category($args){
        $me = human_resources::get_staff();
        $hq = human_resources::get_headquarters_branch();
        $config = storage::get_data('system_config')->db_configs;
        $db = db::get_connection($config);
        $status = 'error';

        if(isset($_POST['category_name'])){
            $data = [
                'category_name'=>addslashes($_POST['category_name']),
                'category_description'=>addslashes($_POST['category_description'])
            ];
            if(isset($_POST['category_id'])){
                $k = intval($_POST['category_id']);
                $db->update('product_category', $data)->where(['category_id'=>$k])->commit();
            }
            else{
                $k = $db->insert('product_category', $data);
            }
            if($db->error()) $msg = $db->error()['message'];
            else $msg = 'Category saved successful';
            if(isset($_POST['ajax_request'])) die(json_encode(['status'=>'info', 'message'=>$msg]));
        }
        ob_start();
        include __DIR__.'/modules/html/add_product_category.html';
        return ob_get_clean();
    }
    public static function add_supplier($args){
        $me = human_resources::get_staff();
        $hq = human_resources::get_headquarters_branch();
        $config = storage::get_data('system_config')->db_configs;
        $db = db::get_connection($config);
        $status = 'error';
        if(isset($_POST['supplier_name'])){
            //var_dump($_POST);
            $data = [
                'owner_branch'=>$me['work_location'],
                'supplier_name'=>$_POST['supplier_name'], 
                'supplier_phone_number'=>$_POST['supplier_phone_number'], 
                'supplier_email'=>$_POST['supplier_email'], 
                'supplier_physical_address'=>$_POST['supplier_physical_adress'], 
                'supplier_details'=>$_POST['supplier_details']
            ];
            if(isset($_POST['supplier_id']) && intval($_POST['supplier_id']) > 0){
                $k = intval($_POST['supplier_id']);
                $db->update('supplier', $data)->where(['supplier_id'=>$_POST['supplier_id']])->commit();
                //var_dump($db->error());
            }
            else $k = $db->insert('supplier', $data);
            //var_dump($k);
            if(!$db->error() && $k) {
                $msg = 'Supplier saved successful';
                $ok =true;
            }
            else $msg = $db->error()['message'];
            die(json_encode(['status'=>'info', 'message'=>$msg]));
            //var_dump($db->error()); 
        }

        ob_start();
        include __DIR__.'/modules/html/add_supplier.html';
        return ob_get_clean();
    }
}