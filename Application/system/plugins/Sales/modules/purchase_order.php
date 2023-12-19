<?php 

$hq = human_resources::get_headquarters_branch();
$config = storage::get_data('system_config')->db_configs;
$db = db::get_connection($config);

if($me['work_location'] == human_resources::get_headquarters_branch()) {
    $whr = 1;
}
else{
    $whr = "owner_branch={$me['work_location']}";
}
if(isset($_POST['purchase_date'])){
    $purch_data = [
        'purchase_date'=>addslashes($_POST['purchase_date']),
        'supplier'=>intval($_POST['supplier']),
        'owner_branch'=>intval($me['work_location']),
        'purchase_no'=>addslashes($_POST['purchase_no']),
        'created_by'=>addslashes($me['work_location'])
    ];
    $purch_items_qry = [];
    $purch_id = $db->insert('purchase', $purch_data);
    if($purch_id && !$db->error()){
        foreach($_POST['item_name'] as $k=>$p){
            $purch_items_qry[] = "('{$p}', {$purch_id}, {$_POST['price'][$k]}, {$_POST['quantity'][$k]})";
        }
        $purch_items_qry = implode(',', $purch_items_qry);
        $db->query("INSERT INTO purchase_items (purchase_item_name, purchase_item_reference, purchase_item_price, purchase_item_quantity) VALUES {$purch_items_qry}");
        if($db->error()){
            $db->delete('purchase')->where(['purchase_id'=>purch_id])->commit();
        }
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
        $tmp[] = "({$id}, '{$_POST['item_name'][$id]}', 0, $qty, {$_POST['price'][$id]})";
    }
    $tmp = implode(',', $tmp);
    $qry = "INSERT INTO purchase_items (purchase_item_id, purchase_item_name, purchase_item_reference, purchase_item_quantity, purchase_item_price) VALUES "
            . " {$tmp} ON DUPLICATE KEY UPDATE purchase_item_name = VALUES(purchase_item_name), purchase_item_price=VALUES(purchase_item_price), purchase_item_quantity=VALUES(purchase_item_quantity)";
    $db->query($qry);
    var_dump($db->error());
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

/*$Purchase = $db->select('product')
                    ->join('product_category', 'category_id=product_category', 'LEFT')
                    ->join('branches', 'branch_id=owner_branch', 'LEFT')
                    ->where($whr)
                    ->fetchAll();*/

if($me['work_location'] == human_resources::get_headquarters_branch()) {
    $whr = 1;
}
else{
    $whr = "purchase.owner_branch={$me['work_location']}";
}
$items_q = "(
                SELECT JSON_ARRAYAGG(
                    JSON_OBJECT(
                        'id', purchase_item_id, 'item_name',purchase_item_name, 'purchase', purchase_item_reference, 'price', purchase_item_price, 'qty', purchase_item_quantity
                    )
                ) 
                FROM purchase_items WHERE purchase_item_reference=purchase_id
            ) AS purchase_items";

$qry = "purchase.*, branches.branch_name, user_accounts.full_name, {$items_q}, supplier.*";

$purchase = $db->select('purchase', $qry)
                ->join('branches','branch_id=owner_branch')
                ->join('user_accounts', 'user_id=created_by')
                ->join('supplier', 'supplier_id=supplier')
                ->where($whr)
                ->order_by('purchase_id', 'desc')
                ->fetchAll();   


$supplier = $db ->select('supplier','supplier_id, supplier_name')
                ->where($whr)
                ->fetchAll();


$sortedPurchase = [];
foreach($purchase as $prod){
    if(!isset($sortedPurchase[$prod['branch_name']])) $sortedPurchase[$prod['branch_name']] = [];
    $sortedPurchase[$prod['branch_name']][] = $prod;
}

$company = [];
$company[] = storage::get_data('system_config')->company_profile;
$branches = $db->select('branches', 'branch_id,branch_profile')->where($whr)->fetchAll();
foreach($branches as $b){
    if(!$b['branch_profile']) $company[$b['branch_id']] = $default;
    else $company[$b['branch_id']] = json_decode($b['branch_profile']);
}

ob_start();
$dir = realpath(__DIR__.'/html/purchase_tpl');
foreach(scandir($dir) as $filename){
    if(pathinfo("{$dir}/{$filename}", PATHINFO_EXTENSION) == 'html') include "{$dir}/{$filename}";
}
$purchase_tpl = ob_get_clean();

ob_start();
include __DIR__.'/html/purchase_order.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];