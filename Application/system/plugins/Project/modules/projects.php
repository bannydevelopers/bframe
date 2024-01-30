<?php 
$is_headquarters = $me['work_location'] == human_resources::get_headquarters_branch();

$whr = $is_headquarters ? 1 : ['owner_branch'=>$me['work_location']];
if(isset($_POST['project_name'])){
    $data = [
        'project_name'=>addslashes($_POST['project_name']),
        'owner_branch'=>intval($me['work_location']),
        'project_client'=>intval($_POST['project_client']),
        'project_invoice'=>0,
        'project_manager'=>intval($_POST['project_manager']),
        'project_budget'=>intval($_POST['project_budget']),
        'project_location'=>addslashes($_POST['project_location']),
        'project_start'=>addslashes($_POST['project_start']),
        'project_end'=>addslashes($_POST['project_end']),
        'project_desc'=>addslashes($_POST['project_desc']),
        'created_by'=>$me['user_reference']
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

$managers = $db->select('staff', 'user_accounts.full_name, user_accounts.user_id')
                ->join('user_accounts', 'user_reference=user_id')
                ->where($whr)
                ->and(['system_role'=>$moduleconfig->project_manager_role])
                ->fetchAll();


$whr = $is_headquarters ? 1 : "projects.owner_branch={$me['work_location']}";
    $base_url = implode(
        '/', 
        [
            $registry->request[0],
            $registry->request[1],
            $registry->request[2],
            $registry->request[3]
        ]
    );
if(isset($registry->request[4])){
    $project = $db->select('projects', 'projects.*, user_accounts.full_name as manager, branches.*, customer.*, uc.full_name as creator')
                    ->join('user_accounts','user_accounts.user_id=projects.project_manager')
                    ->join('branches','owner_branch=branch_id')
                    ->join('customer','project_client=customer_id')
                    ->join('user_accounts as uc','uc.user_id=projects.created_by')
                    ->where(['project_id'=>$registry->request[4]])
                    ->fetch();

    $_q['tools'] = "(
                SELECT JSON_ARRAYAGG( 
                    JSON_OBJECT( 'id', tool_id, 'name', tool_name ) 
                ) FROM tools 
                JOIN project_resources_approved ON tool_id=pra_allocated_resource 
                WHERE pra_resource=resource_id 
            ) AS items";
            

    $_q['staff'] = "(
                    SELECT JSON_ARRAYAGG( 
                        JSON_OBJECT( 'id', user_id, 'name', full_name ) 
                    ) FROM user_accounts 
                    JOIN project_resources_approved ON tool_id=pra_allocated_resource 
                    WHERE pra_resource=resource_id 
                ) AS items";

   $_q['products'] = "(
                    SELECT JSON_ARRAYAGG( 
                        JSON_OBJECT( 'id', product_id, 'name', product_name ) 
                    ) FROM product 
                    JOIN project_resources_approved ON tool_id=pra_allocated_resource 
                    WHERE pra_resource=resource_id 
                ) AS items";
    
    $rt = $db->select('project_resources', 'resource_type as rtype')
             ->where(['resource_project'=>$registry->request[4]])
             ->fetch();
    if(isset($rt['rtype'])) 
    {
        $sub_q = $_q[$rt['rtype']];
        $resources = $db->select('project_resources', "project_resources.*, {$sub_q}")
                        ->where(['resource_project'=>$registry->request[4]])
                        ->fetchAll();
    }
    else{
        $resources = [];
    }

    ob_start();
    include __DIR__.'/html/project.html';
    $body = ob_get_clean();
}
else{
    $projects = $db->select('projects', 'projects.*, user_accounts.full_name as manager, branches.*, customer.*, uc.full_name as creator')
                    ->join('user_accounts','user_accounts.user_id=projects.project_manager')
                    ->join('branches','owner_branch=branch_id')
                    ->join('customer','project_client=customer_id')
                    ->join('user_accounts as uc','uc.user_id=projects.created_by')
                    ->where($whr)
                    ->fetchAll();

    $sortedProjects = [];
    foreach($projects as $proj){
        if(!isset($sortedProjects[$proj['branch_name']])) $sortedProjects[$proj['branch_name']] = [];
        $sortedProjects[$proj['branch_name']][] = $proj;
    }

    ob_start();
    include __DIR__.'/html/projects.html';
    $body = ob_get_clean();
}
$return = ['title'=>' ','body'=>$body];