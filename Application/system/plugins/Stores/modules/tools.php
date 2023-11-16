<?php 
$me = human_resources::get_staff();
if($me){
    $config = storage::get_data('system_config')->db_configs;
    $db = db::get_connection($config);
    if(isset($_POST['tool_name'])){
        //var_dump($_POST);
        $data = [
            'owner_branch'=>$me['work_location'],
            'tool_name'=>$_POST['tool_name'], 
            'tool_description'=>$_POST['tool_description'], 
            'tool_group'=>$_POST['tool_group'], 
            'tool_atatus'=>$_POST['tool_status'], 
            'tool_date_purchased'=>$_POST['tool_date_purchased'],  
            'created_time'=>date('Y-m-d H:i:s')
        ];
        if(isset($_POST['tool_id']) && intval($_POST['tool_id']) > 0){
            $k = intval($_POST['tool_id']);
            $db->update('tools', $data)->where(['tool_id'=>$_POST['tool_id']])->commit();
            //var_dump($db->error());
        }
        else $k = $db->insert('tools', $data);
        //var_dump($k);
        if(!$db->error() && $k) {
            $msg = 'Tool saved successful';
            $ok =true;
        }
        else $msg = $db->error()['message'];
        //var_dump($db->error()); 
    }
    //var_dump($_POST);
    //var_dump($db->error());
    if(isset($_POST['delete_tool'])){
        $db->delete('tools')->where(['user_reference'=>intval($_POST['delete_tool'])])->commit();
        if(!$db->error()) $db->delete('user_accounts')->where(['user_id'=>intval($_POST['delete_tool'])])->commit();
        if(!$db->error()){
            $msg = [
                'status'=>'success',
                'message'=>'Tool deleted successful'
            ];
        }
        else{
            $msg = [
                'status'=>'error',
                'message'=>$db->error()['message']
            ];
        }
    } 
    $tool = $db->select('tools')
                    ->join('branches', 'branch_id=owner_branch', 'left')
                    ->where(1)
                    ->fetchAll();
    //var_dump($db->error(), $tool);
    $sortedTool = [];
    foreach($tool as $st){
        if(!isset($sortedTool[$st['branch_name']])) $sortedTool[$st['branch_name']] = [];
        $sortedTool[$st['branch_name']][] = $st;
    }

    $body = '';
    ob_start();
    include __DIR__.'/html/tool.html';
    $body = ob_get_clean();
    $return = ['title'=>' ','body'=>$body];
}