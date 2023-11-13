<?php 
$me = human_resources::get_staff();
if($me){
    $config = storage::get_data('system_config')->db_configs;
    $db = db::get_connection($config);
    if(isset($_POST['delete_customer'])){
        $db->delete('customer')->where(['user_reference'=>intval($_POST['delete_customer'])])->commit();
        if(!$db->error()) $db->delete('user_accounts')->where(['user_id'=>intval($_POST['delete_customer'])])->commit();
        if(!$db->error()){
            $msg = [
                'status'=>'success',
                'message'=>'Customer deleted successful'
            ];
        }
        else{
            $msg = [
                'status'=>'error',
                'message'=>$db->error()['message']
            ];
        }
        die(json_encode( ["response"=>$msg])); 
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
                $qry .= "INSERT INTO user_accounts (full_name, email, phone, passcode) VALUES ('{$line[0]}','{$line[1]}','{$line[2]}','{$pass}');".PHP_EOL;

                $qry .= "INSERT INTO customer (user_reference, bank, bank_account_number, registration_number, residence_address,designation, work_location, department, date_employed, employment_length, employment_last_renewal, employment_end_date) VALUES (LAST_INSERT_ID(), '{$line[4]}','{$line[5]}','{$line[6]}','{$line[7]}','{$designation_id}','{$branch_id}','{$dept_id}','{$line[11]}','{$line[12]}','{$line[13]}','{$line[14]}');".PHP_EOL;
            }
            $qry .= 'COMMIT;';

            $db->query($qry);
            $chk = $db->select('customer', 'user_reference')
                    ->where("user_reference IN (SELECT user_id FROM user_accounts user_id WHERE email ='{$last_line[1]}')")
                    ->fetch();

            if(!$db->error() && $chk) {
                $msg = 'Customer import complete';
                $status = 'success';
            }
            else $msg = 'Import failed';
        }
        die(json_encode(['message'=>$msg, 'status'=>$status]));
    }
    //var_dump($me);
    $customer = [];
    $sortedCustomer = [];
    foreach($customer as $st){
        if(!isset($sortedCustomer[$st['designation_name']])) $sortedCustomer[$st['designation_name']] = [];
        $sortedCustomer[$st['designation_name']][] = $st;
    }
    $body = '';
    ob_start();
    include __DIR__.'/html/customer.html';
    $body = ob_get_clean();
    $return = ['title'=>' ','body'=>$body];
}