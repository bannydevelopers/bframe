<?php 

$config = storage::get_data('system_config')->db_configs;
$db = db::get_connection($config);
$system_role = user::init()->get_session_user('system_role');
$user_id = user::init()->get_session_user('user_id');

if(isset($_POST['leave_application_description'])){
    $ok = 'error';
        $addData = [
            'leave_type'=> $_POST['leave_type'],
            'leave_start_date'=> $_POST['leave_start'], 
            'leave_length'=> intval( $_POST['leave_length'] ), 
            'responsible_assignee'=> intval($_POST['responsible_assignee']), 
            'leave_application_description'=> $_POST['leave_application_description'],    
            'leave_applicant' => $user_id,
            'next_to_approve' => 3
        ];  
      $k = $db->insert('leave_application', $addData);
        //var_dump($db->error());
        if(!$db->error() && $k) {
            $msg = 'Leave added successful';
            $ok = 'success';
        }
        else $msg = $db->error()['message'];
        if(isset($_POST['ajax_request'])){
            die(json_encode(['status'=>$ok, 'message'=>$msg]));
        }
}

if(in_array($user['system_role'], $moduleconfig->leave_approve_flow)){
    $leave  = $db->select('leave_application')
        ->join('user_accounts', "user_accounts.user_id=leave_application.leave_applicant", 'left')
        ->fetchAll();
}else {
    // $leave  = $db->select('leave_application')
    //     ->join('user_accounts', "user_accounts.user_id=leave_application.leave_applicant", 'left')
    //     ->where(['leave_applicant'=>$user_id])
    //     ->fetchAll();
}
$sr = implode(',', $moduleconfig->leave_approve_flow);
$leave = $db->select('leave_application')
            ->join('user_accounts', "user_accounts.user_id=leave_application.leave_applicant", 'left')
            ->where(['leave_applicant'=>$user['user_id']])
            ->or('leave_applicant')
            ->in("SELECT user_id FROM user_accounts WHERE system_role IN ({$user['system_role']})")
            ->fetchAll();
//var_dump($db->getQuery());
$employees = $db->select('user_accounts', 'full_name, user_id')
    ->where("system_role={$system_role}")
    ->order_by('full_name', 'asc')
    ->fetchAll();

ob_start();
include __DIR__.'/html/leave.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];