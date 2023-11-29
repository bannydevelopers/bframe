<?php 
    $me = human_resources::get_staff();
    if($me){
        $config = storage::get_data('system_config')->db_configs;
        $db = db::get_connection($config);
        $ok = 'error';    
        if(isset($_POST['p_slips'])){
            $slist = implode(',', array_values($_POST['p_slips']));
            $slips = $db->select('salary_slip', "salary_slip.*, full_name")
                        ->join('user_accounts','salary_slip.employee=user_accounts.user_id')
                        ->where("slip_id IN ({$slist})")
                        ->order_by('slip_id', 'desc')->fetchAll();
            $addData = [  
                'payment_slips'=>json_encode($slips), 
                'owner_branch'=>$me['work_location'],
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
            //var_dump($db->error()); 
            if(isset($_POST['ajax_request'])){
            die(json_encode(['status'=>$ok, 'message'=>$msg]));
            }
        }
        
        if(isset($_POST['delete_payroll'])){
            $db->delete('payroll')->where(['payroll_id'=>intval($_POST['delete_payroll'])])->commit();
            $ok = 'error';
            if(!$db->error()) {
                $msg = 'Payroll deleted successful';
                $ok = 'success';
            }
            else $msg = $db->error()['message'];
            if(isset($_POST['ajax_request'])){
                die(json_encode(['status'=>$ok, 'message'=>$msg]));
            }
        }
        if($me['work_location'] == $moduleconfig->headquarters_branch) {
            $whr = 1;
        }
        else{
            $whr = ['owner_branch'=>$me['work_location']];
        }
        $payroll = $db->select('payroll', 'payroll.*')
                    ->join('staff','created_by=user_reference')
                    ->where($whr)
                    ->order_by('payroll_id', 'desc')->fetchAll();

        if($me['work_location'] == $moduleconfig->headquarters_branch) {
            $whr = 1;
        }
        else{
            $whr = ['work_location'=>$me['work_location']];
        }
        $slips = $db->select('salary_slip')
                ->join('user_accounts','user_id=employee')
                ->join('staff','user_id=user_reference')
                ->join('branches', 'work_location=branch_id', 'left')
                ->where($whr)
                ->order_by('branch_id, slip_id', 'desc')->fetchAll();

        //var_dump($db->error(), $slips);
        ob_start();
        include __DIR__.'/html/payroll.html';
        $body = ob_get_clean();
        $return = ['title'=>' ','body'=>$body];
    }
    else $return = ['title'=>'User not staff','body'=>'You must be a staff member to view'];