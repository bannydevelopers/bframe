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
    
    if(isset($_POST['qty'])){
        $qry = "INSERT INTO invoice_items (item_id, price, product, invoice, quantity) VALUES ";
        //INSERT INTO table_users (cod_user, date, user_rol, cod_office) VALUES
        //('622057', '12082014', 'student', '17389551'),
        //('2913659', '12082014', 'assistant','17389551'),
        //('6160230', '12082014', 'admin', '17389551')
        //ON DUPLICATE KEY UPDATE
        //cod_user=VALUES(cod_user), date=VALUES(date)
        $tmp = [];
        foreach($_POST['qty'] as $id=>$qty){
            $tmp[] = "({$id}, {$_POST['price'][$id]}, 8, 8, $qty)";
        }
        $tmp = implode(',', $tmp);
        $qry .= " {$tmp} ON DUPLICATE KEY UPDATE item_id = VALUES(item_id), price=VALUES(price), quantity=VALUES(quantity)";
        $db->query($qry);
        if($db->error()) die('Saving failed');
        else die('Saved successful');
    }
    $items_q = "(SELECT JSON_ARRAYAGG(JSON_OBJECT('id', item_id, 'invoice',invoice, 'product', product_name, 'price', price, 'qty', quantity, 'product_id', product_id, 'item_desc', product_description, 'unit_single', product_unit_singular, 'unit_prural', product_unit_plural)) FROM invoice_items JOIN product ON product_id=product
    WHERE invoice_id=invoice) AS invoice_items";
    $qry = "invoice.*, branches.branch_name, customer.customer_name, {$items_q}";
        //FROM invoice JOIN  ON  JOIN  ON ";
    $invoice = $db->select('invoice',$qry)
                  ->join('branches','branch_id=owner_branch')
                  ->join('customer', 'customer_id=customer')
                  ->where($whr)
                  ->fetchAll();

    //var_dump($db->error(),$invoice);
    $sortedInvoice = [];
    foreach($invoice as $inv){
        if(!isset($sortedInvoice[$inv['branch_name']])) $sortedInvoice[$inv['branch_name']] = [];
        $inv['invoice_items'] = $inv['invoice_items'] ? json_decode($inv['invoice_items'],true) : [];
        $sortedInvoice[$inv['branch_name']][] = $inv;
    } 
    //var_dump('<pre>',$sortedInvoice);die;
    ob_start();
    include __DIR__.'/html/invoice.html';
    $body = ob_get_clean();
    $return = ['title'=>' ','body'=>$body];



    /*SELECT p.id, p.name, 

    (SELECT JSON_ARRAYAGG(JSON_OBJECT('id', pc.id, 'name', pc.name)) FROM people pc
    WHERE pc.parent_id=p.id) AS children
    
    FROM people p
    SELECT invoice.*, 
    (SELECT JSON_ARRAYAGG(JSON_OBJECT('id', item_id, 'invoice',invoice, 'product', product, 'price', price, 'qty', quantity)) FROM invoice_items
    WHERE invoice_id=invoice) AS children
    FROM invoice*/
}