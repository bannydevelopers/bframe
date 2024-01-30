<?php 

$hq = human_resources::get_headquarters_branch();
$config = storage::get_data('system_config')->db_configs;
$db = db::get_connection($config);

if($me['work_location'] == human_resources::get_headquarters_branch()) {
    $whr = 1;
}
else{
    $whr = "delivery.owner_branch={$me['work_location']}";
}
if(isset($_POST['delete_item'])){
    $db->delete('delivery_items')->where(['item_id'=>intval($_POST['delete_item'])])->commit();
    if($db->error()) die('error');
    else die('ok');
}
if(isset($_POST['created_date'])){
    $delv_data = [
        'created_date'=>addslashes($_POST['created_date']),
        't_i_number'=>addslashes($_POST['t_i_number']),
        'prepaired_by'=>intval($me['user_reference']),
        'owner_branch'=>intval($me['work_location'])
    ];
  
    $delv_items_qry = [];
    $delv_id = $db->insert('delivery', $delv_data);
    if($delv_id){
        foreach($_POST['product'] as $k=>$p){
            if(!$p) continue;
            $delv_items_qry[] = "({$p}, {$delv_id}, {$_POST['quantity'][$k]})";
        }
        //var_dump('<pre>',$delv_items_qry);die;
        $delv_items_qry = implode(',', $delv_items_qry);
        $db->query("INSERT INTO delivery_items (product, delivery, quantity) VALUES {$delv_items_qry}");
    }
    if($db->error()) $msg = $db->error()['message'];
    else $msg = 'Saved successful';
    if(isset($_POST['ajax_request'])) die($msg);
}
if(isset($_POST['local_purchase_order'])){
    $data = [
        'reference_delivery'=>intval($_POST['reference_delivery']),
        'local_purchase_order'=>addslashes($_POST['local_purchase_order']),
        'recorded_by'=>user::init()->get_session_user('user_id')
    ];

    $k = $db->insert('tax_delivery', $data);
    if(!$db->error()){
        $msg = 'Saved successful';
    }
    else{
        $msg = $db->error()['message'];
    }
    if(isset($_POST['ajax_request'])) die($msg);
}
if(isset($_POST['qty']) or isset($_POST['qtyx'])){
    /*
        //INSERT INTO table_users (cod_user, date, user_rol, cod_office) VALUES
        //('622057', '12082014', 'student', '17389551'),
        //('2913659', '12082014', 'assistant','17389551'),
        //('6160230', '12082014', 'admin', '17389551')
        //ON DUPLICATE KEY UPDATE
        //cod_user=VALUES(cod_user), date=VALUES(date)
    */
    $tmp = [];
    if(isset($_POST['qty']) && is_array($_POST['qty'])){
        foreach($_POST['qty'] as $id=>$qty){
            $tmp[] = "({$id}, 8, 8, $qty)";
        }
    }
    if(isset($_POST['qtyx']) && is_array($_POST['qtyx'])){
        foreach($_POST['qtyx'] as $idx=>$qtyx){
            $tmp[] = "(NULL, {$_POST['prcx'][$idx]}, {$_POST['product'][$idx]}, {$_POST['delivery']}, $qtyx)";
        }
    }
    //var_dump('<pre>',$tmp);die('</pre>');
    $tmp = implode(',', $tmp);
    $qry = "INSERT INTO delivery_items (item_id, product, delivery, quantity) VALUES "
            . " {$tmp} ON DUPLICATE KEY UPDATE item_id = VALUES(item_id), quantity=VALUES(quantity)";
    $db->query($qry);
    if($db->error()) die('Saving failed');
    else die('Saved successful');
}
if(isset($_POST['delete_delivery'])){
    $db->delete('delivery_items')->where(['delivery'=>intval($_POST['delete_delivery'])])->commit();
    if(!$db->error()){
        $db->delete('delivery')->where(['delivery_id'=>intval($_POST['delete_delivery'])])->commit();
        if($db->error()) $msg = $db->error()['message'];
        else $msg = 'Delivery deleted successful!';
    }
    else{
        $msg = $db->error()['message'];
    }
    die($msg);
}
$items_q = "(SELECT JSON_ARRAYAGG(JSON_OBJECT('id', item_id, 'delivery',delivery, 'product', product_name, 'qty', quantity, 'product_id', product_id, 'item_desc', product_description, 'unit_single', product_unit_singular, 'unit_prural', product_unit_plural)) FROM delivery_items JOIN product ON product_id=product
WHERE delivery_id=delivery) AS delivery_items";
    //FROM delivery JOIN  ON  JOIN  ON ";
$delivery = $db->select('delivery',"delivery.*, {$items_q}, branches.*, customer.*,user_accounts.full_name")
                ->join('branches','branch_id=owner_branch')
                ->join('tax_invoice', 'tax_invoice_id=t_i_number', 'left')
                ->join('invoice', 'invoice_id=reference_invoice', 'left')
                ->join('customer', 'customer_id=customer', 'left')
                ->join('user_accounts', 'user_id=prepaired_by')
                ->where($whr)
                ->order_by('delivery_id', 'desc')
                ->fetchAll();

//var_dump($db->error(),$delivery);

$items_q = "(
                SELECT JSON_ARRAYAGG( 
                    JSON_OBJECT( 'id', tool_id, 'name', tool_name ) 
                ) FROM tools 
                JOIN project_resources_approved ON tool_id=pra_allocated_resource 
                WHERE pra_resource=resource_id 
            ) AS items";

$ti = $db->select('invoice', "tax_invoice_id, invoice_id, customer_name, {$items_q}")
         ->join('customer','customer_id=customer')
         ->join('tax_invoice', 'invoice_id=reference_invoice')
         ->join('user_accounts', 'user_id=sale_represantative')
         ->where("invoice.owner_branch={$me['work_location']}")
         ->fetchAll();

$sortedDelivery = [];
foreach($delivery as $delv){
    if(!isset($sortedDelivery[$delv['branch_name']])) $sortedDelivery[$delv['branch_name']] = [];
    $delv['delivery_items'] = $delv['delivery_items'] ? json_decode($delv['delivery_items'],true) : [];
    $sortedDelivery[$delv['branch_name']][] = $delv;
}
if($me['work_location'] == human_resources::get_headquarters_branch()) {
    $whr = 1;
}
else{
    $whr = ["branch_id"=>$me['work_location']];
}

$company = [];
$company[] = storage::get_data('system_config')->company_profile;
$branches = $db->select('branches', 'branch_id,branch_profile')->where($whr)->fetchAll();
foreach($branches as $b){
    if(!$b['branch_profile']) $company[$b['branch_id']] = $default;
    else $company[$b['branch_id']] = json_decode($b['branch_profile']);
}

ob_start();
$dir = realpath(__DIR__.'/html/delivery_tpl');
foreach(scandir($dir) as $filename){
    if(pathinfo("{$dir}/{$filename}", PATHINFO_EXTENSION) == 'html') include "{$dir}/{$filename}";
}
$delivery_tpl = ob_get_clean();

if($me['work_location'] == human_resources::get_headquarters_branch()) {
    $whr = 1;
}
else{
    $whr = ["owner_branch"=>$me['work_location']];
}
$product = $db->select('product')->where($whr)->fetchAll();
$customer = $db->select('customer')->where($whr)->fetchAll();
$delivery = $db->select('delivery')->where(1)->fetchAll();
//var_dump('<pre>',$sortedDelivery);die;
ob_start();
include __DIR__.'/html/delivery.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];