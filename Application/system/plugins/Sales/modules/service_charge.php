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
    $purch_id = $db->insert('service', $purch_data);
    if($purch_id && !$db->error()){
        foreach($_POST['item_name'] as $k=>$p){
            $purch_items_qry[] = "('{$p}', {$purch_id}, {$_POST['quantity'][$k]}, '{$_POST['description'][$k]}')";
        }
        $purch_items_qry = implode(',', $purch_items_qry);
        $db->query("INSERT INTO service_items (service_item_name, service_item_reference, service_item_quantity, service_item_description) VALUES {$purch_items_qry}");
        if($db->error()){
            $db->delete('service')->where(['service_id'=>$purch_id])->commit();
        }
    }
    //var_dump($db->error());
    if($db->error()) $msg = $db->error()['message'];
    else $msg = 'Saved successful';
    if(isset($_POST['ajax_request'])) die($msg);
}
if(isset($_POST['approve_service'])){
    $db->update('service', ['approved_by'=>$me['user_reference']])
        ->where(['service_id'=>intval($_POST['approve_service'])])
        ->commit();

    if($db->error()) $msg = $db->error()['message'];
    else $msg = 'Approved successful';
    die($msg);
}
if(isset($_POST['local_service_order'])){
    $data = [
        'reference_service'=>intval($_POST['reference_service']),
        'local_service_order'=>addslashes($_POST['local_service_order']),
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
    $lst = $db->select('service_items','service_item_id')
              ->where('service_item_id')
              ->in(array_keys($_POST['qty']))
              ->fetchAll();
    foreach($_POST['qty'] as $id=>$qty){
        if(array_search($id, array_column($lst, 'service_item_id')) === false) $idx = 'NULL';
        else $idx = intval($id);
        $tmp[] = "({$idx}, '{$_POST['item_name'][$id]}', '{$_POST['service']}', $qty, '{$_POST['description'][$id]}')";
    }
    $tmp = implode(',', $tmp);
    $qry = "INSERT INTO service_items (service_item_id, service_item_name, service_item_reference, service_item_quantity, service_item_description) VALUES "
            . " {$tmp} ON DUPLICATE KEY UPDATE service_item_name = VALUES(service_item_name), service_item_description=VALUES(service_item_description), service_item_quantity=VALUES(service_item_quantity)";
            
    //var_dump($tmp);die;
    $db->query($qry);
    //var_dump($db->error());
    if($db->error()) die('Saving failed');
    else die('Saved successful');
}
if(isset($_POST['delete_service'])){
    $db->delete('service_items')->where(['service_item_reference'=>intval($_POST['delete_service'])])->commit();
    if(!$db->error()){
        $db->delete('service')->where(['service_id'=>intval($_POST['delete_service'])])->commit();
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
    $db->delete('service_items')->where(['service_item_id'=>$idx])->commit();
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
    $whr = "service.owner_branch={$me['work_location']}";
}
$items_q = "(
                SELECT JSON_ARRAYAGG(
                    JSON_OBJECT(
                        'id', service_item_id, 'item_name', service_item_name, 'service', service_item_reference, 'description', service_item_description, 'qty', service_item_quantity
                    )
                ) 
                FROM service_items WHERE service_item_reference=service_id
            ) AS service_items";

$qry = "service.*, approver.full_name as approved, branches.branch_name, user_accounts.full_name, {$items_q}, customer.*";

$service = $db->select('service', $qry)
                ->join('branches','branch_id=owner_branch')
                ->join('user_accounts', 'user_id=created_by')
                ->join('user_accounts as approver', 'approver.user_id=approved_by', 'LEFT')
                ->join('customer', 'customer_id=customer')
                ->where($whr)
                ->order_by('service_id', 'desc')
                ->fetchAll();   

var_dump($db->error());
$customer = $db ->select('customer','customer_id, customer_name')
                ->where($whr)
                ->fetchAll();


$sortedservice = [];
foreach($service as $prod){
    if(!isset($sortedservice[$prod['branch_name']])) $sortedservice[$prod['branch_name']] = [];
    $sortedservice[$prod['branch_name']][] = $prod;
}

$default = [
    'branch_name' => 'Default Branch Name',  // Example default value, modify as needed
    'branch_profile' => 'Default Branch Profile'  // Example default value, modify as needed
];

$company = [];
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