<?php 
$me = human_resources::get_staff();
if($me){
    $config = storage::get_data('system_config')->db_configs;
    $db = db::get_connection($config);

    $columns = "product.*, IFNULL(category_name, 'General') as category_name, IFNULL(branch_name, 'Headquarters') as branch_name";
    $Products = $db->select('product', $columns)
                        ->join('product_category', 'category_id=product_category', 'LEFT')
                        ->join('branches', 'branch_id=owner_branch', 'LEFT')
                        ->where(1)
                        ->fetchAll();
    $sortedProduct = [];
    foreach($Products as $prod){
        if(!isset($sortedProduct[$prod['branch_name']])) $sortedProduct[$prod['branch_name']] = [];
        if(!isset($sortedProduct[$prod['branch_name']][$prod['category_name']])){
            $sortedProduct[$prod['branch_name']][$prod['category_name']] = [];
        }
        $sortedProduct[$prod['branch_name']][$prod['category_name']] = $prod;
    }
    //var_dump('<pre>',$sortedProduct);die;
    ob_start();
    include __DIR__.'/html/product.html';
    $body = ob_get_clean();
    $return = ['title'=>' ','body'=>$body];
}