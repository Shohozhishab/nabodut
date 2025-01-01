<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Calculate_unit_and_price;
use App\Libraries\Permission;
use CodeIgniter\HTTP\RedirectResponse;


class Purchase_report_ajax extends BaseController
{


    protected $permission;
    protected $validation;
    protected $session;
    protected $crop;
    protected $calculate_unit_and_price;
    private $module_name = 'Purchase_report';

    public function __construct()
    {
        $this->permission = new Permission();
        $this->validation = \Config\Services::validation();
        $this->session = \Config\Services::session();
        $this->crop = \Config\Services::image();
        $this->calculate_unit_and_price = new Calculate_unit_and_price();
    }

    /**
     * @description This method provides purchase report view
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

            //all suppliers purchase total amount show (start)
            $suppliersTab = DB()->table('suppliers');
            $suppliers = $suppliersTab->where('sch_id', $shopId)->get()->getResult();
            //all suppliers purchase total amount show (start)

            $purchase = array();
            //Purchase item show list (start)
            $i = 0;
            $purchaseTab = DB()->table('purchase');
            $purchaseId = $purchaseTab->where('sch_id', $shopId)->get()->getResult();
            foreach ($purchaseId as $value) {
                $purchase_itemTab = DB()->table('purchase_item');
                $query = $purchase_itemTab->where('purchase_id', $value->purchase_id)->orderBy('purchase_item_id', 'DESC')->limit('10')->get()->getResult();
                $purchase[$i] = $query;
                $i++;
            }
            //Purchase item show list (end)


            $data = array(
                'suppliers' => $suppliers,
                'purchaseItem' => $purchase,
                'calculate_library' => $this->calculate_unit_and_price,
            );


            $data['menu'] = view('Admin/menu_report');
            // All Permissions
            //$perm = array('create','read','update','delete','mod_access');
            $perm = $this->permission->module_permission_list($role_id, $this->module_name);
            foreach ($perm as $key => $val) {
                $data[$key] = $this->permission->have_access($role_id, $this->module_name, $key);
            }
            if (isset($data['mod_access']) and $data['mod_access'] == 1) {
                echo view('Admin/Purchase_report/list', $data);
            } else {
                echo view('no_permission');
            }
        }
    }


}