<div class="content-wrapper" id="viewpage">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1> Capital Ledger <small>Capital Ledger</small></h1>
        <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active">Capital Ledger</li>
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
                                <h3 class="box-title">Capital Ledger</h3>
                            </div>
                            <div class="col-lg-3"></div>
                            <div class="col-lg-12" style="margin-top: 20px;">
                                <?php if (session()->getFlashdata('message') !== NULL) : echo session()->getFlashdata('message'); endif; ?>
                            </div>
                        </div>
                    </div>
                    <!-- /.box-header -->
                    <div class="box-body">
                        <table class="table table-bordered table-striped" id="example1">
                            <thead>
                            <tr>
                                <th>Id</th>
                                <th>Date</th>
                                <th>Particulars</th>
                                <th>Trangaction Id</th>
                                <th>Debit</th>
                                <th>Credit</th>
                                <th>Balance</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php

                            $totalRows = count($ledger_capital)-1;
                            for($i = $totalRows; $i >= 0; $i--) {
                                $particulars = ($ledger_capital[$i]->particulars == NULL) ? "Payment" : $ledger_capital[$i]->particulars;
                                $amountCr = ($ledger_capital[$i]->trangaction_type != "Cr.") ? "---" : showWithCurrencySymbol($ledger_capital[$i]->amount);
                                $amountDr =($ledger_capital[$i]->trangaction_type != "Dr.")?"---":showWithCurrencySymbol($ledger_capital[$i]->amount);
                                ?>
                                <tr>
                                    <td><?php echo $ledger_capital[$i]->capital_id ?></td>
                                    <td><?php echo $ledger_capital[$i]->createdDtm ?></td>
                                    <td><?php echo $particulars ?></td>
                                    <td><?php echo $ledger_capital[$i]->trans_id ?></td>
                                    <td><?php echo $amountDr ?></td>
                                    <td><?php echo $amountCr ?></td>
                                    <td><?php echo showWithCurrencySymbol($ledger_capital[$i]->rest_balance) ?></td>
                                </tr>
                            <?php }?>

                            </tbody>
                            <tfoot>
                            <tr>
                                <th>Id</th>
                                <th>Date</th>
                                <th>Particulars</th>
                                <th>Trangaction Id</th>
                                <th>Debit</th>
                                <th>Credit</th>
                                <th>Balance</th>
                            </tr>

                            </tfoot>
                        </table>
                    </div>
                    <!-- /.box-body -->
                </div>

                <div class="row no-print" >
                    <div class="col-xs-12">
                        <button onclick="printDiv('ledgPrint')"    class="print_line btn btn-primary pull-right" ><i class="fa fa-print "></i> Print Now</button>
                    </div>
                </div>
            </div>

            <div class="col-md-12" id="ledgPrint" style="display: none; text-transform: capitalize; " >
                <div class="col-xs-12" style="margin-bottom: 20px;   ">
                    <div class="col-xs-6">
                        <?php if(logo_image() == NULL){ ?>
                            <img src="<?php echo base_url() ?>/uploads/schools/no_image.jpg" alt="User Image" >
                        <?php }else{ ?>
                            <img src="<?php echo base_url(); ?>/uploads/schools/<?php echo logo_image(); ?>" class="" alt="User Image">
                        <?php } ?>
                    </div>
                    <div class="col-xs-6">
                        <?php print address(); ?>
                    </div>
                </div>
                <div class="col-md-12" >
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
                        <tbody>
                        <?php
                        foreach ($ledger_capital as $row) {

                            $particulars = ($row->particulars == NULL) ? "Payment" : $row->particulars;
                            $amountCr = ($row->trangaction_type != "Cr.") ? "---" : showWithCurrencySymbol($row->amount);
                            $amountDr =($row->trangaction_type != "Dr.")?"---":showWithCurrencySymbol($row->amount);
                            ?>
                            <tr>
                                <td><?php echo bdDateFormat($row->createdDtm) ?></td>
                                <td><?php echo $particulars ?></td>
                                <td><?php echo $amountDr ?></td>
                                <td><?php echo $amountCr ?></td>
                                <td><?php echo showWithCurrencySymbol($row->rest_balance) ?></td>
                            </tr>
                        <?php }?>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- /.row -->

    </section>
    <!-- /.content -->
</div>
