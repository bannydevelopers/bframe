<?php 
$me = human_resources::get_staff();
if($me){
    $config = storage::get_data('system_config')->db_configs;
    $db = db::get_connection($config);
    $productCategory = $db->select('product_category', 'category_id, category_name')
                        ->where(1)
                        ->fetchAll();

    $columns = "product.*, IFNULL(category_name, 'General') as category_name, IFNULL(branch_name, 'Headquarters') as branch_name";
    $Products = $db->select('product', $columns)
                        ->join('product_category', 'category_id=product_category', 'LEFT')
                        ->join('branches', 'branch_id=owner_branch', 'LEFT')
                        ->where(1)
                        ->fetchAll();
    $pic_root = 'Application/storage/uploads/products/product_thumb_';
    $sortedProduct = [];
    foreach($Products as $prod){
        if(!isset($sortedProduct[$prod['branch_name']])) $sortedProduct[$prod['branch_name']] = [];
        if(!isset($sortedProduct[$prod['branch_name']][$prod['category_name']])){
            $sortedProduct[$prod['branch_name']][$prod['category_name']] = [];
        }
        $root = '/storage/uploads/products/product_thumb_';
        if(is_readable(realpath(__DIR__.'/../../../../')."{$root}{$prod['product_id']}.jpg")) 
            $prod['product_image'] = "Application/storage/uploads/products/product_thumb_{$prod['product_id']}.jpg";
        else
            $prod['product_image'] = 'Application/storage/uploads/products/product_thumb_0.jpg';

        $sortedProducts[$prod['branch_name']][$prod['category_name']][] = $prod;
    }
    //var_dump('<pre>',$sortedProduct);die;
    ob_start();
    include __DIR__.'/html/product.html';
    $body = ob_get_clean();
    $return = ['title'=>' ','body'=>$body];
}