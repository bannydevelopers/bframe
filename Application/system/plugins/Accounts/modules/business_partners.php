<?php 
$me = human_resources::get_staff();
if($me){
    $config = storage::get_data('system_config')->db_configs;
    $db = db::get_connection($config);
    if(isset($_POST['partiner_name'])){
        //var_dump($_POST);
        $data = [
            'owner_branch'=>$me['work_location'],
            'partiner_name'=>$_POST['partiner_name'], 
            'partiner_phone_number'=>$_POST['partiner_phone_number'], 
            'partiner_email'=>$_POST['partiner_email'], 
            'partiner_physical_adress'=>$_POST['partiner_physical_adress']
        ];
        //var_dump($db->error());
        if(isset($_POST['partiner_id']) && intval($_POST['partiner_id']) > 0){
            $k = intval($_POST['partiner_id']);
            $db->update('partiner', $data)->where(['partiner_id'=>$_POST['partiner_id']])->commit();
        }
        else $k = $db->insert('partiner', $data);
        $ok = 'error';
        if(!$db->error() && $k) {
            $msg = 'Partiner saved successful';
            $ok ='success';
        }
        else $msg = $db->error()['message']; 
        if(isset($_POST['ajax_request'])) die($msg);
    }
    
    if(isset($_POST['delete_partiner'])){
        $k = $db->delete('partiner')->where(['partiner_id'=>intval($_POST['delete_partiner'])])->commit();
        if(!$db->error() && $k){
            $msg = [
                'status'=>'success',
                'message'=>'Partiner deleted successful'
            ];
        }
        else{
            $msg = [
                'status'=>'error',
                'message'=>$db->error()['message']
            ];
        }
        if(isset($_POST['ajax_request'])) die(json_encode($msg));
    }
    if($me['work_location'] == human_resources::get_headquarters_branch()) {
        $whr = 1;
    }
    else{
        $whr = ['owner_branch'=>$me['work_location']];
    }
    $partiner = $db->select('partiner')
                    ->join('branches', 'branch_id=owner_branch')
                    ->where($whr)
                    ->order_by('partiner_id', 'desc')
                    ->fetchAll();
    $sortedPartiner = [];
    foreach($partiner as $st){
        if(!isset($sortedPartiner[$st['branch_name']])) $sortedPartiner[$st['branch_name']] = [];
        $sortedPartiner[$st['branch_name']][] = $st;
    }

    $body = '';
    ob_start();
    include __DIR__.'/html/partiner.html';
    $body = ob_get_clean();
    $return = ['title'=>' ','body'=>$body];
}