<?php 
$me = human_resources::get_staff();
if($me){
    $config = storage::get_data('system_config')->db_configs;
    $db = db::get_connection($config);
    if(isset($_POST['payment_to'])){
        //var_dump($_POST);
        $data = [
            'owner_branch'=>$me['work_location'],
            'payment_to'=>$_POST['payment_to'],
            'payment_method'=>$_POST['payment_method'],   
            'withdraw_date'=>$_POST['withdraw_date'], 
            'amount'=>$_POST['amount'], 
            'mode_of_payment'=>$_POST['mode_of_payment'],
            'bank'=>$_POST['payment_bank'],
            'cheque_no'=>$_POST['cheque_no'],
            'created_by'=>$me['user_reference']
        ];
        
        if(isset($_POST['withdraw_id']) && intval($_POST['withdraw_id']) > 0){
            $k = intval($_POST['withdraw_id']);
            $db->update('withdraw', $data)->where(['withdraw_id'=>$_POST['withdraw_id']])->commit();
        }
        else $k = $db->insert('withdraw', $data);
        $ok = 'error';
        if(!$db->error() && $k) {
            $msg = 'Withdraw saved successful';
            $ok ='success';
        }
        else $msg = $db->error()['message']; 
        if(isset($_POST['ajax_request'])) die($msg);
    }

    if(isset($_POST['delete_withdraw'])){
        $k = $db->delete('withdraw')->where(['withdraw_id'=>intval($_POST['delete_withdraw'])])->commit();
        if(!$db->error() && $k){
            $msg = [
                'status'=>'success',
                'message'=>'Withdraw deleted successful'
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
    if(isset($_POST['approve_withdraw'])){
        $wdx = intval($_POST['approve_withdraw']);
        $db->update('withdraw', ['approved_by'=>$me['user_reference']])->where(['withdraw_id'=>$wdx])->commit();
        if($db->error()) $msg = $db->error()['message'];
        else $msg = 'Approved successful';
        die($msg);
    }
    if($me['work_location'] == human_resources::get_headquarters_branch()) {
        $whr = 1;
    }
    else{
        $whr = ['owner_branch'=>$me['work_location']];
    }
    $withdraw = $db->select('withdraw', 'withdraw.*, branches.*, banks.*, user_accounts.full_name, approver.full_name as approve')
                    ->join('branches', 'branch_id=owner_branch')
                    ->join('banks','bank_id=bank')
                    ->join('user_accounts','user_id=created_by')
                    ->join('user_accounts as approver','approver.user_id=approved_by', 'left')
                    ->where($whr)
                    ->order_by('withdraw_id', 'desc')
                    ->fetchAll();

    $banks = $db ->select('banks','bank_id, bank_name')
                  ->where($whr)
                  ->fetchAll();

   
    $sortedWithdraw = [];
    foreach($withdraw as $st){
        if(!isset($sortedWithdraw[$st['branch_name']])) $sortedWithdraw[$st['branch_name']] = [];
        $sortedWithdraw[$st['branch_name']][] = $st;
    }

    $body = '';
    ob_start();
    include __DIR__.'/html/withdraw.html';
    $body = ob_get_clean();
    $return = ['title'=>' ','body'=>$body];
}