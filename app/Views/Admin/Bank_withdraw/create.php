<div class="content-wrapper" id="viewpage">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1> Bank Withdraw <small>Bank Withdraw</small> </h1>
        <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active">Bank Withdraw</li>
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
                        <h3 class="box-title">Bank Withdraw</h3>
                    </div>
                    <!-- /.box-header -->
                    <div class="box-body">
                        <div class="row">
                            <div id="message"></div>
                            <?php if (session()->getFlashdata('message') !== NULL) : echo session()->getFlashdata('message'); endif; ?>
                            <div class="col-lg-6" >
                                <form id="geniusform" action="<?php echo $action; ?>" method="post">
                                    <div class="form-group">
                                        <label for="int">Bank</label>
                                        <select class="form-control" name="bank_id" id="bank_id">
                                            <option value="">Please select</option>
                                            <?php echo getTwoValueInOptionWithStatus('','bank_id','name','account_no','bank'); ?>
                                        </select>
                                        <div class="error"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="int">Amount</label>
                                        <input type="text" class="form-control" name="amount" id="amount" placeholder="Amount" >
                                        <div class="error"></div>
                                    </div>
                                    <div class="form-group">
                                        <label for="int">Commont</label>
                                        <input type="text" class="form-control" name="commont" id="commont" placeholder="Commont" >
                                    </div>

                                    <button type="button" class="btn btn-primary" onclick="bankDepositValidat()"  >Withdraw</button>
                                    <a href="javascript:void(0)" onclick="showData('<?php echo site_url('/Admin/Bank_withdraw_ajax/'); ?>','<?php echo '/Admin/Bank_withdraw/'; ?>')" class="btn btn-default">Cancel</a>
                                </form>
                            </div>


                            <div class="col-lg-6" style="border-left: 1px solid #cecdcd;">

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