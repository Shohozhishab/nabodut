<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Permission;
use CodeIgniter\HTTP\RedirectResponse;


class Ledger_bank extends BaseController
{


    protected $permission;
    protected $validation;
    protected $session;
    protected $crop;
    private $module_name = 'Ledger_bank';

    public function __construct()
    {
        $this->permission = new Permission();
        $this->validation = \Config\Services::validation();
        $this->session = \Config\Services::session();
        $this->crop = \Config\Services::image();
    }

    /**
     * @description This method provides view
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

            $data['id'] = $shopId;
            $data['menu'] = view('Admin/menu_ledger');
            // All Permissions
            //$perm = array('create','read','update','delete','mod_access');
            $perm = $this->permission->module_permission_list($role_id, $this->module_name);
            foreach ($perm as $key => $val) {
                $data[$key] = $this->permission->have_access($role_id, $this->module_name, $key);
            }
            echo view('Admin/header');
            echo view('Admin/sidebar');
            if (isset($data['mod_access']) and $data['mod_access'] == 1) {
                echo view('Admin/Ledger_bank/list', $data);
            } else {
                echo view('no_permission');
            }
            echo view('Admin/footer');
        }
    }

    /**
     * @description This method search bank ledger
     * @return void
     */
    public function search_bankLedg()
    {
        $bankId = $this->request->getPost('id');

        $ledger_bankTab = DB()->table('ledger_bank');
        $data = $ledger_bankTab->where("bank_id", $bankId)->orderBy("ledgBank_id", "DESC")->get()->getResult();


        $name = get_data_by_id('name', 'bank', 'bank_id', $bankId);
        $account = get_data_by_id('account_no', 'bank', 'bank_id', $bankId);
        $balance = get_data_by_id('balance', 'bank', 'bank_id', $bankId);

        $view = ' <div class="box" >
                        <div class="box-header">
                            <h3 class="box-title">
                            Bank: ' . $name . ' -- ' . $account . '</h3>
                            <span class="pull-right"><table class="table table-bordered table-striped" id="TFtable"><tr><td>Total Deposit:</td><td>' . showWithCurrencySymbol(get_total('ledger_bank', 'amount', 'Dr.', 'bank_id', $bankId)) . '</td></tr><tr><td>Total Withdraw:</td><td>' . showWithCurrencySymbol(get_total('ledger_bank', 'amount', 'Cr.', 'bank_id', $bankId)) . '</td></tr><tr><td>Balance:</td><td>' . showWithCurrencySymbol($balance) . '</td></tr></table></span>
                        </div>
                    <div class="box-body">
                        <table class="table table-bordered table-striped" id="example1">
                            <thead>
                                <tr>
                                    <th>Id</th>
                                    <th>Date</th>
                                    <th>Particulars</th>
                                    <th>Tran Id</th>
                                    <th>purc Id</th>
                                    <th>inv Id</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody>';
        $totalRows = count($data) - 1;
        for ($i = $totalRows; $i >= 0; $i--) {
            $particulars = ($data[$i]->particulars == NULL) ? "Pay due" : $data[$i]->particulars;
            $amountCr = ($data[$i]->trangaction_type != "Cr.") ? "---" : showWithCurrencySymbol($data[$i]->amount);
            $amountDr = ($data[$i]->trangaction_type != "Dr.") ? "---" : showWithCurrencySymbol($data[$i]->amount);
            $transId = ($data[$i]->trans_id == NULL) ? "---" : $data[$i]->trans_id;
            $purchaseId = ($data[$i]->purchase_id == NULL) ? "---" : $data[$i]->purchase_id;
            $invoiceId = ($data[$i]->invoice_id == 0) ? "---" : $data[$i]->invoice_id;
            $view .= '<tr>
                                    <td>' . $data[$i]->ledgBank_id . '</td>
                                    <td>' . $data[$i]->createdDtm . '</td>
                                    <td>' . $particulars . '</td>
                                    <td>' . $transId . '</td>
                                    <td>' . $purchaseId . '</td>
                                    <td>' . $invoiceId . '</td>
                                    <td>' . $amountDr . '</td>
                                    <td>' . $amountCr . '</td>
                                    <td>' . showWithCurrencySymbol($data[$i]->rest_balance) . '</td>
                                </tr>';
        }

        $view .= '</tbody>
                            <tfoot>
                                <tr>
                                    <th>Id</th>
                                    <th>Date</th>
                                    <th>Particulars</th>
                                    <th>Tran Id</th>
                                    <th>purc Id</th>
                                    <th>inv Id</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                    <th>Balance</th>
                                </tr>
                                <tr>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th>Total</th>
                                    <th>= ' . showWithCurrencySymbol(get_total('ledger_bank', 'amount', 'Dr.', 'bank_id', $bankId)) . '</th>
                                    <th>= ' . showWithCurrencySymbol(get_total('ledger_bank', 'amount', 'Cr.', 'bank_id', $bankId)) . '</th>
                                    <th>= ' . showWithCurrencySymbol($balance) . '</th>
                                </tr>
                            </tfoot>
                        </table>
                        </div>
                    </div>';

        print $view;
    }

