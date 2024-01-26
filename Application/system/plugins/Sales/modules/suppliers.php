<?php 

$config = storage::get_data('system_config')->db_configs;
$db = db::get_connection($config);
//var_dump($_POST);
//var_dump($db->error());
if(isset($_POST['delete_supplier'])){
    $db->delete('supplier')->where(['supplier_id'=>intval($_POST['delete_supplier'])])->commit();
    if(!$db->error()){
        $msg = [
            'status'=>'success',
            'message'=>'Supplier deleted successful'
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
$whr = $is_headquarters ? 1 : ['owner_branch'=>$me['work_location']];
$supplier = $db->select('supplier')
                ->join('branches', 'branch_id=owner_branch', 'left')
                ->where($whr)
                ->order_by('supplier_id', 'DESC')
                ->fetchAll();
//var_dump($db->error(), $supplier);
$sortedSupplier = [];
foreach($supplier as $st){
    if(!isset($sortedSupplier[$st['branch_name']])) $sortedSupplier[$st['branch_name']] = [];
    $sortedSupplier[$st['branch_name']][] = $st;
}

$body = '';
ob_start();
include __DIR__.'/html/supplier.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];