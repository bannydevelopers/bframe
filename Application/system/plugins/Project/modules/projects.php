<?php
$is_headquarters = isset($me['work_location'])? $me['work_location'] == human_resources::get_headquarters_branch():false;

$company = [];
$company = storage::get_data('system_config')->company_profile;
if(isset($me['work_location']) ){
$ti = $db->select('invoice', "invoice_id, customer_name")
    ->join('customer', 'customer_id=customer')
    ->join('tax_invoice', 'invoice_id=reference_invoice')
    ->where("invoice.owner_branch={$me['work_location']}")
    ->fetchAll();
}

if (isset($_POST['action']) && $_POST['action'] == 'DELETE' && isset($_POST['activity_id'])) {

    $activity_id = intval($_POST['activity_id']);
    $db->delete('project_activities')->where(['activity_parent' => $activity_id])->commit();
    $db->delete('project_activities')->where(['activity_id' => $activity_id])->commit();
    if (!$db->error()) $msg = 'Deleted successfully';
    else $msg = $db->error();

    if (isset($_POST['ajax_request'])) {
        $icon = !$db->error() ? 'success' : 'error';
        die(json_encode(['status' => $icon, 'message' => $msg]));
    }
}

if (isset($_POST['action']) && $_POST['action'] == 'DELETE' && isset($_POST['project_id'])) {

    $activity_id = intval($_POST['project_id']);
    $db->delete('projects')->where(['project_id' => $activity_id])->commit();
    if (!$db->error()) $msg = 'Deleted successfully';
    else $msg = $db->error();

    if (isset($_POST['ajax_request'])) {
        $icon = !$db->error() ? 'success' : 'error';
        die(json_encode(['status' => $icon, 'message' => $msg]));
    }
}

if (isset($_POST['action']) && $_POST['action'] == 'DELETE' && isset($_POST['task_id'])) {

$task_id = intval($_POST['task_id']);
$db->delete('project_activities_sub_tasks')->where(['id' => $task_id])->commit();
if (!$db->error()) $msg = 'Deleted successfully';
else $msg = $db->error();

if (isset($_POST['ajax_request'])) {
    $icon = !$db->error() ? 'success' : 'error';
    die(json_encode(['status' => $icon, 'message' => $msg]));
}
}

if (isset($_POST['action']) && $_POST['action'] == 'DELETE' && isset($_POST['comment_id'])) {

    $task_id = intval($_POST['comment_id']);
    $db->delete('project_clients_comments')->where(['id' => $task_id])->commit();
    if (!$db->error()) $msg = 'Deleted successfully';
    else $msg = $db->error();
    
    if (isset($_POST['ajax_request'])) {
        $icon = !$db->error() ? 'success' : 'error';
        die(json_encode(['status' => $icon, 'message' => $msg]));
    }
    }

if (isset($_POST['project_name'])) {
    $data = [
        'project_name' => addslashes($_POST['project_name']),
        'owner_branch' => intval($me['work_location']),
        'project_invoice' => intval($_POST['project_invoice']),
        'project_manager' => intval($_POST['project_manager']),
        'project_budget' => intval($_POST['project_budget']),
        'project_location' => addslashes($_POST['project_location']),
        'project_start' => addslashes($_POST['project_start']),
        'project_end' => addslashes($_POST['project_end']),
        'project_desc' => addslashes($_POST['project_desc']),
        'project_invoice' => intval($_POST['project_invoice']),
        'created_by' => $me['user_reference']
    ];
    if (isset($_POST['project_id']) && !empty($_POST['project_id']))
        $db->update('projects', $data)->where(['project_id' => intval($_POST['project_id'])])->commit();
    else
        $db->insert('projects', $data);

    if (!$db->error()) $msg = 'Saved successfully';
    else $msg = $db->error();

    if (isset($_POST['ajax_request'])) {
        $icon = !$db->error() ? 'success' : 'error';
        die(json_encode(['status' => $icon, 'message' => $msg]));
    }
}

//GET details for updating sub activity

if (isset($_GET['fetch_activity']) && $_GET['fetch_activity'] == 1) {
    $activity_id = $_GET['activity_id'];
    $assigned_user = [];

    $activity = $db->select('project_activities')->where(['activity_id' => $activity_id])->fetch();
    $assigned = $db->select('project_activities_assignees')->where(['project_activity_id' => $activity_id])->fetchAll();

    foreach ($assigned as $value) {
        $assigned_user[] = $value['user_assigned'];
    }

    $activity['user_assigned'] = $assigned_user;
    echo json_encode($activity);
    exit;
}
//End of subactivity

//Endpoint for fetching Daily Activities associated with an activity
if (isset($_GET['fetch_tasks']) && $_GET['fetch_tasks'] == 1) {
    $activity_id = $_GET['activity_id'];
    $assigned_user = [];

    $tasks = $db->select('project_activities_sub_tasks as p','p.*,u.full_name,u.user_id,u.email')
    ->join('user_accounts as u','user_id=recorded_by')
    ->where(['project_activity_id' => $activity_id])->fetchAll();
    echo json_encode($tasks);
    exit;
}
//Endpoint to get single daily actiivity details for editing
if (isset($_GET['pull_task']) && $_GET['pull_task'] == 1) {
    $task_id = $_GET['task_id'];

    $activity = $db->select('project_activities_sub_tasks')->where(['id' => $task_id])->fetch();
    echo json_encode($activity);
    exit;
}
//End of daily activities

//Endpoint to get project details for editing
if (isset($_GET['fetch_project']) && $_GET['fetch_project'] == 1) {
    $project_id = $_GET['project_id'];

    $activity = $db->select('projects')->where(['project_id' => $project_id])->fetch();
    echo json_encode($activity);
    exit;
}
//End of project Details

//Get Project Products involved
if (isset($_GET['fetch_product']) && $_GET['fetch_product'] == 1) {
    $project_id = $_GET['project_id'];
    $project = $db->select('projects')->where(['project_id' => $project_id])->fetch();
    if (!empty($project)) {
        $products_ = $db->select('product')
            ->join('invoice_items as ii', 'ii.product = product.product_id')
            // ->where($whr)
            ->where("ii.invoice =" . $project['project_invoice'])
            ->fetchAll();


        echo json_encode($products_);
    }
    exit;
}
//End Of Project Product

//GET details for updating comment

if (isset($_GET['fetch_comment']) && $_GET['fetch_comment'] == 1) {
    $comment_id = $_GET['comment_id'];
    $comment = $db->select('project_clients_comments')->where(['id' => $comment_id])->fetch();
    echo json_encode($comment);
    exit;
}
//End fetch comment