    /**
     * @description This method bank ledger print
     * @return void
     */
    public function search_bankLedgPrint()
    {

        $bankId = $this->request->getPost('id');

        $ledger_bankTab = DB()->table('ledger_bank');
        $data = $ledger_bankTab->where("bank_id", $bankId)->orderBy("ledgBank_id", "DESC")->get()->getResult();

        $logo = (logo_image() == NULL) ? 'no_image.jpg' : logo_image();


        $view = '<div class="col-xs-12" style="text-transform: capitalize;" >
                            <div class="col-xs-12" style="margin-bottom: 20px;   ">
                                <div class="col-xs-6">
                                    <img src="' . base_url() . '/uploads/schools/' . $logo . '" alt="User Image" >
                                </div>
                                <div class="col-xs-6">
                                    ' . address() . '
                                </div>
                            </div>
                    <div class="col-xs-12">
                        <table class="table table-bordered table-striped" >
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Particulars</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody>';
        $totalRows = count($data);
        foreach ($data as $row) {
            $particulars = ($row->particulars == NULL) ? "Pay due" : $row->particulars;
            $amountCr = ($row->trangaction_type != "Cr.") ? "---" : showWithCurrencySymbol($row->amount);
            $amountDr = ($row->trangaction_type != "Dr.") ? "---" : showWithCurrencySymbol($row->amount);

            $view .= '<tr>
                                    <td>' . bdDateFormat($row->createdDtm) . '</td>
                                    <td>' . $particulars . '</td>
                                    <td>' . $amountDr . '</td>
                                    <td>' . $amountCr . '</td>
                                    <td>' . showWithCurrencySymbol($row->rest_balance) . '</td>
                                </tr>';
        }

        $view .= '</tbody>
                        </table>
                        </div>
                    </div>';

        print $view;
    }

