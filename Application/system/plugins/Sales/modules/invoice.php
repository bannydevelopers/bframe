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