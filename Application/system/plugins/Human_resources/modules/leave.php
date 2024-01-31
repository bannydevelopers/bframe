<?php 

$config = storage::get_data('system_config')->db_configs;
$db = db::get_connection($config);
$system_role = user::init()->get_session_user('system_role');

if(isset($_POST['leave_application_description'])){
    $nta  = intval($_POST['responsible_assignee']) ? 0 : $moduleconfig->leave_approve_flow[0];
    $addData = [
        'leave_type'=> $_POST['leave_type'],
        'leave_start_date'=> $_POST['leave_start'], 
        'leave_length'=> intval( $_POST['leave_length'] ), 
        'responsible_assignee'=> intval($_POST['responsible_assignee']), 
        'leave_application_description'=> $_POST['leave_application_description'],    
        'leave_applicant' => user::init()->get_session_user('user_id'),
        'next_to_approve' => $nta
    ];  
    $k = $db->insert('leave_application', $addData);
    //var_dump($db->error());
    if(!$db->error() && $k) $msg = 'Leave added successful';
    else $msg = $db->error()['message'];
    if(isset($_POST['ajax_request'])) die($msg);
}
if(isset($_POST['approve_leave'])){
    $leave = $db->select('leave_application')
                ->where(['leave_application_id'=>intval($_POST['approve_leave'])])
                ->limit(1)
                ->fetch();

    if($leave){
        $leave['response_date'] = $leave['response_date'] ? json_decode($leave['response_date']) : []; 
        $leave['remarks'] = $leave['remarks'] ? json_decode($leave['remarks']) : []; 
        if($leave['next_to_approve'] == 0){
            $data = [
                'next_to_approve'=>$moduleconfig->leave_approve_flow[0],
                'response_date'=>json_encode([date('Y-m-d H:i:s')]),
                'remarks'=>json_encode([$user['full_name'].' &raquo; '.$_POST['response'].'<br>'.$_POST['remarks']]),
                'leave_application_status'=>'On progress'
            ];
        }
        else{
            $data = [
                'response_date'=>json_encode([...$leave['remarks'], date('Y-m-d H:i:s')]),
                'remarks'=>json_encode([...$leave['response_date'],$_POST['remarks']])
            ];
            if(end($moduleconfig->leave_approve_flow) == $leave['next_to_approve']){
                $data['next_to_approve'] = '';
                $data['leave_application_status'] = 'Approved';
                $data['approval_date'] = date('Y-m-d H:i:s');
            }
            else{
                $k = array_search($leave['next_to_approve'], $moduleconfig->leave_approve_flow);
                $data['next_to_approve'] = $moduleconfig->leave_approve_flow[$k+1];
            }
        }
        if($_POST['response'] == 'reject') {
            $data['leave_application_status'] = 'Rejected';
            $data['approval_date'] = date('Y-m-d H:i:s');
        }
        $db->update('leave_application', $data)
            ->where(['leave_application_id'=>$_POST['approve_leave']])
            ->commit();
        if(!$db->error()) $msg = 'Response recorded successful';
        else $msg = $db->error()['message'];
        die($msg);
    }
}
if(isset($_POST['delete_leave'])){
    $db->delete('leave_application')
        ->where(['leave_application_id'=>intval($_POST['delete_leave'])])
        ->and(['leave_applicant'=>$user['user_id']])
        ->and("leave_application_status != 'Approved'")
        ->commit();

    if(!$db->error()){
        $chk = $db->select('leave_application')
                    ->where(['leave_application_id'=>intval($_POST['delete_leave'])])
                    ->limit(1)
                    ->fetch();
        if(!$chk) $msg = 'Deleted successful';
        else $msg = 'Error: Something unexpected happened';
    }
    else{
        $msg = $db->error()['message'];
    }
    if(isset($_POST['ajax_request'])) die($msg);
}
$sr = implode(',', $moduleconfig->leave_approve_flow);
if(user::init()->user_can('view_all_leave')){
    if($_this::get_headquarters_branch() == $me['work_location']) $whr = 1;
    else $whr = "leave_applicant IN (SELECT user_reference FROM staff WHERE work_location = {$me['work_location']})";
    $leave = $db->select('leave_application', 'leave_application.*, user_accounts.full_name, user_accounts.user_id, role_name')
            ->join('user_accounts', "user_accounts.user_id=leave_application.leave_applicant")
            ->join('roles', 'next_to_approve=role_id', 'left')
            ->where($whr)
            ->order_by('application_date', 'desc')
            ->fetchAll();
}
else{
    $leave = $db->select('leave_application', 'leave_application.*, user_accounts.full_name, user_accounts.user_id, role_name')
                ->join('user_accounts', "user_accounts.user_id=leave_application.leave_applicant")
                ->join('roles', 'next_to_approve=role_id', 'left')
                ->where(['leave_applicant'=>$user['user_id']])
                ->or(['next_to_approve'=>$user['system_role']])
                //->in("SELECT user_id FROM user_accounts WHERE system_role IN ({$sr})")
                ->or("(responsible_assignee={$user['user_id']} AND next_to_approve = 0)")
                ->order_by('application_date', 'desc')
                ->fetchAll();
}

$employees = $db->select('user_accounts', 'full_name, user_id')
                ->where(['system_role'=>user::init()->get_session_user('system_role')])
                ->and("user_id != {$user['user_id']}")
                ->order_by('full_name', 'asc')
                ->fetchAll();

ob_start();

include __DIR__.'/html/leave.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];