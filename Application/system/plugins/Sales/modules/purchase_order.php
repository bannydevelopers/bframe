<?php 

$hq = human_resources::get_headquarters_branch();
$config = storage::get_data('system_config')->db_configs;
$db = db::get_connection($config);

if($me['work_location'] == human_resources::get_headquarters_branch()) {
    $whr = 1;
}
else{
    $whr = "invoice.owner_branch={$me['work_location']}";
}
if(isset($_POST['qty'])){
    $tmp = [];
    foreach($_POST['qty'] as $id=>$qty){
        $tmp[] = "({$id}, {$_POST['price'][$id]}, 8, 8, $qty)";
    }
    $tmp = implode(',', $tmp);
    $qry = "INSERT INTO invoice_items (item_id, price, product, invoice, quantity) VALUES "
            . " {$tmp} ON DUPLICATE KEY UPDATE item_id = VALUES(item_id), price=VALUES(price), quantity=VALUES(quantity)";
    $db->query($qry);
    if($db->error()) die('Saving failed');
    else die('Saved successful');
}
$invoice = $db->select('invoice',$qry)
                ->join('branches','branch_id=owner_branch')
                ->join('customer', 'customer_id=customer')
                ->join('user_accounts', 'user_id=sale_represantative')
                ->where($whr)
                ->order_by('invoice_id', 'desc')
                ->fetchAll();

//var_dump($db->error(),$invoice);
$sortedInvoice = [];
foreach($invoice as $inv){
    if(!isset($sortedInvoice[$inv['branch_name']])) $sortedInvoice[$inv['branch_name']] = [];
    $inv['invoice_items'] = $inv['invoice_items'] ? json_decode($inv['invoice_items'],true) : [];
    $sortedInvoice[$inv['branch_name']][] = $inv;
} 
$company = storage::get_data('system_config')->company_profile;

ob_start();
$dir = realpath(__DIR__.'/html/invoice_tpl');
foreach(scandir($dir) as $filename){
    if(pathinfo("{$dir}/{$filename}", PATHINFO_EXTENSION) == 'html') include "{$dir}/{$filename}";
}
$invoice_tpl = ob_get_clean();
if($me['work_location'] == human_resources::get_headquarters_branch()) {
    $whr = 1;
}
else{
    $whr = ["owner_branch"=>$me['work_location']];
}
$product = $db->select('product')->where($whr)->fetchAll();
$customer = $db->select('customer')->where($whr)->fetchAll();

//var_dump('<pre>',$sortedInvoice);die;
ob_start();
include __DIR__.'/html/invoice.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];