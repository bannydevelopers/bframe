<?php 

$config = storage::get_data('system_config')->db_configs;
$db = db::get_connection($config);
if(isset($_POST['customer_name'])){
    //var_dump($_POST);
    $data = [
        'owner_branch'=>$me['work_location'],
        'customer_name'=>$_POST['customer_name'], 
        'customer_phone_number'=>$_POST['customer_phone_number'], 
        'customer_email'=>$_POST['customer_email'], 
        'customer_physical_adress'=>$_POST['customer_physical_adress'], 
        'tin_number'=>$_POST['tin_number'], 
        'vrn_number'=>$_POST['vrn_number'], 
        'created_time'=>date('Y-m-d H:i:s')
    ];
    //var_dump($db->error());
    if(isset($_POST['customer_id']) && intval($_POST['customer_id']) > 0){
        $k = intval($_POST['customer_id']);
        $db->update('customer', $data)->where(['customer_id'=>$_POST['customer_id']])->commit();
    }
    else $k = $db->insert('customer', $data);
    $ok = 'error';
    if(!$db->error() && $k) {
        $msg = 'Customer saved successful';
        $ok ='success';
    }
    else $msg = $db->error()['message']; 
    if(isset($_POST['ajax_request'])) die($msg);
}

if(isset($_POST['delete_customer'])){
    $k = $db->delete('customer')->where(['customer_id'=>intval($_POST['delete_customer'])])->commit();
    if(!$db->error() && $k){
        $msg = [
            'status'=>'success',
            'message'=>'Customer deleted successful'
        ];
    }
    else{
        $msg = [
            'status'=>'error',
            'message'=>$db->error()['message']
        ];
    }
    if(isset($_POST['ajax_request'])) die(json_encode($msg));
}
if($is_headquarters) {
    $whr = 1;
}
else{
    $whr = ['owner_branch'=>$me['work_location']];
}

$customer = $db->select('customer')
                ->join('branches', 'branch_id=owner_branch')
                ->where($whr)
                ->order_by('customer_id', 'desc')
                ->fetchAll();
$sortedCustomer = [];
foreach($customer as $st){
    if(!isset($sortedCustomer[$st['branch_name']])) $sortedCustomer[$st['branch_name']] = [];
    $sortedCustomer[$st['branch_name']][] = $st;
}

$body = '';
ob_start();
include __DIR__.'/html/customer.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];