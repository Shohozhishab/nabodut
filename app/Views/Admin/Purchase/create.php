<div class="content-wrapper" id="viewpage">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1> Purchase <small>Purchase Create</small> </h1>
        <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active">Purchase </li>
        </ol>
    </section>

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="row">
            <div class="col-xs-12" style="margin-bottom: 15px;">
                <a href="#" onclick="showData('<?php echo site_url('/Admin/Purchase_ajax'); ?>','<?php echo '/Admin/Purchase';?>')"  class="btn btn-default"><i class="fa fa-arrow-left" aria-hidden="true"></i> Back to list</a>
            </div>
            <div class="col-xs-12">

                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title">Purchase Create</h3>
                    </div>
                    <!-- /.box-header -->
                    <div class="box-body">
                        <div class="row">
                            <div id="message"></div>
                            <?php if (session()->getFlashdata('message') !== NULL) : echo session()->getFlashdata('message'); endif; ?>
                            <div class="col-lg-6" >
                                <form  action="<?php echo $action; ?>" method="post">
                                    <div class="form-group">
                                        <label for="int">Type </label>
                                        <select class="form-control" name="type_id" required >
                                            <option value="" >Please select</option>
                                            <option value="1">New Item</option>
                                            <option value="2">Existing Item</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="int">Supplier</label>
                                        <select class="form-control" name="supplier_id" required>
                                            <option value="">Please select</option>
                                            <?php echo getAllListInOptionWithStatus('','supplier_id','name','suppliers','name'); ?>
                                        </select>
                                    </div>


                                    <button type="submit" class="btn btn-primary geniusSubmit-btn">Next</button>
                                    <a href="javascript:void(0)" onclick="showData('<?php echo site_url('/Admin/Purchase_ajax/'); ?>','<?php echo '/Admin/Purchase/'; ?>')" class="btn btn-default">Cancel</a>
                                </form>
                            </div>


                            <div class="col-lg-6" style="border-left: 1px solid #cecdcd;"></div>
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