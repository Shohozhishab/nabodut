<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Calculate_unit_and_price;
use App\Libraries\Mycart;
use App\Libraries\Permission;
use CodeIgniter\HTTP\RedirectResponse;


class Sales extends BaseController
{

    protected $permission;
    protected $validation;
    protected $session;
    protected $crop;
    protected $cart;
    protected $calculate_unit_and_price;
    private $module_name = 'Sales';

    public function __construct()
    {
        $this->permission = new Permission();
        $this->validation = \Config\Services::validation();
        $this->session = \Config\Services::session();
        $this->crop = \Config\Services::image();
        $this->cart = new Mycart();
        $this->calculate_unit_and_price = new Calculate_unit_and_price();
    }

    /**
     * @description This method provides sales view
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
            $salesTable = DB()->table('sales');
            $data['sales'] = $salesTable->where('sch_id', $shopId)->where('deleted IS NULL')->get()->getResult();

            $data['menu'] = view('Admin/menu_sales', $data);
            // All Permissions
            //$perm = array('create','read','update','delete','mod_access');
            $perm = $this->permission->module_permission_list($role_id, $this->module_name);
            foreach ($perm as $key => $val) {
                $data[$key] = $this->permission->have_access($role_id, $this->module_name, $key);
            }
            echo view('Admin/header');
            echo view('Admin/sidebar');
            if (isset($data['mod_access']) and $data['mod_access'] == 1) {
                echo view('Admin/Sales/list', $data);
            } else {
                echo view('no_permission');
            }
            echo view('Admin/footer');
        }
    }

    /**
     * @description This method provides sales create view
     * @return RedirectResponse|void
     */
    public function create()
    {
        $isLoggedIn = $this->session->isLoggedIn;
        $role_id = $this->session->role;
        if (!isset($isLoggedIn) || $isLoggedIn != TRUE) {
            return redirect()->to(site_url('Admin/login'));
        } else {
            $shopId = $this->session->shopId;
            $salesTable = DB()->table('sales');
            $data['sales'] = $salesTable->where('sch_id', $shopId)->where('deleted IS NULL')->get()->getResult();

            $data['action'] = base_url('Admin/Sales/create_action');
            $data['menu'] = view('Admin/menu_sales', $data);
            $data['calculate_library'] = $this->calculate_unit_and_price;
            // All Permissions
            //$perm = array('create','read','update','delete','mod_access');
            $perm = $this->permission->module_permission_list($role_id, $this->module_name);
            foreach ($perm as $key => $val) {
                $data[$key] = $this->permission->have_access($role_id, $this->module_name, $key);
            }
            echo view('Admin/header');
            echo view('Admin/sidebar');
            if (isset($data['mod_access']) and $data['create'] == 1) {
                echo view('Admin/Sales/create', $data);
            } else {
                echo view('no_permission');
            }
            echo view('Admin/footer');
        }
    }

