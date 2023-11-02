<?php 

$config = storage::get_data('system_config')->db_configs;
$db = db::get_connection($config);
if(isset($_POST['leave_description'])){
    $ok = 'error';
        $addData = [
            'leave_type'=>intval( $_POST['leave_type'] ),
            'leave_start'=>intval( $_POST['leave_start'] ), 
            'leave_length'=>intval( $_POST['leave_length'] ), 
            'responsible_assignee'=>intval( $_POST['responsible_assignee'] ), 
            'leave_description'=>intval( $_POST['leave_description'] ),    
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
$leave  = $db->select('leave_application')
              ->join('user_accounts','user_id=requester')
              ->fetchAll();
ob_start();
include __DIR__.'/html/leave.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];