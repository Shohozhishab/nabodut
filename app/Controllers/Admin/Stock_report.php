<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Calculate_unit_and_price;
use App\Libraries\Permission;
use CodeIgniter\HTTP\RedirectResponse;


class Stock_report extends BaseController
{


    protected $permission;
    protected $validation;
    protected $session;
    protected $crop;
    protected $calculate_unit_and_price;
    private $module_name = 'Stock_report';

    public function __construct()
    {
        $this->permission = new Permission();
        $this->validation = \Config\Services::validation();
        $this->session = \Config\Services::session();
        $this->crop = \Config\Services::image();
        $this->calculate_unit_and_price = new Calculate_unit_and_price();
    }

    /**
     * @description This method provides stock report view
     * @return RedirectResponse|void
     */
    public function index()
    {
        $isLoggedIn = $this->session->isLoggedIn;
        $role_id = $this->session->role;
        if (!isset($isLoggedIn) || $isLoggedIn != TRUE) {
            return redirect()->to(site_url('Admin/login'));
        } else {


            $data['menu'] = view('Admin/menu_report');
            // All Permissions
            //$perm = array('create','read','update','delete','mod_access');
            $perm = $this->permission->module_permission_list($role_id, $this->module_name);
            foreach ($perm as $key => $val) {
                $data[$key] = $this->permission->have_access($role_id, $this->module_name, $key);
            }
            echo view('Admin/header');
            echo view('Admin/sidebar');
            if (isset($data['mod_access']) and $data['mod_access'] == 1) {
                echo view('Admin/Stock_report/list', $data);
            } else {
                echo view('no_permission');
            }
            echo view('Admin/footer');
        }
    }

    /**
     * @description This method search store product
     * @return void
     */
    public function search_store_product()
    {
        $storeId = $this->request->getPost('storeId');
        $shopId = $this->session->shopId;


        $productsTb = DB()->table('products');
        $data = $productsTb->where('store_id', $storeId)->where('sch_id', $shopId)->orderBy('prod_id', "DESC")->get()->getResult();


        $prodQtyTb = DB()->table('products');
        $quty = $prodQtyTb->where('store_id', $storeId)->where('sch_id', $shopId)->orderBy('prod_id', "DESC")->get()->getRow();
        $quantity = '0';
        if (!empty($quty)) {
            $quantity = $quty->quantity;
        }

        $prodPurTb = DB()->table('products');
        $purchasePricequery = $prodPurTb->where('store_id', $storeId)->where('sch_id', $shopId)->orderBy('prod_id', "DESC")->get()->getResult();

        $purchasePrice = 0;

        foreach ($purchasePricequery as  $pur) {
            $purchasePrice += $pur->quantity * $pur->purchase_price;
        }

        $name = get_data_by_id('name', 'stores', 'store_id', $storeId);
        $qtyInv = $this->calculate_unit_and_price->convert_total_kg_to_ton($quantity);
        $view = '<div class="box">
                <div class="box-header">
                    <h3 class="box-title" >Store Name: <b >' . $name . '</b></h3>
                    <span class="pull-right" style="margin-right:10px;" ><b>Storage Inventory Prices:</b> ' . showWithCurrencySymbol($purchasePrice) . '</span>
                    <span class="pull-right" style="margin-right:40px;"> <b>Storage Inventory Quantity:</b> ' . $qtyInv['ton'].' Ton '.$qtyInv['kg'].  ' KG </span>
                </div>
                <div class="box-body">
                    <table class="table table-bordered table-striped" id="TFtable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Name</th>
                                <th>Product Category</th>
                                <th>Quantity</th>
                                <th>Purchase Price</th>
                                <th>Selling Price</th>
                            </tr>
                        </thead>
                        <tbody>';
        $i = '';
        foreach ($data as $row) {
            $qty = $this->calculate_unit_and_price->convert_total_kg_to_ton($row->quantity);
            $view .= '<tr>
                        <td>' . ++$i . '</td>
                        <td>' . $row->name . '</td>
                        <td>' . get_data_by_id('product_category', 'product_category', 'prod_cat_id', $row->prod_cat_id) . '</td>
                        <td>' . $qty['ton'].' Ton '.$qty['kg'].' Kg </td>
                        <td>' . showWithCurrencySymbol($this->calculate_unit_and_price->par_ton_price_by_par_kg_price($row->purchase_price)) . '</td>
                        <td>' . showWithCurrencySymbol($this->calculate_unit_and_price->par_ton_price_by_par_kg_price($row->selling_price)) . '</td>
                    </tr>';
        }
        $view .= '</tbody>
                        <tfoot>
                            <tr>
                                <th>No</th>
                                <th>Name</th>
                                <th>Product Category</th>
                                <th>Quantity</th>
                                <th>Purchase Price</th>
                                <th>Selling Price</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>';

        print $view;

    }


}