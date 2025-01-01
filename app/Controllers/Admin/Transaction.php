<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Permission;
use CodeIgniter\HTTP\RedirectResponse;


class Transaction extends BaseController
{

    protected $permission;
    protected $validation;
    protected $session;
    protected $crop;
    private $module_name = 'Transaction';

    public function __construct()
    {
        $this->permission = new Permission();
        $this->validation = \Config\Services::validation();
        $this->session = \Config\Services::session();
        $this->crop = \Config\Services::image();
    }

    /**
     * @description This method provides transaction view
     * @return RedirectResponse|void
     */
    public function index()
    {
        $isLoggedIn = $this->session->isLoggedIn;
        $role_id = $this->session->role;
        if (!isset($isLoggedIn) || $isLoggedIn != TRUE) {
            return redirect()->to(site_url('Admin/login'));
        } else {
            $shopId = $this->session->shopId;
            $transactionTable = DB()->table('transaction');
            $data['transaction_data'] = $transactionTable->where('sch_id', $shopId)->get()->getResult();


            // All Permissions
            //$perm = array('create','read','update','delete','mod_access');
            $perm = $this->permission->module_permission_list($role_id, $this->module_name);
            foreach ($perm as $key => $val) {
                $data[$key] = $this->permission->have_access($role_id, $this->module_name, $key);
            }
            echo view('Admin/header');
            echo view('Admin/sidebar');
            if (isset($data['mod_access']) and $data['mod_access'] == 1) {
                echo view('Admin/Transaction/list', $data);
            } else {
                echo view('no_permission');
            }
            echo view('Admin/footer');
        }
    }

    /**
     * @description This method provides transaction create view
     * @return RedirectResponse|void
     */
    public function create()
    {
        $isLoggedIn = $this->session->isLoggedIn;
        $role_id = $this->session->role;
        if (!isset($isLoggedIn) || $isLoggedIn != TRUE) {
            return redirect()->to(site_url('Admin/login'));
        } else {

            $data['button'] = 'Process';
            $data['action'] = base_url('Admin/Transaction/customer_transaction_action');
            $data['actionsuppl'] = base_url('Admin/Transaction/supplier_transaction_action');
            $data['actionLoanPro'] = base_url('Admin/Transaction/loan_pro_transaction_action');
            $data['actionLc'] = base_url('Admin/Transaction/lc_transaction_action');
            $data['actionBank'] = base_url('Admin/Transaction/bank_transaction_action');
            $data['actionExpense'] = base_url('Admin/Transaction/expense_transaction_action');
            $data['actionOtherSales'] = base_url('Admin/Transaction/otherSales_transaction_action');
            $data['actionSalaryEmployee'] = base_url('Admin/Transaction/salaryEmployee_transaction_action');
            $data['actionVatPay'] = base_url('Admin/Transaction/vat_pay_action');


            // All Permissions
            //$perm = array('create','read','update','delete','mod_access');
            $perm = $this->permission->module_permission_list($role_id, $this->module_name);
            foreach ($perm as $key => $val) {
                $data[$key] = $this->permission->have_access($role_id, $this->module_name, $key);
            }
            echo view('Admin/header');
            echo view('Admin/sidebar');
            if (isset($data['mod_access']) and $data['create'] == 1) {
                echo view('Admin/Transaction/create', $data);
            } else {
                echo view('no_permission');
            }
            echo view('Admin/footer');
        }
    }

    /**
     * @description This method store transaction
     * @return void
     */
    public function create_action()
    {
        $shopId = $this->session->shopId;
        $userId = $this->session->userId;

        $data['name'] = $this->request->getPost('name');
        $data['sch_id'] = $shopId;
        $data['createdBy'] = $userId;
        $data['createdDtm'] = date('Y-m-d h:i:s');

        $this->validation->setRules([
            'name' => ['label' => 'Name', 'rules' => 'required'],
        ]);

        if ($this->validation->run($data) == FALSE) {
            print '<div class="alert alert-danger alert-dismissible" role="alert">' . $this->validation->listErrors() . ' <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
        } else {


            $TransactionTable = DB()->table('Transaction');
            if ($TransactionTable->insert($data)) {
                print '<div class="alert alert-success alert-dismissible" role="alert"> Your transaction is successful  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
            } else {
                print '<div class="alert alert-danger alert-dismissible" role="alert"> Something went wrong  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
            }
        }
    }

