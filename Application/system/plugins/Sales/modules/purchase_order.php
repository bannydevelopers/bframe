<?php 

$hq = human_resources::get_headquarters_branch();
$config = storage::get_data('system_config')->db_configs;
$db = db::get_connection($config);

if($me['work_location'] == human_resources::get_headquarters_branch()) {
    $whr = 1;
}
else{
    $whr = "purchase.owner_branch={$me['work_location']}";
}
if(isset($_POST['purchase_date'])){
    $inv_data = [
        'purchase_date'=>addslashes($_POST['purchase_date']),
        'supplier'=>addslashes($_POST['supplier']),
        'owner_branch'=>intval($me['work_location']),
        'item_name'=>intval($me['item_name']),
        'created_time'=>addslashes($_POST['created_time'])
    ];
    $purch_items_qry = [];
    $purch_id = $db->insert('purchase', $purch_data);
    if($purch_id){
        foreach($_POST['product'] as $k=>$p){
            $purch_items_qry[] = "({$p}, {$purch_id}, {$_POST['price'][$k]}, {$_POST['quantity'][$k]})";
        }
        $purch_items_qry = implode(',', $purch_items_qry);
        $db->query("INSERT INTO purchase_items (product, purchase, price, quantity) VALUES {$purch_items_qry}");
    }
    if($db->error()) $msg = $db->error()['message'];
    else $msg = 'Saved successful';
    if(isset($_POST['ajax_request'])) die($msg);
}
if(isset($_POST['local_purchase_order'])){
    $data = [
        'reference_purchase'=>intval($_POST['reference_purchase']),
        'local_purchase_order'=>addslashes($_POST['local_purchase_order']),
        'tax_purchase_remarks'=>addslashes($_POST['tax_purchase_remarks']),
        'recorded_by'=>user::init()->get_session_user('user_id')
    ];

    $k = $db->insert('tax_purchase', $data);
    if(!$db->error()){
        $msg = 'Saved successful';
    }
    else{
        $msg = $db->error()['message'];
    }
    if(isset($_POST['ajax_request'])) die($msg);
}
if(isset($_POST['qty'])){
    /*
        //INSERT INTO table_users (cod_user, date, user_rol, cod_office) VALUES
        //('622057', '12082014', 'student', '17389551'),
        //('2913659', '12082014', 'assistant','17389551'),
        //('6160230', '12082014', 'admin', '17389551')
        //ON DUPLICATE KEY UPDATE
        //cod_user=VALUES(cod_user), date=VALUES(date)
    */
    $tmp = [];
    foreach($_POST['qty'] as $id=>$qty){
        $tmp[] = "({$id}, {$_POST['price'][$id]}, 8, 8, $qty)";
    }
    $tmp = implode(',', $tmp);
    $qry = "INSERT INTO purchase_items (item_id, price, product, purchase, quantity) VALUES "
            . " {$tmp} ON DUPLICATE KEY UPDATE item_id = VALUES(item_id), price=VALUES(price), quantity=VALUES(quantity)";
    $db->query($qry);
    if($db->error()) die('Saving failed');
    else die('Saved successful');
}
if(isset($_POST['delete_purchase'])){
    $db->delete('purchase_items')->where(['purchase'=>intval($_POST['delete_purchase'])])->commit();
    if(!$db->error()){
        $db->delete('purchase')->where(['purchase_id'=>intval($_POST['delete_purchase'])])->commit();
        if($db->error()) $msg = $db->error()['message'];
        else $msg = 'purchase deleted successful!';
    }
    else{
        $msg = $db->error()['message'];
    }
    die($msg);
}

$Purchase = $db->select('product')
                    ->join('product_category', 'category_id=product_category', 'LEFT')
                    ->join('branches', 'branch_id=owner_branch', 'LEFT')
                    ->where($whr)
                    ->fetchAll();


$sortedPurchase = [];
foreach($Purchase as $prod){
    if(!isset($sortedPurchase[$prod['branch_name']])) $sortedPurchase[$prod['branch_name']] = [];
    if(!isset($sortedPurchase[$prod['branch_name']][$prod['category_name']])){
        $sortedPurchase[$prod['branch_name']][$prod['category_name']] = [];
    }
}

//var_dump($db->error(),$purchoice);

//var_dump('<pre>',$sortedpurchase);die;
ob_start();
include __DIR__.'/html/purchase_order.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];