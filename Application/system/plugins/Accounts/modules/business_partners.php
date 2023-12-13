<?php 
$me = human_resources::get_staff();
if($me){
    $config = storage::get_data('system_config')->db_configs;
    $db = db::get_connection($config);
    if(isset($_POST['partiner_name'])){
        //var_dump($_POST);
        $data = [
            'owner_branch'=>$me['work_location'],
            'business_partiner_name'=>$_POST['partiner_name'], 
            'business_partiner_phone_number'=>$_POST['partiner_phone_number'], 
            'business_partiner_email'=>$_POST['partiner_email'],
            'business_partiner_details'=>$_POST['partiner_details'],
            'business_partiner_physical_adress'=>$_POST['partiner_physical_adress']
        ];
        //var_dump($db->error());
        if(isset($_POST['business_partiner_id']) && intval($_POST['business_partiner_id']) > 0){
            $k = intval($_POST['business_partiner_id']);
            $db->update('business_partiner', $data)->where(['business_partiner_id'=>$_POST['business_partiner_id']])->commit();
        }
        else $k = $db->insert('business_partiner', $data);
        $ok = 'error';
        if(!$db->error() && $k) {
            $msg = 'Partiner saved successful';
            $ok ='success';
        }
        else $msg = $db->error()['message']; 
        if(isset($_POST['ajax_request'])) die($msg);
    }
    
    if(isset($_POST['delete_business_partiner'])){
        $k = $db->delete('business_partiner')->where(['business_partiner_id'=>intval($_POST['delete_business_partiner'])])->commit();
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
    $business_partiner = $db->select('business_partiner')
                    ->join('branches', 'branch_id=owner_branch')
                    ->where($whr)
                    ->order_by('business_partiner_id', 'desc')
                    ->fetchAll();
    $sortedPartiner = [];
    foreach($business_partiner as $st){
        if(!isset($sortedPartiner[$st['branch_name']])) $sortedPartiner[$st['branch_name']] = [];
        $sortedPartiner[$st['branch_name']][] = $st;
    }

    $body = '';
    ob_start();
    include __DIR__.'/html/partiner.html';
    $body = ob_get_clean();
    $return = ['title'=>' ','body'=>$body];
}