<?php init_head(); ?>
<div id="wrapper">
    <div class="content paymentmodes-manage-page">
        <div class="row">
            <div class="col-md-12">
                <h1 class="pageTitleH1"><i class="fa fa-money"></i><?php echo $title; ?></h1>
                <div class="pull-right">
                    <div class="breadcrumb mb0">
                        <a href="<?php echo admin_url(); ?>"><i class="fa fa-home"></i></a>
                        <i class="fa fa-angle-right breadcrumb-arrow"></i>
                        <a href="<?php echo admin_url('setup'); ?>">Settings</a>
                        <i class="fa fa-angle-right breadcrumb-arrow"></i>
                        <span>Offline Payment Modes
</span>
                    </div>
                </div>
                <div class="clearfix"></div>
                <div class="panel_s btmbrd">
                    <div class="panel-body">
                        <div class="_buttons">
                            <a href="#" class="btn btn-info pull-left" data-toggle="modal" data-target="#payment_mode_modal"><?php echo _l('new_payment_mode'); ?></a>
                            <p class="text-warning pull-left"><?php echo _l('payment_modes_add_edit_announcement', admin_url('brand_settings?group=online_payment_modes')); ?></p>
                        </div>
                        <div class="clearfix"></div>

                        <div class="clearfix"></div>
                        <?php render_datatable(array(
                            _l('payment_modes_dt_name'),
                            _l('payment_modes_dt_description'),
                            _l('payment_modes_dt_active'),
                            //_l('options')
                            _l('')
                        ),'payment-modes'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="payment_mode_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">
                    <span class="edit-title"><?php echo _l('payment_mode_edit_heading'); ?></span>
                    <span class="add-title"><?php echo _l('payment_mode_add_heading'); ?></span>
                </h4>
            </div>
            <?php echo form_open('admin/paymentmodes/manage',array('id'=>'payment_modes_form')); ?>
            <?php echo form_hidden('paymentmodeid'); ?>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <?php echo render_input('name','payment_mode_add_edit_name'); ?>
                        <?php echo render_textarea('description','payment_mode_add_edit_description','',array('data-toggle'=>'tooltip','title'=>'payment_mode_add_edit_description_tooltip','rows'=>5)); ?>
                        <div class="checkbox checkbox-primary">
                            <input type="checkbox" name="active" id="active">
                            <label for="active"><?php echo _l('payment_mode_add_edit_active'); ?></label>
                        </div>
                        <div class="checkbox checkbox-primary">
                            <input type="checkbox" name="show_on_pdf" id="show_on_pdf">
                            <label for="show_on_pdf"><?php echo _l('show_on_invoice_on_pdf',_l('payment_mode_add_edit_description')); ?></label>
                        </div>
                        <div class="checkbox checkbox-primary">
                            <input type="checkbox" name="selected_by_default" id="selected_by_default">
                            <label for="selected_by_default"><?php echo _l('settings_paymentmethod_default_selected_on_invoice'); ?></label>
                        </div>
                        <!-- <hr /> -->
                        <div class="checkbox checkbox-primary pm-available-to hide">
                            <input type="checkbox" name="invoices_only" id="invoices_only">
                            <label for="invoices_only"><?php echo _l('payment_mode_invoices_only'); ?></label>
                        </div>
                        <div class="checkbox checkbox-primary pm-available-to hide">
                            <input type="checkbox" name="expenses_only" id="expenses_only">
                            <label for="expenses_only"><?php echo _l('payment_mode_expenses_only'); ?></label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    initDataTable('.table-payment-modes', window.location.href, [3], [3]);
    _validate_form($('#payment_modes_form'), {
        name:{
            required:true,
            remote: {
                url: admin_url + "paymentmodes/paymentmode_name_exists",
                type: 'post',
                data: {
                    id:function(){
                        return $('input[name="paymentmodeid"]').val();
                    }
                }
            }
        }
    }, manage_payment_modes);
    /* PAYMENT MODES MANAGE FUNCTIONS */
    function manage_payment_modes(form) {
        var data = $(form).serialize();
        var url = form.action;
        $.post(url, data).done(function(response) {
            response = JSON.parse(response);
            if (response.success == true) {
                $('.table-payment-modes').DataTable().ajax.reload();
                alert_float('success', response.message);
            }
            $('#payment_mode_modal').modal('hide');
        });
        return false;
    }

    $('.pm-available-to input').on('change',function(){
        var checked = $(this).prop('checked');
        var name = $(this).attr('name');
        if(checked == 1 && name == 'invoices_only'){
            $('input[name="expenses_only"]').prop('disabled',true);
        } else if(checked == 0 && name == 'invoices_only'){
            $('input[name="expenses_only"]').prop('disabled',false);
        } else if(checked == 1 && name=='expenses_only'){
            $('input[name="invoices_only"]').prop('disabled',true);
        } else if(checked == 0 && name == 'expenses_only'){
            $('input[name="invoices_only"]').prop('disabled',false);
        }
    });
    $('#payment_mode_modal').on('show.bs.modal', function(event) {

        var button = $(event.relatedTarget)
        var id = button.data('id');
        var expenses_only = button.data('expenses-only');
        var selected_by_default = button.data('default-selected');
        var invoices_only = button.data('invoices-only');
        var show_on_pdf = button.data('show-on-pdf');
        $('#payment_mode_modal input').val('');
        $('#payment_mode_modal input[name="active"]').prop('checked', true);
        $('#payment_mode_modal input[name="expenses_only"]').prop('checked', false).prop('disabled',false);
        $('#payment_mode_modal input[name="invoices_only"]').prop('checked', false).prop('disabled',false);
        $('#payment_mode_modal input[name="show_on_pdf"]').prop('checked', false);
        $('#payment_mode_modal input[name="selected_by_default"]').prop('checked', false);
        $('#payment_mode_modal textarea[name="description"]').val('');
        $('#payment_mode_modal .add-title').removeClass('hide');
        $('#payment_mode_modal .edit-title').addClass('hide');

        if (typeof(id) !== 'undefined') {
            $('input[name="paymentmodeid"]').val(id);
            var name = $(button).parents('tr').find('td').eq(0).text();
            var description = $(button).parents('tr').find('td').eq(1).text();
            var active = $(button).parents('tr').find('td').eq(2).find('input').prop('checked');
            $('#payment_mode_modal input[name="active"]').prop('checked', active);
            $('#payment_mode_modal input[name="expenses_only"]').prop('checked', expenses_only).change();
            $('#payment_mode_modal input[name="invoices_only"]').prop('checked', invoices_only).change();
            $('#payment_mode_modal input[name="show_on_pdf"]').prop('checked', show_on_pdf);
            $('#payment_mode_modal input[name="selected_by_default"]').prop('checked', selected_by_default);
            $('#payment_mode_modal .add-title').addClass('hide');
            $('#payment_mode_modal .edit-title').removeClass('hide');
            $('#payment_mode_modal input[name="name"]').val(name);
            $('#payment_mode_modal textarea[name="description"]').val(description);
        }
    });
    /* END PAYMENT MODES MANAGE FUNCTIONS */
</script>
</body>
</html>
