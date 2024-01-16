<?php 

$config = storage::get_data('system_config')->db_configs;
$db = db::get_connection($config);
if(isset($_POST['sales_date'])){
    //var_dump($_POST);
    
    $data = [
        'owner_branch'=>$me['work_location'],
        'customer_name'=>$_POST['customer_name'], 
        'sales_date'=>$_POST['sales_date'],
        'unit_amount'=>$_POST['unit_amount'],
        'product'=>$_POST['product_name'], 
        'quantity'=>$_POST['quantity']
    ];

    if(isset($_POST['sales_id']) && intval($_POST['sales_id']) > 0){
        $k = intval($_POST['sales_id']);
        $db->update('sales', $data)->where(['sales_id'=>$_POST['sales_id']])->commit();
    }
    else $k = $db->insert('sales', $data);
    $ok = 'error';
    if(!$db->error() && $k) {
        $msg = 'sales saved successful';
        $ok ='success';
    }
    else $msg = $db->error()['message']; 
    if(isset($_POST['ajax_request'])) die($msg);
}

if(isset($_POST['delete_sales'])){
    $k = $db->delete('sales')->where(['sales_id'=>intval($_POST['delete_sales'])])->commit();
    if(!$db->error() && $k){
        $msg = [
            'status'=>'success',
            'message'=>'sales deleted successful'
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
if($is_headquarters) {
    $whr = 1;
}
else{
    $whr = ['owner_branch'=>$me['work_location']];
}
$sales = $db->select('sales')
                ->join('branches', 'branch_id=owner_branch')
                ->join('product', 'product_id=product')
                ->where($whr)
                ->order_by('sales_id', 'desc')
                ->fetchAll();

$product = $db->select('product', 'product_id, product_name, product_price')
                ->where(1)
                ->order_by('product_id', 'desc')
                ->fetchAll();

$sortedSales = [];
foreach($sales as $st){
    if(!isset($sortedSales[$st['branch_name']])) $sortedSales[$st['branch_name']] = [];
    $sortedSales[$st['branch_name']][] = $st;
}

$body = '';
ob_start();
include __DIR__.'/html/sales.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];