    /**
     * @description This method search bank ledger date to date
     * @return void
     */
    public function search_dateTodate()
    {
        $bankId = $this->request->getPost('bankId');
        $st_date = $this->request->getPost('st_date');
        $en_date = $this->request->getPost('en_date');

        $ledger_bankTab = DB()->table('ledger_bank');
        $data = $ledger_bankTab->where("bank_id", $bankId)->where('createdDtm >=', $st_date . ' 00:00:00')->where('createdDtm <=', $en_date . ' 23:59:59')->orderBy("ledgBank_id", "DESC")->get()->getResult();

        $name = get_data_by_id('name', 'bank', 'bank_id', $bankId);
        $account = get_data_by_id('account_no', 'bank', 'bank_id', $bankId);
        $balance = get_data_by_id('balance', 'bank', 'bank_id', $bankId);

        $view = ' <div class="box">
                        <div class="box-header">
                            <h3 class="box-title">Bank: ' . $name . ' -- ' . $account . '</h3>
                            <span class="pull-right"><table class="table table-bordered table-striped" id="TFtable"><tr><td>Total Deposit:</td><td>' . showWithCurrencySymbol(get_total('ledger_bank', 'amount', 'Dr.', 'bank_id', $bankId, $st_date, $en_date)) . '</td></tr><tr><td>Total Withdraw:</td><td>' . showWithCurrencySymbol(get_total('ledger_bank', 'amount', 'Cr.', 'bank_id', $bankId, $st_date, $en_date)) . '</td></tr><tr><td>Balance:</td><td>' . showWithCurrencySymbol($balance) . '</td></tr></table></span>

                            
                        </div>
                    <div class="box-body">
                        <table class="table table-bordered table-striped" id="example1">
                            <thead>
                                <tr>
                                    <th>Id</th>
                                    <th>Date</th>
                                    <th>Particulars</th>
                                    <th>Tran Id</th>
                                    <th>purc Id</th>
                                    <th>inv Id</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody>';
        $totalRows = count($data) - 1;
        for ($i = $totalRows; $i >= 0; $i--) {
            $particulars = ($data[$i]->particulars == NULL) ? "Pay due" : $data[$i]->particulars;
            $amountCr = ($data[$i]->trangaction_type != "Cr.") ? "---" : showWithCurrencySymbol($data[$i]->amount);
            $amountDr = ($data[$i]->trangaction_type != "Dr.") ? "---" : showWithCurrencySymbol($data[$i]->amount);
            $transId = ($data[$i]->trans_id == NULL) ? "---" : $data[$i]->trans_id;
            $purchaseId = ($data[$i]->purchase_id == NULL) ? "---" : $data[$i]->purchase_id;
            $invoiceId = ($data[$i]->invoice_id == 0) ? "---" : $data[$i]->invoice_id;
            $view .= '<tr>
                                    <td>' . $data[$i]->ledgBank_id . '</td>
                                    <td>' . $data[$i]->createdDtm . '</td>
                                    <td>' . $particulars . '</td>
                                    <td>' . $transId . '</td>
                                    <td>' . $purchaseId . '</td>
                                    <td>' . $invoiceId . '</td>
                                    <td>' . $amountDr . '</td>
                                    <td>' . $amountCr . '</td>
                                    <td>' . showWithCurrencySymbol($data[$i]->rest_balance) . '</td>
                                </tr>';
        }

        $view .= '</tbody>
                            <tfoot>
                                <tr>
                                    <th>Id</th>
                                    <th>Date</th>
                                    <th>Particulars</th>
                                    <th>Tran Id</th>
                                    <th>purc Id</th>
                                    <th>inv Id</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                    <th>Balance</th>
                                </tr>
                                <tr>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th>Total</th>
                                    <th>= ' . showWithCurrencySymbol(get_total('ledger_bank', 'amount', 'Dr.', 'bank_id', $bankId, $st_date, $en_date)) . '</th>
                                    <th>= ' . showWithCurrencySymbol(get_total('ledger_bank', 'amount', 'Cr.', 'bank_id', $bankId, $st_date, $en_date)) . '</th>
                                    <th>= ' . showWithCurrencySymbol($balance) . '</th>
                                </tr>
                            </tfoot>
                        </table>
                        </div>
                    </div>';

        print $view;
    }

    /**
     * @description This method bank ledger print
     * @return void
     */
    public function search_dateTodatePrint()
    {
        $bankId = $this->request->getPost('bankId');
        $st_date = $this->request->getPost('st_date');
        $en_date = $this->request->getPost('en_date');

        $ledger_bankTab = DB()->table('ledger_bank');
        $data = $ledger_bankTab->where("bank_id", $bankId)->where('createdDtm >=', $st_date . ' 00:00:00')->where('createdDtm <=', $en_date . ' 23:59:59')->orderBy("ledgBank_id", "DESC")->get()->getResult();

        $logo = (logo_image() == NULL) ? 'no_image.jpg' : logo_image();

        $view = '<div class="col-xs-12" style="text-transform: capitalize;" >
                            <div class="col-xs-12" style="margin-bottom: 20px;   ">
                                <div class="col-xs-6">
                                    <img src="' . base_url() . '/uploads/schools/' . $logo . '" alt="User Image" >
                                </div>
                                <div class="col-xs-6">
                                    ' . address() . '
                                </div>
                            </div>
                    <div class="col-xs-12">
                        <table class="table table-bordered table-striped" >
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Particulars</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody>';
        $totalRows = count($data) - 1;
        for ($i = $totalRows; $i >= 0; $i--) {
            $particulars = ($data[$i]->particulars == NULL) ? "Pay due" : $data[$i]->particulars;
            $amountCr = ($data[$i]->trangaction_type != "Cr.") ? "---" : showWithCurrencySymbol($data[$i]->amount);
            $amountDr = ($data[$i]->trangaction_type != "Dr.") ? "---" : showWithCurrencySymbol($data[$i]->amount);
            $view .= '<tr>
                                    <td>' . bdDateFormat($data[$i]->createdDtm) . '</td>
                                    <td>' . $particulars . '</td>
                                    <td>' . $amountDr . '</td>
                                    <td>' . $amountCr . '</td>
                                    <td>' . showWithCurrencySymbol($data[$i]->rest_balance) . '</td>
                                </tr>';
        }

        $view .= '</tbody>
                        </table>
                        </div>
                    </div>';

        print $view;
    }


}