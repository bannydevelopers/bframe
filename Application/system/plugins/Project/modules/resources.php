<?php 
$is_headquarters = $me['work_location'] == human_resources::get_headquarters_branch();

$whr = $is_headquarters ? 1 : ['owner_branch'=>$me['work_location']];
if(isset($_POST['project_name'])){
    $data = [
        `resource_type`, 
        `resource_reference`, 
        `resource_quantity`, 
        `resource_requester`, 
        `resource_activity`, 
        `resource_status`, 
        `resource_approver`, 
        `request_date`, 
        `approve_date`
    ];
    if(isset($_POST['project_id']))
      $db->update('projects', $data)->where(['project_id'=>intval($_POST['project_id'])])->commit();
    else
        $db->insert('projects', $data);

    if(!$db->error()) $msg = 'Saved successfully';
    else $msg = $db->error();

    if(isset($_POST['ajax_request'])){
        $icon = !$db->error() ? 'success' : 'error';
        die(json_encode(['status'=>$icon, 'message'=>$msg]));
    }
}


$customer = $db->select('customer')->where($whr)->fetchAll();

$whr = $is_headquarters ? 1 : ['work_location'=>$me['work_location']];

$staff = $db->select('staff', 'user_accounts.full_name, user_accounts.user_id')
                ->join('user_accounts', 'user_reference=user_id')
                ->where($whr)
                ->and(['system_role'=>$moduleconfig->project_manager_role])
                ->fetchAll();

$whr = $is_headquarters ? 1 : ['owner_branch'=>$me['work_location']];

$products = $db->select('product')->where($whr)->fetchAll();
var_dump($db->error());
$projects = $db->select('projects')->where($whr)->fetchAll();
                
$sortedProjects = [];
/*foreach($projects as $proj){
    if(!isset($sortedProjects[$proj['branch_name']])) $sortedProjects[$proj['branch_name']] = [];
    $sortedProjects[$proj['branch_name']][] = $proj;
}*/

ob_start();
include __DIR__.'/html/resources.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];