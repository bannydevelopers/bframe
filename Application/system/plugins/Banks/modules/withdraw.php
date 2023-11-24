<?php 
$me = human_resources::get_staff();
if($me){
    $config = storage::get_data('system_config')->db_configs;
    $db = db::get_connection($config);
    if(isset($_POST['payment_to'])){
        //var_dump($_POST);
        $data = [
            'owner_branch'=>$me['work_location'],
            'bank_name'=>$_POST['bank_name'],
            'payment_to'=>$_POST['payment_to'],   
            'due_date'=>$_POST['due_date'], 
            'amount'=>$_POST['amount'], 
            'mode_of_payment'=>$_POST['mode_of_payment']
        ];
        //var_dump($db->error());
        if(isset($_POST['withdraw_id']) && intval($_POST['withdraw_id']) > 0){
            $k = intval($_POST['withdraw_id']);
            $db->update('withdraw', $data)->where(['withdraw_id'=>$_POST['withdraw_id']])->commit();
        }
        else $k = $db->insert('withdraw', $data);
        $ok = 'error';
        if(!$db->error() && $k) {
            $msg = 'withdraw saved successful';
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
                'message'=>'withdraw deleted successful'
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
    $withdraw = $db->select('withdraw')
                    ->join('branches', 'branch_id=owner_branch')
                    ->where($whr)
                    ->order_by('withdraw_id', 'desc')
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