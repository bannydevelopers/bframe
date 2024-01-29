<?php 
$me = human_resources::get_staff();
if($me){
    $hq = human_resources::get_headquarters_branch();
    $config = storage::get_data('system_config')->db_configs;
    $db = db::get_connection($config);
    $status = 'error';
    if(isset($_POST['expenses_date'])){
        $data = [
            'expenses_date'=>addslashes($_POST['expenses_date']),
            'expenses_description'=>addslashes($_POST['expenses_description']),
            'expenses_amount'=>intval($_POST['expenses_price']),
            'purchased_by'=>$me['user_reference'],
            'expenses_category'=>intval($_POST['expenses_category']),
            'owner_branch'=>intval($me['work_location'])
        ];
        if(isset($_POST['expenses_id'])){
            $k = intval($_POST['expenses_id']);
            $db->update('expenses', $data)->where(['expenses_id'=>$k])->commit();
        }
        else{
            $k = $db->insert('expenses', $data);
        }
        if(!$db->error()) {
            $msg = 'Saved successful';
        }
        else{
            $msg = $db->error()['message'];
        }
        if(isset($_FILES['expenses_image']) && is_readable($_FILES['expenses_image']['tmp_name']) && $k){
            $dir = realpath(__DIR__.'/../../../../storage/uploads/expenses/');
            $source = $_FILES['expenses_image']['tmp_name'];//expenses_thumb_0.jpg
            $bp = system::upload_image($source, "{$dir}/expenses_{$k}.jpg", ['width'=>600, 'height'=>400]);

            file_put_contents("{$dir}/tmp.jpg", file_get_contents($bp));
            $thumb = system::upload_image("{$dir}/tmp.jpg", "{$dir}/expenses_thumb_{$k}.jpg", ['width'=>200, 'height'=>170]);
        }
        
        if(isset($_POST['ajax_request'])) die(json_encode($msg));
    }
    if(isset($_POST['category_name'])){
        $data = [
            'category_name'=>addslashes($_POST['category_name']),
            'category_description'=>addslashes($_POST['category_description'])
        ];
        if(isset($_POST['category_id'])){
            $k = intval($_POST['category_id']);
            $db->update('expenses_category', $data)->where(['category_id'=>$k])->commit();
        }
        else{
            $k = $db->insert('expenses_category', $data);
        }
        if(!$db->error()) $msg = 'Category saved successful';
        else $msg = $db->error()['message'];
        if(isset($_POST['ajax_request'])) die($msg);
    }
    if($me['work_location'] == human_resources::get_headquarters_branch()) {
        $whr = 1;
    }
    else{
        $whr = ['owner_branch'=>$me['work_location']];
    }
    $expensesCategory = $db->select('expenses_category', 'category_id, category_name')
                        ->where($whr)
                        ->fetchAll();

    if($me['work_location'] == $hq) $whr = 1;
    else $whr = ['owner_branch'=>$me['work_location']];

    $columns = "expenses.*, IFNULL(category_name, 'General') as category_name, IFNULL(branch_name, 'Headquarters') as branch_name,user_accounts.full_name, purchaser.full_name as purchaser_name";
    $expenses = $db->select('expenses', $columns)
                        ->join('user_accounts','user_id=approved_by','LEFT')
                        ->join('user_accounts as purchaser','purchaser.user_id=purchased_by')
                        ->join('expenses_category', 'category_id=expenses_category', 'LEFT')
                        ->join('branches', 'branch_id=owner_branch', 'LEFT')
                        ->where($whr)
                        ->order_by('expenses_category', 'desc')
                        ->fetchAll();
    $sortedExpenses = [];
    foreach($expenses as $exp){
        if(!isset($sortedExpenses[$exp['branch_name']])) $sortedExpenses[$exp['branch_name']] = [];
        if(!isset($sortedExpenses[$exp['branch_name']][$exp['category_name']])){
            $sortedExpenses[$exp['branch_name']][$exp['category_name']] = [];
        }
        $root = '/storage/uploads/expenses/expense_thumb_';
        if(is_readable(realpath(__DIR__.'/../../../../')."{$root}{$exp['expenses_id']}.jpg")) 
            $exp['expense_image'] = "Application/storage/uploads/expenses/expense_thumb_{$exp['expenses_id']}.jpg";
        else
            $exp['expense_image'] = 'Application/storage/uploads/expenses/expenses_thumb_0.jpg';
    
        $sortedExpenses[$exp['branch_name']][$exp['category_name']][] = $exp;
    }
    
    $employees = $db->select('staff')
                ->join('user_accounts','user_id=user_reference')
                ->where($whr)
                ->order_by('staff_id', 'desc')
                ->fetchAll();

    $pic_root = 'Application/storage/uploads/expenses/expenses_thumb_';
    $sortedExpenses = [];
    foreach($expenses as $prod){
        if(!isset($sortedExpenses[$prod['branch_name']])) $sortedExpenses[$prod['branch_name']] = [];
        if(!isset($sortedExpenses[$prod['branch_name']][$prod['category_name']])){
            $sortedExpenses[$prod['branch_name']][$prod['category_name']] = [];
        }
        $sortedExpenses[$prod['branch_name']][$prod['category_name']][] = $prod;
    }
    //var_dump('<pre>', $sortedExpenses);die;z
    ob_start();
    include __DIR__.'/html/expenses.html';
    $body = ob_get_clean();
    $return = ['title'=>' ','body'=>$body];
}