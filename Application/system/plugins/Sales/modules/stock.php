<?php 

$config = storage::get_data('system_config')->db_configs;
$db = db::get_connection($config);
$hq = human_resources::get_headquarters_branch();
if(isset($_POST['stock_batch'])){
    var_dump($_POST);
    $data = [
        'owner_branch'=>$me['work_location'],
        'product'=>$_POST['product_name'], 
        'stock_batch'=>$_POST['stock_batch'], 
        'stock_quantity'=>$_POST['stock_quantity'], 
        'buying_price'=>$_POST['buying_price'], 
        'selling_price'=>$_POST['selling_price'], 
        'stock_expenses'=>$_POST['stock_expenses'],
        'stock_receiver'=>$me['user_reference'],
        'stock_supplier'=>$_POST['supplier']
    ];
    if(isset($_POST['stock_id']) && intval($_POST['stock_id']) > 0){
        $k = intval($_POST['stock_id']);
        $db->update('stock', $data)->where(['stock_id'=>$_POST['stock_id']])->commit();
    }
    else $k = $db->insert('stock', $data);
    if(!$db->error() && $k) {
        $msg = 'Stock saved successful';
        $ok =true;
    }
    else $msg = $db->error()['message']; 
}
//var_dump($db->error());
if(isset($_POST['delete_stock'])){
    $db->delete('stock')->where(['user_reference'=>intval($_POST['delete_stock'])])->commit();
    if(!$db->error()) $db->delete('user_accounts')->where(['user_id'=>intval($_POST['delete_stock'])])->commit();
    if(!$db->error()){
        $msg = [
            'status'=>'success',
            'message'=>'Stock deleted successful'
        ];
    }
    else{
        $msg = [
            'status'=>'error',
            'message'=>$db->error()['message']
        ];
    }
}
$stock = $db ->select('stock')
                ->join('branches', 'branch_id=owner_branch')
                ->join('product', 'product_id=product', 'product_price=selling_price')
                ->join('supplier','supplier_id=stock_supplier')
                ->join('user_accounts', 'user_id=stock_receiver')
                ->fetchAll();

$whr = 'stock.stock_ref < 1';
if($me['work_location'] != $hq) {
    $whr .= " AND stock.owner_branch={$me['work_location']}";
}
$stock = $db->select('stock', 'stock.*,product.*,supplier.*,branches.branch_name,user_accounts.full_name,sum(outgoing.stock_quantity) as stock_out')
            ->join('branches', 'branch_id=owner_branch')
            ->join('product', 'product_id=stock.product')
            ->join('stock as outgoing', 'outgoing.stock_ref=stock.stock_id', 'left')
            //->join('store', 'store.store_id=stock.store')
            ->join('supplier', 'supplier.supplier_id=stock.stock_supplier')
            ->join('user_accounts', 'user_id=stock.stock_receiver')
            ->where($whr)
            ->group_by('stock_id')
            ->fetchAll();

$whr = $me['work_location'] == $hq ? 1 : ['owner_branch'=>$me['work_location']];
$product = $db->select('product', 'product_id, product_name, product_price')
            ->where($whr)
            ->fetchAll();


$supplier = $db ->select('supplier','supplier_id, supplier_name')
                ->where($whr)
                ->fetchAll();

$sortedStock = [];
foreach($stock as $st){
    if(!isset($sortedStock[$st['branch_name']])) $sortedStock[$st['branch_name']] = [];
    $sortedStock[$st['branch_name']][] = $st;
}

$body = '';
ob_start();
include __DIR__.'/html/stock.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];