    /**
     * @description This method store customer transaction
     * @return void
     */
    public function customer_transaction_action()
    {
        $shopId = $this->session->shopId;
        $userId = $this->session->userId;

        $trangactionType = $this->request->getPost('trangaction_type');
        $paymentType = $this->request->getPost('payment_type');
        $amount = str_replace(',', '', $this->request->getPost('amount'));
        $custId = $this->request->getPost('customer_id');
        //customer rest balance
        $custBalance = get_data_by_id('balance', 'customers', 'customer_id', $custId);

        //Ledger Nagodan
        $shopsBalance = get_data_by_id('cash', 'shops', 'sch_id', $shopId);


        $chequeNo = $this->request->getPost('chequeNo');
        $chequeAmount = $this->request->getPost('chequeAmount');
        $sms = $this->request->getPost('sms');


        if ($amount < 0) {
            print '<div class="alert alert-danger alert-dismissible" role="alert">Please enter valid amount<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
            die();
        }


        if ($paymentType == 1) {
            $bankId = $this->request->getPost('bank_id');
            if ($bankId) {
                $bankCash = get_data_by_id('balance', 'bank', 'bank_id', $bankId);
                $bankUpData = $bankCash - $amount;
            }
            $availableBalance = checkBankBalance($bankId, $amount);

            if (empty($bankId)) {
                print '<div class="alert alert-danger alert-dismissible" role="alert">Please select a bank <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
                die();
            }
        }
        if ($paymentType == 2) {
            $availableBalance = checkNagadBalance($amount);
        }


        $shopCheck = check_shop('customers', 'customer_id', $custId);

        if ($shopCheck == 1) {
            if ($trangactionType == 1) {
                if ($availableBalance == true) {

                    DB()->transStart();

                    $custBalance2 = get_data_by_id('balance', 'customers', 'customer_id', $custId);
                    $custRestBalan2 = $custBalance2 + $amount;

                    //insert Transaction in transaction table (start)
                    $transdata = array(
                        'sch_id' => $shopId,
                        'customer_id' => $custId,
                        'title' => $this->request->getPost('particulars'),
                        'trangaction_type' => 'Cr.',
                        'amount' => $amount,
                        'createdBy' => $userId,
                        'createdDtm' => date('Y-m-d h:i:s')
                    );
                    $transactionTab = DB()->table('transaction');
                    $transactionTab->insert($transdata);
                    $transId2 = DB()->insertID();
                    //insert Transaction in transaction table (end)


                    // transaction amount calculet to customer balance and update customer balance (start)
                    $dataCustBlan2 = array(
                        'balance' => $custRestBalan2,
                        'updatedBy' => $userId,
                    );
                    $customersTab = DB()->table('customers');
                    $customersTab->where('customer_id', $custId)->update($dataCustBlan2);
                    // transaction amount calculet to customer balance and update customer balance (end)


                    //insert transaction in ledger Transaction table (start)
                    $cusLedgdata2 = array(
                        'sch_id' => $shopId,
                        'trans_id' => $transId2,
                        'customer_id' => $custId,
                        'particulars' => $this->request->getPost('particulars'),
                        'trangaction_type' => 'Dr.',
                        'amount' => $amount,
                        'rest_balance' => $custRestBalan2,
                        'createdBy' => $userId,
                        'createdDtm' => date('Y-m-d h:i:s')
                    );
                    $ledgerTab = DB()->table('ledger');
                    $ledgerTab->insert($cusLedgdata2);
                    //insert transaction in ledger Transaction table (end)

                    //transaction payment amount payment cash or bank(start)
                    //Ledger Nagodan
                    $shopsBalance2 = get_data_by_id('cash', 'shops', 'sch_id', $shopId);
                    $shopRestBalan2 = $shopsBalance2 - $amount;

                    if ($paymentType == 2) {
                        //transaction cash payment calculet cash amount and update cash or create ledger (start)
                        $shopedata2 = array(
                            'sch_id' => $shopId,
                            'trans_id' => $transId2,
                            'particulars' => $this->request->getPost('particulars'),
                            'trangaction_type' => 'Cr.',
                            'amount' => $amount,
                            'rest_balance' => $shopRestBalan2,
                            'createdBy' => $userId,
                            'createdDtm' => date('Y-m-d h:i:s')
                        );
                        $ledger_nagodanTab = DB()->table('ledger_nagodan');
                        $ledger_nagodanTab->insert($shopedata2);

                        //update shops balance
                        $shopeupdatedata2 = array(
                            'cash' => $shopRestBalan2,
                            'updatedBy' => $userId,
                        );
                        $shopsTab = DB()->table('shops');
                        $shopsTab->where('sch_id', $shopId)->update($shopeupdatedata2);
                        //transaction cash payment calculet cash amount and update cash or create ledger (end)
                    } else {
                        //transaction bank payment calculet bank amount and update bank or create ledger bank (start)
                        $bankId = $this->request->getPost('bank_id');
                        $bankRestBalan2 = '';
                        if ($bankId) {
                            $bankCash2 = get_data_by_id('balance', 'bank', 'bank_id', $bankId);
                            $bankRestBalan2 = $bankCash2 - $amount;
                        }

                        $lgBankData2 = array(
                            'sch_id' => $shopId,
                            'bank_id' => $bankId,
                            'trans_id' => $transId2,
                            'trangaction_type' => 'Cr.',
                            'particulars' => $this->request->getPost('particulars'),
                            'amount' => $amount,
                            'rest_balance' => $bankRestBalan2,
                            'createdBy' => $userId,
                            'createdDtm' => date('Y-m-d h:i:s')
                        );
                        $ledger_bankTab = DB()->table('ledger_bank');
                        $ledger_bankTab->insert($lgBankData2);

                        //update bank balance
                        $bankData2 = array(
                            'balance' => $bankRestBalan2,
                            'updatedBy' => $userId,
                        );
                        $bankTab = DB()->table('bank');
                        $bankTab->where('bank_id', $bankId)->update($bankData2);
                        //transaction bank payment calculet bank amount and update bank or create ledger bank (end)
                    }
                    //transaction payment amount payment cash or bank(end)

                    DB()->transComplete();

                    if(!empty($sms)) {
                        $message = 'Thank you for your transaction.Your transaction amount is-' . $amount;
                        $phone = get_data_by_id('mobile', 'customers', 'customer_id', $custId);
                        send_sms($phone, $message);
                    }

                    print '<div class="alert alert-success alert-dismissible" role="alert">Your transaction is successful<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
                } else {
                    print '<div class="alert alert-danger alert-dismissible" role="alert">Not Enough Balance<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
                }


            } else {
                if ($chequeNo > 0) {

                    //cheque pay amount calculate and insert cheque tabile(start)
                    $chequeData = array(
                        'sch_id' => $shopId,
                        'chaque_number' => $chequeNo,
                        'to' => $userId,
                        'from' => $custId,
                        'amount' => $chequeAmount,
                        'createdDtm' => date('Y-m-d h:i:s')
                    );

                    $chaqueTab = DB()->table('chaque');
                    $chaqueTab->insert($chequeData);

                    print '<div class="alert alert-success alert-dismissible" role="alert">Your transaction is successful<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
                    //cheque pay amount calculate and insert cheque tabile(end)

                } else {

                    $bankId = $this->request->getPost('bank_id');
                    $bankRestBalan = '';
                    if ($bankId) {
                        $bankCash = get_data_by_id('balance', 'bank', 'bank_id', $bankId);
                        $bankRestBalan = $bankCash + $amount;
                    }

//                    if ($custBalance >= $amount) {
                        $custRestBalan = $custBalance - $amount;
                        $shopRestBalan = $shopsBalance + $amount;

                        DB()->transStart();
                        //insert Transaction in transaction table (start)
                        $transdata = array(
                            'sch_id' => $shopId,
                            'customer_id' => $custId,
                            'title' => $this->request->getPost('particulars'),
                            'trangaction_type' => 'Dr.',
                            'amount' => $amount,
                            'createdBy' => $userId,
                            'createdDtm' => date('Y-m-d h:i:s')
                        );

                        $transactionTable = DB()->table('transaction');
                        $transactionTable->insert($transdata);
                        $transId = DB()->insertID();
                        //insert Transaction in transaction table (end)


                        // transaction amount calculet to customer balance and update customer balance (start)
                        $dataCustBlan = array(
                            'balance' => $custRestBalan,
                            'updatedBy' => $userId,
                        );
                        $customersTable = DB()->table('customers');
                        $customersTable->where('customer_id', $custId)->update($dataCustBlan);
                        // transaction amount calculet to customer balance and update customer balance (end)


                        //insert transaction in ledger Transaction table (start)
                        $cusLedgdata = array(
                            'sch_id' => $shopId,
                            'trans_id' => $transId,
                            'customer_id' => $custId,
                            'particulars' => $this->request->getPost('particulars'),
                            'trangaction_type' => 'Cr.',
                            'amount' => $amount,
                            'rest_balance' => $custRestBalan,
                            'createdBy' => $userId,
                            'createdDtm' => date('Y-m-d h:i:s')
                        );
                        $ledgerTable = DB()->table('ledger');
                        $ledgerTable->insert($cusLedgdata);
                        //insert transaction in ledger Transaction table (end)


                        //transaction payment amount payment cash or bank(start)
                        if ($paymentType == 2) {
                            //transaction cash payment calculet cash amount and update cash or create ledger (start)
                            $shopedata = array(
                                'sch_id' => $shopId,
                                'trans_id' => $transId,
                                'particulars' => $this->request->getPost('particulars'),
                                'trangaction_type' => 'Dr.',
                                'amount' => $amount,
                                'rest_balance' => $shopRestBalan,
                                'createdBy' => $userId,
                                'createdDtm' => date('Y-m-d h:i:s')
                            );
                            $ledger_nagodanTable = DB()->table('ledger_nagodan');
                            $ledger_nagodanTable->insert($shopedata);

                            //update shops balance
                            $shopeupdatedata = array(
                                'cash' => $shopRestBalan,
                                'updatedBy' => $userId,
                            );
                            $shopsTable = DB()->table('shops');
                            $shopsTable->where('sch_id', $shopId)->update($shopeupdatedata);
                            //transaction cash payment calculet cash amount and update cash or create ledger (end)
                        } else {
                            //transaction bank payment calculet bank amount and update bank or create ledger bank (start)
                            $lgBankData = array(
                                'sch_id' => $shopId,
                                'bank_id' => $bankId,
                                'trans_id' => $transId,
                                'trangaction_type' => 'Dr.',
                                'particulars' => $this->request->getPost('particulars'),
                                'amount' => $amount,
                                'rest_balance' => $bankRestBalan,
                                'createdBy' => $userId,
                                'createdDtm' => date('Y-m-d h:i:s')
                            );
                            $ledger_bankTable = DB()->table('ledger_bank');
                            $ledger_bankTable->insert($lgBankData);

                            //update bank balance
                            $bankData = array(
                                'balance' => $bankRestBalan,
                                'updatedBy' => $userId,
                            );
                            $bankTable = DB()->table('bank');
                            $bankTable->where('bank_id', $bankId)->update($bankData);
                            //transaction bank payment calculet bank amount and update bank or create ledger bank (end)
                        }
                        //transaction payment amount payment cash or bank(end)

                        DB()->transComplete();

                        print '<div class="alert alert-success alert-dismissible" role="alert">Your transaction is successful<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
//                    } else {
//                        print '<div class="alert alert-danger alert-dismissible" role="alert">This customer could pay maximum<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
//                    }
                }
            }
        } else {
            print '<div class="alert alert-danger alert-dismissible" role="alert">Please input valid customer<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
        }


    }

    /**
     * @description This method customer ledger
     * @return void
     */
    public function custData()
    {

        $custData = $this->request->getPost('custId');

        $ledgerTable = DB()->table('ledger');
        $data = $ledgerTable->where('customer_id', $custData)->orderBy('ledg_id', 'DESC')->limit(10)->get()->getResult();

        $customerBalance = get_data_by_id('balance', 'customers', 'customer_id', $custData);

        $view = '<span class="pull-right"> Balance: ' . showWithCurrencySymbol($customerBalance) . '</span>';
        $view .= '<table class="table table-bordered table-striped" id="TFtable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Particulars</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody>';
        $i = '';
        foreach ($data as $row) {
            $particulars = ($row->particulars == NULL) ? "Pay due" : $row->particulars;
            $amountCr = ($row->trangaction_type != "Cr.") ? "---" : showWithCurrencySymbol($row->amount);
            $amountDr = ($row->trangaction_type != "Dr.") ? "---" : showWithCurrencySymbol($row->amount);

            $view .= '<tr>
                                    <td>' . ++$i . '</td>
                                    <td>' . bdDateFormat($row->createdDtm) . '</td>
                                    <td>' . get_data_by_id('customer_name', 'customers', 'customer_id', $row->customer_id) . '</td>
                                    <td>' . $particulars . '</td>
                                    <td>' . $amountDr . '</td>
                                    <td>' . $amountCr . '</td>
                                    <td>' . showWithCurrencySymbol($row->rest_balance) . '</td>
                                </tr>';
        }

        $view .= '</tbody></table>';

        print $view;
    }