if (isset($_POST['approved_resource'])) {
    if (isset($_POST['resource_item'])) {
        $qry = 'INSERT INTO project_resources_approved (pra_resource, pra_allocated_resource, pra_count) VALUES';
        $vals = [];
        foreach ($_POST['resource_item'] as $x => $ri) {
            if (!$_POST['resource_count'][$x]) $_POST['resource_count'][$x] = 'NULL';
            $vals[] = " ({$_POST['approved_resource']}, {$ri}, {$_POST['resource_count'][$x]})";
        }
        $qry .= implode(',', $vals);
        $k = $db->query($qry);
        if ($k  && !$db->error()) {
            $data = [
                'resource_status' => 'approved',
                'resource_approver' => $user['user_id'],
                'approve_date' => date('Y-m-d H:i:s')
            ];
            $db->update('project_resources', $data)
                ->where(['resource_id' => intval($_POST['approved_resource'])])
                ->commit();
        } else {
            $db->delete('project_resources_approved')->where(['pra_id' => $k])->commit();
        }
        if (!$db->error()) $msg = '<h3>Approved successful</h3>';
        else $msg = json_encode($db->error());
    } else $msg = 'Nothing to approve, please select some';
    die($msg);
}
if (isset($_POST['approve_resource_items'])) {
    $res = $db->select('project_resources')
        ->where(['resource_id' => intval($_POST['approve_resource_items'])])
        ->fetch();

    if ($res['resource_type'] == 'money') {
        $data = [
            'resource_status' => 'approved',
            'resource_approver' => $user['user_id'],
            'approve_date' => date('Y-m-d H:i:s')
        ];
        $db->update('project_resources', $data)
            ->where(['resource_id' => intval($_POST['approve_resource_items'])])
            ->commit();
        if (!$db->error()) $msg = '<h3>Approved successful</h3>';
        else $msg = $db->error()['message'];
        die($msg);
    } else if ($res['resource_type'] == 'tools') {
        $res_list = $db->select('tools', 'tool_name as name, tool_id as id')
            ->where(1)
            ->fetchAll();
    } else if ($res['resource_type'] == 'products') {
        $res_list = $db->select('product', 'DISTINCT(product_name) as name, product_id as id')
            ->join('invoice_items', 'invoice_items.product=product_id')
            ->join('invoice', 'invoice_items.invoice=invoice_id')
            ->join('projects', 'project_invoice=invoice_id')
            ->join('project_activities', 'activity_project=project_id')
            ->join('project_resources', 'resource_activity=activity_id')
            ->fetchAll();
    } else if ($res['resource_type'] == 'staff') {
        $res_list = $db->select('user_accounts', 'full_name as name, user_id as id')
            ->where(1)
            ->fetchAll();
    } else {
        die('Unknown resource type');
    }
    ob_start();
    include __DIR__ . '/html/resource_approve_entities.html';
    $msg = ob_get_clean();
    die($msg);
}
# Dealing with Daily Activities Logics are here
if (isset($_POST['activity_id']) && isset($_POST['daily'])) {
    $activity_id = intval($_POST['activity_id']);
    $db_percentage = $total_percentage = $total_activities = $activity_percent = 0;

    if(isset($_POST['task_status'])){
        $data['status']= $_POST['task_status'];
    } else{
    $data = [
        'recorded_by' => $me['user_reference'],
        'description' => htmlentities($_POST['description']),
        'project_activity_id' => $activity_id,
        'task_percentage' => doubleval($_POST['task_percentage'])
    ];
}
    if(isset($_POST['task_id'])){
        $task_id =intval($_POST['task_id']);

        if(!isset($_POST['task_status'])){
        $check_subtasks = $db->select('project_activities_sub_tasks')->where(['project_activity_id' => $activity_id])->and('id !='.$task_id)->fetchAll();
        $activity_percent = $db->select('project_activities', 'activity_percentage,activity_project')->where(['activity_id' => $activity_id])->fetch();
        if (!empty($check_subtasks)) {
            foreach ($check_subtasks as $check_sub) {
                $db_percentage += $check_sub['task_percentage'];
            }
        }
        $total_percentage = $db_percentage + doubleval($_POST['task_percentage']);
        if ($total_percentage > $activity_percent['activity_percentage']) {
            $msg = 'Only total of ' . $activity_percent['activity_percentage'] . '% can be allocated to this Activity, You are trying to allocate ' . $total_percentage . '%, Fix it and try again!';
            $icon = 'error';
            die(json_encode(['status' => $icon, 'message' => $msg]));
        }
    }
    
        $last = $db->update('project_activities_sub_tasks', $data)->where(['id'=>$task_id])->commit();
        
    }else{

    $check_subtasks = $db->select('project_activities_sub_tasks')->where(['project_activity_id' => $activity_id])->fetchAll();
    $activity_percent = $db->select('project_activities', 'activity_percentage,activity_project')->where(['activity_id' => $activity_id])->fetch();
    if (!empty($check_subtasks)) {
        foreach ($check_subtasks as $check_sub) {
            $db_percentage += $check_sub['task_percentage'];
        }
    }
    $total_percentage = $db_percentage + doubleval($_POST['task_percentage']);
    if ($total_percentage > $activity_percent['activity_percentage']) {
        $msg = 'Only total of ' . $activity_percent['activity_percentage'] . '% can be allocated to this Activity, You are trying to allocate ' . $total_percentage . '%, Fix it and try again!';
        $icon = 'error';
        die(json_encode(['status' => $icon, 'message' => $msg]));
    }

    $last = $db->insert('project_activities_sub_tasks', $data);
    $project_data= $db->select('projects')->where(['project_id'=>$activity_percent['activity_project']])->fetch();
    if(!$db->error() && $project_data) {
        $msg = 'Saved successfully';
        $url = 'plugin/Project/projects';
        $user = $db->select('user_accounts', 'user_id')
                    ->where(['user_id'=>$project_data['project_manager']])
                    ->fetch();
        $opts = [
                    'icon'=>'fa fa-info-circle',
                    'url'=>$url,
                    'brief'=>'Daily Task recorded by '.user::init()->get_session_user('full_name'),
                    'target'=>$user['user_id']??''
        ];
        $ns = system::send_notification($opts);
        if($ns !== 'ok') $msg = $ns;
    }
}


    if (!$db->error()) $msg = 'Saved successfully';
    else $msg = $db->error();

    if (isset($_POST['ajax_request'])) {
        $icon = !$db->error() ? 'success' : 'error';
        die(json_encode(['status' => $icon, 'message' => $msg]));
    }
}

