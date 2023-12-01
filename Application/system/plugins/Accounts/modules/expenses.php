<?php 
$me = human_resources::get_staff();
if($me){
    $hq = human_resources::get_headquarters_branch();
    $config = storage::get_data('system_config')->db_configs;
    $db = db::get_connection($config);
    $status = 'error';
    if(isset($_POST['expenses_date'])){
        $data = [
            'expenses_date'=>intval($_POST['expenses_date']),
            'expenses_number_item'=>addslashes($_POST['expenses_number_item']),
            'expenses_description'=>addslashes($_POST['expenses_description']),
            'expenses_amount'=>addslashes($_POST['expenses_amount']),
            'purchased_by'=>addslashes($_POST['purchased_by']),
            'approved_by'=>addslashes($_POST['approved_by']),
            'owner_branch'=>intval($me['work_location'])
        ];
        if(isset($_POST['expenses_id'])){
            $k = intval($_POST['expenses_id']);
            $db->update('expenses', $data)->where(['expenses_id'=>$k])->commit();
        }
        else{
            $chk = $db->select('expenses', 'expenses_date')
                      ->where(['expenses_date'=>$data['expenses_date'], 'owner_branch'=>$data['owner_branch']])
                      ->limit(1)
                      ->fetchAll();

            if(!$chk) $k = $db->insert('expenses', $data);
            else $k = 0;
        }
        if(isset($_FILES['expenses_image']) && is_readable($_FILES['expenses_image']['tmp_name']) && $k){
            $dir = realpath(__DIR__.'/../../../../storage/uploads/expensess/');
            $source = $_FILES['expenses_image']['tmp_name'];//expenses_thumb_0.jpg
            $bp = system::upload_image($source, "{$dir}/expenses_{$k}.jpg", ['width'=>600, 'height'=>400]);

            file_put_contents("{$dir}/tmp.jpg", file_get_contents($bp));
            $thumb = system::upload_image("{$dir}/tmp.jpg", "{$dir}/expenses_thumb_{$k}.jpg", ['width'=>200, 'height'=>170]);
        }
        if(!$db->error() && $k) {
            $msg = 'Saved successful';
            $status = 'success';
        }
        else{
            $err = $db->error();
            $msg = $k == 0 ? 'expenses exists' : $err['message'];

        }
        if(isset($_POST['ajax_request'])) die(json_encode(['status'=>$status, 'message'=>$msg]));
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

    $columns = "expenses.*, IFNULL(category_name, 'General') as category_name, IFNULL(branch_name, 'Headquarters') as branch_name";
    $expenses = $db->select('expenses', $columns)
                        ->join('user_accounts','user_id=approved_by')
                        ->join('expenses_category', 'category_id=expenses_category', 'LEFT')
                        ->join('branches', 'branch_id=owner_branch', 'LEFT')
                        ->where($whr)
                        ->fetchAll();

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