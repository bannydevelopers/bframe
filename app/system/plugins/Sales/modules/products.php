<?php 
$me = human_resources::get_staff();
if($me){
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
                      ->where(['product_name'=>$data['product_name'], 'owner_branch'=>$data['owner_branch']])
                      ->limit(1)
                      ->fetchAll();

            if(!$chk) $k = $db->insert('product', $data);
            else $k = 0;
        }
        if(isset($_FILES['product_image']) && is_readable($_FILES['product_image']['tmp_name']) && $k){
            $dir = realpath(__DIR__.'/../../../../storage/uploads/products/');
            $source = $_FILES['product_image']['tmp_name'];//product_thumb_0.jpg
            $bp = system::upload_image($source, "{$dir}/product_{$k}.jpg", ['width'=>600, 'height'=>400]);

            file_put_contents("{$dir}/tmp.jpg", file_get_contents($bp));
            $thumb = system::upload_image("{$dir}/tmp.jpg", "{$dir}/product_thumb_{$k}.jpg", ['width'=>200, 'height'=>170]);
        }
        if(!$db->error() && $k) {
            $msg = 'Saved successful';
            $status = 'success';
        }
        else{
            $err = $db->error();
            $msg = $k == 0 ? 'Product exists' : $err['message'];
        }
        if(isset($_POST['ajax_request'])) die(json_encode(['status'=>$status, 'message'=>$msg]));
    }
    if($me['work_location'] == human_resources::get_headquarters_branch()) {
        $whr = 1;
    }
    else{
        $whr = ['owner_branch'=>$me['work_location']];
    }
    $productCategory = $db->select('product_category', 'category_id, category_name')
                        ->where($whr)
                        ->fetchAll();

    if($me['work_location'] == $hq) $whr = 1;
    else $whr = ['owner_branch'=>$me['work_location']];

    $columns = "product.*, IFNULL(category_name, 'General') as category_name, IFNULL(branch_name, 'Headquarters') as branch_name";
    $Products = $db->select('product', $columns)
                        ->join('product_category', 'category_id=product_category', 'LEFT')
                        ->join('branches', 'branch_id=owner_branch', 'LEFT')
                        ->where($whr)
                        ->fetchAll();

    $pic_root = 'Application/storage/uploads/products/product_thumb_';
    $sortedProducts = [];
    foreach($Products as $prod){
        if(!isset($sortedProducts[$prod['branch_name']])) $sortedProducts[$prod['branch_name']] = [];
        if(!isset($sortedProducts[$prod['branch_name']][$prod['category_name']])){
            $sortedProducts[$prod['branch_name']][$prod['category_name']] = [];
        }
        $root = '/storage/uploads/products/product_thumb_';
        if(is_readable(realpath(__DIR__.'/../../../../')."{$root}{$prod['product_id']}.jpg")) 
            $prod['product_image'] = "Application/storage/uploads/products/product_thumb_{$prod['product_id']}.jpg";
        else
            $prod['product_image'] = 'Application/storage/uploads/products/product_thumb_0.jpg';

        $sortedProducts[$prod['branch_name']][$prod['category_name']][] = $prod;
    }
    //var_dump('<pre>',$sortedProducts);die;
    ob_start();
    include __DIR__.'/html/product.html';
    $body = ob_get_clean();
    $return = ['title'=>' ','body'=>$body];
}