<div class="content-wrapper" id="viewpage">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1> Sales  <small>Sales List</small></h1>
        <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active">Sales</li>
        </ol>
    </section>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-xs-12" style="margin-bottom: 15px;">
                <?php echo $menu;?>
            </div>
            <div class="col-xs-12">

                <div class="box">
                    <div class="box-header">
                        <div class="row">
                            <div class="col-lg-9">
                                <h3 class="box-title">Sales List</h3>
                            </div>
                            <div class="col-lg-3">
                                <a href="javascript:void(0)"
                                   onclick="showData('<?php echo site_url('/Admin/Sales_ajax/create/'); ?>','<?php echo '/Admin/Sales/create/'; ?>')"
                                   class="btn btn-block btn-primary">Sales</a>
                            </div>
                            <div class="col-lg-12" style="margin-top: 20px;">
                                <?php if (session()->getFlashdata('message') !== NULL) : echo session()->getFlashdata('message'); endif; ?>
                            </div>
                        </div>


                    </div>
                    <!-- /.box-header -->
                    <div class="box-body">
                        <table id="example1" class="table table-bordered table-striped text-capitalize">
                            <thead>
                            <tr>
                                <th>No</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Invoice Id</th>
                                <th>Total Amount</th>
                                <th>Total Due</th>
                                <?php if (isset($sale_profit) AND ($sale_profit == 1)) { ?>
                                <th>Profit</th>
                                <?php } ?>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php $i = 1;
                                foreach ($sales as $sales) {
                                    $cus_id = get_data_by_id('customer_id','invoice','invoice_id',$sales->invoice_id);
                                    $cusName = !empty($cus_id)?get_data_by_id('customer_name','customers','customer_id',$cus_id):get_data_by_id('customer_name','invoice','invoice_id',$sales->invoice_id);
                                    $profit = get_data_by_id('profit','invoice','invoice_id',$sales->invoice_id);
                            ?>
                                <tr>
                                    <td><?php echo $i++ ?></td>
                                    <td><?php echo invoiceDateFormat($sales->createdDtm) ?></td>
                                    <td><?php echo $cusName ?></td>
                                    <td><?php echo $sales->invoice_id ?></td>
                                    <td><?php echo showWithCurrencySymbol(get_data_by_id('amount','invoice','invoice_id',$sales->invoice_id)) ?></td>
                                    <td><?php echo showWithCurrencySymbol(get_data_by_id('due','invoice','invoice_id',$sales->invoice_id))?></td>
                                    <?php if (isset($sale_profit) AND ($sale_profit == 1)) { ?>
                                    <td><?php echo showWithCurrencySymbol($profit) ?></td>
                                    <?php } ?>
                                    <td>

                                        <a href="javascript:void(0)"
                                           onclick="showData('<?php echo site_url('/Admin/Invoice_ajax/view/' . $sales->invoice_id); ?>','<?php echo '/Admin/Invoice/view/' . $sales->invoice_id; ?>')" class="btn btn-warning btn-xs">View</a>
                                    </td>
                                </tr>
                            <?php } ?>

                            </tbody>
                        </table>
                    </div>
                    <!-- /.box-body -->
                </div>
                <!-- /.box -->
            </div>

        </div>
        <!-- /.row -->

    </section>
    <!-- /.content -->
</div>