    /**
     * @description This method store supplier transaction
     * @return void
     */
    public function supplier_transaction_action()
    {
        $shopId = $this->session->shopId;
        $userId = $this->session->userId;

        $chequeNo = $this->request->getPost('chequeNo');
        $chequeAmount = str_replace(',', '', $this->request->getPost('chequeAmount'));
        $amount = str_replace(',', '', $this->request->getPost('amount'));
        //Supplier Balance
        $supplierId = $this->request->getPost('supplier_id');
        $supplierBalance = get_data_by_id('balance', 'suppliers', 'supplier_id', $this->request->getPost('supplier_id'));

        //Payment Type
        $paymentType = $this->request->getPost('payment_type');
        $trangactionType = $this->request->getPost('trangaction_type');
        $sms = $this->request->getPost('sms');
        //shop data
        $shopBalance = get_data_by_id('cash', 'shops', 'sch_id', $shopId);


        if ($paymentType == 1) {
            $bankId = $this->request->getPost('bank_id');

            if ($bankId) {
                $bankCash = get_data_by_id('balance', 'bank', 'bank_id', $bankId);
                $bankUpData = $bankCash - $amount;
            }
            $availableBalance = checkBankBalance($bankId, $amount);

            if (empty($bankId)) {
                print '<div class="alert alert-danger alert-dismissible" role="alert">Please select a bank <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
                die();
            }
        }
        if ($paymentType == 2) {
            $availableBalance = checkNagadBalance($amount);
        }


        if ($amount < 0) {
            print '<div class="alert alert-danger alert-dismissible" role="alert">Please enter valid amount<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
            die();
        }


        $shopCheck = check_shop('suppliers', 'supplier_id', $supplierId);
        if ($shopCheck == 1) {
            if ($availableBalance == true) {
                $restBalance = $supplierBalance + $amount;
                $shopUpdateBalance = $shopBalance - $amount;

                DB()->transStart();

                if ($trangactionType == 1) {
                    //insert Transaction table
                    $transdata = array(
                        'sch_id' => $shopId,
                        'supplier_id' => $this->request->getPost('supplier_id'),
                        'title' => $this->request->getPost('particulars'),
                        'trangaction_type' => 'Cr.',
                        'amount' => $amount,
                        'createdBy' => $userId,
                        'createdDtm' => date('Y-m-d h:i:s')
                    );
                    $transactionTab = DB()->table('transaction');
                    $transactionTab->insert($transdata);
                    $ledgSupId = DB()->insertID();


                    //insert data
                    $data = array(
                        'sch_id' => $shopId,
                        'trans_id' => $ledgSupId,
                        'supplier_id' => $this->request->getPost('supplier_id'),
                        'particulars' => $this->request->getPost('particulars'),
                        'trangaction_type' => 'Dr.',
                        'amount' => $amount,
                        'rest_balance' => $restBalance,
                        'createdBy' => $userId,
                        'createdDtm' => date('Y-m-d h:i:s')
                    );
                    $ledger_suppliersTab = DB()->table('ledger_suppliers');
                    $ledger_suppliersTab->insert($data);

                    //Suppliers Balance Update
                    $dataSuppBlan = array(
                        'balance' => $restBalance,
                        'updatedBy' => $userId,
                    );
                    $suppliersTab = DB()->table('suppliers');
                    $suppliersTab->where('supplier_id', $supplierId)->update($dataSuppBlan);
                    //admin transaction
                    if ($paymentType == 2) {
                        //shop balance update
                        $shopData = array(
                            'cash' => $shopUpdateBalance,
                            'updatedBy' => $userId,
                        );
                        $shopsTab = DB()->table('shops');
                        $shopsTab->where('sch_id', $shopId)->update($shopData);

                        //insert ledger_nagodan
                        $lgNagData = array(
                            'sch_id' => $shopId,
                            'trans_id' => $ledgSupId,
                            'trangaction_type' => 'Cr.',
                            'particulars' => $this->request->getPost('particulars'),
                            'amount' => $amount,
                            'rest_balance' => $shopUpdateBalance,
                            'createdBy' => $userId,
                            'createdDtm' => date('Y-m-d h:i:s')
                        );
                        $ledger_nagodanTab = DB()->table('ledger_nagodan');
                        $ledger_nagodanTab->insert($lgNagData);

                    } else {
                        $bankData = array(
                            'balance' => $bankUpData,
                            'updatedBy' => $userId,
                        );
                        $bankTab = DB()->table('bank');
                        $bankTab->where('bank_id', $bankId)->update($bankData);

                        //insert ledger_bank
                        $lgBankData = array(
                            'sch_id' => $shopId,
                            'bank_id' => $bankId,
                            'trans_id' => $ledgSupId,
                            'trangaction_type' => 'Cr.',
                            'particulars' => $this->request->getPost('particulars'),
                            'amount' => $amount,
                            'rest_balance' => $bankUpData,
                            'createdBy' => $userId,
                            'createdDtm' => date('Y-m-d h:i:s')
                        );
                        $ledger_bankTab = DB()->table('ledger_bank');
                        $ledger_bankTab->insert($lgBankData);

                    }
                } else {
                    if ($chequeNo > 0) {

                        //cheque pay amount calculate and insert cheque tabile(start)
                        $chequeData = array(
                            'sch_id' => $shopId,
                            'chaque_number' => $chequeNo,
                            'to' => $userId,
                            'from' => $supplierId,
                            'amount' => $chequeAmount,
                            'createdDtm' => date('Y-m-d h:i:s')
                        );

                        $chaqueTab = DB()->table('chaque');
                        $chaqueTab->insert($chequeData);

                        print '<div class="alert alert-success alert-dismissible" role="alert">Your transaction is successful<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
                        //cheque pay amount calculate and insert cheque tabile(end)

                    } else {

                        //insert Transaction table
                        $transdata = array(
                            'sch_id' => $shopId,
                            'supplier_id' => $supplierId,
                            'title' => $this->request->getPost('particulars'),
                            'trangaction_type' => 'Dr.',
                            'amount' => $amount,
                            'createdBy' => $userId,
                            'createdDtm' => date('Y-m-d h:i:s')
                        );
                        $transactionTab = DB()->table('transaction');
                        $transactionTab->insert($transdata);
                        $ledgSupId2 = DB()->insertID();


                        //insert data
                        $supplierBalance2 = get_data_by_id('balance', 'suppliers', 'supplier_id', $supplierId);
                        $restBalance2 = $supplierBalance2 - $amount;
                        $data2 = array(
                            'sch_id' => $shopId,
                            'trans_id' => $ledgSupId2,
                            'supplier_id' => $supplierId,
                            'particulars' => $this->request->getPost('particulars'),
                            'trangaction_type' => 'Cr.',
                            'amount' => $amount,
                            'rest_balance' => $restBalance2,
                            'createdBy' => $userId,
                            'createdDtm' => date('Y-m-d h:i:s')
                        );
                        $ledger_suppliersTab = DB()->table('ledger_suppliers');
                        $ledger_suppliersTab->insert($data2);

                        //Suppliers Balance Update(start)
                        $dataSuppBlan2 = array(
                            'balance' => $restBalance2,
                            'updatedBy' => $userId,
                        );
                        $suppliersTab = DB()->table('suppliers');
                        $suppliersTab->where('supplier_id', $supplierId)->update($dataSuppBlan2);
                        //Suppliers Balance Update(start)

                        //admin transaction
                        if ($paymentType == 2) {
                            //shop balance update
                            $shopBalance2 = get_data_by_id('cash', 'shops', 'sch_id', $shopId);
                            $shopUpdateBalance2 = $shopBalance2 + $amount;
                            $shopData2 = array(
                                'cash' => $shopUpdateBalance2,
                                'updatedBy' => $userId,
                            );
                            $shopsTab = DB()->table('shops');
                            $shopsTab->where('sch_id', $shopId)->update($shopData2);

                            //insert ledger_nagodan
                            $lgNagData2 = array(
                                'sch_id' => $shopId,
                                'trans_id' => $ledgSupId2,
                                'trangaction_type' => 'Dr.',
                                'particulars' => $this->request->getPost('particulars'),
                                'amount' => $amount,
                                'rest_balance' => $shopUpdateBalance2,
                                'createdBy' => $userId,
                                'createdDtm' => date('Y-m-d h:i:s')
                            );
                            $ledger_nagodanTab = DB()->table('ledger_nagodan');
                            $ledger_nagodanTab->insert($lgNagData2);

                        } else {
                            if ($bankId) {
                                $bankCash2 = get_data_by_id('balance', 'bank', 'bank_id', $bankId);
                                $bankUpData2 = $bankCash2 + $amount;
                            }

                            $bankData2 = array(
                                'balance' => $bankUpData2,
                                'updatedBy' => $userId,
                            );
                            $bankTab = DB()->table('bank');
                            $bankTab->where('bank_id', $bankId)->update($bankData2);

                            //insert ledger_bank
                            $lgBankData2 = array(
                                'sch_id' => $shopId,
                                'bank_id' => $bankId,
                                'trans_id' => $ledgSupId2,
                                'trangaction_type' => 'Dr.',
                                'particulars' => $this->request->getPost('particulars'),
                                'amount' => $amount,
                                'rest_balance' => $bankUpData2,
                                'createdBy' => $userId,
                                'createdDtm' => date('Y-m-d h:i:s')
                            );
                            $ledger_bankTab = DB()->table('ledger_bank');
                            $ledger_bankTab->insert($lgBankData2);

                        }

                    }
                }

                DB()->transComplete();
                if (!empty($sms)) {
                    $message = 'Thank you for your transaction.Your transaction amount is-' . $amount;
                    $phone = get_data_by_id('phone', 'suppliers', 'supplier_id', $supplierId);
                    send_sms($phone, $message);
                }

                print '<div class="alert alert-success alert-dismissible" role="alert">Your transaction is successful<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';

            } else {

                print '<div class="alert alert-danger alert-dismissible" role="alert">Not Enough Balance<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
            }
        } else {
            print '<div class="alert alert-danger alert-dismissible" role="alert">Please input valid supplier<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
        }

    }