#THIS LOGIC DEAL WITH COMMENTS
if(isset($_POST['is_comment'])){
    if(empty($_POST['project_comment'])){

    $msg = 'Please enter something in a comment section';
    $icon = 'error';
    die(json_encode(['status' => $icon, 'message' => $msg]));
    }
    $data = [
        'project_comment' => htmlentities($_POST['project_comment']),
        'client_id'=>isset($me['user_reference']) ?$me['user_reference']:$me['user_id'],
        'project_id'=>intval($_POST['project_id'])
    ];
    if(isset($_POST['comment_id'])){
        if(isset($_POST['is_a_reply']) && $_POST['is_a_reply'] == 1){
            $data['reply_comment_id']=intval($_POST['comment_id']);
            $data['is_a_reply']= 1;
            $last = $db->insert('project_clients_comments', $data);

        }else{
        $db->update('project_clients_comments',$data)->where(['id'=>intval($_POST['comment_id'])])->commit();
        }        
    }else{
        $last = $db->insert('project_clients_comments', $data);
    }

    if (!$db->error()) $msg = 'Saved successfully';
    else $msg = $db->error();

    if (isset($_POST['ajax_request'])) {
        $icon = !$db->error() ? 'success' : 'error';
        die(json_encode(['status' => $icon, 'message' => $msg]));
    }
}

#END OF COMMENTS


#Logic to deal with Activities and Subactivity
if (isset($_POST['activity_project'])) {
    $data = [
        'activity_name' => addslashes($_POST['activity_name'] ?? ''),
        'activity_creator' => $me['user_reference'],
        'activity_description' => addslashes($_POST['activity_description'] ?? ''),
        'activity_project' => intval($_POST['activity_project']),
        'activity_duration' => intval($_POST['activity_duration'] ?? ''),
        'budget' => doubleval($_POST['budget'] ?? 0.0),
        'activity_percentage' => doubleval($_POST['activity_percentage'] ?? 0.0),
    ];
    if (isset($_POST['activity_id'])) {
        $activity_id = intval($_POST['activity_id']);
        $parent_activity = $db->select('project_activities')->where(['activity_id' => $activity_id])->fetch();
        //if Request has Activity status  field
        if ($_POST['activity_status'] > 0) {
            $status = ['activity_status' => $_POST['activity_status']];
            $sub_acts_query = $db->select('project_activities')->where(['activity_parent' => $activity_id])->and("activity_status != 'completed'")->fetchAll();
            

            if (empty($sub_acts_query)) {
                if ($parent_activity['activity_parent'] > 0) {
                    $check_activity_status = $db->select('project_activities')->where(['activity_id' => $parent_activity['activity_parent']])->fetch();
                    //Checking if parent activities is marked completed
                    if ($check_activity_status['activity_status'] == 'completed' && $_POST['activity_status'] != 'completed') {
                        die(json_encode(['status' => 'info', 'message' => 'You can\'t change Sub Activity Status to other status because its Activity is marked completed']));
                    }
                }

                $db->update('project_activities', $status)->where(['activity_id' => $activity_id])->commit();

                /**Check if there is any subactivities that has a status other than complete
                 * If None, them mark main activity also as Complete
                 * if there are then continue without marking the main activiti as completed
                 * 
                 * */
                if ($parent_activity['activity_parent'] > 0) {

                    $check_other_subactivities = $db->select('project_activities')->where(['activity_parent' => $parent_activity['activity_parent']])->and("activity_status != 'completed'")->fetchAll();
                    if (empty($check_other_subactivities) &&  $_POST['activity_status'] == 'completed') {
                        $db->update('project_activities', ['activity_status' => 'completed'])->where(['activity_id' => $parent_activity['activity_parent']])->commit();
                    }
                }
            } else {
                die(json_encode(['status' => 'info', 'message' => 'You can\'t change Activity Status to complete before its subactivity to be marked completed']));
            }
        } else {
            $status = $data;
        }
        $db_percentage = $total_percentage = $total_activities = $activity_percent = 0;
        $is_mainactivity = true;
        
        if ($parent_activity['activity_parent'] > 0) {
            $status['activity_parent'] = $parent_activity['activity_parent'];
            $is_mainactivity = false;
            $activity_percent = $db->select('project_activities', 'activity_percentage')->where(['activity_id' => $parent_activity['activity_parent']])->fetch();
            $db_subactivities = $db->select('project_activities')->where(['activity_parent' => $parent_activity['activity_parent']])->and('activity_id != '.$activity_id)->fetchAll();
            if (!empty($db_subactivities)) {
                foreach ($db_subactivities as $db_activity) {
                    $db_percentage += $db_activity['activity_percentage'];
                }
            }
            $total_percentage = $db_percentage + doubleval($_POST['activity_percentage']);
        } else {
            $db_activities = $db->select('project_activities')->where(['activity_project' => intval($_POST['activity_project']), 'activity_parent'=>0])->and('activity_id != '.$activity_id)->fetchAll();
            if (!empty($db_activities)) {
                foreach ($db_activities as $db_activity) {
                    $db_percentage += $db_activity['activity_percentage'];
                }
                $total_activities = count($db_activities);
            }
            $total_percentage = $db_percentage + doubleval($_POST['activity_percentage']);
        }
        /*
# For Main Activity Check if 3 Activities has already recorded before adding another activity
# Check if Overall Percent for 3 Activities is equal to 100
*/
if ($total_activities >= 3 && $is_mainactivity) {
    $msg = 'Project can have only Three (3) Main Task, Please check again';
    $icon = 'error';
    die(json_encode(['status' => $icon, 'message' => $msg]));
} elseif ($is_mainactivity && $total_percentage > 100) {

    $msg = 'Only total of 100% can be allocate to this Project, You are trying to allocate ' . $total_percentage . '%, Fix and try again';
    $icon = 'error';
    die(json_encode(['status' => $icon, 'message' => $msg]));
            //This is for sub activity
        } elseif (!$is_mainactivity) {
            if ($total_percentage > $activity_percent['activity_percentage']) {

                $msg = 'Only total of ' . $activity_percent['activity_percentage'] . '% can be allocate to this Activity, You are trying to allocate ' . $total_percentage . '%, Fix and try again';
                $icon = 'error';
                die(json_encode(['status' => $icon, 'message' => $msg]));
            }
        }
        #if all rule in above not violated, then Update the Activity or Sub activity
        $db->update('project_activities', $status)->where(['activity_id' => $activity_id])->commit();

        if (isset($_POST['project_activity_assignees'])) {
            $users_array = $_POST['project_activity_assignees'];
            $db->delete('project_activities_assignees')->where(['project_activity_id' => intval($_POST['activity_id'])])->commit();
            foreach ($users_array as  $value) {
                $db->insert('project_activities_assignees', ['project_activity_id' => intval($_POST['activity_id']), 'user_assigned' => $value]);
            }
        }
    } else {$db_percentage = $total_percentage = $total_activities = $activity_percent = 0;
        $is_mainactivity = true;

        if (isset($_POST['activity_parent'])) {
            $data['activity_parent'] = intval($_POST['activity_parent']);
            $is_mainactivity = false;
            $activity_percent = $db->select('project_activities', 'activity_percentage')->where(['activity_id' => intval($_POST['activity_parent'])])->fetch();
            $db_subactivities = $db->select('project_activities')->where(['activity_parent' => intval($_POST['activity_parent'])])->fetchAll();
            if (!empty($db_subactivities)) {
                foreach ($db_subactivities as $db_activity) {
                    $db_percentage += $db_activity['activity_percentage'];
                }
            }
            $total_percentage = $db_percentage + doubleval($_POST['activity_percentage']);
        } else {
            $db_activities = $db->select('project_activities')->where(['activity_project' => intval($_POST['activity_project'])])->and('activity_parent = 0')->fetchAll();
            if (!empty($db_activities)) {
                foreach ($db_activities as $db_activity) {
                    $db_percentage += $db_activity['activity_percentage'];
                }
                $total_activities = count($db_activities);
            }
            $total_percentage = $db_percentage + doubleval($_POST['activity_percentage']);
        }
        /*
# For Main Activity Check if 3 Activities has already recorded before adding another activity
# Check if Overall Percent for 3 Activities is equal to 100
*/
if ($total_activities >= 3 && $is_mainactivity) {
    $msg = 'Project can have only Three (3) Main Task, Please check again';
    $icon = 'error';
    die(json_encode(['status' => $icon, 'message' => $msg]));
} elseif ($is_mainactivity && $total_percentage > 100) {

    $msg = 'Only total of 100% can be allocate to this Project, You are trying to allocate ' . $total_percentage . '%, Fix and try again';
    $icon = 'error';
    die(json_encode(['status' => $icon, 'message' => $msg]));
            //This is for sub activity
        } elseif (!$is_mainactivity) {
            if ($total_percentage > $activity_percent['activity_percentage']) {

                $msg = 'Only total of ' . $activity_percent['activity_percentage'] . '% can be allocate to this Activity, You are trying to allocate ' . $total_percentage . '%, Fix and try again';
                $icon = 'error';
                die(json_encode(['status' => $icon, 'message' => $msg]));
            }
        }

        #if all rule in above not violated, then Store Activity or Sub activity
        $last = $db->insert('project_activities', $data);
        if (isset($_POST['project_activity_assignees']) && $last) {
            $users_array = $_POST['project_activity_assignees'];
            foreach ($users_array as  $value) {
                $db->insert('project_activities_assignees', ['project_activity_id' => intval($last), 'user_assigned' => $value]);
            }
        }
    }
    if (!$db->error()) $msg = 'Saved successfully';
    else $msg = $db->error();

    if (isset($_POST['ajax_request'])) {
        $icon = !$db->error() ? 'success' : 'error';
        die(json_encode(['status' => $icon, 'message' => $msg]));
    }
}