    /**
     * @description This method sales search products
     * @return void
     */
    public function search_prod()
    {
        $shopId = $this->session->shopId;

        $keyWord = $this->request->getPost("keyWord");

        $proTable = DB()->table('products');
        $whereLike = "(`name` LIKE '%{$keyWord}%' ESCAPE '!' OR  `prod_id` LIKE '%{$keyWord}%' ESCAPE '!')";
        $data = $proTable->where("sch_id", $shopId)->where("quantity >", 0)->where($whereLike)->get()->getResult();


        $view = '';
        foreach ($data as $sval) {
            $image = ($sval->picture == NULL) ? 'no_image.jpg' : $sval->picture;
            $unit = get_data_by_id('unit', 'products', 'prod_id', $sval->prod_id);

            $totalCalculet = $this->calculate_unit_and_price->convert_total_kg_to_ton($sval->quantity);
            $totalQty = $totalCalculet['ton'].' Ton '.$totalCalculet['kg'].  ' KG';

            $view = $view . '<li>
                            <form action="' . site_url('Admin/Sales/add_cart') . '" method="post">
                                <div class="col-xs-12" style="padding:15px; border-bottom: 1px solid;color: #d2d6de;" ><a>
                                <div class="col-xs-1 sel_css">
                                    <img class="img-circle" src="' . base_url() . '/uploads/product_image/' . $image . '" width="40" height="40">
                                </div>
                                <div class="col-xs-3 sel_css"><label for="usr">Product Name :</label><h4 style="color:black;">' . $sval->name. '</h4><input class="form-control" type="hidden" readonly id="name" name="name" value="' . $sval->name . '"><input class="form-control" type="text" id="price" name="price" value="' . $this->calculate_unit_and_price->par_ton_price_by_par_kg_price($sval->selling_price) . '"><input class="form-control" type="hidden" readonly id="prod_id" name="prod_id" value="' . $sval->prod_id . '"></div>
                                <div class="col-xs-2 sel_css"><span for="usr">Category:</span><br><h5 style="color:black;">' . get_data_by_id('product_category', 'product_category', 'prod_cat_id', $sval->prod_cat_id) . '</h5>'.$totalQty.'
                                </div>
                                <div class="col-xs-4 sel_css"><span>Quantity:</span>
                                    <div  style="display: flex;">
                                        <input type="number" class="qty_ton form-control " name="qty_ton" placeholder="Quantity" value="1"/>
                                        <select class="form-control" name="unit_1">
                                            '.selectOptions($selected = 4, unitArray()).'
                                        </select>
                                    </div>
                                    <div  style="display: flex;">
                                        <input type="number" class="qty_kg form-control " name="qty_kg" placeholder="Quantity" value="0"/>
                                        <select class="form-control" name="unit_2">
                                            '.selectOptions($selected = 2, unitArray()).'
                                        </select>
                                    </div>
                                </div>
                                <div class="col-xs-2 sel_css" style="padding-top:28px; ">
                                    <button  type="subbmit" class="add_cart btn btn-success btn-xs" >Add To Cart</button>
                                </div></a></div>
                                </form>
                                </li>';

        }
        echo $view;

    }