    /**
     * @description This method supplier ledger
     * @return void
     */
    public function suppData()
    {
        $suppId = $this->request->getPost('suppId');


        $ledger_suppliersTable = DB()->table('ledger_suppliers');
        $data = $ledger_suppliersTable->where('supplier_id', $suppId)->orderBy('ledg_sup_id', 'DESC')->limit(10)->get()->getResult();

        $suppliersBalance = get_data_by_id('balance', 'suppliers', 'supplier_id', $suppId);

        $view = '<span class="pull-right"> Balance: ' . showWithCurrencySymbol($suppliersBalance) . '</span>';


        $view .= '<table class="table table-bordered table-striped" id="TFtable" >
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Date</th>
                                    <th>Supplier</th>
                                    <th>Particulars</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody>';
        $i = '';
        foreach ($data as $row) {
            $particulars = ($row->particulars == NULL) ? "Purchase" : $row->particulars;
            $amountCr = ($row->trangaction_type != "Cr.") ? "---" : showWithCurrencySymbol($row->amount);
            $amountDr = ($row->trangaction_type != "Dr.") ? "---" : showWithCurrencySymbol($row->amount);

            $view .= '<tr>
                                    <td>' . ++$i . '</td>
                                    <td>' . bdDateFormat($row->createdDtm) . '</td>
                                    <td>' . get_data_by_id('name', 'suppliers', 'supplier_id', $row->supplier_id) . '</td>
                                    <td>' . $particulars . '</td>
                                    <td>' . $amountDr . '</td>
                                    <td>' . $amountCr . '</td>
                                    <td>' . showWithCurrencySymbol($row->rest_balance) . '</td>
                                </tr>';
        }

        $view .= '</tbody>
                            <tfoot>
                                <tr>
                                    <th>No</th>
                                    <th>Date</th>
                                    <th>Supplier</th>
                                    <th>Particulars</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                    <th>Balance</th>
                                </tr>
                            </tfoot>
                        </table>';


        print $view;
    }

    /**
     * @description This method store Loan Provider transaction
     * @return void
     */
    public function loan_pro_transaction_action()
    {

        $shopId = $this->session->shopId;
        $userId = $this->session->userId;

        $amount = str_replace(',', '', $this->request->getPost('amount'));
        $loanProId = $this->request->getPost('loan_pro_id');
        //loan_pro Balance
        $loanProBalance = get_data_by_id('balance', 'loan_provider', 'loan_pro_id', $loanProId);

        //Payment Type
        $paymentType = $this->request->getPost('payment_type');
        //shop data
        $shopBalance = get_data_by_id('cash', 'shops', 'sch_id', $shopId);


        $trangactionType = $this->request->getPost('trangaction_type');

        $chequeNo = $this->request->getPost('chequeNo');
        $chequeAmount = $this->request->getPost('chequeAmount');
        $sms = $this->request->getPost('sms');

        if ($paymentType == 1) {
            $bankId = $this->request->getPost('bank_id');
            if ($bankId) {
                $bankCash = get_data_by_id('balance', 'bank', 'bank_id', $bankId);
                $bankUpData = $bankCash - $amount;
                $bankUpDataCr = $bankCash + $amount;
            }
            $availableBalance = checkBankBalance($bankId, $amount);
            if (empty($bankId)) {
                print '<div class="alert alert-danger alert-dismissible" role="alert">Please select a bank <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
                die();
            }
        }
        if ($paymentType == 2) {
            $availableBalance = checkNagadBalance($amount);
        }


        if ($amount < 0) {
            print '<div class="alert alert-danger alert-dismissible" role="alert">Please enter valid amount<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
            die();
        }


        $shopCheck = check_shop('loan_provider', 'loan_pro_id', $loanProId);
        if ($shopCheck == 1) {
            if ($chequeNo > 0) {

                //cheque pay amount calculate and insert cheque tabile(start)
                $chequeData = array(
                    'sch_id' => $shopId,
                    'chaque_number' => $chequeNo,
                    'to' => $userId,
                    'from_loan_provider' => $loanProId,
                    'amount' => $chequeAmount,
                    'createdDtm' => date('Y-m-d h:i:s')
                );

                $chaquetab = DB()->table('chaque');
                $chaquetab->insert($chequeData);

                print '<div class="alert alert-success alert-dismissible" role="alert">Your transaction is successful<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
                //cheque pay amount calculate and insert cheque tabile(end)

            } else {


                //Cr.
                if ($trangactionType == 2) {
                    $restBalanceDr = $loanProBalance - $amount;
                    $shopUpdateBalanceCr = $shopBalance + $amount;
                    DB()->transStart();

                    //insert Transaction table
                    $transdata = array(
                        'sch_id' => $shopId,
                        'loan_pro_id' => $loanProId,
                        'title' => $this->request->getPost('particulars'),
                        'trangaction_type' => 'Dr.',
                        'amount' => $amount,
                        'createdBy' => $userId,
                        'createdDtm' => date('Y-m-d h:i:s')
                    );
                    $transaction = DB()->table('transaction');
                    $transaction->insert($transdata);
                    $transId = DB()->insertID();


                    //insert data
                    $data = array(
                        'sch_id' => $shopId,
                        'trans_id' => $transId,
                        'loan_pro_id' => $loanProId,
                        'particulars' => $this->request->getPost('particulars'),
                        'trangaction_type' => 'Cr.',
                        'amount' => $amount,
                        'rest_balance' => $restBalanceDr,
                        'createdBy' => $userId,
                        'createdDtm' => date('Y-m-d h:i:s')
                    );
                    $ledger_loanTab = DB()->table('ledger_loan');
                    $ledger_loanTab->insert($data);

                    //loan_provider Balance Update
                    $dataLonProBlan = array(
                        'balance' => $restBalanceDr,
                        'updatedBy' => $userId,
                    );
                    $loan_providerTab = DB()->table('loan_provider');
                    $loan_providerTab->where('loan_pro_id', $loanProId)->update($dataLonProBlan);

                    //admin transaction
                    if ($paymentType == 2) {
                        //shop balance update
                        $shopData = array(
                            'cash' => $shopUpdateBalanceCr,
                            'updatedBy' => $userId,
                        );
                        $shopsTab = DB()->table('shops');
                        $shopsTab->where('sch_id', $shopId)->update($shopData);

                        //insert ledger_nagodan
                        $lgNagData = array(
                            'sch_id' => $shopId,
                            'trans_id' => $transId,
                            'trangaction_type' => 'Dr.',
                            'particulars' => $this->request->getPost('particulars'),
                            'amount' => $amount,
                            'rest_balance' => $shopUpdateBalanceCr,
                            'createdBy' => $userId,
                            'createdDtm' => date('Y-m-d h:i:s')
                        );
                        $ledger_nagodanTab = DB()->table('ledger_nagodan');
                        $ledger_nagodanTab->insert($lgNagData);

                    } else {
                        $bankData = array(
                            'balance' => $bankUpDataCr,
                            'updatedBy' => $userId,
                        );
                        $bankTab = DB()->table('bank');
                        $bankTab->where('bank_id', $bankId)->update($bankData);

                        //insert ledger_bank
                        $lgBankData = array(
                            'sch_id' => $shopId,
                            'bank_id' => $bankId,
                            'trans_id' => $transId,
                            'trangaction_type' => 'Dr.',
                            'particulars' => $this->request->getPost('particulars'),
                            'amount' => $amount,
                            'rest_balance' => $bankUpDataCr,
                            'createdBy' => $userId,
                            'createdDtm' => date('Y-m-d h:i:s')
                        );
                        $ledger_bankTab = DB()->table('ledger_bank');
                        $ledger_bankTab->insert($lgBankData);

                    }
                    DB()->transComplete();
                    if(!empty($sms)) {
                        $message = 'Thank you for your transaction.Your transaction amount is-' . $amount;
                        $phone = get_data_by_id('phone', 'loan_provider', 'loan_pro_id', $loanProId);
                        send_sms($phone, $message);
                    }
                    print '<div class="alert alert-success alert-dismissible" role="alert">Your transaction is successful<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
                }
            }
            //Dr.
            if ($trangactionType == 1) {
                $restBalance = $loanProBalance + $amount;
                $shopUpdateBalance = $shopBalance - $amount;
                if ($availableBalance == true) {
                    DB()->transStart();

                    //insert Transaction table
                    $transdata = array(
                        'sch_id' => $shopId,
                        'loan_pro_id' => $loanProId,
                        'title' => $this->request->getPost('particulars'),
                        'trangaction_type' => 'Cr.',
                        'amount' => $amount,
                        'createdBy' => $userId,
                        'createdDtm' => date('Y-m-d h:i:s')
                    );
                    $transactionTab = DB()->table('transaction');
                    $transactionTab->insert($transdata);
                    $transId = DB()->insertID();


                    //insert data
                    $data = array(
                        'sch_id' => $shopId,
                        'trans_id' => $transId,
                        'loan_pro_id' => $loanProId,
                        'particulars' => $this->request->getPost('particulars'),
                        'trangaction_type' => 'Dr.',
                        'amount' => $amount,
                        'rest_balance' => $restBalance,
                        'createdBy' => $userId,
                        'createdDtm' => date('Y-m-d h:i:s')
                    );
                    $ledger_loanTab = DB()->table('ledger_loan');
                    $ledger_loanTab->insert($data);

                    //loan_provider Balance Update
                    $dataLonProBlan = array(
                        'balance' => $restBalance,
                        'updatedBy' => $userId,
                    );
                    $loan_providerTab = DB()->table('loan_provider');
                    $loan_providerTab->where('loan_pro_id', $loanProId)->update($dataLonProBlan);

                    //admin transaction
                    if ($paymentType == 2) {
                        //shop balance update
                        $shopData = array(
                            'cash' => $shopUpdateBalance,
                            'updatedBy' => $userId,
                        );
                        $shopsTab = DB()->table('shops');
                        $shopsTab->where('sch_id', $shopId)->update($shopData);

                        //insert ledger_nagodan
                        $lgNagData = array(
                            'sch_id' => $shopId,
                            'trans_id' => $transId,
                            'particulars' => $this->request->getPost('particulars'),
                            'trangaction_type' => 'Cr.',
                            'amount' => $amount,
                            'rest_balance' => $shopUpdateBalance,
                            'createdBy' => $userId,
                            'createdDtm' => date('Y-m-d h:i:s')
                        );
                        $ledger_nagodanTab = DB()->table('ledger_nagodan');
                        $ledger_nagodanTab->insert($lgNagData);

                    } else {
                        $bankData = array(
                            'balance' => $bankUpData,
                            'updatedBy' => $userId,
                        );
                        $bankTab = DB()->table('bank');
                        $bankTab->where('bank_id', $bankId)->update($bankData);

                        //insert ledger_bank
                        $lgBankData = array(
                            'sch_id' => $shopId,
                            'bank_id' => $bankId,
                            'trans_id' => $transId,
                            'trangaction_type' => 'Cr.',
                            'particulars' => $this->request->getPost('particulars'),
                            'amount' => $amount,
                            'rest_balance' => $bankUpData,
                            'createdBy' => $userId,
                            'createdDtm' => date('Y-m-d h:i:s')
                        );
                        $ledger_bankTab = DB()->table('ledger_bank');
                        $ledger_bankTab->insert($lgBankData);

                    }
                    DB()->transComplete();

                    print '<div class="alert alert-success alert-dismissible" role="alert">Your transaction is successful<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';

                } else {
                    print '<div class="alert alert-danger alert-dismissible" role="alert">Not Enough Balance<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
                }
            }
        } else {
            print '<div class="alert alert-danger alert-dismissible" role="alert">Please input valid Account Head<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
        }


    }

