<?php
$client_contacts_fields = $this->db->list_fields('tblcontacts');
$i = 0;
foreach($client_contacts_fields as $cf){
    if($cf == 'phonenumber'){
        $client_contacts_fields[$i] = 'contact_phonenumber';
    }
    $i++;
}
$client_db_fields = $this->db->list_fields('tblclients');
$custom_fields = get_custom_fields('customers');
if($this->input->post('download_sample') === 'true'){
    $_total_sample_fields = 0;
    header("Pragma: public");
    header("Expires: 0");
    header('Content-Type: application/csv');
    header("Content-Disposition: attachment; filename=\"sample_import_file.csv\";");
    header("Content-Transfer-Encoding: binary");
    foreach($client_contacts_fields as $field){
        if(in_array($field,$not_importable)){continue;}
        if($field == 'title') {
            echo '"Position",';
        } else {
            echo '"'.ucfirst($field).'",';
        }
        $_total_sample_fields++;
    }
    foreach($client_db_fields as $field){
        if(in_array($field,$not_importable)){continue;}
        echo '"'.ucfirst($field).'",';
        $_total_sample_fields++;
    }
    foreach($custom_fields as $field){
        echo '"'.$field['name'].'",';
    }
    echo "\n";
    $sample_data = 'Sample Data';
    for($f = 0;$f<$_total_sample_fields;$f++){
        echo '"'.$sample_data.'",';
    }
    echo "\n";
    exit;
}
?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="breadcrumb">
                    <a href="<?php echo admin_url(); ?>"><i class="fa fa-home"></i></a>
                    <i class="fa fa-angle-right breadcrumb-arrow"></i>
                    <a href="<?php echo admin_url('clients'); ?>">Customers</a>
                    <i class="fa fa-angle-right breadcrumb-arrow"></i>
                    <span>Import</span>
                </div>
                <h1 class="pageTitleH1"><i class="fa fa-user-o"></i><?php echo $title; ?></h1>
                <div class="clearfix"></div>
                <div class="panel_s btmbrd">
                    <div class="panel-body">
                        <?php echo form_open($this->uri->uri_string()); ?>
                        <?php echo form_hidden('download_sample','true'); ?>
                        <button type="submit" class="btn btn-success">Download Sample</button>
                        <hr />
                        <?php echo form_close(); ?>
                        <?php $max_input = ini_get('max_input_vars');
                        if(($max_input>0 && isset($total_rows_post) && $total_rows_post >= $max_input)){ ?>
                            <div class="alert alert-warning">
                                Your hosting provider has PHP setting <b>max_input_vars</b> at <?php echo $max_input;?>.<br/>
                                Ask your hosting provider to increase the <b>max_input_vars</b> setting to <?php echo $total_rows_post;?> or higher or import less rows.
                            </div>
                        <?php } ?>
                        <?php

                        if(!isset($simulate) > 0) { ?>
                            <p>
                                Your CSV data should be in the format below. The first line of your CSV file should be the column headers as in the table example. Also make sure that your file is UTF-8 to avoid unnecessary encoding problems.
                            </p>
                            <p>If the column you are trying to import is date make sure that is formatted in format Y-m-d (<?php echo date('Y-m-d'); ?>)</p>
                            <p class="text-danger">Duplicate email rows wont be imported</p>
                            <div class="table-responsive no-dt">
                                <table class="table table-hover table-bordered">
                                    <thead>
                                    <tr>
                                        <?php
                                        $total_fields = 0; ?>
                                        <?php foreach($client_contacts_fields as $field){
                                            if(in_array($field,$not_importable)){continue;}
                                            ?>
                                            <?php $total_fields++; ?>
                                            <th class="bold"><?php if($field == 'firstname' || $field =='lastname' || $field == 'email'){echo '<span class="text-danger">*</span>';} ?> <?php
                                                if($field == 'title') {
                                                    echo 'Position';
                                                } else {
                                                    echo str_replace('_',' ',ucfirst($field));
                                                }
                                                ?>
                                                <span class="text-info"><?php echo _l('import_contact_field'); ?></span></th>
                                        <?php } ?>
                                        <?php foreach($client_db_fields as $field){
                                            if(in_array($field,$not_importable)){continue;}
                                            ?>
                                            <?php $total_fields++; ?>
                                            <th class="bold"> <?php if($field == 'company' && get_option('company_is_required') == 1){echo '<span class="text-danger">* </span>';} ?><?php echo str_replace('_',' ',ucfirst($field)); ?></th>
                                        <?php } ?>
                                        <?php foreach($custom_fields as $field){ ?>
                                            <?php $total_fields++; ?>
                                            <th class="bold"><?php echo $field['name']; ?></th>
                                        <?php } ?>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php for($i = 0; $i<1;$i++){
                                        echo '<tr>';
                                        for($x = 0; $x<$total_fields;$x++){
                                            echo '<td>Sample Data</td>';
                                        }
                                        echo '</tr>';
                                    }
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } ?>
                        <?php if(isset($simulate) > 0) { ?>
                            <h4 class="bold">Simulation Data <small class="text-info">Max 100 rows are shown</small></h4>
                            <p class="text-info">If you are satisfied with the results upload the file again and click import</p>
                            <div class="table-responsive no-dt">
                                <table class="table table-hover table-bordered">
                                    <thead>
                                    <tr>
                                        <?php
                                        $client_db_fields = array_merge($client_contacts_fields,$client_db_fields);
                                        foreach($client_db_fields as $field){
                                            if(in_array($field,$not_importable)){continue;}
                                            ?>
                                            <th class="bold"><?php echo str_replace('_',' ',ucfirst($field)); ?></th>
                                        <?php } ?>
                                        <?php $custom_fields = get_custom_fields('customers');
                                        foreach($custom_fields as $field){ ?>
                                            <th class="bold"><?php echo $field['name']; ?></th>
                                        <?php } ?>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $simulate_fields = array();
                                    foreach($client_db_fields as $field){
                                        if(in_array($field,$not_importable)){continue;}
                                        array_push($simulate_fields,$field);
                                    }
                                    $custom_fields = get_custom_fields('customers');
                                    foreach($custom_fields as $field){
                                        array_push($simulate_fields,$field['name']);
                                    }
                                    $total_simulate = count($simulate);
                                    for($i = 0; $i < $total_simulate;$i++){
                                        echo '<tr>';
                                        for($x = 0;$x < count($simulate_fields);$x++){
                                            if(!isset($simulate[$i][$simulate_fields[$x]])){echo '<td>/</td>';}else{
                                                echo '<td>'.$simulate[$i][$simulate_fields[$x]].'</td>';
                                            }
                                        }
                                        echo '</tr>';
                                    }
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } ?>
                        <div class="row">
                            <div class="col-md-4 mtop15">
                                <?php echo form_open_multipart($this->uri->uri_string(),array('id'=>'import_form')) ;?>
                                <?php echo form_hidden('clients_import','true'); ?>
                                <?php echo render_input('file_csv','choose_csv_file','','file'); ?>
                                <?php echo render_select('groups_in[]',$groups,array('id','name'),'customer_groups',($this->input->post('groups_in') ? $this->input->post('groups_in') : array()),array('multiple'=>true,'data-none-selected-text'=>_l('dropdown_non_selected_tex'))); ?>
                                <?php echo render_input('default_pass_all','default_pass_clients_import',$this->input->post('default_pass_all')); ?>
                                <div class="form-group">
                                    <button type="button" class="btn btn-info import btn-import-submit"><?php echo _l('import'); ?></button>
                                    <button type="button" class="btn btn-info simulate btn-import-submit"><?php echo _l('simulate_import'); ?></button>
                                </div>
                                <?php echo form_close(); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script src="<?php echo base_url('assets/plugins/jquery-validation/additional-methods.min.js'); ?>"></script>
<script>
    _validate_form($('#import_form'),{file_csv:{required:true,extension: "csv"},source:'required',status:'required'});
    $(function(){
        $('.btn-import-submit').on('click',function(){
            if($(this).hasClass('simulate')){
                $('#import_form').append(hidden_input('simulate',true));
            }
            $('#import_form').submit();
        });
    })
</script>
</body>
</html>
