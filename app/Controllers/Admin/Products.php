<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Calculate_unit_and_price;
use App\Libraries\Permission;
use CodeIgniter\HTTP\RedirectResponse;


class Products extends BaseController
{

    protected $permission;
    protected $validation;
    protected $session;
    protected $crop;
    protected $calculate_unit_and_price;
    private $module_name = 'Products';

    public function __construct()
    {
        $this->permission = new Permission();
        $this->validation = \Config\Services::validation();
        $this->session = \Config\Services::session();
        $this->crop = \Config\Services::image();
        $this->calculate_unit_and_price = new Calculate_unit_and_price();
    }

    /**
     * @description This method provides products view
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
            $productTable = DB()->table('products');
            $data['products_data'] = $productTable->where('sch_id', $shopId)->where('deleted IS NULL')->get()->getResult();
            $data['calculate_library'] = $this->calculate_unit_and_price;

            $data['menu'] = view('Admin/menu_stock');
            // All Permissions
            //$perm = array('create','read','update','delete','mod_access');
            $perm = $this->permission->module_permission_list($role_id, $this->module_name);
            foreach ($perm as $key => $val) {
                $data[$key] = $this->permission->have_access($role_id, $this->module_name, $key);
            }
            echo view('Admin/header');
            echo view('Admin/sidebar');
            if (isset($data['mod_access']) and $data['mod_access'] == 1) {
                echo view('Admin/Products/list', $data);
            } else {
                echo view('no_permission');
            }
            echo view('Admin/footer');
        }
    }

    /**
     * @description This method update products
     * @param int $id
     * @return RedirectResponse|void
     */
    public function update($id)
    {
        $isLoggedIn = $this->session->isLoggedIn;
        $role_id = $this->session->role;
        if (!isset($isLoggedIn) || $isLoggedIn != TRUE) {
            return redirect()->to(site_url('Admin/login'));
        } else {
            $shopId = $this->session->shopId;
            $productsTable = DB()->table('products');
            $data['product'] = $productsTable->where('prod_id', $id)->where('sch_id', $shopId)->get()->getRow();
            $data['calculate_library'] = $this->calculate_unit_and_price;

            $data['menu'] = view('Admin/menu_stock');
            // All Permissions
            //$perm = array('create','read','update','delete','mod_access');
            $perm = $this->permission->module_permission_list($role_id, $this->module_name);
            foreach ($perm as $key => $val) {
                $data[$key] = $this->permission->have_access($role_id, $this->module_name, $key);
            }
            echo view('Admin/header');
            echo view('Admin/sidebar');
            if (isset($data['mod_access']) and $data['update'] == 1) {
                echo view('Admin/Products/update', $data);
            } else {
                echo view('no_permission');
            }
            echo view('Admin/footer');
        }
    }

    /**
     * @description This method update products general
     * @return void
     */
    public function general_update()
    {
        $userId = $this->session->userId;

        $data['prod_id'] = $this->request->getPost('prod_id');
        $data['store_id'] = $this->request->getPost('store_id');
        $data['name'] = $this->request->getPost('name');
        $data['supplier_id'] = $this->request->getPost('supplier_id');
        $data['serial_number'] = empty($this->request->getPost('serial_number')) ? null : $this->request->getPost('serial_number');
        $data['updatedBy'] = $userId;

        $this->validation->setRules([
            'store_id' => ['label' => 'store_id', 'rules' => 'required'],
            'name' => ['label' => 'name', 'rules' => 'required|only_numeric_not_allow'],
            'supplier_id' => ['label' => 'supplier_id', 'rules' => 'required'],
        ]);

        if ($this->validation->run($data) == FALSE) {
            print '<div class="alert alert-danger alert-dismissible" role="alert">' . $this->validation->listErrors() . ' <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
        } else {
            $productTable = DB()->table('products');
            if ($productTable->where('prod_id', $data['prod_id'])->update($data)) {
                print '<div class="alert alert-success alert-dismissible" role="alert"> Update data successfully  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
            } else {
                print '<div class="alert alert-danger alert-dismissible" role="alert"> something went wrong  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
            }
        }
    }