    /**
     * @description This method Loan Provider ledger
     * @return void
     */
    public function lonProvData()
    {
        $lonProvId = $this->request->getPost('lonProvId');


        $ledger_loanTable = DB()->table('ledger_loan');
        $data = $ledger_loanTable->where('loan_pro_id', $lonProvId)->orderBy('ledg_loan_id', 'DESC')->limit(10)->get()->getResult();


        $loanProBalance = get_data_by_id('balance', 'loan_provider', 'loan_pro_id', $lonProvId);

        $view = '<span class="pull-right"> Balance: ' . showWithCurrencySymbol($loanProBalance) . '</span>';
        $view .= '<table class="table table-bordered table-striped" id="TFtable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Date</th>
                                    <th>Particulars</th>
                                    <th>Loan Provider</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody>';
        $i = '';
        foreach ($data as $row) {
            $particulars = ($row->particulars == NULL) ? "Loan" : $row->particulars;
            $loanProId = get_data_by_id('name', 'loan_provider', "loan_pro_id", $row->loan_pro_id);
            $amountCr = ($row->trangaction_type != "Cr.") ? "---" : showWithCurrencySymbol($row->amount);
            $amountDr = ($row->trangaction_type != "Dr.") ? "---" : showWithCurrencySymbol($row->amount);
            $view .= '<tr>
                                    <td>' . ++$i . '</td>
                                    <td>' . bdDateFormat($row->createdDtm) . '</td>
                                    <td>' . $particulars . '</td>
                                    <td>' . $loanProId . '</td>
                                    <td>' . $amountDr . '</td>
                                    <td>' . $amountCr . '</td>
                                    <td>' . showWithCurrencySymbol($row->rest_balance) . '</td>
                                </tr>';
        }

        $view .= '</tbody>
                            <tfoot>
                                <tr>
                                    <th>No</th>
                                    <th>Date</th>
                                    <th>Particulars</th>
                                    <th>Loan Provider</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                    <th>Balance</th>
                                </tr>
                            </tfoot>
                        </table>';


        print $view;
    }

    /**
     * @description This method store bank transaction
     * @return void
     */
    public function bank_transaction_action()
    {
        $shopId = $this->session->shopId;
        $userId = $this->session->userId;

        $bank_id = $this->request->getPost('bank_id');
        $bank_id2 = $this->request->getPost('bank_id2');
        $particulars = $this->request->getPost('particulars');
        $amount = $this->request->getPost('amount');

        $bankBalance = get_data_by_id('balance', 'bank', 'bank_id', $bank_id);


        if ($amount < 0) {
            print '<div class="alert alert-danger alert-dismissible" role="alert">Please enter valid amount<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
            die();
        }

        $firstBankBalance = $bankBalance - $amount;

        $shopCheck = check_shop('bank', 'bank_id', $bank_id);
        if ($shopCheck == 1) {
            if ($bankBalance > $amount) {

                DB()->transStart();

                //insert Transaction table
                $transdata = array(
                    'sch_id' => $shopId,
                    'title' => 'Withdraw',
                    'bank_id' => $bank_id,
                    'trangaction_type' => 'Cr.',
                    'amount' => $amount,
                    'createdBy' => $userId,
                    'createdDtm' => date('Y-m-d h:i:s')
                );
                $transactionTab = DB()->table('transaction');
                $transactionTab->insert($transdata);
                $transaction = DB()->insertID();


                //bank balance update  (start)
                $firstBankData = array(
                    'balance' => $firstBankBalance,
                    'updatedBy' => $userId,
                );
                $bankTab = DB()->table('bank');
                $bankTab->where('bank_id', $bank_id)->update($firstBankData);
                //bank balance update  (end)

                //Bank ledger create (start)
                $firstLedgerData = array(
                    'sch_id' => $shopId,
                    'bank_id' => $bank_id,
                    'trans_id' => $transaction,
                    'particulars' => 'Withdraw',
                    'trangaction_type' => 'Cr.',
                    'amount' => $amount,
                    'rest_balance' => $firstBankBalance,
                    'createdBy' => $userId,
                    'createdDtm' => date('Y-m-d h:i:s')
                );
                $ledger_bankTab = DB()->table('ledger_bank');
                $ledger_bankTab->insert($firstLedgerData);
                //Bank ledgher create (end)


                //2Nd bank balance update (Start)
                $bankBalance2 = get_data_by_id('balance', 'bank', 'bank_id', $bank_id2);
                $lastBankBalance = $bankBalance2 + $amount;

                $lastBankData = array(
                    'balance' => $lastBankBalance,
                    'updatedBy' => $userId,
                );
                $bankTable = DB()->table('bank');
                $bankTable->where('bank_id', $bank_id2)->update($lastBankData);
                //2Nd bank balance update (Start)

                //Bank ledger create (start)
                $lastLedgerData = array(
                    'sch_id' => $shopId,
                    'bank_id' => $bank_id2,
                    'trans_id' => $transaction,
                    'particulars' => $particulars,
                    'trangaction_type' => 'Dr.',
                    'amount' => $amount,
                    'rest_balance' => $lastBankBalance,
                    'createdBy' => $userId,
                    'createdDtm' => date('Y-m-d h:i:s')
                );
                $ledger_bankTable = DB()->table('ledger_bank');
                $ledger_bankTable->insert($lastLedgerData);
                //Bank ledgher create (end)

                DB()->transComplete();

                print '<div class="alert alert-success alert-dismissible" role="alert">Your transaction is successful<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';

            } else {
                print '<div class="alert alert-danger alert-dismissible" role="alert">Not Enough Balance<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
            }
        } else {
            print '<div class="alert alert-danger alert-dismissible" role="alert">Please input valid Bank<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
        }


    }

