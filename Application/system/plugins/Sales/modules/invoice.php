<?php 
$me = human_resources::get_staff();
if($me){
    $hq = human_resources::get_headquarters_branch();
    $config = storage::get_data('system_config')->db_configs;
    $db = db::get_connection($config);

    $qry = "SELECT invoice.*, branches.branch_name, customer.customer_name,
        (SELECT JSON_ARRAYAGG(JSON_OBJECT('id', item_id, 'invoice',invoice, 'product', product_name, 'price', price, 'qty', quantity, 'product_id', product_id, 'item_desc', product_description, 'unit_single', product_unit_singular, 'unit_prural', product_unit_plural)) FROM invoice_items JOIN product ON product_id=product
        WHERE invoice_id=invoice) AS invoice_items
        FROM invoice JOIN branches ON branch_id=owner_branch JOIN customer ON customer_id=customer";
    $invoice = $db->query($qry)->fetchAll();
    //var_dump($db->error(),$invoice);
    $sortedInvoice = [];
    foreach($invoice as $inv){
        if(!isset($sortedInvoice[$inv['branch_name']])) $sortedInvoice[$inv['branch_name']] = [];
        $inv['invoice_items'] = $inv['invoice_items'] ? json_decode($inv['invoice_items']) : [];
        $sortedInvoice[$inv['branch_name']][] = $inv;
    } 
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