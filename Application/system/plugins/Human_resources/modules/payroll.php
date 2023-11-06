<?php 
$config = storage::get_data('system_config')->db_configs;
$db = db::get_connection($config);
if(isset($_POST['employee'])){
    $ok = 'error';    
    }
    if(isset($_POST['p_slips'])){
            $slist = implode(',', array_values($_POST['p_slips']));
            $slips = $db->select('salary_slip', "salary_slip.*, full_name")
                       ->join('user_accounts','salary_slip.employee=user_accounts.user_id')
                       ->where("slip_id IN ({$slist})")
                       ->order_by('slip_id', 'desc')->fetchAll();
            $addData = [  
                'payment_slips'=>json_encode($slips), 
                'created_by'=>user::init()->get_session_user('user_id'),
                'payroll_name'=>"{$_POST['payroll_month']}, {$_POST['payroll_year']}", 
            ];
            $k = $db->insert('payroll', $addData);
           //var_dump($db->error());
         if(!$db->error() && $k) {
                $msg = 'Payroll added successful';
                $ok  =  'success';
            }
            else $msg = $db->error()['message'];
            var_dump($db->error()); 
         if(isset($_POST['ajax_request'])){
            die(json_encode(['status'=>$ok, 'message'=>$msg]));
           }
     }
  $payroll = $db->select('payroll')
              ->order_by('payroll_id', 'desc')->fetchAll();
       
  $employee = $db->select('user')
               ->where(1)
               ->fetchAll();

  $slips = $db->select('salary_slip')
            ->join('user_accounts','salary_slip.employee=user_accounts.user_id')
            ->order_by('slip_id', 'desc')->fetchAll();
//var_dump($db->error(), $slips);
ob_start();
include __DIR__.'/html/payroll.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];