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
        'customer'=>intval($_POST['customer']),
        'owner_branch'=>intval($me['work_location']),
        'created_by'=>addslashes($me['work_location'])
    ];
    $purch_items_qry = [];
    $purch_id = $db->insert('service_charge', $purch_data);
    if($purch_id && !$db->error()){
        foreach($_POST['item_name'] as $k=>$p){
            $purch_items_qry[] = "('{$p}', {$purch_id}, {$_POST['quantity'][$k]}, '{$_POST['description'][$k]}', {$_POST['price'][$k]})";
        }
        $purch_items_qry = implode(',', $purch_items_qry);
        $db->query("INSERT INTO service_charge_item (service_item_name, service_item_reference, service_item_quantity, service_item_description, service_item_price) VALUES {$purch_items_qry}");
        if($db->error()){
            $db->delete('service_charge')->where(['service_id'=>$purch_id])->commit();
        }
    }
    var_dump($db->error());
$error = $db->error();

if ($error) {
    $msg = isset($error['message']) ? $error['message'] : 'An error occurred';
} else {
    $msg = 'Saved successfully';
}

if (isset($_POST['ajax_request'])) die($msg);
}

if (isset($_POST['approve_service'])) {
    $db->update('service', ['approved_by' => $me['user_reference']])
        ->where(['service_id' => intval($_POST['approve_service'])])
        ->commit();

    $error = $db->error();
    if ($error) {
        $msg = isset($error['message']) ? $error['message'] : 'An error occurred';
    } else {
        $msg = 'Approved successfully';
    }
    die($msg);
}
/*if(isset($_POST['service_charge'])){
    $data = [
        'reference_service'=>intval($_POST['reference_service']),
        'service_charge'=>addslashes($_POST['service_charge']),
        'tax_service_remarks'=>addslashes($_POST['tax_service_remarks']),
        'recorded_by'=>user::init()->get_session_user('user_id')
    ];

    $k = $db->insert('tax_service', $data);
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
    $lst = $db->select('service_charge_item','service_item_id')
              ->where('service_item_id')
              ->in(array_keys($_POST['qty']))
              ->fetchAll();
    foreach($_POST['qty'] as $id=>$qty){
        if(array_search($id, array_column($lst, 'service_item_id')) === false) $idx = 'NULL';
        else $idx = intval($id);
        $tmp[] = "({$idx}, '{$_POST['name'][$id]}', '{$_POST['service_id']}', $qty, '{$_POST['desc'][$id]}', '{$_POST['price'][$id]}')";
    }

    $tmp = implode(',', $tmp);
    $qry = "INSERT INTO service_charge_item (service_item_id, service_item_name, service_item_reference, service_item_quantity, service_item_description, service_item_price) VALUES "
            . " {$tmp} ON DUPLICATE KEY UPDATE service_item_name = VALUES(service_item_name), service_item_description=VALUES(service_item_description), service_item_quantity=VALUES(service_item_quantity), service_item_price=VALUES(service_item_price)";
            
    $db->query($qry);
    var_dump($db->error());
    if($db->error()) die($db->error()['message']);
    else die('Saved successful');
}
if(isset($_POST['delete_service'])){
    $db->delete('service_charge_item')->where(['service_item_reference'=>intval($_POST['delete_service'])])->commit();
    if(!$db->error()){
        $db->delete('service_charge')->where(['service_id'=>intval($_POST['delete_service'])])->commit();
        if($db->error()) $msg = $db->error()['message'];
        else $msg = 'service deleted successful!';
    }
    else{
        $msg = $db->error()['message'];
    }
    die($msg);
}
if(isset($_POST['delete_service_item'])){
    $idx = intval($_POST['delete_service_item']);
    $db->delete('service_charge_item')->where(['service_item_id'=>$idx])->commit();
    if($db->error()) $msg = $db->error()['message'];
    else $msg = 'ok';
    die($msg);
}
/*$service = $db->select('product')
                    ->join('product_category', 'category_id=product_category', 'LEFT')
                    ->join('branches', 'branch_id=owner_branch', 'LEFT')
                    ->where($whr)
                    ->fetchAll();*/

if($me['work_location'] == human_resources::get_headquarters_branch()) {
    $whr = 1;
}
else{
    $whr = "service_charge.owner_branch={$me['work_location']}";
}
$items_q = "(
                SELECT JSON_ARRAYAGG(
                    JSON_OBJECT(
                        'id', service_item_id, 'name', service_item_name, 'qty', service_item_quantity, 'desc', service_item_description, 'price', service_item_price
                    )
                ) 
                FROM service_charge_item WHERE service_item_reference=service_id
            ) AS service_items";

$qry = "service_charge.*, approver.full_name as approved, branches.branch_name, user_accounts.full_name, {$items_q}, customer.*";

$service = $db->select('service_charge', $qry)
                ->join('branches','branch_id=owner_branch')
                ->join('user_accounts', 'user_id=created_by')
                ->join('user_accounts as approver', 'approver.user_id=approved_by', 'LEFT')
                ->join('customer', 'customer_id=customer')
                ->where($whr)
                ->order_by('service_id', 'desc')
                ->fetchAll();   

$customer = $db ->select('customer','customer_id, customer_name')
                ->where($whr)
                ->fetchAll();


$sortedservice = [];
foreach($service as $prod){
    if(!isset($sortedservice[$prod['branch_name']])) $sortedservice[$prod['branch_name']] = [];
    $sortedservice[$prod['branch_name']][] = $prod;
}

$company = [];
$default = []; // Initialize $default here
$company[] = storage::get_data('system_config')->company_profile;
$branches = $db->select('branches', 'branch_id,branch_profile')->where($whr)->fetchAll();
foreach($branches as $b){
    if(!$b['branch_profile']) $company[$b['branch_id']] = $default;
    else $company[$b['branch_id']] = json_decode($b['branch_profile']);
}

ob_start();
$dir = realpath(__DIR__.'/html/service_tpl');
foreach(scandir($dir) as $filename){
    if(pathinfo("{$dir}/{$filename}", PATHINFO_EXTENSION) == 'html') include "{$dir}/{$filename}";
}
$service_tpl = ob_get_clean();

ob_start();
include __DIR__.'/html/service_charge.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];