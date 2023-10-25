<?php 

$config = storage::get_data('system_config')->db_configs;
$db = db::get_connection($config);
if(isset($_POST['delete_staff'])){
    $db->delete('staff')->where(['user_reference'=>intval($_POST['delete_staff'])])->commit();
    if(!$db->error()) $db->delete('user_accounts')->where(['user_id'=>intval($_POST['delete_staff'])])->commit();
    if(!$db->error()){
        $msg = [
            'status'=>'success',
            'message'=>'Staff deleted successful'
        ];
    }
    else{
        $msg = [
            'status'=>'error',
            'message'=>$db->error()['message']
        ];
    }
    die(json_encode( ["response"=>$msg]));
}
if(isset($_POST['import_staff'])){
    die('We will continue tomorrow');
}
$staff = $db->select('staff')
              ->join('user_accounts','user_id=user_reference')
              ->join('roles', 'system_role=role_id', 'left')
              ->join('designations', 'designation=designation_id', 'left')
              ->join('departments', 'department=dept_id', 'left')
              ->join('branches', 'work_location=branch_id', 'left')
              ->join('banks', 'bank=bank_id', 'left')
              ->order_by('staff_id', 'desc')
              ->fetchAll();

$body = '';

$designations = $db->select('designations')->fetchAll();
$departments = $db->select('departments')->fetchAll();
$branches = $db->select('branches')->fetchAll();
$banks = $db->select('banks')->fetchAll();
$roles = $db->select('roles')->fetchAll();
ob_start();
include __DIR__.'/html/staff.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];