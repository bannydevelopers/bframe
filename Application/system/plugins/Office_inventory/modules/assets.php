<?php 
$me = human_resources::get_staff();
if($me){
    $config = storage::get_data('system_config')->db_configs;
    $db = db::get_connection($config);
    if(isset($_POST['store_name'])){
        //var_dump($_POST);
        $data = [
            'owner_branch'=>$me['work_location'],
            'store_name'=>$_POST['store_name'], 
            'store_location'=>$_POST['store_location'], 
            'created_time'=>date('Y-m-d H:i:s')
        ];
        if(isset($_POST['store_id']) && intval($_POST['store_id']) > 0){
            $k = intval($_POST['store_id']);
            $db->update('store', $data)->where(['store_id'=>$_POST['store_id']])->commit();
            //var_dump($db->error());
        }
        else $k = $db->insert('store', $data);
        //var_dump($k);
        if(!$db->error() && $k) {
            $msg = 'Store saved successful';
            $ok =true;
        }
        else $msg = $db->error()['message'];
        //var_dump($db->error()); 
    }
    if(isset($_POST['delete_store'])){
        $db->delete('stores')->where(['user_reference'=>intval($_POST['delete_store'])])->commit();
        if(!$db->error()) $db->delete('user_accounts')->where(['user_id'=>intval($_POST['delete_store'])])->commit();
        if(!$db->error()){
            $msg = [
                'status'=>'success',
                'message'=>'Store deleted successful'
            ];
        }
        else{
            $msg = [
                'status'=>'error',
                'message'=>$db->error()['message']
            ];
        }
    } 
    $store = $db->select('store')
                    ->join('branches', 'branch_id=owner_branch', 'left')
                    ->join('user_accounts', 'user_id=store_keeper')
                    ->fetchAll();
                    
    $whr = 'store.store_ref < 1';
    if($me['work_location'] != human_resources::get_headquarters_branch()) {
        $whr .= " AND store.owner_branch={$me['work_location']}";
    }
    $sortedStore = [];
    foreach($store as $st){
        if(!isset($sortedStore[$st['branch_name']])) $sortedStore[$st['branch_name']] = [];
        $sortedStore[$st['branch_name']][] = $st;
    }
    $body = '';
    ob_start();
    include __DIR__.'/html/store.html';
    $body = ob_get_clean();
    $return = ['title'=>' ','body'=>$body];
}