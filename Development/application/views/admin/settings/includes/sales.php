<?php echo form_hidden('finance_settings'); ?>
<ul class="nav nav-tabs" role="tablist">
	<li role="presentation" class="active">
		<a href="#general" aria-controls="general" role="tab" data-toggle="tab"><?php echo _l('settings_sales_heading_general'); ?></a>
	</li>
	<li role="presentation">
		<a href="#invoice" aria-controls="invoice" role="tab" data-toggle="tab"><?php echo _l('settings_sales_heading_invoice'); ?></a>
	</li>
	<li role="presentation">
		<a href="#estimates" aria-controls="estimates" role="tab" data-toggle="tab"><?php echo _l('settings_sales_heading_estimates'); ?></a>
	</li>
	<li role="presentation">
		<a href="#set_proposals" aria-controls="set_proposals" role="tab" data-toggle="tab"><?php echo _l('proposals'); ?></a>
	</li>

</ul>
<div class="tab-content">
	<div role="tabpanel" class="tab-pane active" id="general">
		<h4 class="bold">
			<?php echo _l('settings_sales_general'); ?>
		</h4>
		<p class="text-muted">
			<p><?php echo _l('settings_sales_general_note'); ?></p>
		</p>
		<hr />
		<div class="row">
			<div class="col-md-6">
			<div class="form-group">
				<label for="decimal_separator"><?php echo _l('settings_sales_decimal_separator'); ?></label>
				<select id="decimal_separator" class="selectpicker" name="settings[decimal_separator]" data-width="100%">
					<option value=","<?php if(get_option('decimal_separator') == ','){echo ' selected';}; ?>>,</option>
					<option value="."<?php if(get_option('decimal_separator') == '.'){echo ' selected';}; ?>>.</option>
				</select>
			</div>
			</div>
			<div class="col-md-6">
			<div class="form-group">
				<label for="thousand_separator"><?php echo _l('settings_sales_thousand_separator'); ?></label>
				<select id="thousand_separator" class="selectpicker" name="settings[thousand_separator]" data-width="100%">
					<option value=","<?php if(get_option('thousand_separator') == ','){echo ' selected';}; ?>>,</option>
					<option value="."<?php if(get_option('thousand_separator') == '.'){echo ' selected';}; ?>>.</option>
				</select>
			</div>
			</div>
		</div>
		<hr class="no-mtop" />
		<i class="fa fa-question-circle" data-toggle="tooltip" data-title="<?php echo _l('invoices').', '. _l('estimates').', '._l('proposals') ?>"></i>
		<?php echo render_input('settings[number_padding_prefixes]','settings_number_padding_prefix',get_option('number_padding_prefixes')); ?>
		<hr />
		<?php render_yes_no_option('show_tax_per_item','settings_show_tax_per_item'); ?>
		<hr />
		<i class="fa fa-question-circle" data-toggle="tooltip" data-title="<?php echo _l('remove_tax_name_from_item_table_help'); ?>"></i>
		<?php render_yes_no_option('remove_tax_name_from_item_table','remove_tax_name_from_item_table'); ?>
		<hr />
		<?php render_yes_no_option('remove_decimals_on_zero','remove_decimals_on_zero'); ?>
		<hr />
		<div class="form-group">
			<label for="currency_placement" class="control-label clearfix"><?php echo _l('settings_sales_currency_placement'); ?></label>
			<div class="radio radio-primary radio-inline">
				<input type="radio" name="settings[currency_placement]" value="before" id="c_placement_before" <?php if(get_option('currency_placement') == 'before'){echo 'checked';} ?>>
				<label for="c_placement_before"><?php echo _l('settings_sales_currency_placement_before'); ?> ( $25.00 ) </label>
			</div>
			<div class="radio radio-primary radio-inline">
				<input type="radio" name="settings[currency_placement]" id="c_placement_after" value="after" <?php if(get_option('currency_placement') == 'after'){echo 'checked';} ?>>
				<label for="c_placement_after"><?php echo _l('settings_sales_currency_placement_after'); ?> ( 25.00$ )</label>
			</div>
		</div>
		<hr />
		<?php
			$default_tax = unserialize(get_option('default_tax'));
		?>
		<div class="form-group">
			<label for="default_tax"><?php echo _l('settings_default_tax'); ?></label>
			<?php echo $this->misc_model->get_taxes_dropdown_template('settings[default_tax][]',$default_tax); ?>
		</div>
		<div class="clearfix"></div>
		<hr />
		<h4 class="bold"><?php echo _l('settings_amount_to_words'); ?></h4>
		<p class="text-muted"><?php echo _l('settings_amount_to_words_desc') .'/'.mb_strtolower(_l('proposal')); ?></p>
		<div class="row">
			<div class="col-md-6">
				<?php render_yes_no_option('total_to_words_enabled','settings_amount_to_words_enabled'); ?>
			</div>
			<div class="col-md-6">
				<?php render_yes_no_option('total_to_words_lowercase','settings_total_to_words_lowercase'); ?>
			</div>
		</div>


	</div>
	<div role="tabpanel" class="tab-pane" id="invoice">
		<div class="form-group">
			<label class="control-label" for="invoice_prefix"><?php echo _l('settings_sales_invoice_prefix'); ?></label>
			<input type="text" name="settings[invoice_prefix]" class="form-control" value="<?php echo get_option('invoice_prefix'); ?>">
		</div>
        <hr />
		<i class="fa fa-question-circle" data-toggle="tooltip" data-title="<?php echo _l('settings_sales_next_invoice_number_tooltip'); ?>"></i>
		<?php echo render_input('settings[next_invoice_number]','settings_sales_next_invoice_number',get_option('next_invoice_number')); ?>
		<hr />

		<i class="fa fa-question-circle" data-toggle="tooltip" data-title="<?php echo _l('invoice_due_after_help'); ?>"></i>
		<?php echo render_input('settings[invoice_due_after]','settings_sales_invoice_due_after',get_option('invoice_due_after')); ?>
		<hr />
		<?php render_yes_no_option('view_invoice_only_logged_in','settings_sales_require_client_logged_in_to_view_invoice'); ?>
		<hr />

		<?php render_yes_no_option('delete_only_on_last_invoice','settings_delete_only_on_last_invoice'); ?>
		<hr />
		<i class="fa fa-question-circle" data-toggle="tooltip" data-title="<?php echo _l('settings_sales_decrement_invoice_number_on_delete_tooltip'); ?>"></i>
		<?php render_yes_no_option('invoice_number_decrement_on_delete','settings_sales_decrement_invoice_number_on_delete'); ?>
		<hr />

		<?php render_yes_no_option('exclude_invoice_from_client_area_with_draft_status','exclude_invoices_draft_from_client_area'); ?>
		<hr />
		<?php render_yes_no_option('show_sale_agent_on_invoices','settings_show_sale_agent_on_invoices'); ?>
		<hr />
		<div class="form-group">
			<label for="invoice_number_format" class="control-label clearfix"><?php echo _l('settings_sales_invoice_number_format'); ?></label>
			<div class="radio radio-primary radio-inline">
				<input type="radio" id="number_based" name="settings[invoice_number_format]" value="1" <?php if(get_option('invoice_number_format') == '1'){echo 'checked';} ?>>
				<label for="number_based"><?php echo _l('settings_sales_invoice_number_format_number_based'); ?></label>
			</div>
			<div class="radio radio-primary radio-inline">
				<input type="radio" name="settings[invoice_number_format]" value="2" id="year_based" <?php if(get_option('invoice_number_format') == '2'){echo 'checked';} ?>>
				<label for="year_based"><?php echo _l('settings_sales_invoice_number_format_year_based'); ?> (YYYY/01)</label>
			</div>
			<div class="radio radio-primary radio-inline">
				<input type="radio" name="settings[invoice_number_format]" value="3" id="month_based" <?php if(get_option('invoice_number_format') == '3'){echo 'checked';} ?>>
				<label for="month_based"><?php echo _l('settings_sales_invoice_number_format_month_based'); ?> (YYYYMMDD/01)</label>
			</div>
			<hr />
		</div>

		<?php echo render_textarea('settings[predefined_clientnote_invoice]','settings_predefined_clientnote',get_option('predefined_clientnote_invoice'),array('rows'=>6)); ?>
		<?php echo render_textarea('settings[predefined_terms_invoice]','settings_predefined_predefined_term',get_option('predefined_terms_invoice'),array('rows'=>6)); ?>

	</div>
	<div role="tabpanel" class="tab-pane" id="estimates">
		<div class="form-group">
			<label class="control-label" for="estimate_prefix"><?php echo _l('settings_sales_estimate_prefix'); ?></label>
			<input type="text" name="settings[estimate_prefix]" class="form-control" value="<?php echo get_option('estimate_prefix'); ?>">
		</div>
		<i class="fa fa-question-circle" data-toggle="tooltip" data-title="<?php echo _l('settings_sales_next_invoice_number_tooltip'); ?>"></i>
		<?php echo render_input('settings[next_estimate_number]','settings_sales_next_estimate_number',get_option('next_estimate_number')); ?>
		<hr />
		<?php render_yes_no_option('delete_only_on_last_estimate','settings_delete_only_on_last_estimate'); ?>
		<hr />
		<i class="fa fa-question-circle" data-toggle="tooltip" data-title="<?php echo _l('settings_sales_decrement_estimate_number_on_delete_tooltip'); ?>"></i>
		<?php render_yes_no_option('estimate_number_decrement_on_delete','settings_sales_decrement_estimate_number_on_delete'); ?>
		<hr />
		<i class="fa fa-question-circle" data-toggle="tooltip" data-title="<?php echo _l('invoice_due_after_help'); ?>"></i>
		<?php echo render_input('settings[estimate_due_after]','estimate_due_after',get_option('estimate_due_after')); ?>
		<hr />
		<?php render_yes_no_option('view_estimate_only_logged_in','settings_sales_require_client_logged_in_to_view_estimate'); ?>
		<hr />
		<?php render_yes_no_option('show_sale_agent_on_estimates','settings_show_sale_agent_on_estimates'); ?>
		<hr />
		<?php render_yes_no_option('estimate_auto_convert_to_invoice_on_client_accept','settings_estimate_auto_convert_to_invoice_on_client_accept'); ?>
		<hr />
		<?php render_yes_no_option('exclude_estimate_from_client_area_with_draft_status','settings_exclude_estimate_from_client_area_with_draft_status'); ?>
		<hr />
		<div class="form-group">
			<label for="estimate_number_format" class="control-label clearfix"><?php echo _l('settings_sales_estimate_number_format'); ?></label>
			<div class="radio radio-primary radio-inline">
				<input type="radio" name="settings[estimate_number_format]" value="1" id="e_number_based" <?php if(get_option('estimate_number_format') == '1'){echo 'checked';} ?>>
				<label for="e_number_based"><?php echo _l('settings_sales_estimate_number_format_number_based'); ?></label>
			</div>
			<div class="radio radio-primary radio-inline">
				<input type="radio" name="settings[estimate_number_format]" value="2" id="e_year_based" <?php if(get_option('estimate_number_format') == '2'){echo 'checked';} ?>>
				<label for="e_year_based"><?php echo _l('settings_sales_estimate_number_format_year_based'); ?> (YYYY/000001)</label>
			</div>
			<hr />
		</div>
		<div class="row">

			<div class="col-md-12">
				<?php echo render_input('settings[estimates_pipeline_limit]','pipeline_limit_status',get_option('estimates_pipeline_limit')); ?>
			</div>
			<div class="col-md-7">
				<label for="default_proposals_pipeline_sort" class="control-label"><?php echo _l('default_pipeline_sort'); ?></label>
				<select name="settings[default_estimates_pipeline_sort]" id="default_estimates_pipeline_sort" class="selectpicker" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
					<option value=""></option>
					<option value="datecreated" <?php if(get_option('default_estimates_pipeline_sort') == 'datecreated'){echo 'selected'; }?>><?php echo _l('estimates_sort_datecreated'); ?></option>
					<option value="date" <?php if(get_option('default_estimates_pipeline_sort') == 'date'){echo 'selected'; }?>><?php echo _l('estimates_sort_estimate_date'); ?></option>
					<option value="pipeline_order" <?php if(get_option('default_estimates_pipeline_sort') == 'pipeline_order'){echo 'selected'; }?>><?php echo _l('estimates_sort_pipeline'); ?></option>
					<option value="expirydate" <?php if(get_option('default_estimates_pipeline_sort') == 'expirydate'){echo 'selected'; }?>><?php echo _l('estimates_sort_expiry_date'); ?></option>
				</select>
			</div>
			<div class="col-md-5">
				<div class="mtop30 text-right">
					<div class="radio radio-inline radio-primary">
						<input type="radio" id="k_desc_estimate" name="settings[default_estimates_pipeline_sort_type]" value="asc" <?php if(get_option('default_estimates_pipeline_sort_type') == 'asc'){echo 'checked';} ?>>
						<label for="k_desc_estimate"><?php echo _l('order_ascending'); ?></label>
					</div>
					<div class="radio radio-inline radio-primary">
						<input type="radio" id="k_asc_estimate" name="settings[default_estimates_pipeline_sort_type]" value="desc" <?php if(get_option('default_estimates_pipeline_sort_type') == 'desc'){echo 'checked';} ?>>
						<label for="k_asc_estimate"><?php echo _l('order_descending'); ?></label>
					</div>
				</div>
			</div>
			<div class="clearfix"></div>
		</div>
		<hr  />
		<?php echo render_textarea('settings[predefined_clientnote_estimate]','settings_predefined_clientnote',get_option('predefined_clientnote_estimate'),array('rows'=>6)); ?>
		<?php echo render_textarea('settings[predefined_terms_estimate]','settings_predefined_predefined_term',get_option('predefined_terms_estimate'),array('rows'=>6)); ?>
	</div>

	<div role="tabpanel" class="tab-pane" id="set_proposals">
		<?php echo render_input('settings[proposal_number_prefix]','proposal_number_prefix',get_option('proposal_number_prefix')); ?>
		<hr />
        <i class="fa fa-question-circle" data-toggle="tooltip" data-title="<?php echo _l('settings_sales_next_invoice_number_tooltip'); ?>"></i>
        <?php echo render_input('settings[next_proposal_number]','settings_sales_next_proposal_number',get_option('next_proposal_number')); ?>
        <hr />

		<i class="fa fa-question-circle" data-toggle="tooltip" data-title="<?php echo _l('invoice_due_after_help'); ?>"></i>
		<?php echo render_input('settings[proposal_due_after]','proposal_due_after',get_option('proposal_due_after'),'number'); ?>
		<hr />
		<div class="row">
			<div class="col-md-12">
				<?php echo render_input('settings[proposals_pipeline_limit]','pipeline_limit_status',get_option('proposals_pipeline_limit')); ?>
				<hr />
			</div>
			<div class="col-md-7">
				<label for="default_proposals_pipeline_sort" class="control-label"><?php echo _l('default_pipeline_sort'); ?></label>
				<select name="settings[default_proposals_pipeline_sort]" id="default_proposals_pipeline_sort" class="selectpicker" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
					<option value=""></option>
					<option value="datecreated" <?php if(get_option('default_proposals_pipeline_sort') == 'datecreated'){echo 'selected'; }?>><?php echo _l('proposals_sort_datecreated'); ?></option>
					<option value="date" <?php if(get_option('default_proposals_pipeline_sort') == 'date'){echo 'selected'; }?>><?php echo _l('proposals_sort_proposal_date'); ?></option>
					<option value="pipeline_order" <?php if(get_option('default_proposals_pipeline_sort') == 'pipeline_order'){echo 'selected'; }?>><?php echo _l('proposals_sort_pipeline'); ?></option>
					<option value="open_till" <?php if(get_option('default_proposals_pipeline_sort') == 'open_till'){echo 'selected'; }?>><?php echo _l('proposals_sort_open_till'); ?></option>
				</select>
			</div>
			<div class="col-md-5">
				<div class="mtop30 text-right">
					<div class="radio radio-inline radio-primary">
						<input type="radio" id="k_desc_proposal" name="settings[default_proposals_pipeline_sort_type]" value="asc" <?php if(get_option('default_proposals_pipeline_sort_type') == 'asc'){echo 'checked';} ?>>
						<label for="k_desc_proposal"><?php echo _l('order_ascending'); ?></label>
					</div>
					<div class="radio radio-inline radio-primary">
						<input type="radio" id="k_asc_proposal" name="settings[default_proposals_pipeline_sort_type]" value="desc" <?php if(get_option('default_proposals_pipeline_sort_type') == 'desc'){echo 'checked';} ?>>
						<label for="k_asc_proposal"><?php echo _l('order_descending'); ?></label>
					</div>
				</div>
			</div>
			<div class="clearfix"></div>
		</div>
		<hr />
		<?php render_yes_no_option('exclude_proposal_from_client_area_with_draft_status','exclude_proposal_from_client_area_with_draft_status'); ?>
		<hr />
		<?php render_yes_no_option('allow_staff_view_proposals_assigned','allow_staff_view_proposals_assigned'); ?>
	</div>
</div>
