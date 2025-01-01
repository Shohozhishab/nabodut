<div class="content-wrapper" id="viewpage">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1> Bank Deposit <small>Bank Deposit List</small></h1>
        <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active">Bank Deposit</li>
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
                                <h3 class="box-title">Bank Deposit List</h3>
                            </div>
                            <div class="col-lg-3">
                                <a href="javascript:void(0)"
                                   onclick="showData('<?php echo site_url('/Admin/Bank_deposit_ajax/create/'); ?>','<?php echo '/Admin/Bank_deposit/create/'; ?>'),activeTab(this)"
                                   class="btn btn-block btn-primary">Deposit</a>
                            </div>
                            <div class="col-lg-12" style="margin-top: 20px;">
                                <?php if (session()->getFlashdata('message') !== NULL) : echo session()->getFlashdata('message'); endif; ?>
                            </div>
                        </div>


                    </div>
                    <!-- /.box-header -->
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-8">
                                <table id="example1" class="table table-bordered table-striped text-capitalize">
                                    <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Bank Name</th>
                                        <th>Amount</th>
                                        <th>Commont</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php $start = 1;
                                    foreach ($bank_deposit as $bank_deposit) { ?>
                                        <tr>
                                            <td width="80px"><?php echo ++$start ?></td>
                                            <td><?php echo get_data_by_id('name', 'bank', 'bank_id', $bank_deposit->bank_id) ?></td>
                                            <td><?php echo showWithCurrencySymbol($bank_deposit->amount) ?></td>
                                            <td><?php echo $bank_deposit->commont ?></td>

                                        </tr>
                                    <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-4">
                                <table class="table table-bordered table-striped" >
                                    <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Bank Name</th>
                                        <th>Amount</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                    <?php $i=1; foreach ($bank as $val){?>
                                        <tr>
                                            <td><?php echo $i++?></td>
                                            <td><?php echo $val->name?></td>
                                            <td><?php echo showWithCurrencySymbol($val->balance)?></td>
                                        </tr>
                                    <?php } ?>
                                    <tfoot>
                                    <tr>
                                        <th>No</th>
                                        <th>Bank Name</th>
                                        <th>Amount</th>
                                    </tr>
                                    </tfoot>
                                </table>

                            </div>
                        </div>
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