    /**
     * @description This method update products personal
     * @return void
     */
    public function personal_update()
    {
        $userId = $this->session->userId;

        $data['prod_id'] = $this->request->getPost('prod_id');
        $data['prod_cat_id'] = $this->request->getPost('sub_cat_id');
        $data['brand_id'] = $this->request->getPost('brand_id');
        $data['selling_price'] = $this->request->getPost('selling_price');
        $data['size'] = $this->request->getPost('size');
        $data['warranty'] = $this->request->getPost('warranty');
        $data['updatedBy'] = $userId;

        $this->validation->setRules([
            'selling_price' => ['label' => 'selling_price', 'rules' => 'required'],
        ]);

        if ($this->validation->run($data) == FALSE) {
            print '<div class="alert alert-danger alert-dismissible" role="alert">' . $this->validation->listErrors() . ' <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
        } else {

            $data['selling_price'] =  $this->calculate_unit_and_price->ton_price_to_kg_price($data['selling_price']);
            $productTable = DB()->table('products');
            if ($productTable->where('prod_id', $data['prod_id'])->update($data)) {
                print '<div class="alert alert-success alert-dismissible" role="alert"> Update data successfully  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
            } else {
                print '<div class="alert alert-danger alert-dismissible" role="alert"> something went wrong  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
            }
        }
    }

    /**
     * @description This method update products photo
     * @return void
     */
    public function photo_update()
    {

        $data['prod_id'] = $this->request->getPost('prod_id');

        if (!empty($_FILES['picture']['name'])) {
            $target_dir = FCPATH . '/uploads/product_image/';
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777);
            }

            //old image unlink
            $old_img = get_data_by_id('picture', 'products', 'prod_id', $data['prod_id']);
            if (!empty($old_img)) {
                unlink($target_dir . $old_img);
            }

            //new image uplode
            $pic = $this->request->getFile('picture');
            $namePic = $pic->getRandomName();
            $pic->move($target_dir, $namePic);
            $pro_nameimg = 'product_' . $pic->getName();
            $this->crop->withFile($target_dir .  $namePic)->fit(300, 300, 'center')->save($target_dir .  $pro_nameimg);
            unlink($target_dir .  $namePic);
            $data['picture'] = $pro_nameimg;


