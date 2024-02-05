<?php 

$config = storage::get_data('system_config')->db_configs;
$db = db::get_connection($config);
if(isset($_POST['sales_date'])){

    $data = [
        'owner_branch'=>$me['work_location'],
        'customer_name'=>$_POST['customer_name'], 
        'sales_date'=>$_POST['sales_date'],
        'unit_amount'=>$_POST['unit_amount'],
        'product'=>$_POST['product_name'], 
        'quantity'=>$_POST['quantity'],
        'stock'=>intval($_POST['product_batch'])
    ];

    if(isset($_POST['sales_id']) && intval($_POST['sales_id']) > 0){
        $k = intval($_POST['sales_id']);
        $db->update('sales', $data)->where(['sales_id'=>$_POST['sales_id']])->commit();
    }
    else {
        $k = $db->insert('sales', $data);
        $ok = 'error';
        if(!$db->error() && $k) {
            $msg = 'Sale saved successful';
            $ok ='success';
            // Deduct stock
            $stok = $db->select('stock')->where(['stock_id'=>$data['stock']])->limit(1)->fetch();
            $stok['stock_ref'] = $stok['stock_id'];
            $stok['stock_quantity'] = $data['quantity'];
            $stok['stock_receiver'] = $me['user_reference'];
            $stok['create_date'] = date('Y-m-d H:i:s');
            unset($stok['stock_id']);
            $kx = $db->insert('stock', $stok);
            if($db->error() or !$kx){
                $db->delete('sales')->where(['sale_id'=>$k])->commit();
                $msg = $db->error()['message'];
                $ok ='error';
            }
        }
        else $msg = $db->error()['message']; 
        if(isset($_POST['ajax_request'])) die($msg);
    }
}
if(isset($_POST['get_batch'])){
    $prod = intval($_POST['get_batch']);
    $stock = $db->select('stock', 'stock.stock_id, stock.stock_quantity, stock.stock_batch, stock.create_date, sum(outgoing.stock_quantity) as stock_out')
                ->join('stock as outgoing', 'outgoing.stock_ref=stock.stock_id', 'left')
                ->where("stock.product='{$prod}'")
                ->and("stock.stock_ref=0")
                // ->and('stock_out  < stock.stock_quantity')
                ->group_by('stock.stock_id')
                ->fetchAll();
//var_dump($db->error());
    die(json_encode($stock));
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
                ->where($whr)
                ->and('product_id IN (SELECT product FROM stock)')
                ->order_by('product_id', 'desc')
                ->fetchAll();

$sortedSales = [];
foreach($sales as $st){
    if(!isset($sortedSales[$st['branch_name']])) $sortedSales[$st['branch_name']] = [];
    $sortedSales[$st['branch_name']][] = $st;
}

$body = '';
ob_start();
include __DIR__.'/html/Expenses_summary.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];