$whr = $is_headquarters ? 1 : (isset($me['work_location'])?['work_location' => $me['work_location']]:1);

$customer = $db->select('customer')->where($whr)->fetchAll();


$managers = $db->select('staff', 'user_accounts.full_name, user_accounts.user_id')
    ->join('user_accounts', 'user_reference=user_id')
    ->where($whr)
    ->and(['system_role' => $moduleconfig->project_manager_role])
    ->fetchAll();


isset($me['work_location']) ?($whr = $is_headquarters ? 1 : "projects.owner_branch={$me['work_location']}"):'';
$base_url = implode(
    '/',
    [
        $registry->request[0],
        $registry->request[1],
        $registry->request[2],
        $registry->request[3]
    ]
);
if (isset($registry->request[5]) && $registry->request[5] == 'print') {
    $project = $db->select('projects', 'projects.*, user_accounts.full_name as manager, branches.*, customer.*, uc.full_name as creator')
        ->join('user_accounts', 'user_accounts.user_id=projects.project_manager')
        ->join('branches', 'owner_branch=branch_id')
        ->join('invoice', 'project_invoice=invoice_id')
        ->join('customer', 'invoice.customer=customer_id')
        ->join('user_accounts as uc', 'uc.user_id=projects.created_by')
        ->where(['project_id' => $registry->request[4]])
        ->fetch();

    $_q['tools'] = "(
    SELECT JSON_ARRAYAGG( 
        JSON_OBJECT( 'id', tool_id, 'name', tool_name, 'total', pra_count ) 
    ) FROM tools 
    JOIN project_resources_approved ON tool_id=pra_allocated_resource 
    WHERE pra_resource=resource_id 
) AS tools";


    $_q['staff'] = "(
        SELECT JSON_ARRAYAGG( 
            JSON_OBJECT( 'id', user_id, 'name', full_name, 'total', pra_count ) 
        ) FROM user_accounts 
        JOIN project_resources_approved ON user_id=pra_allocated_resource 
        WHERE pra_resource=resource_id 
    ) AS staff";

    $_q['products'] = "(
        SELECT JSON_ARRAYAGG( 
            JSON_OBJECT( 'id', product_id, 'name', product_name, 'total', pra_count ) 
        ) FROM product 
        JOIN project_resources_approved ON product_id=pra_allocated_resource 
        WHERE pra_resource=resource_id 
    ) AS products";

    $rt = $db->select('project_resources', 'resource_type as rtype')
        ->join('project_activities', 'resource_activity=activity_id')
        ->where(['activity_project' => $registry->request[4]])
        ->fetch();

    if (isset($rt['rtype'])) {
        $sub_q = implode(', ', $_q); //$_q[$rt['rtype']];
        //var_dump($sub_q);die;
        $resources = $db->select('project_resources', "project_resources.*, {$sub_q}, user_accounts.full_name as requester_name, appr.full_name as approver_name,project_activities.*")
            ->join('user_accounts', 'resource_requester=user_accounts.user_id')
            ->join('project_activities', 'resource_activity=activity_id')
            ->join('user_accounts as appr', 'resource_approver=appr.user_id')
            ->where(['activity_project' => $registry->request[4]])
            ->order_by('resource_id', 'desc')
            ->fetchAll();

        //var_dump($db->error());
    } else {
        $resources = [];
    }

    $activities = $db->select('project_activities as pa', 'pa.*,uc.full_name,uc.user_id')
        ->join('user_accounts as uc', 'uc.user_id = pa.activity_creator', 'left')
        ->where(['activity_project' => $registry->request[4], 'activity_parent' => 0])
        ->fetchAll();
    $activity_statement = $db->select('project_activities as pa', 'pa.*,uc.full_name,uc.user_id')
        ->join('user_accounts as uc', 'uc.user_id = pa.activity_creator', 'left')
        ->where(['activity_project' => $registry->request[4]])
        ->fetchAll();
    $sub_acts = $db->select('project_activities as pa', 'pa.*,uc.full_name,uc.user_id')
        ->join('user_accounts as uc', 'uc.user_id = pa.activity_creator', 'left')
        ->where(['activity_project' => $registry->request[4]])
        ->and('activity_parent != 0')
        ->fetchAll();

    $whr = $is_headquarters ? 1 : (isset($me['work_location'])?['work_location' => $me['work_location']]:1);

    $users = $db->select('user_accounts')->where($whr)->fetchAll();
    $tools = $db->select('tools')->where($whr)->fetchAll();
    $products = $db->select('product')
        ->join('invoice_items as ii', 'ii.product = product.product_id')
        ->where($whr)
        ->and("ii.invoice =" . $project['project_invoice'])
        ->fetchAll();
    $projects = [$project];

    $total_activities = count($activities);
    $percent = [];
    $percent = $act_ids = $sub_acts_ids = $daily_task = $daily_percent = $complete_tasks = $activities_assignees = $replies = [];
    foreach ($activity_statement as $act) {

        if ($act['activity_parent'] == 0) {
            $act_ids[] = $act['activity_id'];
        } else {
            $sub_acts_ids[] = $act['activity_parent'];
        }
        if ($act['activity_status'] != 'completed') {
            $daily_tasks[] =  $act['activity_id'];
        }
    }

    $activity_assignees = array_merge($act_ids,$sub_acts_ids);
    $imploded =implode(',',$activity_assignees);
    $assignees = !empty($activity_assignees)? $db->select('project_activities_assignees as paa','paa.*,uc.full_name')->join('user_accounts as uc','paa.user_assigned=uc.user_id')
    ->where('paa.project_activity_id in ('.$imploded.')' )
    ->fetchAll():[];
    if(!empty($assignees)){
        foreach($assignees as $assigned){
            $activities_assignees[$assigned['project_activity_id']][$assigned['user_assigned']]=$assigned;
        }
    }

    $assignees = $db->select('project_activities_assignees as paa','paa.*,uc.full_name')->join('user_accounts as uc','paa.user_assigned=uc.user_id')
    ->where('paa.project_activity_id in (select activity_id from project_activities where activity_project='.$registry->request[4].')' )
    ->fetchAll();
    if(!empty($assignees)){
        foreach($assignees as $assigned){
            $activities_assignees[$assigned['project_activity_id']][$assigned['user_assigned']]=$assigned;
        }
    }
    //Find all Activities whose are not compelete but their work is in progress state
    $in_progress_tasks = !empty($daily_tasks) ? $db->select('project_activities_sub_tasks')
        ->where('project_activity_id in (' . implode(',', $daily_tasks) . ')')
        ->and("status !='canceled'")
        ->fetchAll() : [];
    if (!empty($in_progress_tasks)) {
        foreach ($in_progress_tasks as $daily_task) {
            $daily_percent[$daily_task['project_activity_id']] = isset($daily_percent[$daily_task['project_activity_id']]) ?  $daily_percent[$daily_task['project_activity_id']] + $daily_task['task_percentage'] : $daily_task['task_percentage'];
        }
    }
    foreach ($activity_statement as $activity) {
        if ($activity['activity_status'] == 'completed') {
            // $this_percent = $total_activities > 0 ? number_format((100 / $total_activities), 2) : number_format(0, 1);

            $this_percent = $activity['activity_percentage'];
            if ($activity['activity_parent'] > 0) {
                $percent[$activity['activity_project']] = isset($percent[$activity['activity_project']]) ? $percent[$activity['activity_project']] + $this_percent : $this_percent;

                $complete_tasks[$activity['activity_id']] = isset($complete_tasks[$activity['activity_id']]) ? $complete_tasks[$activity['activity_id']] + $activity['activity_percentage'] : $activity['activity_percentage'];
            } else if (in_array($activity['activity_id'], $act_ids) &&  !in_array($activity['activity_id'], $sub_acts_ids)) {
                $percent[$activity['activity_project']] = isset($percent[$activity['activity_project']]) ? $percent[$activity['activity_project']] + $this_percent : $this_percent;

                $complete_tasks[$activity['activity_id']] = isset($complete_tasks[$activity['activity_id']]) ? $complete_tasks[$activity['activity_id']] + $activity['activity_percentage'] : $activity['activity_percentage'];
            }
        } elseif (array_key_exists($activity['activity_id'], $daily_percent)) {
            $percent[$activity['activity_project']] = isset($percent[$activity['activity_project']]) ? $percent[$activity['activity_project']] + $daily_percent[$activity['activity_id']] : $daily_percent[$activity['activity_id']];

            $complete_tasks[$activity['activity_id']] =  isset($percent[$activity['activity_id']]) ? $percent[$activity['activity_id']] + $daily_percent[$activity['activity_id']] : $daily_percent[$activity['activity_id']];
        }
    }
    
    $sub_activities = [];
    foreach ($sub_acts as $sub_) {
        $sub_activities[$sub_['activity_parent']][$sub_['activity_id']] = $sub_;
    }
    //Clients Comments on the Projects
    $comments = $db->select('project_clients_comments')
    ->join('user_accounts','client_id=user_id')
    ->where(['project_id'=>$registry->request[4],'is_a_reply'=>0])->order_by('created_at')->fetchAll();
    $replies_data = $db->select('project_clients_comments')
    ->join('user_accounts','client_id=user_id')
    ->where(['project_id'=>$registry->request[4],'is_a_reply'=>1])->order_by('created_at')->fetchAll();
    if(!empty($replies_data)){
        foreach($replies_data as $reply){
            $replies[$reply['reply_comment_id']][] =$reply;
        }
    }
    ob_start();
    include __DIR__ . '/html/report_page.html';
    $body = ob_get_clean();
} elseif (isset($registry->request[5]) && $registry->request[5] == 'daily') {
    $user_id = $me['user_reference'];

    // clear notification
    $db->update('system_notification',['notification_status'=>'read'])
    ->where(['notification_url'=>'plugin/Project/projects', 'notification_target'=>$user_id])
    ->commit();
    $query = $db->select('projects', 'projects.*, user_accounts.full_name as manager, branches.*, customer.*, uc.full_name as creator')
        ->join('user_accounts', 'user_accounts.user_id=projects.project_manager')
        ->join('branches', 'owner_branch=branch_id')
        ->join('invoice', 'project_invoice=invoice_id')
        ->join('customer', 'invoice.customer=customer_id')
        ->join('user_accounts as uc', 'uc.user_id=projects.created_by');

        if ($user_role == 'technicians') {
            $project = $query->where($whr)
            ->and("project_id in (select activity_project from project_activities as a join project_activities_assignees as b on (a.activity_id = b.project_activity_id) where user_assigned=$user_id)")
            ->order_by('project_id', 'desc')
            ->fetch();
        }else{
       $project = $query->where(['project_id' => $registry->request[4]])
        ->fetch();
        }

    $_q['tools'] = "(
        SELECT JSON_ARRAYAGG( 
            JSON_OBJECT( 'id', tool_id, 'name', tool_name, 'total', pra_count ) 
        ) FROM tools 
        JOIN project_resources_approved ON tool_id=pra_allocated_resource 
        WHERE pra_resource=resource_id 
    ) AS tools";


    $_q['staff'] = "(
            SELECT JSON_ARRAYAGG( 
                JSON_OBJECT( 'id', user_id, 'name', full_name, 'total', pra_count ) 
            ) FROM user_accounts 
            JOIN project_resources_approved ON user_id=pra_allocated_resource 
            WHERE pra_resource=resource_id 
        ) AS staff";

    $_q['products'] = "(
            SELECT JSON_ARRAYAGG( 
                JSON_OBJECT( 'id', product_id, 'name', product_name, 'total', pra_count ) 
            ) FROM product 
            JOIN project_resources_approved ON product_id=pra_allocated_resource 
            WHERE pra_resource=resource_id 
        ) AS products";

    $rt = $db->select('project_resources', 'resource_type as rtype')
        ->join('project_activities', 'resource_activity=activity_id')
        ->where(['activity_project' => $registry->request[4]])
        ->fetch();

    if (isset($rt['rtype'])) {
        $sub_q = implode(', ', $_q); //$_q[$rt['rtype']];
        //var_dump($sub_q);die;
        $resources = $db->select('project_resources', "project_resources.*, {$sub_q}, user_accounts.full_name as requester_name, appr.full_name as approver_name,project_activities.*")
            ->join('user_accounts', 'resource_requester=user_accounts.user_id')
            ->join('project_activities', 'resource_activity=activity_id')
            ->join('user_accounts as appr', 'resource_approver=appr.user_id')
            ->where(['activity_project' => $registry->request[4]])
            ->order_by('resource_id', 'desc')
            ->fetchAll();

        //var_dump($db->error());
    } else {
        $resources = [];
    }
    if ($user_role == 'technicians') {
     $activities = $db->select('project_activities as pa', 'pa.*,uc.full_name,uc.user_id')
     ->join('user_accounts as uc', 'uc.user_id = pa.activity_creator', 'left')
     ->where(['activity_project' => $registry->request[4], 'activity_parent' => 0])
    // ->and("activity_id in (select project_activity_id from  project_activities_assignees  where user_assigned=$user_id)")
        ->fetchAll();
    }else{
        $activities = $db->select('project_activities as pa', 'pa.*,uc.full_name,uc.user_id')
        ->join('user_accounts as uc', 'uc.user_id = pa.activity_creator', 'left')
        ->where(['activity_project' => $registry->request[4], 'activity_parent' => 0])
        ->fetchAll(); 
    }
 
    if ($user_role == 'technicians') {
        $activity_statement = $db->select('project_activities as pa', 'pa.*,uc.full_name,uc.user_id')
        ->join('user_accounts as uc', 'uc.user_id = pa.activity_creator', 'left')
        ->where(['activity_project' => $registry->request[4]])
        ->and("activity_id in (select project_activity_id from  project_activities_assignees  where user_assigned=$user_id)")->fetchAll();
    }else{
    $activity_statement =  $db->select('project_activities as pa', 'pa.*,uc.full_name,uc.user_id')
    ->join('user_accounts as uc', 'uc.user_id = pa.activity_creator', 'left')
    ->where(['activity_project' => $registry->request[4]])
    ->fetchAll();
    }

    if ($user_role == 'technicians') {
        $sub_acts = $db->select('project_activities as pa', 'pa.*,uc.full_name,uc.user_id')
        ->join('user_accounts as uc', 'uc.user_id = pa.activity_creator', 'left')
        ->where(['activity_project' => $registry->request[4]])
        ->and("activity_id in (select project_activity_id from  project_activities_assignees  where user_assigned=$user_id)")
        ->and('activity_parent != 0')
        ->fetchAll();
    }else{
    $sub_acts = $db->select('project_activities as pa', 'pa.*,uc.full_name,uc.user_id')
                ->join('user_accounts as uc', 'uc.user_id = pa.activity_creator', 'left')
                ->where(['activity_project' => $registry->request[4]])
                ->and('activity_parent != 0')
                ->fetchAll();
    }

    $whr = $is_headquarters ? 1 : ['work_location' => $me['work_location']];

    $users = $db->select('user_accounts')->where($whr)->fetchAll();
    $tools = $db->select('tools')->where($whr)->fetchAll();
    $products = $db->select('product')
        ->join('invoice_items as ii', 'ii.product = product.product_id')
        ->where($whr)
        ->and("ii.invoice =" . $project['project_invoice'])
        ->fetchAll();
    $projects = [$project];

    $total_activities = count($activities);
    $percent = $act_ids = $sub_acts_ids = $daily_tasks = $daily_percent = [];

    foreach ($activity_statement as $act) {

        if ($act['activity_parent'] == 0) {
            $act_ids[] = $act['activity_id'];
        } else {
            $sub_acts_ids[] = $act['activity_parent'];
        }

        if ($act['activity_status'] != 'completed') {
            $daily_tasks[] =  $act['activity_id'];
        }
    }
    //Find all Activities whose are not compelete but their work is in progress state

    $assignees = $db->select('project_activities_assignees as paa','paa.*,uc.full_name')->join('user_accounts as uc','paa.user_assigned=uc.user_id')
    ->where('paa.project_activity_id in (select activity_id from project_activities where activity_project='.$registry->request[4].')' )
    ->fetchAll();
    if(!empty($assignees)){
        foreach($assignees as $assigned){
            $activities_assignees[$assigned['project_activity_id']][$assigned['user_assigned']]=$assigned;
        }
    }
    $in_progress_tasks = !empty($daily_tasks) ? $db->select('project_activities_sub_tasks')
        ->where('project_activity_id in (' . implode(',', $daily_tasks) . ')')
        ->and("status !='canceled'")
        ->fetchAll() : [];
    if (!empty($in_progress_tasks)) {
        foreach ($in_progress_tasks as $daily_task) {
            $daily_percent[$daily_task['project_activity_id']] = isset($daily_percent[$daily_task['project_activity_id']]) ?  $daily_percent[$daily_task['project_activity_id']] + $daily_task['task_percentage'] : $daily_task['task_percentage'];
        }
    }


    foreach ($activity_statement as $activity) {
        if ($activity['activity_status'] == 'completed') {

            $this_percent = $activity['activity_percentage'];
            if ($activity['activity_parent'] > 0) {
                $percent[$activity['activity_id']] = isset($percent[$activity['activity_id']]) ? $percent[$activity['activity_id']] + $this_percent : $this_percent;
            } else if (in_array($activity['activity_id'], $act_ids) &&  !in_array($activity['activity_id'], $sub_acts_ids)) {
                $percent[$activity['activity_id']] = isset($percent[$activity['activity_id']]) ? $percent[$activity['activity_id']] + $this_percent : $this_percent;
            }
        } elseif (array_key_exists($activity['activity_id'], $daily_percent)) {

            $percent[$activity['activity_id']] = isset($percent[$activity['activity_id']]) ? $percent[$activity['activity_id']] + $daily_percent[$activity['activity_id']] : $daily_percent[$activity['activity_id']];
        }
    }
    
    $sub_activities = [];
    foreach ($sub_acts as $sub_) {
        $sub_activities[$sub_['activity_parent']][$sub_['activity_id']] = $sub_;
    }
    ob_start();
    include __DIR__ . '/html/daily_page.html';
    $body = ob_get_clean();
} else if (isset($registry->request[4]) && user::init()->user_can('view_project')) {
    $user_id =$me['user_reference'];
    $project = $db->select('projects', 'projects.*, user_accounts.full_name as manager, branches.*, customer.*, uc.full_name as creator')
        ->join('user_accounts', 'user_accounts.user_id=projects.project_manager')
        ->join('branches', 'owner_branch=branch_id')
        ->join('invoice', 'project_invoice=invoice_id')
        ->join('customer', 'invoice.customer=customer_id')
        ->join('user_accounts as uc', 'uc.user_id=projects.created_by')
        ->where(['project_id' => $registry->request[4]])
        ->fetch();

    $_q['tools'] = "(
                SELECT JSON_ARRAYAGG( 
                    JSON_OBJECT( 'id', tool_id, 'name', tool_name, 'total', pra_count ) 
                ) FROM tools 
                JOIN project_resources_approved ON tool_id=pra_allocated_resource 
                WHERE pra_resource=resource_id 
            ) AS tools";


    $_q['staff'] = "(
                    SELECT JSON_ARRAYAGG( 
                        JSON_OBJECT( 'id', user_id, 'name', full_name, 'total', pra_count ) 
                    ) FROM user_accounts 
                    JOIN project_resources_approved ON user_id=pra_allocated_resource 
                    WHERE pra_resource=resource_id 
                ) AS staff";

    $_q['products'] = "(
                    SELECT JSON_ARRAYAGG( 
                        JSON_OBJECT( 'id', product_id, 'name', product_name, 'total', pra_count ) 
                    ) FROM product 
                    JOIN project_resources_approved ON product_id=pra_allocated_resource 
                    WHERE pra_resource=resource_id 
                ) AS products";

    $rt = $db->select('project_resources', 'resource_type as rtype')
        ->join('project_activities', 'resource_activity=activity_id')
        ->where(['activity_project' => $registry->request[4]])
        ->fetch();

    if (isset($rt['rtype'])) {
        $sub_q = implode(', ', $_q); //$_q[$rt['rtype']];
        //var_dump($sub_q);die;
        $resources = $db->select('project_resources', "project_resources.*,project_activities.activity_name, {$sub_q}, user_accounts.full_name as requester_name, appr.full_name as approver_name")
            ->join('user_accounts', 'resource_requester=user_accounts.user_id')
            ->join('project_activities', 'resource_activity=activity_id')
            ->join('user_accounts as appr', 'resource_approver=appr.user_id', 'LEFT')
            ->where(['activity_project' => $registry->request[4]])
            ->order_by('resource_id', 'desc')
            ->fetchAll();

        //var_dump($db->error());
    } else {
        $resources = [];
    }



        if ($user_role == 'technicians') {
            $activities = $db->select('project_activities as pa', 'pa.*,uc.full_name,uc.user_id')
            ->join('user_accounts as uc', 'uc.user_id = pa.activity_creator', 'left')
            ->where(['activity_project' => $registry->request[4], 'activity_parent' => 0])
           // ->and("activity_id in (select project_activity_id from  project_activities_assignees  where user_assigned=$user_id)")
               ->fetchAll();
           }else{
               $activities = $db->select('project_activities as pa', 'pa.*,uc.full_name,uc.user_id')
               ->join('user_accounts as uc', 'uc.user_id = pa.activity_creator', 'left')
               ->where(['activity_project' => $registry->request[4], 'activity_parent' => 0])
               ->fetchAll(); 
           }
           if ($user_role == 'technicians') {
               $activity_statement = $db->select('project_activities as pa', 'pa.*,uc.full_name,uc.user_id')
               ->join('user_accounts as uc', 'uc.user_id = pa.activity_creator', 'left')
               ->where(['activity_project' => $registry->request[4]])
               ->and("activity_id in (select project_activity_id from  project_activities_assignees  where user_assigned=$user_id)")->fetchAll();
           }else{
           $activity_statement =  $db->select('project_activities as pa', 'pa.*,uc.full_name,uc.user_id')
           ->join('user_accounts as uc', 'uc.user_id = pa.activity_creator', 'left')
           ->where(['activity_project' => $registry->request[4]])
           ->fetchAll();
           }
       
           if ($user_role == 'technicians') {
               $sub_acts = $db->select('project_activities as pa', 'pa.*,uc.full_name,uc.user_id')
               ->join('user_accounts as uc', 'uc.user_id = pa.activity_creator', 'left')
               ->where(['activity_project' => $registry->request[4]])
               ->and("activity_id in (select project_activity_id from  project_activities_assignees  where user_assigned=$user_id)")
               ->and('activity_parent != 0')
               ->fetchAll();
           }else{
           $sub_acts = $db->select('project_activities as pa', 'pa.*,uc.full_name,uc.user_id')
                       ->join('user_accounts as uc', 'uc.user_id = pa.activity_creator', 'left')
                       ->where(['activity_project' => $registry->request[4]])
                       ->and('activity_parent != 0')
                       ->fetchAll();
           }

    $whr = $is_headquarters ? 1 : ['work_location' => $me['work_location']];

    $users = $db->select('user_accounts')->where($whr)->fetchAll();
    $tools = $db->select('tools')->where($whr)->fetchAll();
    // $products = $db->select('product')->where($whr)->fetchAll();
    $products = $db->select('product')
        ->join('invoice_items as ii', 'ii.product = product.product_id')
        ->where($whr)
        ->and("ii.invoice =" . $project['project_invoice'])
        ->fetchAll();
    $projects = [$project];

    $total_activities = count($activities);
    $percent = $act_ids = $sub_acts_ids = $daily_task = $daily_percent = $complete_tasks = $activities_assignees=[];
    foreach ($activity_statement as $act) {

        if ($act['activity_parent'] == 0) {
            $act_ids[] = $act['activity_id'];
        } else {
            $sub_acts_ids[] = $act['activity_parent'];
        }
        if ($act['activity_status'] != 'completed') {
            $daily_tasks[] =  $act['activity_id'];
        }
    }

    $activity_assignees = array_merge($act_ids,$sub_acts_ids);
    $imploded =implode(',',$activity_assignees);
    $assignees = !empty($activity_assignees)? $db->select('project_activities_assignees as paa','paa.*,uc.full_name')->join('user_accounts as uc','paa.user_assigned=uc.user_id')
    ->where('paa.project_activity_id in (select activity_id from project_activities where activity_project='.$registry->request[4].')' )
    ->fetchAll():[];
    if(!empty($assignees)){
        foreach($assignees as $assigned){
            $activities_assignees[$assigned['project_activity_id']][$assigned['user_assigned']]=$assigned;
        }
    }
    //Find all Activities whose are not compelete but their work is in progress state
    $in_progress_tasks = !empty($daily_tasks) ? $db->select('project_activities_sub_tasks')
        ->where('project_activity_id in (' . implode(',', $daily_tasks) . ')')
        ->and("status !='canceled'")
        ->fetchAll() : [];
    if (!empty($in_progress_tasks)) {
        foreach ($in_progress_tasks as $daily_task) {
            $daily_percent[$daily_task['project_activity_id']] = isset($daily_percent[$daily_task['project_activity_id']]) ?  $daily_percent[$daily_task['project_activity_id']] + $daily_task['task_percentage'] : $daily_task['task_percentage'];
        }
    }
    foreach ($activity_statement as $activity) {
        if ($activity['activity_status'] == 'completed') {
            // $this_percent = $total_activities > 0 ? number_format((100 / $total_activities), 2) : number_format(0, 1);

            $this_percent = $activity['activity_percentage'];
            if ($activity['activity_parent'] > 0) {
                $percent[$activity['activity_project']] = isset($percent[$activity['activity_project']]) ? $percent[$activity['activity_project']] + $this_percent : $this_percent;

                $complete_tasks[$activity['activity_id']] = isset($complete_tasks[$activity['activity_id']]) ? $complete_tasks[$activity['activity_id']] + $activity['activity_percentage'] : $activity['activity_percentage'];
            } else if (in_array($activity['activity_id'], $act_ids) &&  !in_array($activity['activity_id'], $sub_acts_ids)) {
                $percent[$activity['activity_project']] = isset($percent[$activity['activity_project']]) ? $percent[$activity['activity_project']] + $this_percent : $this_percent;

                $complete_tasks[$activity['activity_id']] = isset($complete_tasks[$activity['activity_id']]) ? $complete_tasks[$activity['activity_id']] + $activity['activity_percentage'] : $activity['activity_percentage'];
            }
        } elseif (array_key_exists($activity['activity_id'], $daily_percent)) {
            $percent[$activity['activity_project']] = isset($percent[$activity['activity_project']]) ? $percent[$activity['activity_project']] + $daily_percent[$activity['activity_id']] : $daily_percent[$activity['activity_id']];

            $complete_tasks[$activity['activity_id']] =  isset($percent[$activity['activity_id']]) ? $percent[$activity['activity_id']] + $daily_percent[$activity['activity_id']] : $daily_percent[$activity['activity_id']];
        }
    }
    // print("<pre>");
    // print_r($activity_statement);
    // print_r($complete_tasks);exit;
    $sub_activities = [];
    foreach ($sub_acts as $sub_) {
        $sub_activities[$sub_['activity_parent']][$sub_['activity_id']] = $sub_;
    }
    ob_start();
    include __DIR__ . '/html/project.html';
    $body = ob_get_clean();
} else {
    $user_id =isset($me['user_reference']) ? $me['user_reference']:$me['user_id'];;

    $query = $db->select('projects', 'projects.*, user_accounts.full_name as manager, branches.*, customer.*, uc.full_name as creator')
    ->join('user_accounts', 'user_accounts.user_id=projects.project_manager')
    ->join('branches', 'owner_branch=branch_id')
    ->join('invoice', 'project_invoice=invoice_id')
    ->join('customer', 'invoice.customer=customer_id')
    ->join('user_accounts as uc', 'uc.user_id=projects.created_by');

    if ($user_role == 'technicians') {
        $projects = $query->where($whr)
        ->and("project_id in (select activity_project from project_activities as a join project_activities_assignees as b on (a.activity_id = b.project_activity_id) where user_assigned=$user_id)")
        ->order_by('project_id', 'desc')
        ->fetchAll();
    }elseif($user_role == 'viewer'){
        $projects = $query->join('client_projects as cp','cp.project_id = projects.project_id')
        ->where(['client_id'=>$user_id])
        ->fetchAll();
    }
    else{

    $projects = $query
        ->where($whr)
        ->order_by('project_id', 'desc')
        ->fetchAll();
    }
    $whr = $is_headquarters ? 1 : (isset($me['work_location']) ? ['work_location' => $me['work_location']] :1 );
    $query =$db->select('project_activities', 'activity_id,activity_parent,activity_percentage, activity_status, activity_project');
    if ($user_role == 'technicians') {
        $statements = $query->where($whr) 
       //->and("activity_id in (select project_activity_id project_activities_assignees where user_assigned=$user_id)")
        ->fetchAll();
    }else{
        $statements = $query->where($whr)->fetchAll();
    }

    $percent = $act_ids = $sub_ids = $daily_tasks = $daily_percent = $complete_tasks = [];

    foreach ($statements as $act) {

        if ($act['activity_parent'] == 0) {
            $ids[] = $act['activity_id'];
        } else {
            $sub_ids[] = $act['activity_parent'];
        }
        if ($act['activity_status'] != 'completed') {
            $daily_tasks[] =  $act['activity_id'];
        }
    }
    //Find all Activities whose are not compelete but their work is in progress state
    $in_progress_tasks = !empty($daily_tasks) ? $db->select('project_activities_sub_tasks')
        ->where('project_activity_id in (' . implode(',', $daily_tasks) . ')')
        ->and("status !='canceled'")
        ->fetchAll() : [];
    if (!empty($in_progress_tasks)) {
        foreach ($in_progress_tasks as $daily_task) {
            $daily_percent[$daily_task['project_activity_id']] = isset($daily_percent[$daily_task['project_activity_id']]) ?  $daily_percent[$daily_task['project_activity_id']] + $daily_task['task_percentage'] : $daily_task['task_percentage'];
        }
    }

    foreach ($statements as $stm) {
        if ($stm['activity_status'] == 'completed') {
            if ($stm['activity_parent'] > 0) {
                $complete_tasks[$stm['activity_project']] = isset($complete_tasks[$stm['activity_project']]) ? $complete_tasks[$stm['activity_project']] + $stm['activity_percentage'] : $stm['activity_percentage'];
            } else if (in_array($stm['activity_id'], $ids) &&  !in_array($stm['activity_id'], $sub_ids)) {
                $complete_tasks[$stm['activity_project']] = isset($complete_tasks[$stm['activity_project']]) ? $complete_tasks[$stm['activity_project']] + $stm['activity_percentage'] : $stm['activity_percentage'];
            }
        } elseif (array_key_exists($stm['activity_id'], $daily_percent)) {

            $complete_tasks[$stm['activity_project']] =  isset($complete_tasks[$stm['activity_project']]) ? $complete_tasks[$stm['activity_project']] + $daily_percent[$stm['activity_id']] : $daily_percent[$stm['activity_id']];
        }
    }


    $sortedProjects = [];
    foreach ($projects as $proj) {
        if (!isset($sortedProjects[$proj['branch_name']])) $sortedProjects[$proj['branch_name']] = [];
        $sortedProjects[$proj['branch_name']][] = $proj;
    }

    ob_start();
    include __DIR__ . '/html/projects.html';
    $body = ob_get_clean();
}
$return = ['title' => ' ', 'body' => $body];
