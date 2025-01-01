<div class="content-wrapper" id="viewpage">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1> Supplier Ledger <small>Supplier Ledger</small></h1>
        <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active">Supplier Ledger</li>
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
                                <h3 class="box-title">Supplier Ledger</h3>
                            </div>
                            <div class="col-lg-3"></div>
                            <div class="col-lg-12" style="margin-top: 20px;">
                                <?php if (session()->getFlashdata('message') !== NULL) : echo session()->getFlashdata('message'); endif; ?>
                            </div>
                        </div>
                    </div>
                    <!-- /.box-header -->
                    <div class="box-body">

                        <div class="col-md-3" style="padding: 20px;">
                            <label for="int">Supplier name</label>

                            <select class="form-control select2 select2-hidden-accessible" onchange="supplierLedgher(this.value);supplierLedgherPrint(this.value)" id="supplierId" style=" width: 100%;" tabindex="-1" aria-hidden="true">
                                <option selected="selected"  value="">Please Select</option>
                                <?php echo getAllListInOption('supplier_id','supplier_id','name','suppliers'); ?>
                            </select>

                        </div>
                        <div class="col-xs-3" style="padding: 17px;">
                            <label>Start Date</label>
                            <input type="date" class="form-control" name="st_date" id="st_date"  required>
                        </div>
                        <div class="col-xs-3" style="padding: 17px;">
                            <label>End Date</label>
                            <input type="date" class="form-control" name="en_date" id="en_date"  required>
                        </div>
                        <div class="col-xs-3" style="padding: 18px;">
                            <button style="margin-top: 23px;" onclick="suppLedSerc();suppLedSercprint()" class="btn btn-primary " type="submit">Filter</button>
                        </div>

                    </div>
                    <!-- /.box-body -->
                </div>

                <div id="supplierLedg"></div>

                <div style="display: none;" >
                    <div id="ledgPrint"></div>
                </div>
                <div class="row no-print" >
                    <div class="col-xs-12">
                        <button onclick="printDiv('ledgPrint')"    class="print_line btn btn-primary pull-right" ><i class="fa fa-print "></i> Print Now</button>
                    </div>
                </div>


            </div>

        </div>
        <!-- /.row -->

    </section>
    <!-- /.content -->
</div>
