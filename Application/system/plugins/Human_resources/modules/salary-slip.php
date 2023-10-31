<?php 

$config = storage::get_data('system_config')->db_configs;
$db = db::get_connection($config);
if(isset($_POST['employee'])){
    $ok = 'error';
        $addData = [
            'employee'=>intval( $_POST['employee'] ),
            'basic_salary'=>intval( $_POST['basic_salary'] ), 
            'payee'=>intval( $_POST['payee'] ), 
            'health_insurance_fund'=>intval( $_POST['health_insurance_fund'] ), 
            'social_security_fund'=>intval( $_POST['social_security_fund'] ), 
            'worker_compasion_fund'=>intval( $_POST['worker_compasion_fund'] ), 
            'education_fund'=>intval( $_POST['education_fund'] ), 
            'allowance'=>intval( $_POST['allowance'] ), 
            'bonus'=>intval( $_POST['bonus'] ), 
            'other_deduction'=>intval( $_POST['other_deduction'] )    
        ];
       
        $k = $db->insert('salary_slip', $addData);
        //var_dump($db->error());
        if(!$db->error() && $k) {
            $msg = 'Salary slip added successful';
            $ok = 'success';
        }
        else $msg = $db->error()['message'];
        if(isset($_POST['ajax_request'])){
            die(json_encode(['status'=>$ok, 'message'=>$msg]));
        }
}
$salary_slip = $db->select('salary_slip')
              ->join('user_accounts','user_id=employee')
              ->order_by('slip_id', 'desc')->fetchAll();

$employees = $db->select('staff')
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
include __DIR__.'/html/salary_slip.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];