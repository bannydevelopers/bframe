<?php 
$me = human_resources::get_staff();
if($me){
    $config = storage::get_data('system_config')->db_configs;
    $db = db::get_connection($config);
    if(isset($_POST['amount'])){
        //var_dump($_POST);
        $data = [
            'owner_branch'=>$me['work_location'],
            'received_from'=>$_POST['received_from'],
            'per_invoice_no'=>$_POST['per_invoice_no'],   
            'date'=>$_POST['date'], 
            'amount'=>$_POST['amount'], 
            'bank_name'=>$_POST['bank_name'],
            'cheque_no'=>$_POST['cheque_no']
        ];
        //var_dump($db->error());
        if(isset($_POST['deposit_id']) && intval($_POST['deposit_id']) > 0){
            $k = intval($_POST['deposit_id']);
            $db->update('deposit_info', $data)->where(['deposit_id'=>$_POST['deposit_id']])->commit();
        }
        else $k = $db->insert('deposit_info', $data);
        $ok = 'error';
        if(!$db->error() && $k) {
            $msg = 'deposit saved successful';
            $ok ='success';
        }
        else $msg = $db->error()['message']; 
        if(isset($_POST['ajax_request'])) die($msg);
    }
    
    if(isset($_POST['delete_deposit'])){
        $k = $db->delete('deposit_info')->where(['deposit_id'=>intval($_POST['delete_deposit'])])->commit();
        if(!$db->error() && $k){
            $msg = [
                'status'=>'success',
                'message'=>'deposit deleted successful'
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
    $deposit = $db->select('deposit_info')
                    ->join('branches', 'branch_id=owner_branch')
                    ->where($whr)
                    ->order_by('deposit_id', 'desc')
                    ->fetchAll();
    $sortedDeposit = [];
    foreach($deposit as $st){
        if(!isset($sortedDeposit[$st['branch_name']])) $sortedDeposit[$st['branch_name']] = [];
        $sortedDeposit[$st['branch_name']][] = $st;
    }

    $body = '';
    ob_start();
    include __DIR__.'/html/deposit_info.html';
    $body = ob_get_clean();
    $return = ['title'=>' ','body'=>$body];
}