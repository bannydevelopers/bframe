<?php 
$me = human_resources::get_staff();
if($me){
    $config = storage::get_data('system_config')->db_configs;
    $db = db::get_connection($config);
    if(isset($_POST['supplier_name'])){
        //var_dump($_POST);
        $data = [
            'owner_branch'=>$me['work_location'],
            'supplier_name'=>$_POST['supplier_name'], 
            'supplier_phone_number'=>$_POST['supplier_phone_number'], 
            'supplier_email'=>$_POST['supplier_email'], 
            'supplier_physical_address'=>$_POST['supplier_physical_adress'], 
            'supplier_details'=>$_POST['supplier_details']
        ];
        if(isset($_POST['supplier_id']) && intval($_POST['supplier_id']) > 0){
            $k = intval($_POST['supplier_id']);
            $db->update('supplier', $data)->where(['supplier_id'=>$_POST['supplier_id']])->commit();
            //var_dump($db->error());
        }
        else $k = $db->insert('supplier', $data);
        //var_dump($k);
        if(!$db->error() && $k) {
            $msg = 'Supplier saved successful';
            $ok =true;
        }
        else $msg = $db->error()['message'];
        //var_dump($db->error()); 
    }
    //var_dump($_POST);
    //var_dump($db->error());
    if(isset($_POST['delete_supplier'])){
        $db->delete('supplier')->where(['user_reference'=>intval($_POST['delete_supplier'])])->commit();
        if(!$db->error()) $db->delete('user_accounts')->where(['user_id'=>intval($_POST['delete_supplier'])])->commit();
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
    } 
    $supplier = $db->select('supplier')
                    ->join('branches', 'branch_id=owner_branch', 'left')
                    ->where(1)
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
}