    /**
     * @description This method add to cart
     * @return RedirectResponse
     */
    public function add_cart()
    {
        $proPrice = $this->request->getPost('price');
        if (!empty($proPrice)) {
            $proId = $this->request->getPost('prod_id');
            $proName = $this->request->getPost('name');
            $price = $this->calculate_unit_and_price->ton_price_to_kg_price($proPrice);
            $qty_ton = $this->request->getPost('qty_ton');
            $qty_kg = $this->request->getPost('qty_kg');

            $quantity = $this->calculate_unit_and_price->convert_total_kg($qty_ton, $qty_kg);

            $productQnt = get_data_by_id('quantity', 'products', 'prod_id', $proId);

            $qty = 0;
            foreach ($this->cart->contents() as $row) {
                if ($proId == $row['id']) {
                    $qty = $row['qty'];
                }
            }
            $totalquantity = $quantity + $qty;

            if ($productQnt >= $totalquantity) {
                if ($quantity > 0) {
                    $data = array(
                        'id' => $proId,
                        'name' => strval($proName),
                        'qty' => $quantity,
                        'price' => $price
                    );
                    $this->cart->insert($data);
                } else {
                    $this->session->setFlashdata('message', '<div class="alert alert-danger alert-dismissible" role="alert"> Invalid Quantity  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
                    return redirect()->to(site_url('Admin/Sales/create'));
                }

            } else {
                $this->session->setFlashdata('message', '<div class="alert alert-warning alert-dismissible" role="alert">Warning: You have no available product quantity to sale<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');

            }
            $this->session->set('cartType', 'sale');
            return redirect()->to(site_url('Admin/Sales/create'));
        }else{
            $this->session->setFlashdata('message', '<div class="alert alert-warning alert-dismissible" role="alert">Warning: Please input sale price<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
            return redirect()->to(site_url('Admin/Sales/create'));
        }
    }

    /**
     * @description This method clear cart
     * @return RedirectResponse
     */
    public function clearCart()
    {

        $this->cart->destroy();
        return redirect()->to(site_url('Admin/Sales/create'));

    }

    /**
     * @description This method remove cart
     * @param int $id
     * @return RedirectResponse
     */
    public function remove_cart($id)
    {
        $this->cart->remove($id);
        return redirect()->to(site_url('Admin/Sales/create'));
    }

    /**
     * @description This method store sales
     * @return RedirectResponse
     */
    public function create_action()
    {
        $shopId = $this->session->shopId;
        $userId = $this->session->userId;


        $customerId = $this->request->getPost('customer_id');
        $customerName = $this->request->getPost('name');

        $proId = $this->request->getPost('productId[]');
        $quantity = $this->request->getPost('qty[]');
        $proPrice = $this->request->getPost('price[]');

        $number = count($proId);
        for ($i = 0; $i < $number; $i++) {
            $prodsaleDiscSingle[] = '';
        }

        $prodsaleDisc = !empty($this->request->getPost('disc[]')) ? $this->request->getPost('disc[]') : $prodsaleDiscSingle;
        $prodsubtotal = $this->request->getPost('subtotal[]');
        $prosubTo = $this->request->getPost('suballtotal[]');

        $entiresaleDisc = $this->request->getPost('saleDisc');
        $vat = !empty($this->request->getPost('vat')) ? $this->request->getPost('vat') : '';
        $vatAmount = $this->request->getPost('vatAmount');


        $amount = $this->request->getPost('grandtotal2');
        $finalAmount = $this->request->getPost('grandtotal');

        $nagod = $this->request->getPost('nagod');
        $bankAmount = $this->request->getPost('bankAmount');
        $bankId = $this->request->getPost('bank_id');
        $chequeNo = $this->request->getPost('chequeNo');
        $chequeAmount = $this->request->getPost('chequeAmount');
        $sms = $this->request->getPost('sms');

        $dueAmount = $this->request->getPost('grandtotaldue');
        $singDiscount = empty($this->request->getPost('granddiscountlast')) ? 0 : $this->request->getPost('granddiscountlast');

        $discountAmount = $amount - $finalAmount;
        $alldiscount = $discountAmount + $singDiscount;

        //customer shop check(start)
        if (!empty($customerId)) {
            $shopCheck = check_shop('customers', 'customer_id', $customerId);
            if ($shopCheck != 1) {
                $this->session->setFlashdata('message', '<div class="alert alert-danger alert-dismissible" role="alert">Please enter valid customer <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
                return redirect()->to(site_url('Admin/Sales/create'));
            }
        }
        //customer shop check(end)


        // If customer name of Id not selected (start)
        if (empty($customerName) && empty($customerId)) {
            $this->session->setFlashdata('message', '<div class="alert alert-danger alert-dismissible" role="alert">please enter valid customer!<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
            return redirect()->to(site_url('Admin/Sales/create'));
        }
        // If customer name of Id not selected (End)


        // Validation for the new customer. New customer should only pay through cash and full payment. Other payment will not exeute. (Start)
        if (!empty($customerName)) {
            if (($chequeAmount > 0) || ($dueAmount > 0)) {
                return redirect()->to(site_url('Admin/Sales/create'));
            }
        }
        // Validation for the new customer. New customer should only pay through cash and full payment. Other payment will not exeute. (End)


        if (empty($proId)) {
            return redirect()->to(site_url('Admin/Sales/create'));
        }

        $toAm = (double)$nagod + (double)$bankAmount + (double)$chequeAmount + (double)$dueAmount;

        if ($toAm != $finalAmount) {
            $this->session->setFlashdata('message', '<div class="alert alert-danger alert-dismissible" role="alert">wrong input!! please correct inputs to proceed.<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
            return redirect()->to(site_url('Admin/Sales/create'));
        }

        if (!empty($nagod) && $nagod < 0) {
            $this->session->setFlashdata('message', '<div class="alert alert-danger alert-dismissible" role="alert">Please enter valid amount!<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
            return redirect()->to(site_url('Admin/Sales/create'));
        }

        if (!empty($bankAmount) && $bankAmount < 0) {
            $this->session->setFlashdata('message', '<div class="alert alert-danger alert-dismissible" role="alert">Please enter valid amount!<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
            return redirect()->to(site_url('Admin/Sales/create'));
        }

        if (!empty($chequeAmount) && $chequeAmount < 0) {
            $this->session->setFlashdata('message', '<div class="alert alert-danger alert-dismissible" role="alert">Please enter valid amount!<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>');
            return redirect()->to(site_url('Admin/Sales/create'));
        }

        DB()->transStart();


        //create invoice in invoice table (start)
        $invData = array(
            'sch_id' => $shopId,
            'amount' => $amount,
            'entire_sale_discount' => $entiresaleDisc,
            'vat' => $vat,
            'final_amount' => $finalAmount,
            'nagad_paid' => $nagod,
            'bank_paid' => $bankAmount,
            'bank_id' => $bankId,
            'chaque_paid' => $chequeAmount,
            'due' => $dueAmount,
            'createdBy' => $userId,
            'createdDtm' => date('Y-m-d h:i:s')
        );


        if (!empty($customerId)) {
            $invData['customer_id'] = $customerId;
        } else {
            $invData['customer_name'] = $customerName;
        }
        $invoiceTab = DB()->table('invoice');
        $invoiceTab->insert($invData);
        $invoiceId = DB()->insertID();
        //create invoice in invoice table (end)


        //discount ledger make (start)
        if (!empty($alldiscount)) {
            $prevdis = get_data_by_id('discount', 'shops', 'sch_id', $shopId);
            $disRestBel = $prevdis + $alldiscount;

            $disLedgher = array(
                'sch_id' => $shopId,
                'invoice_id' => $invoiceId,
                'amount' => $alldiscount,
                'particulars' => 'Sale discount',
                'trangaction_type' => 'Dr.',
                'rest_balance' => $disRestBel,
                'createdDtm' => date('Y-m-d h:i:s')
            );
            $ledger_discountTab = DB()->table('ledger_discount');
            $ledger_discountTab->insert($disLedgher);


            //update discount balance(start)
            $disData = array(
                'discount' => $disRestBel,
                'updatedBy' => $userId,
            );
            $shopsTab = DB()->table('shops');
            $shopsTab->where('sch_id', $shopId)->update($disData);
            //update discount balance(end)
        }
        //discount ledger make (end)


        //vat ledgher insert (start)
        if (!empty($vatAmount)) {
            $vatId = get_data_by_id('vat_id', 'vat_register', 'sch_id', $shopId);
            $previousVat = get_data_by_id('balance', 'vat_register', 'sch_id', $shopId);
            $vatRestBalance = $previousVat - $vatAmount;

            $VatLedgher = array(
                'sch_id' => $shopId,
                'vat_id' => $vatId,
                'invoice_id' => $invoiceId,
                'amount' => $vatAmount,
                'particulars' => 'Sale Vat Earn ',
                'trangaction_type' => 'Cr.',
                'rest_balance' => $vatRestBalance,
                'createdDtm' => date('Y-m-d h:i:s')
            );
            $ledger_vatTab = DB()->table('ledger_vat');
            $ledger_vatTab->insert($VatLedgher);

            //update vat register table(start)
            $vatRegData = array(
                'balance' => $vatRestBalance,
                'updatedBy' => $userId,
            );
            $ledger_vatTab = DB()->table('vat_register');
            $ledger_vatTab->where('sch_id', $shopId)->update($vatRegData);
            //update vat register table(end)
        }
        //vat ledgher insert (end)


        //invoice itame insert
        $totalpurPrice = 0;
        $number = count($proId);
        for ($i = 0; $i < $number; $i++) {
            $kgPrice = $this->calculate_unit_and_price->ton_price_to_kg_price($proPrice[$i]);
            // Inserting invoice item data into invoice_item table(Start)
            $invItemData = array(
                'sch_id' => $shopId,
                'invoice_id' => $invoiceId,
                'prod_id' => $proId[$i],
                'price' => $kgPrice,
                'quantity' => $quantity[$i],
                'total_price' => $prodsubtotal[$i],
                'discount' => $prodsaleDisc[$i],
                'final_price' => $prosubTo[$i],
                'createdBy' => $userId,
                'createdDtm' => date('Y-m-d h:i:s')
            );
            $invoice_itemTab = DB()->table('invoice_item');
            $invoice_itemTab->insert($invItemData);
            //print $this->db->last_query();
            // Inserting invoice item data into invoice_item table(End)


            //calculating profit for indivisual item and updating the profite column (start)
            $productPurPrice = get_data_by_id('purchase_price', 'products', 'prod_id', $proId[$i]);
            $purPrice = $productPurPrice * $quantity[$i];
            $totalpurPrice += $productPurPrice * $quantity[$i];
            $profit = $prosubTo[$i] - $purPrice;
            $profitData = array('profit' => $profit);

            $where = array(
                'invoice_id' => $invoiceId,
                'prod_id' => $proId[$i],
            );
            $invoice_itemTab2 = DB()->table('invoice_item');
            $invoice_itemTab2->where($where)->update($profitData);
            //calculating profit for indivisual item and updating the profite column (end)


            //product Qnt Update in product table (start)
            $productQnt = get_data_by_id('quantity', 'products', 'prod_id', $proId[$i]);
            $qnt = $productQnt - $quantity[$i];
            $qntProData = array(
                'quantity' => $qnt,
                'updatedBy' => $userId,
            );
            $productsTable = DB()->table('products');
            $productsTable->where('prod_id', $proId[$i])->update($qntProData);
            //product Qnt Update in product table (end)
        }


        //create sals in sales table(start)
        $saleData = array(
            'sch_id' => $shopId,
            'invoice_id' => $invoiceId,
            'createdDtm' => date('Y-m-d h:i:s')
        );
        $salesTab = DB()->table('sales');
        $salesTab->insert($saleData);
        //create sals in sales table(end)


        //sale balance update and ledger create (start)
        $withoutVat = $finalAmount - (int)$vatAmount;
        $saleBal = get_data_by_id('sale_balance', 'shops', 'sch_id', $shopId);
        $restBalSale = $saleBal - $withoutVat;


        $saleUpdata = array('sale_balance' => $restBalSale);
        $shopsTabl = DB()->table('shops');
        $shopsTabl->where('sch_id', $shopId)->update($saleUpdata);


        $saleLedgData = array(
            'sch_id' => $shopId,
            'invoice_id' => $invoiceId,
            'trangaction_type' => 'Cr.',
            'particulars' => 'New Sale amount',
            'amount' => $withoutVat,
            'rest_balance' => $restBalSale,
            'createdBy' => $userId,
            'createdDtm' => date('Y-m-d h:i:s')
        );
        $ledger_salesTab = DB()->table('ledger_sales');
        $ledger_salesTab->insert($saleLedgData);

        //sale balance update and ledger create (end)


        //Update salse profit in invoice table (start)
        $invoice_itemT = DB()->table('invoice_item');
        $totalProfit = $invoice_itemT->selectSum('profit')->where('invoice_id', $invoiceId)->get()->getRow()->profit;
//        $totalProfit = $this->db->select_sum('profit')->from('invoice_item')->where('invoice_id',$invoiceId)->get()->row()->profit;
        $invoiceT = DB()->table('invoice');
        $invData = $invoiceT->where('invoice_id', $invoiceId)->get()->getRow();
        $invProfit = $invData->amount - $invData->final_amount;
        $prifitAll = $totalProfit - $invProfit;

        $inData = array(
            'profit' => $prifitAll,
            'updatedBy' => $userId,
        );
        $invoiceTabl = DB()->table('invoice');
        $invoiceTabl->where('invoice_id', $invoiceId)->update($inData);


        $shopProfit = get_data_by_id('profit', 'shops', 'sch_id', $shopId);
        $totShopPro = $shopProfit - $totalProfit + $discountAmount + (double)$vatAmount;

        $dataShoproUp = array(
            'profit' => $totShopPro,
        );
        $shopsTable = DB()->table('shops');
        $shopsTable->where('sch_id', $shopId)->update($dataShoproUp);


        $profitLedData = array(
            'sch_id' => $shopId,
            'invoice_id' => $invoiceId,
            'trangaction_type' => 'Cr.',
            'particulars' => 'Sales profit get',
            'amount' => $totalProfit,
            'rest_balance' => $totShopPro,
            'createdBy' => $userId,
            'createdDtm' => date('Y-m-d h:i:s')
        );
        $ledger_profitTab = DB()->table('ledger_profit');
        $ledger_profitTab->insert($profitLedData);


        $stockBal = get_data_by_id('stockAmount', 'shops', 'sch_id', $shopId);
        $restBalStock = $stockBal - $totalpurPrice;


        $stockUpdata = array('stockAmount' => $restBalStock);
        $shopsTabl = DB()->table('shops');
        $shopsTabl->where('sch_id', $shopId)->update($stockUpdata);


        $stockLedgData = array(
            'sch_id' => $shopId,
            'invoice_id' => $invoiceId,
            'trangaction_type' => 'Cr.',
            'particulars' => 'Sale amount',
            'amount' => $totalpurPrice,
            'rest_balance' => $restBalStock,
            'createdBy' => $userId,
            'createdDtm' => date('Y-m-d h:i:s')
        );
        $ledger_stockTabl = DB()->table('ledger_stock');
        $ledger_stockTabl->insert($stockLedgData);
        //Update salse profit in invoice table (end)


        //existing customer balance update and customer ledger create (start)
        if ($customerId) {


            //customer balance update in customer table (start)
            $customerCash = get_data_by_id('balance', 'customers', 'customer_id', $customerId);
            $newCash = $customerCash + $finalAmount;
            //update balance
            $custData = array(
                'balance' => $newCash,
                'updatedBy' => $userId,
            );
            $customersTab = DB()->table('customers');
            $customersTab->where('customer_id', $customerId)->update($custData);
            //customer balance update in customer table (end)


            //insert customer ledger in ledger(start)
            $ledgerData = array(
                'sch_id' => $shopId,
                'customer_id' => $customerId,
                'invoice_id' => $invoiceId,
                'trangaction_type' => 'Dr.',
                'particulars' => 'Sales Cash Due',
                'amount' => $finalAmount,
                'rest_balance' => $newCash,
                'createdBy' => $userId,
                'createdDtm' => date('Y-m-d h:i:s')
            );
            $ledgerTab = DB()->table('ledger');
            $ledgerTab->insert($ledgerData);
            //insert customer ledger in ledger(end)
            if(!empty($sms)) {
                $message = 'Thank you for your order.Your order amount is-' . $finalAmount;
                $phone = get_data_by_id('mobile', 'customers', 'customer_id', $customerId);
                send_sms($phone, $message);
            }

        }
        //existing customer balance update and customer ledger create (end)


        //cash pay shop cash update and create nagod ledger (start)
        if ($nagod > 0) {
            //cash pay amount update shops cash (start)
            $shopsCash = get_data_by_id('cash', 'shops', 'sch_id', $shopId);
            $upCahs = $shopsCash + $nagod;

            $shopsData = array(
                'cash' => $upCahs,
                'updatedBy' => $userId,
            );
            $shopsTab = DB()->table('shops');
            $shopsTab->where('sch_id', $shopId)->update($shopsData);
            //cash pay amount update shops cash (end)


            //insert ledger in ledger_nagodan cash pay amount(start)
            $lgNagData = array(
                'sch_id' => $shopId,
                'invoice_id' => $invoiceId,
                'trangaction_type' => 'Dr.',
                'particulars' => 'Sales Cash Pay',
                'amount' => $nagod,
                'rest_balance' => $upCahs,
                'createdBy' => $userId,
                'createdDtm' => date('Y-m-d h:i:s')
            );
            $ledger_nagodanTab = DB()->table('ledger_nagodan');
            $ledger_nagodanTab->insert($lgNagData);
            //insert ledger in ledger_nagodan cash pay amount(start)


            //cash pay amount and customer balance amount calculate and update customer balance (start)
            if ($customerId) {
                //customer balance calculate (start)
                $custCash = get_data_by_id('balance', 'customers', 'customer_id', $customerId);
                $newcastCash = $custCash - $nagod;
                //customer balance calculate (end)


                //update calculate balance in customer table(start)
                $custnewData = array(
                    'balance' => $newcastCash,
                    'updatedBy' => $userId,
                );
                $customersTab = DB()->table('customers');
                $customersTab->where('customer_id', $customerId)->update($custnewData);
                //update calculate balance in customer table(end)


                //create ledger in ledger table
                $ledgernogodData = array(
                    'sch_id' => $shopId,
                    'customer_id' => $customerId,
                    'invoice_id' => $invoiceId,
                    'trangaction_type' => 'Cr.',
                    'particulars' => 'Sales Cash Pay',
                    'amount' => $nagod,
                    'rest_balance' => $newcastCash,
                    'createdBy' => $userId,
                    'createdDtm' => date('Y-m-d h:i:s')
                );
                $ledgerTab = DB()->table('ledger');
                $ledgerTab->insert($ledgernogodData);
            }
            //cash pay amount and customer balance amount calculate and update customer balance (end)
        }
        //cash pay shop cash update and create nagod ledger (end)


        // bank pay amount calculate and bank balance update (start)
        if ($bankAmount > 0) {
            //bank pay amount calculate and update bank balance (start)
            $bankCash = get_data_by_id('balance', 'bank', 'bank_id', $bankId);
            $upCahs = $bankCash + $bankAmount;

            $bankData = array(
                'balance' => $upCahs,
                'updatedBy' => $userId,
            );
            $bankTab = DB()->table('bank');
            $bankTab->where('bank_id', $bankId)->update($bankData);
            //bank pay amount calculate and update bank balance (end)


            //insert ledger in table ledger_bank (start)
            $lgBankData = array(
                'sch_id' => $shopId,
                'bank_id' => $bankId,
                'invoice_id' => $invoiceId,
                'particulars' => 'Sales Bank Pay',
                'trangaction_type' => 'Dr.',
                'amount' => $bankAmount,
                'rest_balance' => $upCahs,
                'createdBy' => $userId,
                'createdDtm' => date('Y-m-d h:i:s')
            );
            $ledger_bankTab = DB()->table('ledger_bank');
            $ledger_bankTab->insert($lgBankData);
            //insert ledger in table ledger_bank (end)


            if ($customerId) {
                //bank pay amount calculate and customer balance update (start)
                $cusCash = get_data_by_id('balance', 'customers', 'customer_id', $customerId);
                $bankastCash = $cusCash - $bankAmount;

                $custnewData = array(
                    'balance' => $bankastCash,
                    'updatedBy' => $userId,
                );
                $customersTab = DB()->table('customers');
                $customersTab->where('customer_id', $customerId)->update($custnewData);
                //bank pay amount calculate and customer balance update (start)


                //insert ledger in table ledger (start)
                $ledgerbankData = array(
                    'sch_id' => $shopId,
                    'customer_id' => $customerId,
                    'invoice_id' => $invoiceId,
                    'trangaction_type' => 'Cr.',
                    'particulars' => 'Sales Bank Pay',
                    'amount' => $bankAmount,
                    'rest_balance' => $bankastCash,
                    'createdBy' => $userId,
                    'createdDtm' => date('Y-m-d h:i:s')
                );
                $ledgerTab = DB()->table('ledger');
                $ledgerTab->insert($ledgerbankData);
                //insert ledger in table ledger (start)

            }

        }
        // bank pay amount calculate and bank balance update (end)


        // cheque pay amount calculate and insert cheque tabile (end)
        if ($chequeAmount > 0) {

            //cheque pay amount calculate and insert cheque tabile(start)
            $chequeData = array(
                'sch_id' => $shopId,
                'chaque_number' => $chequeNo,
                'to' => $userId,
                'from' => $customerId,
                'amount' => $chequeAmount,
                'createdDtm' => date('Y-m-d h:i:s')
            );
            if (!empty($customerId)) {
                $chequeData ['from'] = $customerId;
            } else {
                $chequeData ['from_name'] = $customerName;
            }
            $chaqueTab = DB()->table('chaque');
            $chaqueTab->insert($chequeData);
            $chaqueId = DB()->insertID();
            //cheque pay amount calculate and insert cheque tabile(end)


            //chaque id update in invoice table(start)
            $invChaqueId = array(
                'chaque_id' => $chaqueId,
                'updatedBy' => $userId,
            );
            $invoiceTab = DB()->table('invoice');
            $invoiceTab->where('invoice_id', $invoiceId)->update($invChaqueId);
            //chaque id update in invoice table(end)
        }

        DB()->transComplete();

        $this->cart->destroy();
        return redirect()->to(site_url('Admin/Invoice/view/' . $invoiceId));
    }

    /**
     * @description This method scan add to cart
     * @return void
     */
    public function scanAddToCart()
    {
        $shopId = $this->session->shopId;

        $proId = $this->request->getPost('prod_id');

        $checkShop = get_data_by_id('sch_id', 'products', 'prod_id', $proId);

        if ($checkShop == $shopId) {

            $proName = get_data_by_id('name', 'products', 'prod_id', $proId);
            $proPrice = get_data_by_id('selling_price', 'products', 'prod_id', $proId);
            $quantity = 1;

            $productQnt = get_data_by_id('quantity', 'products', 'prod_id', $proId);

            $qty = 0;
            foreach ($this->cart->contents() as $row) {
                if ($proId == $row['id']) {
                    $qty = $row['qty'];
                }
            }
            $totalquantity = $quantity + $qty;

            if ($productQnt >= $totalquantity) {
                if ($quantity > 0) {
                    $data = array(
                        'id' => $proId,
                        'name' => strval($proName),
                        'qty' => $quantity,
                        'price' => $proPrice
                    );
                    $this->cart->insert($data);
                }
            }
            $this->session->set('cartType', 'sale');
        } else {
            print '<div class="alert alert-warning alert-dismissible" role="alert">Warning: You have no available product.<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>';
        }

    }
    public function customerBalance(){
        $customerId = $this->request->getPost('customer_id');
        $balance = get_data_by_id('balance', 'customers', 'customer_id', $customerId);
        return $balance;
    }





}