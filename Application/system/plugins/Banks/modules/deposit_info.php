<?php 
$me = human_resources::get_staff();
if($me){
    $config = storage::get_data('system_config')->db_configs;
    $db = db::get_connection($config);
    if(isset($_POST['amount'])){
        //var_dump($_POST);
        if(empty($_POST['received_from']) && empty($_POST['per_invoice_no'])){
            $msg = 'No deposit person';
            $ok = 'error';
        }
        else{
            $data = [
                'owner_branch'=>$me['work_location'],
                'per_invoice_no'=>intval($_POST['per_invoice_no']),   
                'date'=>$_POST['date'], 
                'amount'=>$_POST['amount'],
                'cheque_no'=>$_POST['cheque_no'],
                'received_from'=>$_POST['received_from'],
                'received_by'=>$me['user_reference'],
                'bank'=>$_POST['bank']
            ];

            if($data['per_invoice_no'] > 0){
                $inv_total = $db->select('invoice_items', 'SUM(price * quantity) as amount, skip_list')
                                ->join('invoice', 'invoice_id=invoice')
                                ->where(['invoice'=>$data['per_invoice_no']])
                                ->fetch();

                $deposit_total = $db->select('deposit_info', 'SUM(amount) as paid')
                                    ->where(['per_invoice_no'=>$data['per_invoice_no']])
                                    ->fetch();
                //var_dump($inv_total, $deposit_total); die;

                $depo = intval($deposit_total['paid']) + intval($data['amount']);
                $inv = intval($inv_total['amount']);
                $skiplist = $inv_total['skip_list'] ? json_decode($inv_total['skip_list'], true) : [];
                if(!in_array('VAT', $skiplist)) $inv = $inv + (0.18 * $inv);
                if($depo >= $inv){
                    $db->update('tax_invoice', ['payment_status'=>'full_paid'])
                        ->where(['reference_invoice'=>$data['per_invoice_no']])
                        ->commit();
                }
                else{
                    $db->update('tax_invoice', ['payment_status'=>'incomplete_payment'])
                        ->where(['reference_invoice'=>$data['per_invoice_no']])
                        ->commit();
                }
            }

            if(isset($_POST['deposit_id']) && intval($_POST['deposit_id']) > 0){
                $k = intval($_POST['deposit_id']);
                $db->update('deposit_info', $data)->where(['deposit_id'=>$_POST['deposit_id']])->commit();
            }
            else $k = $db->insert('deposit_info', $data);
            $ok = 'error';
            if(!$db->error() && $k) {
                $msg = 'deposit saved successful';
                $ok ='success';
            }
            else $msg = $db->error()['message']; 
        }
        if(isset($_POST['ajax_request'])) die($msg);
    }
    
    if(isset($_POST['delete_deposit'])){
        $k = $db->delete('deposit_info')->where(['deposit_id'=>intval($_POST['delete_deposit'])])->commit();
        if(!$db->error() && $k){
            $msg = [
                'status'=>'success',
                'message'=>'deposit deleted successful'
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
    if($me['work_location'] == human_resources::get_headquarters_branch()) {
        $whr = 1;
    }
    else{
        $whr = ['owner_branch'=>$me['work_location']];
    }
    $deposit = $db->select('deposit_info')
                    ->join('branches', 'branch_id=owner_branch')
                    ->join('user_accounts','user_id=received_by')
                    ->join('invoice', 'invoice_id=per_invoice_no','left')
                    ->join('customer', 'invoice.customer=customer_id', 'left')
                    ->join('banks','bank_id=bank')
                    ->where($whr)
                    ->order_by('deposit_id', 'desc')
                    ->fetchAll();

    $banks = $db ->select('banks','bank_id, bank_name')
                    ->where(1)
                    ->fetchAll();

    $customer = $db ->select('customer','customer_id, customer_name')
                    ->where(1)
                    ->fetchAll();

    $ti = $db->select('invoice', "invoice_id, customer_name")
         ->join('customer','customer_id=customer')
         ->join('tax_invoice', 'invoice_id=reference_invoice')
         ->join('user_accounts', 'user_id=sale_represantative')
         ->where("invoice.owner_branch={$me['work_location']}")
         ->and("payment_status != 'full_paid'")
         ->fetchAll();

    $sortedDeposit = [];
    foreach($deposit as $st){
        if(!isset($sortedDeposit[$st['branch_name']])) $sortedDeposit[$st['branch_name']] = [];
        $sortedDeposit[$st['branch_name']][] = $st;
    }

    $body = '';
    ob_start();
    include __DIR__.'/html/deposit_info.html';
    $body = ob_get_clean();
    $return = ['title'=>' ','body'=>$body];
}