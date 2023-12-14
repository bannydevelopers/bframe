<?php 
$me = human_resources::get_staff();
if($me){
    $config = storage::get_data('system_config')->db_configs;
    $db = db::get_connection($config);
    if(isset($_POST['asset_name'])){
        //var_dump($_POST);
        $data = [
            'owner_branch'=>$me['work_location'],
            'asset_name'=>addslashes($_POST['asset_name']), 
            'asset_no'=>addslashes($_POST['asset_no']),
            'asset_status'=>addslashes($_POST['asset_status'])
        ];
        if(isset($_POST['asset_id']) && intval($_POST['asset_id']) > 0){
            $k = intval($_POST['asset_id']);
            $db->update('assets', $data)->where(['asset_id'=>$_POST['asset_id']])->commit();
            //var_dump($db->error());
        }
        else $k = $db->insert('assets', $data);
        //var_dump($k);
        if(!$db->error() && $k) {
            $msg = 'Assets saved successful';
            $ok =true;
        }
        else $msg = $db->error()['message'];
        //var_dump($db->error()); 
    }
    if(isset($_POST['delete_asset'])){
        $db->delete('assets')->where(['user_reference'=>intval($_POST['delete_asset'])])->commit();
        if(!$db->error()) $db->delete('user_accounts')->where(['user_id'=>intval($_POST['delete_asset'])])->commit();
        if(!$db->error()){
            $msg = [
                'status'=>'success',
                'message'=>'Assets deleted successful'
            ];
        }
        else{
            $msg = [
                'status'=>'error',
                'message'=>$db->error()['message']
            ];
        }
    } 
    $asset = $db->select('assets')
                    ->join('branches', 'branch_id=owner_branch', 'left')
                    ->fetchAll();
     //var_dump($db->error()); 

    $whr = 'asset.asset_ref < 1';
    if($me['work_location'] != human_resources::get_headquarters_branch()) {
        $whr .= " AND asset.owner_branch={$me['work_location']}";
    }
    $sortedAsset = [];
    foreach($asset as $st){
        if(!isset($sortedAsset[$st['branch_name']])) $sortedAsset[$st['branch_name']] = [];
        $sortedAsset[$st['branch_name']][] = $st;
    }
    $body = '';
    ob_start();
    include __DIR__.'/html/asset.html';
    $body = ob_get_clean();
    $return = ['title'=>' ','body'=>$body];
}