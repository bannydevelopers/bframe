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
if(isset($_POST['delete_item'])){
    $db->delete('invoice_items')->where(['item_id'=>intval($_POST['delete_item'])])->commit();
    if($db->error()) die('error');
    else die('ok');
}
if(isset($_POST['due_date'])){
    $inv_data = [
        'due_date'=>addslashes($_POST['due_date']),
        'order_number'=>addslashes($_POST['order_number']),
        'customer'=>addslashes($_POST['customer']),
        'sale_represantative'=>intval($me['user_reference']),
        'owner_branch'=>intval($me['work_location']),
        'created_time'=>addslashes($_POST['created_time']),
        'remarks'=>addslashes($_POST['invoice_remarks'])
    ];

    if(isset($_POST['skip_list'])) $inv_data['skip_list'] = json_encode(array_keys($_POST['skip_list']));
    $inv_items_qry = [];
    $inv_id = $db->insert('invoice', $inv_data);
    if($inv_id){
        foreach($_POST['product'] as $k=>$p){
            $inv_items_qry[] = "({$p}, {$inv_id}, {$_POST['price'][$k]}, {$_POST['quantity'][$k]})";
        }
        $inv_items_qry = implode(',', $inv_items_qry);
        $db->query("INSERT INTO invoice_items (product, invoice, price, quantity) VALUES {$inv_items_qry}");
    }
    if($db->error()) $msg = $db->error()['message'];
    else $msg = 'Saved successful';
    if(isset($_POST['ajax_request'])) die($msg);
}
if(isset($_POST['local_purchase_order'])){
    $data = [
        'reference_invoice'=>intval($_POST['reference_invoice']),
        'local_purchase_order'=>addslashes($_POST['local_purchase_order']),
        'recorded_by'=>user::init()->get_session_user('user_id')
    ];

    $k = $db->insert('tax_invoice', $data);
    if(!$db->error()){
        $msg = 'Saved successful';
    }
    else{
        $msg = $db->error()['message'];
    }
    if(isset($_POST['ajax_request'])) die($msg);
}
if(isset($_POST['qty']) or isset($_POST['qtyx'])){
    /*
        //INSERT INTO table_users (cod_user, date, user_rol, cod_office) VALUES
        //('622057', '12082014', 'student', '17389551'),
        //('2913659', '12082014', 'assistant','17389551'),
        //('6160230', '12082014', 'admin', '17389551')
        //ON DUPLICATE KEY UPDATE
        //cod_user=VALUES(cod_user), date=VALUES(date)
    */
    $tmp = [];
    if(isset($_POST['qty']) && is_array($_POST['qty'])){
        foreach($_POST['qty'] as $id=>$qty){
            $tmp[] = "({$id}, {$_POST['price'][$id]}, 8, 8, $qty)";
        }
    }
    if(isset($_POST['qtyx']) && is_array($_POST['qtyx'])){
        foreach($_POST['qtyx'] as $idx=>$qtyx){
            $tmp[] = "(NULL, {$_POST['prcx'][$idx]}, {$_POST['product'][$idx]}, {$_POST['invoice']}, $qtyx)";
        }
    }
    //var_dump('<pre>',$tmp);die('</pre>');
    $tmp = implode(',', $tmp);
    $qry = "INSERT INTO invoice_items (item_id, price, product, invoice, quantity) VALUES "
            . " {$tmp} ON DUPLICATE KEY UPDATE item_id = VALUES(item_id), price=VALUES(price), quantity=VALUES(quantity)";
    $db->query($qry);
    if($db->error()) die('Saving failed');
    else die('Saved successful');
}
if(isset($_POST['delete_invoice'])){
    $db->delete('invoice_items')->where(['invoice'=>intval($_POST['delete_invoice'])])->commit();
    if(!$db->error()){
        $db->delete('invoice')->where(['invoice_id'=>intval($_POST['delete_invoice'])])->commit();
        if($db->error()) $msg = $db->error()['message'];
        else $msg = 'Invoice deleted successful!';
    }
    else{
        $msg = $db->error()['message'];
    }
    die($msg);
}
$items_q = "(
                SELECT JSON_ARRAYAGG(
                    JSON_OBJECT(
                        'id', item_id, 'invoice',invoice, 'product', product_name, 'price', price, 'qty', quantity, 
                        'product_id', product_id, 'item_desc', product_description, 'unit_single', 
                        product_unit_singular, 'unit_prural', product_unit_plural
                    )
                ) 
                FROM invoice_items JOIN product ON product_id=product WHERE invoice_id=invoice
            ) AS invoice_items";
$qry = "invoice.*, branches.branch_name, customer.*, user_accounts.full_name, {$items_q}, tax_invoice.*, invoice.created_time as created";
    //FROM invoice JOIN  ON  JOIN  ON ";
$proforma = $db->select('invoice',$qry)
                ->join('branches','branch_id=owner_branch')
                ->join('tax_invoice', 'invoice_id=reference_invoice', 'left')
                ->join('customer', 'customer_id=customer')
                ->join('user_accounts', 'user_id=sale_represantative')
                ->where($whr)
                ->order_by('invoice_id', 'desc')
                ->fetchAll();

//var_dump($db->error(),$invoice);
$sortedInvoice = [];
foreach($proforma as $inv){
    if(!isset($sortedInvoice[$inv['branch_name']])) $sortedInvoice[$inv['branch_name']] = ['proforma'=>[]];
    $inv['invoice_items'] = $inv['invoice_items'] ? json_decode($inv['invoice_items'],true) : [];
    $sortedInvoice[$inv['branch_name']]['proforma'][] = $inv;
}
$tax_invoices = $db->select('tax_invoice', $qry)
                    ->join('invoice','invoice_id=reference_invoice')
                    ->join('branches','branch_id=owner_branch')
                    ->join('customer', 'customer_id=customer')
                    ->join('user_accounts', 'user_id=sale_represantative')
                    ->where($whr)
                    ->order_by('invoice_id', 'desc')
                    ->fetchAll();

foreach($tax_invoices as $inv){
    if(!isset($sortedInvoice[$inv['branch_name']])) $sortedInvoice[$inv['branch_name']] = ['tax'=>[]];
    $inv['invoice_items'] = $inv['invoice_items'] ? json_decode($inv['invoice_items'],true) : [];
    $inv['invoice_type'] = 'tax';
    $sortedInvoice[$inv['branch_name']]['tax'][] = $inv;
}
if($me['work_location'] == human_resources::get_headquarters_branch()) {
    $whr = 1;
}
else{
    $whr = ["branch_id"=>$me['work_location']];
}

$company = [];
$company[] = storage::get_data('system_config')->company_profile;
$branches = $db->select('branches', 'branch_id,branch_profile')->where($whr)->fetchAll();
foreach($branches as $b){
    if(!$b['branch_profile']) $company[$b['branch_id']] = $company[0];
    else $company[$b['branch_id']] = json_decode($b['branch_profile']);
}

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
//$proforma = $db->select('invoice')->where(1)->fetchAll();
//var_dump('<pre>',$sortedInvoice);die;
ob_start();
include __DIR__.'/html/invoice.html';
$body = ob_get_clean();
$return = ['title'=>' ','body'=>$body];