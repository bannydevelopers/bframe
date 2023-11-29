<?php 

$me = human_resources::get_staff();
if($me){
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
    if(isset($_FILES['staff_list'])){
        $fh = file_get_contents($_FILES['staff_list']['tmp_name']); // get contents of uploaded file
        //$lines = explode(PHP_EOL,$fh); //split lines
        $lines = preg_split('/\n|\r/', $fh, -1, PREG_SPLIT_NO_EMPTY); // split lines securely
        $system_role = $bank = $designation = $work_location = $department = [];
        //0-full_name, 1-email, 2-phone,3-system_role,4-bank,5-bank_account_number,6-registration_number,7-residence_address,
        //8-designation,9-work_location,10-department,11-date_employed,12-employment_length,13-employment_last_renewal,14employment_end_date
        foreach($lines as $k=>$line) {
            if(empty($line)) continue;
            $lines[$k] = explode(',', $line); // convert string to array
            if(count($lines[0]) != 15) die('Invalid file uploaded');
            if($k == 0) continue;
            $lines[$k][11] = date('Y-m-d', strtotime($lines[$k][11]));
            $lines[$k][13] = date('Y-m-d', strtotime($lines[$k][13]));
            $lines[$k][14] = date('Y-m-d', strtotime($lines[$k][14]));
            $system_role[] = trim($lines[$k][3]);
            $bank[] = trim($lines[$k][4]);
            $designation[] = trim($lines[$k][8]);
            $work_location[] = trim($lines[$k][9]);
            $department[] = trim($lines[$k][10]);
        }
        $system_roles = $db->select('roles','role_id,role_name')
                            ->where('role_name')->in($system_role)->fetchAll();
                            
        $banks = $db->select('banks','bank_id,bank_name')
                    ->where('bank_name')->in($bank)->fetchAll();

        $designations = $db->select('designations','designation_id,designation_name')
                            ->where('designation_name')->in($designation)->fetchAll();

        $work_locations = $db->select('branches','branch_id,branch_name')
                            ->where('branch_name')->in($work_location)->fetchAll();

        $departments = $db->select('departments','dept_id,dept_name')
                        ->where('dept_name')->in($department)->fetchAll();
        $msg = 'Unknown error occured, probably file format not recognized';
        $status = 'error';
        $last_line = [];
        if(!$db->error()){
            $qry = 'BEGIN;'.PHP_EOL;
            foreach($lines as $k => $line){
                if($k == 0 or empty($line[0])) continue;

                $last_line = $line;
                $role = array_search($line[3], array_column($system_roles, 'role_id'));
                $role_id = $system_roles[$role]['role_id'];

                $pass = system::create_hash($line[1]);
                $qry .= "INSERT INTO user_accounts (full_name, email, phone, system_role, passcode) VALUES ('{$line[0]}','{$line[1]}','{$line[2]}','{$role_id}','{$pass}');".PHP_EOL;
                
                $bnk = array_search($line[4], array_column($banks, 'bank_id'));
                $bank_id = $banks[$bnk]['bank_id'];
                
                $desn = array_search($line[4], array_column($designations, 'designation_id'));
                $designation_id = $designations[$desn]['designation_id'];
                
                $wl = array_search($line[4], array_column($work_locations, 'branch_id'));
                $branch_id = $work_locations[$wl]['branch_id'];
                if($me['work_location'] != $moduleconfig->headquarters_branch) $branch_id = $me['work_location'];
                
                $dept = array_search($line[4], array_column($departments, 'dept_id'));
                $dept_id = $departments[$dept]['dept_id'];

                $qry .= "INSERT INTO staff (user_reference, bank, bank_account_number, registration_number, residence_address,designation, work_location, department, date_employed, employment_length, employment_last_renewal, employment_end_date) VALUES (LAST_INSERT_ID(), '{$line[4]}','{$line[5]}','{$line[6]}','{$line[7]}','{$designation_id}','{$branch_id}','{$dept_id}','{$line[11]}','{$line[12]}','{$line[13]}','{$line[14]}');".PHP_EOL;
            }
            $qry .= 'COMMIT;';

            $db->query($qry);
            $chk = $db->select('staff', 'user_reference')
                    ->where("user_reference IN (SELECT user_id FROM user_accounts user_id WHERE email ='{$last_line[1]}')")
                    ->fetch();

            if(!$db->error() && $chk) {
                $msg = 'Staff import complete';
                $status = 'success';
            }
            else $msg = 'Import failed';
        }
        die(json_encode(['message'=>$msg, 'status'=>$status]));
    }
    //var_dump($me);
    if($me['work_location'] == human_resources::get_headquarters_branch()) {
        $whr = 1;
    }
    else{
        $whr = ['work_location'=>$me['work_location']];
    }
    $staff = $db->select('staff')
                ->join('user_accounts','user_id=user_reference')
                ->join('roles', 'system_role=role_id', 'left')
                ->join('designations', 'designation=designation_id', 'left')
                ->join('departments', 'department=dept_id', 'left')
                ->join('branches', 'work_location=branch_id', 'left')
                ->join('banks', 'bank=bank_id', 'left')
                ->where($whr)
                ->order_by('staff_id', 'desc')
                ->fetchAll();
    $sortedStaff = [];
    foreach($staff as $st){
        if(!isset($sortedStaff[$st['designation_name']])) $sortedStaff[$st['designation_name']] = [];
        $sortedStaff[$st['designation_name']][] = $st;
    }
    $body = '';

    $designations = $db->select('designations')->fetchAll();
    $departments = $db->select('departments')->fetchAll();

    if($whr != 1) $whr = ['branch_id'=>$me['work_location']];
    $branches = $db->select('branches')->where($whr)->fetchAll();
    $banks = $db->select('banks')->fetchAll();
    $roles = $db->select('roles')->fetchAll();
    ob_start();
    include __DIR__.'/html/staff.html';
    $body = ob_get_clean();
    $return = ['title'=>' ','body'=>$body];
}
else $return = ['title'=>'User not staff','body'=>'You must be a staff member to view'];