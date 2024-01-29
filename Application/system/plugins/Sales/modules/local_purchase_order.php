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
if(isset($_POST['created_date'])){
    $purch_data = [
        'created_date'=>addslashes($_POST['created_date']),
        'supplier'=>intval($_POST['supplier']),
        'owner_branch'=>intval($me['work_location']),
        'created_by'=>addslashes($me['work_location'])
    ];
    $purch_items_qry = [];
    $purch_id = $db->insert('local_purchase_order', $purch_data);
    if($purch_id && !$db->error()){
        foreach($_POST['item_name'] as $k=>$p){
            $purch_items_qry[] = "('{$p}', {$purch_id}, {$_POST['quantity'][$k]}, '{$_POST['description'][$k]}', {$_POST['price'][$k]})";
        }
        $purch_items_qry = implode(',', $purch_items_qry);
        $db->query("INSERT INTO local_purchase_order_item (lpo_item_name, lpo_item_reference, lpo_item_quantity, lpo_item_description, lpo_item_price) VALUES {$purch_items_qry}");
        if($db->error()){
            $db->delete('local_purchase_order')->where(['lpo_id'=>$purch_id])->commit();
        }
    }
    if ($db->error()) {
        $msg = $db->error()['message'];
    } 
    else {
        $msg = 'Saved successful';
    }

    if (isset($_POST['ajax_request'])) {
        die($msg);
    }
}
if(isset($_POST['approve_local_purchase_order'])){
    $db->update('local_purchase_order', ['approved_by'=>$me['user_reference']])
        ->where(['lpo_id'=>intval($_POST['approve_local_purchase_order'])])
        ->commit();

    if($db->error()) $msg = $db->error()['message'];
    else $msg = 'Approved successful';
    die($msg);
}
/*if(isset($_POST['local_purchase_order'])){
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
}*/
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
    $lst = $db->select('local_purchase_order_item','lpo_item_id')
              ->where('lpo_item_id')
              ->in(array_keys($_POST['qty']))
              ->fetchAll();
    foreach($_POST['qty'] as $id=>$qty){
        if(array_search($id, array_column($lst, 'lpo_item_id')) === false) $idx = 'NULL';
        else $idx = intval($id);
        $tmp[] = "({$idx}, '{$_POST['name'][$id]}', '{$_POST['lpo_id']}', $qty, '{$_POST['desc'][$id]}', '{$_POST['price'][$id]}')";
    }

    $tmp = implode(',', $tmp);
    $qry = "INSERT INTO local_purchase_order_item (lpo_item_id, lpo_item_name, lpo_item_reference, lpo_item_quantity, lpo_item_description, lpo_item_price) VALUES "
            . " {$tmp} ON DUPLICATE KEY UPDATE lpo_item_name = VALUES(lpo_item_name), lpo_item_description=VALUES(lpo_item_description), lpo_item_quantity=VALUES(lpo_item_quantity), lpo_item_price=VALUES(lpo_item_price)";
            
    $db->query($qry);
    //var_dump($db->error());
    if($db->error()) die($db->error()['message']);
    else die('Saved successful');
}
if(isset($_POST['delete_lpo'])){
    $db->delete('local_purchase_order_item')->where(['lpo_item_reference'=>intval($_POST['delete_lpo'])])->commit();
    if(!$db->error()){
        $db->delete('local_purchase_order')->where(['lpo_id'=>intval($_POST['delete_lpo'])])->commit();
        if($db->error()) $msg = $db->error()['message'];
        else $msg = 'LPO deleted successful!';
    }
    else{
        $msg = $db->error()['message'];
    }
    die($msg);
}
if(isset($_POST['delete_lpo_item'])){
    $idx = intval($_POST['delete_lpo_item']);
    $db->delete('local_purchase_order_item')->where(['lpo_item_id'=>$idx])->commit();
    if($db->error()) $msg = $db->error()['message'];
    else $msg = 'ok';
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
    $whr = "local_purchase_order.owner_branch={$me['work_location']}";
}
$items_q = "(
                SELECT JSON_ARRAYAGG(
                    JSON_OBJECT(
                        'id', lpo_item_id, 'name', lpo_item_name, 'qty', lpo_item_quantity, 'desc', lpo_item_description, 'price', lpo_item_price
                    )
                ) 
                FROM local_purchase_order_item WHERE lpo_item_reference=lpo_id
            ) AS lpo_items";

$qry = "local_purchase_order.*, approver.full_name as approved, branches.branch_name, user_accounts.full_name, {$items_q}, supplier.*";

$purchase = $db->select('local_purchase_order', $qry)
                ->join('branches','branch_id=owner_branch')
                ->join('user_accounts', 'user_id=created_by')
                ->join('user_accounts as approver', 'approver.user_id=approved_by', 'LEFT')
                ->join('supplier', 'supplier_id=supplier')
                ->where($whr)
                ->order_by('lpo_id', 'desc')
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
$dir = realpath(__DIR__.'/html/lpo_tpl');
foreach(scandir($dir) as $filename){
    if(pathinfo("{$dir}/{$filename}", PATHINFO_EXTENSION) == 'html') include "{$dir}/{$filename}";
}
$purchase_tpl = ob_get_clean();

ob_start();
include __DIR__.'/html/local_purchase_order.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];