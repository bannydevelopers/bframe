<?php 

$config = storage::get_data('system_config')->db_configs;
$db = db::get_connection($config);


$staff = $db->select('staff')
              ->join('user_accounts','user_id=user_reference')
              ->join('roles', 'system_role=role_id', 'left')
              ->join('designations', 'designation=designation_id', 'left')
              ->join('departments', 'department=dept_id', 'left')
              ->order_by('staff_id', 'desc')
              ->fetchAll();

$body = '';
ob_start();
include __DIR__.'/html/staff.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];