    /**
     * @description This method store bank ledger
     * @return void
     */
    public function bankData()
    {
        $bankId = $this->request->getPost('bankId');

        $ledger_bankTable = DB()->table('ledger_bank');
        $data = $ledger_bankTable->where('bank_id', $bankId)->orderBy('ledgBank_id', 'DESC')->limit(10)->get()->getResult();

        $view = '<table class="table table-bordered table-striped" id="TFtable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Date</th>
                                    <th>Particulars</th>
                                    <th>Bank</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody>';
        $i = '';
        foreach ($data as $row) {
            $particulars = ($row->particulars == NULL) ? "Transaction" : $row->particulars;
            $bankId = get_data_by_id('name', 'bank', "bank_id", $row->bank_id);
            $amountCr = ($row->trangaction_type != "Cr.") ? "---" : $row->amount;
            $amountDr = ($row->trangaction_type != "Dr.") ? "---" : $row->amount;
            $view .= '<tr>
                                    <td>' . ++$i . '</td>
                                    <td>' . bdDateFormat($row->createdDtm) . '</td>
                                    <td>' . $particulars . '</td>
                                    <td>' . $bankId . '</td>
                                    <td>' . $amountDr . '</td>
                                    <td>' . $amountCr . '</td>
                                    <td>' . $row->rest_balance . '</td>
                                </tr>';
        }

        $view .= '</tbody>
                            <tfoot>
                                <tr>
                                    <th>No</th>
                                    <th>Date</th>
                                    <th>Particulars</th>
                                    <th>Bank</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                    <th>Balance</th>
                                </tr>
                            </tfoot>
                        </table>';
        print $view;
    }

    /**
     * @description This method check bank balance
     * @return void
     */
    public function check_bank_balance()
    {

        $amount = $this->request->getPost('balance');
        $bankId = $this->request->getPost('bank_id');


        $bankBalance = get_data_by_id('balance', 'bank', 'bank_id', $bankId);
        if ($amount > $bankBalance) {
            print '<span style="color:red">Balance is too low</span>';
        } else {
            print '<span style="color:green">Balance is ok</span>';
        }

    }

    /**
     * @description This method store expense transaction
     * @return void
     */
    public function expense_transaction_action()
    {
        $shopId = $this->session->shopId;
        $userId = $this->session->userId;

        $amount = str_replace(',', '', $this->request->getPost('amount'));
        //Payment Type
        $paymentType = $this->request->getPost('payment_type');
        //shop data
        $shopBalance = get_data_by_id('cash', 'shops', 'sch_id', $shopId);
        $exrest = get_data_by_id('expense', 'shops', 'sch_id', $shopId);

        if ($paymentType == 1) {
            $bankId = $this->request->getPost('bank_id');
            if ($bankId) {
                $bankCash = get_data_by_id('balance', 'bank', 'bank_id', $bankId);
                $bankUpData = $bankCash - $amount;
            }
            $availableBalance = checkBankBalance($bankId, $amount);
            if (empty($bankId)) {
                print '<div class="alert alert-danger alert-dismissible" role="alert">Please select a bank <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
                die();
            }
        }
        if ($paymentType == 2) {
            $availableBalance = checkNagadBalance($amount);
        }


        if ($amount < 0) {
            print '<div class="alert alert-danger alert-dismissible" role="alert">Please enter valid amount<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
            die();
        }

        $shopUpdateBalance = $shopBalance - $amount;
        $exRestbalance = $exrest + $amount;

        if ($availableBalance == true) {

            DB()->transStart();
            //insert Transaction table
            $transdata = array(
                'sch_id' => $shopId,
                'title' => $this->request->getPost('particulars'),
                'trangaction_type' => 'Cr.',
                'amount' => $amount,
                'createdBy' => $userId,
                'createdDtm' => date('Y-m-d h:i:s')
            );
            $transactionTab = DB()->table('transaction');
            $transactionTab->insert($transdata);
            $ledgtranId = DB()->insertID();


            $exData = array(
                'expense' => $exRestbalance,
                'updatedBy' => $userId,
            );
            $shopsTab = DB()->table('shops');
            $shopsTab->where('sch_id', $shopId)->update($exData);


            //insert data
            $data = array(
                'sch_id' => $shopId,
                'memo_number' => $this->request->getPost('memo_number'),
                'trans_id' => $ledgtranId,
                'particulars' => $this->request->getPost('particulars'),
                'trangaction_type' => 'Dr.',
                'amount' => $amount,
                'rest_balance' => $exRestbalance,
                'createdBy' => $userId,
                'createdDtm' => date('Y-m-d h:i:s')
            );
            $ledger_expenseTab = DB()->table('ledger_expense');
            $ledger_expenseTab->insert($data);

            //admin transaction
            if ($paymentType == 2) {
                //shop balance update
                $shopData = array(
                    'cash' => $shopUpdateBalance,
                    'updatedBy' => $userId,
                );
                $shopsTable = DB()->table('shops');
                $shopsTable->where('sch_id', $shopId)->update($shopData);

                //insert ledger_nagodan
                $lgNagData = array(
                    'sch_id' => $shopId,
                    'trans_id' => $ledgtranId,
                    'particulars' => $this->request->getPost('particulars'),
                    'trangaction_type' => 'Cr.',
                    'amount' => $amount,
                    'rest_balance' => $shopUpdateBalance,
                    'createdBy' => $userId,
                    'createdDtm' => date('Y-m-d h:i:s')
                );
                $ledger_nagodanTable = DB()->table('ledger_nagodan');
                $ledger_nagodanTable->insert($lgNagData);

            } else {
                $bankData = array(
                    'balance' => $bankUpData,
                    'updatedBy' => $userId,
                );
                $bankTable = DB()->table('bank');
                $bankTable->where('bank_id', $bankId)->update($bankData);

                //insert ledger_bank
                $lgBankData = array(
                    'sch_id' => $shopId,
                    'bank_id' => $bankId,
                    'trans_id' => $ledgtranId,
                    'particulars' => $this->request->getPost('particulars'),
                    'trangaction_type' => 'Cr.',
                    'amount' => $amount,
                    'rest_balance' => $bankUpData,
                    'createdBy' => $userId,
                    'createdDtm' => date('Y-m-d h:i:s')
                );
                $ledger_bankTable = DB()->table('ledger_bank');
                $ledger_bankTable->insert($lgBankData);

            }

            DB()->transComplete();

            print '<div class="alert alert-success alert-dismissible" role="alert">Your transaction is successful<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
        } else {

            print '<div class="alert alert-danger alert-dismissible" role="alert">Not Enough Balance<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
        }

    }
    /**
     * @description This method store other sales transaction
     * @return void
     */
    public function otherSales_transaction_action()
    {
        $shopId = $this->session->shopId;
        $userId = $this->session->userId;

        $amount = str_replace(',', '', $this->request->getPost('amount'));
        //shop data
        $shopBalance = get_data_by_id('cash', 'shops', 'sch_id', $shopId);
        $oldprofit = get_data_by_id('profit', 'shops', 'sch_id', $shopId);


        if ($amount < 0) {
            print '<div class="alert alert-danger alert-dismissible" role="alert">Please enter valid amount<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
            die();
        }

        $shopUpdateBalance = $shopBalance + $amount;
        $newProfite = $oldprofit - $amount;


        DB()->transStart();
        //insert Transaction table
        $transdata = array(
            'sch_id' => $shopId,
            'title' => $this->request->getPost('particulars'),
            'trangaction_type' => 'Dr.',
            'amount' => $amount,
            'createdBy' => $userId,
            'createdDtm' => date('Y-m-d h:i:s')
        );
        $transactionTab = DB()->table('transaction');
        $transactionTab->insert($transdata);
        $ledgtranId = DB()->insertID();


        //insert data
        $data = array(
            'sch_id' => $shopId,
            'trans_id' => $ledgtranId,
            'particulars' => $this->request->getPost('particulars'),
            'trangaction_type' => 'Dr.',
            'amount' => $amount,
            'createdBy' => $userId,
            'createdDtm' => date('Y-m-d h:i:s')
        );
        $ledger_other_salesTab = DB()->table('ledger_other_sales');
        $ledger_other_salesTab->insert($data);

        //shop balance update
        $shopData = array(
            'cash' => $shopUpdateBalance,
            'profit' => $newProfite,
            'updatedBy' => $userId,
        );
        $shopsTab = DB()->table('shops');
        $shopsTab->where('sch_id', $shopId)->update($shopData);

        //insert ledger_nagodan
        $lgNagData = array(
            'sch_id' => $shopId,
            'trans_id' => $ledgtranId,
            'particulars' => $this->request->getPost('particulars'),
            'trangaction_type' => 'Dr.',
            'amount' => $amount,
            'rest_balance' => $shopUpdateBalance,
            'createdBy' => $userId,
            'createdDtm' => date('Y-m-d h:i:s')
        );
        $ledger_nagodanTab = DB()->table('ledger_nagodan');
        $ledger_nagodanTab->insert($lgNagData);


        DB()->transComplete();

        print '<div class="alert alert-success alert-dismissible" role="alert">Your transaction is successful<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';

    }

