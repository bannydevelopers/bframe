<?php 
$me = human_resources::get_staff();
if($me){
    $hq = human_resources::get_headquarters_branch();
    $config = storage::get_data('system_config')->db_configs;
    $db = db::get_connection($config);

    if($me['work_location'] == human_resources::get_headquarters_branch()) {
        $whr = 1;
    }
    else{
        $whr = "invoice.owner_branch={$me['work_location']}";
    }
    if(isset($_POST['invoice_type'])){
        $inv_data = [
            'invoice_type'=>addslashes($_POST['invoice_type']),
            'due_date'=>addslashes($_POST['due_date']),
            'order_number'=>addslashes($_POST['order_number']),
            'customer'=>addslashes($_POST['customer']),
            'sale_represantative'=>intval($me['user_reference']),
            'owner_branch'=>intval($me['work_location']),
            'created_time'=>addslashes($_POST['created_time'])
        ];
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
    if(isset($_POST['qty'])){
        /*
            //INSERT INTO table_users (cod_user, date, user_rol, cod_office) VALUES
            //('622057', '12082014', 'student', '17389551'),
            //('2913659', '12082014', 'assistant','17389551'),
            //('6160230', '12082014', 'admin', '17389551')
            //ON DUPLICATE KEY UPDATE
            //cod_user=VALUES(cod_user), date=VALUES(date)
        */
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
    $items_q = "(SELECT JSON_ARRAYAGG(JSON_OBJECT('id', item_id, 'invoice',invoice, 'product', product_name, 'price', price, 'qty', quantity, 'product_id', product_id, 'item_desc', product_description, 'unit_single', product_unit_singular, 'unit_prural', product_unit_plural)) FROM invoice_items JOIN product ON product_id=product
    WHERE invoice_id=invoice) AS invoice_items";
    $qry = "invoice.*, branches.branch_name, customer.*, user_accounts.full_name, {$items_q}";
        //FROM invoice JOIN  ON  JOIN  ON ";
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

    if($me['work_location'] == human_resources::get_headquarters_branch()) {
        $whr = 1;
    }
    else{
        $whr = ["branch_id"=>$me['work_location']];
    }

    $default = storage::get_data('system_config')->company_profile;
    $branches = $db->select('branches', 'branch_id,branch_profile')->where($whr)->fetchAll();
    $company = [];
    foreach($branches as $b){
        if(!$b['branch_profile']) $company[$b['branch_id']] = $default;
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
  
    //var_dump('<pre>',$sortedInvoice);die;
    ob_start();
    include __DIR__.'/html/invoice.html';
    $body = ob_get_clean();
    $return = ['title'=>' ','body'=>$body];
}