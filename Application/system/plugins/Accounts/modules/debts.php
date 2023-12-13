<?php 
$me = human_resources::get_staff();
if($me){
    $config = storage::get_data('system_config')->db_configs;
    $db = db::get_connection($config);
    
    if(isset($_POST['debt_date'])){
        var_dump($_POST);
        
        $data = [
            'debt_date'=>addslashes($_POST['debt_date']), 
            'debt_description'=>addslashes($_POST['debt_description']), 
            'debt_amount'=>addslashes($_POST['debt_amount']), 
            'debt_party_type'=>addslashes($_POST['debt_party_type']),
            'debt_party_id'=>intval($_POST['debt_party']),
            'owner_branch'=>intval($me['work_location'])
        ];
        var_dump($db->error());
        if(isset($_POST['debt_id']) && intval($_POST['debt_id']) > 0){
            $k = intval($_POST['debt_id']);
            $db->update('debts', $data)->where(['debt_id'=>$_POST['debt_id']])->commit();
        }
        else $k = $db->insert('debts', $data);
        $ok = 'error';
        if(!$db->error() && $k) {
            $msg = 'debt saved successful';
            $ok ='success';
        }
        else $msg = $db->error()['message']; 
        if(isset($_POST['ajax_request'])) die($msg);
    }
    
    if(isset($_POST['delete_debt'])){
        $k = $db->delete('debts')->where(['debt_id'=>intval($_POST['delete_debt'])])->commit();
        if(!$db->error() && $k){
            $msg = [
                'status'=>'success',
                'message'=>'debt deleted successful'
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
    if($me['work_location'] == human_resources::get_headquarters_branch()) {
        $whr = 1;
    }
    else{
        $whr = ['owner_branch'=>$me['work_location']];
    }
    $debt = $db->select('debts')
                    ->join('branches', 'branch_id=owner_branch')
                    ->join('customer','customer_id=debt_party_id', 'left')
                    ->join('business_partiner','business_partiner_id=debt_party_id', 'left')
                    ->join('supplier','supplier_id=debt_party_id', 'left')
                    ->join('user_accounts','user_id=debt_party_id', 'left')
                    ->where($whr)
                    ->and(['debt_type'=>'lend'])
                    ->order_by('debt_id', 'desc')
                    ->fetchAll();
var_dump($db->error());
    $sortedDebt = [];
    foreach($debt as $st){
        if(!isset($sortedDebt[$st['branch_name']])) $sortedDebt[$st['branch_name']] = [];
        $sortedDebt[$st['branch_name']][] = $st;
    }

    $customer = $db->select('customer', 'customer_id, customer_name')->fetchAll();
    $supplier = $db->select('supplier', 'supplier_id, supplier_name')->fetchAll();
    $staff = $db->select('user_accounts', 'user_id, full_name')
                ->join('staff', 'user_id=user_reference')
                ->fetchAll();
    $partiner = $db->select('business_partiner', 'business_partiner_id, business_partiner_name')->fetchAll();
    $body = '';
    ob_start();
    include __DIR__.'/html/debts.html';
    $body = ob_get_clean();
    $return = ['title'=>' ','body'=>$body];
}