            $productTable = DB()->table('products');
            if ($productTable->where('prod_id', $data['prod_id'])->update($data)) {
                print '<div class="alert alert-success alert-dismissible" role="alert"> Update data successfully  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
            } else {
                print '<div class="alert alert-danger alert-dismissible" role="alert"> something went wrong  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
            }

        } else {
            print '<div class="alert alert-danger alert-dismissible" role="alert"> please select a image  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
        }


    }

    /**
     * @description This method update products barcode
     * @return void
     */
    public function barcode()
    {
        $data['barcodeqty'] = $this->request->getPost('barcodeqty');

        $data['generator'] = new \Picqer\Barcode\BarcodeGeneratorPNG();
        $tabGenSet = DB()->table('gen_settings');

        $sizeBarcode = $tabGenSet->where('label', 'barcode_img_size')->get()->getRow()->value;
        $data['barcodeSize'] = empty($sizeBarcode) ? '100' : $sizeBarcode;

        $typeBarcode = $tabGenSet->where('label', 'barcode_type')->get()->getRow()->value;
        $data['barcodeType'] = empty($typeBarcode) ? 'C128' : $typeBarcode;

        echo view('Admin/header');
        echo view('Admin/sidebar');
        echo view('Admin/Products/barcode', $data);
        echo view('Admin/footer');
    }

    public function add_existing_product(){
        $isLoggedIn = $this->session->isLoggedIn;
        $role_id = $this->session->role;
        if (!isset($isLoggedIn) || $isLoggedIn != TRUE) {
            return redirect()->to(site_url('Admin/login'));
        } else {
            $shopId = $this->session->shopId;
            $productTable = DB()->table('products');
            $data['products_data'] = $productTable->where('sch_id', $shopId)->where('deleted IS NULL')->get()->getResult();

            $data['menu'] = view('Admin/menu_stock');
            // All Permissions
            //$perm = array('create','read','update','delete','mod_access');
            $perm = $this->permission->module_permission_list($role_id, $this->module_name);
            foreach ($perm as $key => $val) {
                $data[$key] = $this->permission->have_access($role_id, $this->module_name, $key);
            }
            echo view('Admin/header');
            echo view('Admin/sidebar');
            if (isset($data['mod_access']) and $data['mod_access'] == 1) {
                echo view('Admin/Products/add_product', $data);
            } else {
                echo view('no_permission');
            }
            echo view('Admin/footer');
        }
    }

    public function add_action(){
        $data['prod_cat_id'] = $this->request->getPost('sub_category');
        $data['name'] = $this->request->getPost('name');
        $data['purchase_price'] = $this->request->getPost('price');
        $data['selling_price'] = $this->request->getPost('selling_price');
        $data['qty_ton'] = $this->request->getPost('qty_ton');
        $data['qty_kg'] = $this->request->getPost('qty_kg');
        $data['unit_1'] = $this->request->getPost('unit_1');
        $data['unit_2'] = $this->request->getPost('unit_2');

        $this->validation->setRules([
            'prod_cat_id' => ['label' => 'Category', 'rules' => 'required'],
            'name' => ['label' => 'name', 'rules' => 'required'],
            'purchase_price' => ['label' => 'price', 'rules' => 'required'],
            'selling_price' => ['label' => 'salePrice', 'rules' => 'required'],
        ]);

        if ($this->validation->run($data) == FALSE) {
            print '<div class="alert alert-danger alert-dismissible" role="alert">' . $this->validation->listErrors() . ' <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
        } else {

            $purchasePrice =  $this->calculate_unit_and_price->ton_price_to_kg_price($data['purchase_price']);
            $qty =  $this->calculate_unit_and_price->convert_total_kg($data['qty_ton'],$data['qty_kg']);
            $sellingPrice =  $this->calculate_unit_and_price->ton_price_to_kg_price($data['selling_price']);


            DB()->transStart();
            $shopId = $this->session->shopId;

            //get default store
            $storeTab = DB()->table('stores');
            $store = $storeTab->where('sch_id', $shopId)->where('is_default', 1)->get()->getRow();

            //insert product
            $dataPro['prod_cat_id'] = $data['prod_cat_id'];
            $dataPro['name'] = $data['name'];
            $dataPro['unit'] = $data['unit_2'];
            $dataPro['unit_display'] = $data['unit_1'];

            $dataPro['purchase_price'] = $purchasePrice;
            $dataPro['selling_price'] = $sellingPrice;
            $dataPro['quantity'] = $qty;
            $dataPro['store_id'] = $store->store_id;
            $dataPro['sch_id'] = $shopId;
            $dataPro['createdBy'] = $this->session->userId;
            $dataPro['createdDtm'] = date('Y-m-d H:i:s');
            $productTable = DB()->table('products');
            $productTable->insert($dataPro);


            //total amount product
            $totalAmountPro = $purchasePrice * $qty;

            //capital last balance
            $oldCapital = get_data_by_id('capital', 'shops', 'sch_id', $shopId);
            $newCapital = $oldCapital - $totalAmountPro;

            // capital ledger data insert
            $cpitalLedData = array(
                'sch_id' => $shopId,
                'particulars' => 'New Existing Products Add Amount',
                'trangaction_type' => 'Cr.',
                'amount' => $totalAmountPro,
                'rest_balance' => $newCapital,
                'createdBy' => $this->session->userId,
                'createdDtm' => date('Y-m-d h:i:s')
            );
            $ledger_capitalTable = DB()->table('ledger_capital');
            $ledger_capitalTable->insert($cpitalLedData);
            // capital ledger data insert


            //stock last balance
            $oldStock = get_data_by_id('stockAmount', 'shops', 'sch_id', $shopId);
            $newStock = $oldStock + $totalAmountPro;

            //Stock ledger data insert
            $stockLedgData = array(
                'sch_id' => $shopId,
                'trangaction_type' => 'Dr.',
                'particulars' => 'New Existing Products Add Amount',
                'amount' => $totalAmountPro,
                'rest_balance' => $newStock,
                'createdBy' => $this->session->userId,
                'createdDtm' => date('Y-m-d h:i:s')
            );
            $tabledger_stock = DB()->table('ledger_stock');
            $tabledger_stock->insert($stockLedgData);
            //Stock ledger data insert


            //update capital and stock
            $dataCapital['stockAmount'] = $newStock;
            $dataCapital['capital'] = $newCapital;
            $tableCapital = DB()->table('shops');
            $tableCapital->where('sch_id', $shopId)->update($dataCapital);
            DB()->transComplete();

            print '<div class="alert alert-success alert-dismissible" role="alert"> Product added successfully  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';

        }
    }


}