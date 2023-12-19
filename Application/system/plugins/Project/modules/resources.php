<?php 
$is_headquarters = $me['work_location'] == human_resources::get_headquarters_branch();

$whr = $is_headquarters ? 1 : ['owner_branch'=>$me['work_location']];
if(isset($_POST['resource_project'])){
    $data = [
        'resource_type'=>addslashes($_POST['resource_type']), 
        'resource_quantity'=>intval($_POST['resource_quantity']), 
        'resource_requester'=>$me['user_reference'], 
        'request_description'=>addslashes($_POST['request_desc']),
        'resource_project'=>intval($_POST['resource_project']), 
        'resource_status'=>'requested', 
        'request_date'=>date('Y-m-d H:i:s')
    ];
    if(isset($_POST['project_id']))
      $db->update('project_resources', $data)->where(['project_id'=>intval($_POST['project_id'])])->commit();
    else
        $db->insert('project_resources', $data);

    if(!$db->error()) $msg = 'Saved successfully';
    else $msg = $db->error();

    if(isset($_POST['ajax_request'])){
        $icon = !$db->error() ? 'success' : 'error';
        die(json_encode(['status'=>$icon, 'message'=>$msg]));
    }
}


$whr = $is_headquarters ? 1 : ['work_location'=>$me['work_location']];

$staff = $db->select('staff', 'user_accounts.full_name, user_accounts.user_id')
                ->join('user_accounts', 'user_reference=user_id')
                ->where($whr)
                ->and(['system_role'=>$moduleconfig->project_manager_role])
                ->fetchAll();

$whr = $is_headquarters ? 1 : ['owner_branch'=>$me['work_location']];

$products = $db->select('product')->where($whr)->fetchAll();

$project_resources = $db->select('project_resources')
                        ->join('projects', 'project_id=resource_project')
                        ->join('branches', 'branch_id=projects.owner_branch')
                        ->join('user_accounts', 'user_id=resource_requester')
                        ->where($whr)->fetchAll();

$sortedProjects = [];
foreach($project_resources as $proj){
    if(!isset($sortedProjects[$proj['branch_name']])) {
        $sortedProjects[$proj['branch_name']] = ['requested'=>[], 'approved'=>[], 'issued'=>[], 'returned'=>[]];
    }
    $sortedProjects[$proj['branch_name']][$proj['resource_status']][] = $proj;
}

ob_start();
include __DIR__.'/html/resources.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];