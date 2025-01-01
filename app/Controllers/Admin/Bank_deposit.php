<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Permission;
use CodeIgniter\HTTP\RedirectResponse;


class Bank_deposit extends BaseController
{

    protected $permission;
    protected $validation;
    protected $session;
    protected $crop;
    private $module_name = 'Bank_deposit';

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
            $bank_depositTable = DB()->table('bank_deposit');
            $data['bank_deposit'] = $bank_depositTable->where('sch_id', $shopId)->where('deleted IS NULL')->get()->getResult();

            $bank = DB()->table('bank');
            $data['bank'] = $bank->where('sch_id', $shopId)->where('deleted IS NULL')->get()->getResult();

            $data['menu'] = view('Admin/menu_bank');
            // All Permissions
            //$perm = array('create','read','update','delete','mod_access');
            $perm = $this->permission->module_permission_list($role_id, $this->module_name);
            foreach ($perm as $key => $val) {
                $data[$key] = $this->permission->have_access($role_id, $this->module_name, $key);
            }
            echo view('Admin/header');
            echo view('Admin/sidebar');
            if (isset($data['mod_access']) and $data['mod_access'] == 1) {
                echo view('Admin/Bank_deposit/list', $data);
            } else {
                echo view('no_permission');
            }
            echo view('Admin/footer');
        }
    }

    /**
     * @description This method provides create view
     * @return RedirectResponse|void
     */
    public function create()
    {
        $isLoggedIn = $this->session->isLoggedIn;
        $role_id = $this->session->role;
        if (!isset($isLoggedIn) || $isLoggedIn != TRUE) {
            return redirect()->to(site_url('Admin/login'));
        } else {
            $data['action'] = base_url('Admin/Bank_deposit/create_action');

            $data['menu'] = view('Admin/menu_bank');
            // All Permissions
            //$perm = array('create','read','update','delete','mod_access');
            $perm = $this->permission->module_permission_list($role_id, $this->module_name);
            foreach ($perm as $key => $val) {
                $data[$key] = $this->permission->have_access($role_id, $this->module_name, $key);
            }
            echo view('Admin/header');
            echo view('Admin/sidebar');
            if (isset($data['mod_access']) and $data['create'] == 1) {
                echo view('Admin/Bank_deposit/create', $data);
            } else {
                echo view('no_permission');
            }
            echo view('Admin/footer');
        }
    }

    /**
     * @description This method create bank deposit
     * @return void
     */
    public function create_action()
    {
        $shopId = $this->session->shopId;
        $userId = $this->session->userId;

        $data['bank_id'] = $this->request->getPost('bank_id');
        $data['amount'] = $this->request->getPost('amount');
        $data['commont'] = $this->request->getPost('commont');
        $data['sch_id'] = $shopId;
        $data['createdBy'] = $userId;
        $data['createdDtm'] = date('Y-m-d h:i:s');

        $this->validation->setRules([
            'bank_id' => ['label' => 'bank', 'rules' => 'required'],
            'amount' => ['label' => 'amount', 'rules' => 'required|is_natural_no_zero|max_length[32]'],
        ]);

        if ($this->validation->run($data) == FALSE) {
            print '<div class="alert alert-danger alert-dismissible" role="alert">' . $this->validation->listErrors() . ' <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
        } else {
            $shopsBalance = get_data_by_id('cash', 'shops', 'sch_id', $shopId);
            $shopCheck = check_shop('bank', 'bank_id', $data['bank_id']);

            if ($shopCheck == 1) {
                if ($shopsBalance >= $data['amount']) {
                    if ($data['amount'] > 0) {
                        DB()->transStart();

                        //insert deposit amount in deposit table (start)
                        $data = array(
                            'sch_id' => $shopId,
                            'bank_id' => $data['bank_id'],
                            'amount' => $data['amount'],
                            'commont' => $this->request->getPost('commont'),
                            'createdBy' => $userId,
                            'createdDtm' => date('Y-m-d h:i:s')
                        );
                        $bank_depositTable = DB()->table('bank_deposit');
                        $bank_depositTable->insert($data);
                        //insert deposit amount in deposit table (end)


                        //shops deduct balance
                        $shopUpBalance = $shopsBalance - $data['amount'];
                        $shopData = array(
                            'cash' => $shopUpBalance,
                            'updatedDtm' => date('Y-m-d h:i:s')
                        );
                        $shopsTable = DB()->table('shops');
                        $shopsTable->where('sch_id', $shopId)->update($shopData);

                        //insert ledger_nagodan
                        $lgNagData = array(
                            'sch_id' => $shopId,
                            'bank_id' => $data['bank_id'],
                            'trangaction_type' => 'Cr.',
                            'particulars' => 'Bank Cash Deposit',
                            'amount' => $data['amount'],
                            'rest_balance' => $shopUpBalance,
                            'createdDtm' => date('Y-m-d h:i:s'),
                        );
                        $ledger_nagodanTable = DB()->table('ledger_nagodan');
                        $ledger_nagodanTable->insert($lgNagData);

                        //bank In balance
                        $bankBalance = get_data_by_id('balance', 'bank', 'bank_id', $data['bank_id']);
                        $bankUpBalance = $bankBalance + $data['amount'];
                        $bankdata = array(
                            'balance' => $bankUpBalance,
                            'updatedDtm' => date('Y-m-d h:i:s')
                        );
                        $bankTable = DB()->table('bank');
                        $bankTable->where('bank_id', $data['bank_id'])->update($bankdata);

                        //insert ledger_bank
                        $ledBankData = array(
                            'sch_id' => $shopId,
                            'bank_id' => $data['bank_id'],
                            'amount' => $data['amount'],
                            'particulars' => 'Bank Cash Deposit',
                            'trangaction_type' => 'Dr.',
                            'rest_balance' => $bankUpBalance,
                            'createdDtm' => date('Y-m-d h:i:s'),
                        );
                        $ledger_bankTable = DB()->table('ledger_bank');
                        $ledger_bankTable->insert($ledBankData);

                        print '<div class="alert alert-success alert-dismissible" role="alert"> Deposit data successfully  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';

                        DB()->transComplete();
                    } else {
                        print '<div class="alert alert-danger alert-dismissible" role="alert"> Enter a valid amount  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
                    }


                } else {
                    print '<div class="alert alert-danger alert-dismissible" role="alert"> Shop balance is too low for this Deposit  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
                }
            } else {
                print '<div class="alert alert-danger alert-dismissible" role="alert"> Please input valid bank  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
            }


        }
    }


}