    /**
     * @description This method store salary employee transaction
     * @return void
     */
    public function salaryEmployee_transaction_action()
    {
        $shopId = $this->session->shopId;
        $userId = $this->session->userId;


        $amount = str_replace(',', '', $this->request->getPost('amount'));
        //Supplier Balance
        $employeeBalance = get_data_by_id('balance', 'employee', 'employee_id', $this->request->getPost('employee_id'));
        $restBalance = $employeeBalance + $amount;
        //Payment Type
        $paymentType = $this->request->getPost('payment_type');
        //shop data
        $shopBalance = get_data_by_id('cash', 'shops', 'sch_id', $shopId);
        $shopUpdateBalance = $shopBalance - $amount;

        if ($paymentType == 1) {
            $bankId = $this->request->getPost('bank_id');
            if ($bankId) {
                $bankCash = get_data_by_id('balance', 'bank', 'bank_id', $bankId);
                $bankUpData = $bankCash - $amount;
            }
            $availableBalance = checkBankBalance($bankId, $amount);

            if (empty($bankId)) {
                print '<div class="alert alert-danger alert-dismissible" role="alert">Please select a bank <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
                die();
            }
        }
        if ($paymentType == 2) {
            $availableBalance = checkNagadBalance($amount);
        }


        if ($amount < 0) {
            print '<div class="alert alert-danger alert-dismissible" role="alert">Please enter valid amount<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
            die();
        }

        $shopCheck = check_shop('employee', 'employee_id', $this->request->getPost('employee_id'));
        if ($shopCheck == 1) {
            if ($availableBalance == true) {

                DB()->transStart();

                //insert Transaction table
                $transdata = array(
                    'sch_id' => $shopId,
                    'employee_id' => $this->request->getPost('employee_id'),
                    'title' => 'Salary',
                    'trangaction_type' => 'Cr.',
                    'amount' => $amount,
                    'createdBy' => $userId,
                    'createdDtm' => date('Y-m-d h:i:s')
                );
                $transactionTab = DB()->table('transaction');
                $transactionTab->insert($transdata);
                $ledgSupId = DB()->insertID();


                //insert data
                $data = array(
                    'sch_id' => $shopId,
                    'trans_id' => $ledgSupId,
                    'employee_id' => $this->request->getPost('employee_id'),
                    'particulars' => $this->request->getPost('particulars'),
                    'trangaction_type' => 'Dr.',
                    'amount' => $amount,
                    'rest_balance' => $restBalance,
                    'createdBy' => $userId,
                    'createdDtm' => date('Y-m-d h:i:s')
                );
                $ledger_employeeTab = DB()->table('ledger_employee');
                $ledger_employeeTab->insert($data);

                //Suppliers Balance Update
                $dataemployeeBlan = array(
                    'balance' => $restBalance,
                    'updatedBy' => $userId,
                );
                $employeeTab = DB()->table('employee');
                $employeeTab->where('employee_id', $this->request->getPost('employee_id'))->update($dataemployeeBlan);
                //admin transaction
                if ($paymentType == 2) {
                    //shop balance update
                    $shopData = array(
                        'cash' => $shopUpdateBalance,
                        'updatedBy' => $userId,
                    );
                    $shopsTab = DB()->table('shops');
                    $shopsTab->where('sch_id', $shopId)->update($shopData);

                    //insert ledger_nagodan
                    $lgNagData = array(
                        'sch_id' => $shopId,
                        'trans_id' => $ledgSupId,
                        'trangaction_type' => 'Cr.',
                        'particulars' => $this->request->getPost('particulars'),
                        'amount' => $amount,
                        'rest_balance' => $shopUpdateBalance,
                        'createdBy' => $userId,
                        'createdDtm' => date('Y-m-d h:i:s')
                    );
                    $ledger_nagodanTab = DB()->table('ledger_nagodan');
                    $ledger_nagodanTab->insert($lgNagData);

                } else {
                    $bankData = array(
                        'balance' => $bankUpData,
                        'updatedBy' => $userId,
                    );
                    $bankTab = DB()->table('bank');
                    $bankTab->where('bank_id', $bankId)->update($bankData);
                    //insert ledger_bank
                    $lgBankData = array(
                        'sch_id' => $shopId,
                        'bank_id' => $bankId,
                        'trans_id' => $ledgSupId,
                        'trangaction_type' => 'Cr.',
                        'particulars' => $this->request->getPost('particulars'),
                        'amount' => $amount,
                        'rest_balance' => $bankUpData,
                        'createdBy' => $userId,
                        'createdDtm' => date('Y-m-d h:i:s')
                    );
                    $ledger_bankTab = DB()->table('ledger_bank');
                    $ledger_bankTab->insert($lgBankData);

                }

                DB()->transComplete();

                print '<div class="alert alert-success alert-dismissible" role="alert">Your transaction is successful<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';

            } else {
                print '<div class="alert alert-danger alert-dismissible" role="alert">Not Enough Balance<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
            }
        } else {
            print '<div class="alert alert-danger alert-dismissible" role="alert">Please input valid employee<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
        }


    }

    /**
     * @description This method search salary employee
     * @return void
     */
    public function search_employeeSalary()
    {
        $employeeId = $this->request->getPost('id');
        $employeeTab = DB()->table('employee');
        $query = $employeeTab->where('employee_id', $employeeId)->get()->getRow();

        print $query->salary;
    }

