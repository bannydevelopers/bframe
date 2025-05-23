<?php 
$is_headquarters = $me['work_location'] == human_resources::get_headquarters_branch();

$whr = $is_headquarters ? 1 : ['owner_branch'=>$me['work_location']];
if(isset($_POST['activity_project'])){
    $data = [
        'activity_name'=>addslashes($_POST['activity_name'] ?? ''), 
        'activity_creator'=>$me['user_reference'], 
        'activity_description'=>addslashes($_POST['activity_description'] ?? ''), 
        'activity_project'=>intval($_POST['activity_project'])
    ];
    if(isset($_POST['activity_id'])){
        $status =['activity_status'=>$_POST['activity_status']];
       
      $db->update('project_activities', $status)->where(['activity_id'=>intval($_POST['activity_id'])])->commit();
    }
    else
        $db->insert('project_activities', $data);

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

$project_activities = $db->select('project_activities')
                        ->join('projects', 'project_id=activity_project')
                        ->join('branches', 'branch_id=projects.owner_branch')
                        ->join('user_accounts', 'user_id=activity_creator')
                        ->and('activity_parent != 0')
                        ->where($whr)->fetchAll();
                       
$projects = $db->select('projects', 'project_id, project_name')->where($whr)->fetchAll();

$sortedProjects = [];
foreach($project_activities as $proj){
    if(!isset($sortedProjects[$proj['branch_name']])) {
        $sortedProjects[$proj['branch_name']] = [];
    }
    $sortedProjects[$proj['branch_name']][] = $proj;
}

ob_start();
include __DIR__.'/html/activities.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];