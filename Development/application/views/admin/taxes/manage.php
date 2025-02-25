<?php init_head();?>
<div id="wrapper">
    <div class="content taxes-manage-page">
        <div class="row">
            <div class="col-md-12">
                <div class="breadcrumb">
                    <a href="<?php echo admin_url(); ?>"><i class="fa fa-home"></i></a>
                    <i class="fa fa-angle-right breadcrumb-arrow"></i>
                    <a href="<?php echo admin_url('setup'); ?>">Settings</a>
                    <i class="fa fa-angle-right breadcrumb-arrow"></i>
                    <span>Taxes</span>
                </div>
                <h1 class="pageTitleH1"><i class="fa fa-money"></i><?php echo $title; ?></h1>
                <div class="clearfix"></div>
                <div class="panel_s btmbrd">
                    <div class="panel-body">
                        <div class="_buttons">
                            <?php if (has_permission('lists','','create')) { ?>
                                <a href="#" class="btn btn-info pull-left" data-toggle="modal" data-target="#tax_modal"><?php echo _l('new_tax'); ?></a>
                            <?php } ?>
                        </div>
                        <div class="clearfix"></div>
                        <?php render_datatable(array(
                            _l('tax_dt_name'),
                            _l('tax_dt_rate'),
                            //_l('options')
                            _l('')
                        ),'taxes'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="tax_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">
                    <span class="edit-title"><?php echo _l('tax_edit_title'); ?></span>
                    <span class="add-title"><?php echo _l('tax_add_title'); ?></span>
                </h4>
            </div>
            <?php echo form_open('admin/taxes/manage',array('id'=>'tax_form')); ?>
            <?php echo form_hidden('taxid'); ?>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-warning hide tax_is_used_in_expenses_warning">
                            <?php echo _l('tax_is_used_in_expenses_warning'); ?>
                        </div>
                        <?php echo render_input('name','tax_add_edit_name'); ?>
                        <?php echo render_input('taxrate','tax_add_edit_rate','','number'); ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('cancel'); ?></button>
                <button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    /* TAX MANAGE FUNCTIONS */
    function manage_tax(form) {
        var data = $(form).serialize();
        var url = form.action;
        $.post(url, data).done(function(response) {
            response = JSON.parse(response);
            if (response.success == true) {
                $('.table-taxes').DataTable().ajax.reload();
                alert_float('success', response.message);
            } else {
                if(response.message != ''){
                    alert_float('warning', response.message);
                }
            }
            $('#tax_modal').modal('hide');
        });
        return false;
    }

    $(function(){

        initDataTable('.table-taxes', window.location.href, [2], [2]);
        _validate_form($('#tax_form'),{
                name:{
                    required:true,
                    remote: {
                        url: admin_url + "taxes/tax_name_exists",
                        type: 'post',
                        data: {
                            taxid:function(){
                                return $('input[name="taxid"]').val();
                            }
                        }
                    }
                },
                rate:{number:true,required:true}},
            manage_tax);

        // don't allow | charachter in tax name
        // is used for tax name and tax rate separations!
        $('#tax_modal input[name="name"]').on('change',function(){
            var val = $(this).val();
            if(val.indexOf('|') > -1){
                val = val.replace('|','');
                // Clean extra spaces in case this char is in the middle with space
                val = val.replace( / +/g, ' ' );
                $(this).val(val);
            }
        });
        $('#tax_modal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget)
            var id = button.data('id');
            $('#tax_modal input').val('').prop('disabled',false);
            $('#tax_modal .add-title').removeClass('hide');
            $('#tax_modal .edit-title').addClass('hide');
            $('.tax_is_used_in_expenses_warning').addClass('hide');
            if (typeof(id) !== 'undefined') {
                $('input[name="taxid"]').val(id);
                var name = $(button).parents('tr').find('td').eq(0).text();
                var rate = $(button).parents('tr').find('td').eq(1).text();
                var is_referenced = $(button).data('is-referenced');
                if(is_referenced == 1){
                    $('.tax_is_used_in_expenses_warning').removeClass('hide');
                }
                $('#tax_modal .add-title').addClass('hide');
                $('#tax_modal .edit-title').removeClass('hide');
                $('#tax_modal input[name="name"]').val(name).prop('disabled',(is_referenced == 1 ? true : false));
                $('#tax_modal input[name="taxrate"]').val(rate).prop('disabled',(is_referenced == 1 ? true : false));
            }
        });

        $('#tax_modal').on('hidden.bs.modal', function() {
            $('.form-group').removeClass('has-error');
            $('.form-group').find('p.text-danger').remove();
        });
    });

    /* END TAX MANAGE FUNCTIONS */
</script>
</body>
</html>