    /**
     * @description This method store vat pay transaction
     * @return void
     */
    public function vat_pay_action()
    {

        $shopId = $this->session->shopId;
        $userId = $this->session->userId;

        $amount = str_replace(',', '', $this->request->getPost('amount'));
        //Vat Balance
        $previousVat = get_data_by_id('balance', 'vat_register', 'sch_id', $shopId);

        $restBalance = $previousVat + $amount;

        $vatId = $this->request->getPost('vat_id');
        //Payment Type
        $paymentType = $this->request->getPost('payment_type');

        //shop data
        $shopBalance = get_data_by_id('cash', 'shops', 'sch_id', $shopId);
        $shopUpdateBalance = $shopBalance - $amount;

        if ($paymentType == 1) {
            $bankId = $this->request->getPost('bank_id');
            if ($bankId) {
                $bankCash = get_data_by_id('balance', 'bank', 'bank_id', $bankId);
                $bankUpData = $bankCash - $amount;
            }
            $availableBalance = checkBankBalance($bankId, $amount);
            if (empty($bankId)) {
                print '<div class="alert alert-danger alert-dismissible" role="alert">Please select a bank <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
                die();
            }
        }

        if ($paymentType == 2) {
            $availableBalance = checkNagadBalance($amount);
        }


        if ($amount < 0) {
            print '<div class="alert alert-danger alert-dismissible" role="alert">Please enter valid amount<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
            die();
        }

        $shopCheck = check_shop('vat_register', 'vat_id', $vatId);
        if ($shopCheck == 1) {
            if ($availableBalance == true) {
                $vat_registerT = DB()->table('vat_register');
                $vatamo = $vat_registerT->where('sch_id', $shopId)->where('is_default', '1')->get()->getRow()->balance;

                $vatTotal = -$vatamo;
                if ($vatTotal >= $amount) {

                    DB()->transStart();

                    //insert Transaction table
                    $transdata = array(
                        'sch_id' => $shopId,
                        'vat_id' => $this->request->getPost('vat_id'),
                        'title' => $this->request->getPost('particulars'),
                        'trangaction_type' => 'Dr.',
                        'amount' => $amount,
                        'createdBy' => $userId,
                        'createdDtm' => date('Y-m-d h:i:s')
                    );
                    $transactionTab = DB()->table('transaction');
                    $transactionTab->insert($transdata);
                    $ledgSupId = DB()->insertID();


                    //insert data ledger_vat
                    $data = array(
                        'sch_id' => $shopId,
                        'trans_id' => $ledgSupId,
                        'vat_id' => $this->request->getPost('vat_id'),
                        'particulars' => $this->request->getPost('particulars'),
                        'trangaction_type' => 'Dr.',
                        'amount' => $amount,
                        'rest_balance' => $restBalance,
                        'createdBy' => $userId,
                        'createdDtm' => date('Y-m-d h:i:s')
                    );
                    $ledger_vatTab = DB()->table('ledger_vat');
                    $ledger_vatTab->insert($data);

                    //vat register Balance Update
                    $datavatBlan = array(
                        'balance' => $restBalance,
                        'updatedBy' => $userId,
                    );
                    $vat_registerTab = DB()->table('vat_register');
                    $vat_registerTab->where('vat_id', $this->request->getPost('vat_id'))->update($datavatBlan);


                    //admin transaction
                    if ($paymentType == 2) {
                        //shop balance update
                        $shopData = array(
                            'cash' => $shopUpdateBalance,
                            'updatedBy' => $userId,
                        );
                        $shopsTab = DB()->table('shops');
                        $shopsTab->where('sch_id', $shopId)->update($shopData);

                        //insert ledger_nagodan
                        $lgNagData = array(
                            'sch_id' => $shopId,
                            'trans_id' => $ledgSupId,
                            'trangaction_type' => 'Cr.',
                            'particulars' => $this->request->getPost('particulars'),
                            'amount' => $amount,
                            'rest_balance' => $shopUpdateBalance,
                            'createdBy' => $userId,
                            'createdDtm' => date('Y-m-d h:i:s')
                        );
                        $ledger_nagodanTab = DB()->table('ledger_nagodan');
                        $ledger_nagodanTab->insert($lgNagData);

                    } else {

                        $bankData = array(
                            'balance' => $bankUpData,
                            'updatedBy' => $userId,
                        );
                        $bankTab = DB()->table('bank');
                        $bankTab->where('bank_id', $bankId)->update($bankData);

                        //insert ledger_bank
                        $lgBankData = array(
                            'sch_id' => $shopId,
                            'bank_id' => $bankId,
                            'trans_id' => $ledgSupId,
                            'trangaction_type' => 'Cr.',
                            'particulars' => $this->request->getPost('particulars'),
                            'amount' => $amount,
                            'rest_balance' => $bankUpData,
                            'createdBy' => $userId,
                            'createdDtm' => date('Y-m-d h:i:s')
                        );
                        $ledger_bankTab = DB()->table('ledger_bank');
                        $ledger_bankTab->insert($lgBankData);

                    }

                    DB()->transComplete();

                    print '<div class="alert alert-success alert-dismissible" role="alert">Your transaction is successful<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
                } else {
                    print '<div class="alert alert-danger alert-dismissible" role="alert">Vat amount to large<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
                }

            } else {
                print '<div class="alert alert-danger alert-dismissible" role="alert">Not Enough Balance<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
            }
        } else {
            print '<div class="alert alert-danger alert-dismissible" role="alert">Please input valid Vat id<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
        }

    }

    /**
     * @description This method ledger vat pay
     * @return void
     */
    public function vatLedgerData()
    {
        $vatId = $this->request->getPost('vatId');

        $ledger_vatTable = DB()->table('ledger_vat');
        $data = $ledger_vatTable->where('vat_id', $vatId)->orderBy('ledg_vat_id', 'DESC')->limit(10)->get()->getResult();

        $vatBalance = get_data_by_id('balance', 'vat_register', 'vat_id', $vatId);

        $view = '<span class="pull-right"> Balance: ' . showWithCurrencySymbol($vatBalance) . '</span>';
        $view .= '<table class="table table-bordered table-striped" id="TFtable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Particulars</th>
                                    <th>Vat register no</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody>';

        foreach ($data as $row) {
            $particulars = ($row->particulars == NULL) ? "Loan" : $row->particulars;
            $vat_register = get_data_by_id('name', 'vat_register', "vat_id", $row->vat_id);
            $amountCr = ($row->trangaction_type != "Cr.") ? "---" : showWithCurrencySymbol($row->amount);
            $amountDr = ($row->trangaction_type != "Dr.") ? "---" : showWithCurrencySymbol($row->amount);
            $view .= '<tr>
                                    <td>' . bdDateFormat($row->createdDtm) . '</td>
                                    <td>' . $particulars . '</td>
                                    <td>' . $vat_register . '</td>
                                    <td>' . $amountDr . '</td>
                                    <td>' . $amountCr . '</td>
                                    <td>' . showWithCurrencySymbol($row->rest_balance) . '</td>
                                </tr>';
        }

        $view .= '</tbody>
                            <tfoot>
                                <tr>
                                    <th>Date</th>
                                    <th>Particulars</th>
                                    <th>Vat register no</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                    <th>Balance</th>
                                </tr>
                            </tfoot>
                        </table>';


        print $view;
    }

    /**
     * @description This method store money receipt transaction
     * @param int $id
     * @return RedirectResponse|void
     */
    public function moneyReceipt($id)
    {
        $isLoggedIn = $this->session->isLoggedIn;
        $role_id = $this->session->role;
        if (!isset($isLoggedIn) || $isLoggedIn != TRUE) {
            return redirect()->to(site_url('Admin/login'));
        } else {
            $shopId = $this->session->shopId;

            $transactionTab = DB()->table('transaction');
            $data['money'] = $transactionTab->where('trans_id', $id)->get()->getResult();

            $shopsTab = DB()->table('shops');
            $data['shops'] = $shopsTab->where('sch_id', $shopId)->get()->getResult();
            $data['transactionId'] = $id;


            // All Permissions
            //$perm = array('create','read','update','delete','mod_access');
            $perm = $this->permission->module_permission_list($role_id, $this->module_name);
            foreach ($perm as $key => $val) {
                $data[$key] = $this->permission->have_access($role_id, $this->module_name, $key);
            }
            echo view('Admin/header');
            echo view('Admin/sidebar');
            if ($data['mod_access'] == 1) {
                echo view('Admin/Transaction/moneyreceipt', $data);
            } else {
                echo view('no_permission');
            }
            echo view('Admin/footer');
        }
    }

    /**
     * @description This method store salary receipt transaction
     * @param int $id
     * @return RedirectResponse|void
     */
    public function salaryreceipt($id)
    {
        $isLoggedIn = $this->session->isLoggedIn;
        $role_id = $this->session->role;
        if (!isset($isLoggedIn) || $isLoggedIn != TRUE) {
            return redirect()->to(site_url('Admin/login'));
        } else {
            $shopId = $this->session->shopId;
            $shopTab = DB()->table('shops');
            $data['shops'] = $shopTab->where('sch_id', $shopId)->get()->getResult();
            $data['transactionId'] = $id;


            // All Permissions
            //$perm = array('create','read','update','delete','mod_access');
            $perm = $this->permission->module_permission_list($role_id, $this->module_name);
            foreach ($perm as $key => $val) {
                $data[$key] = $this->permission->have_access($role_id, $this->module_name, $key);
            }
            echo view('Admin/header');
            echo view('Admin/sidebar');
            if ($data['mod_access'] == 1) {
                echo view('Admin/Transaction/salaryreceipt', $data);
            } else {
                echo view('no_permission');
            }
            echo view('Admin/footer');
        }
    }

    /**
     * @description This method transaction view
     * @param int $id
     * @return RedirectResponse|void
     */
    public function read($id)
    {
        $isLoggedIn = $this->session->isLoggedIn;
        $role_id = $this->session->role;
        if (!isset($isLoggedIn) || $isLoggedIn != TRUE) {
            return redirect()->to(site_url('Admin/login'));
        } else {

            $transactionTab = DB()->table('transaction');
            $data['trans'] = $transactionTab->where('trans_id', $id)->get()->getRow();
            $data['transactionId'] = $id;


            // All Permissions
            //$perm = array('create','read','update','delete','mod_access');
            $perm = $this->permission->module_permission_list($role_id, $this->module_name);
            foreach ($perm as $key => $val) {
                $data[$key] = $this->permission->have_access($role_id, $this->module_name, $key);
            }
            echo view('Admin/header');
            echo view('Admin/sidebar');
            if (isset($data['mod_access']) and $data['read'] == 1) {
                echo view('Admin/Transaction/read', $data);
            } else {
                echo view('no_permission');
            }
            echo view('Admin/footer');
        }
    }


}