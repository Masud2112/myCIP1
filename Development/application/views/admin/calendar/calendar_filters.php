<div id="calendar_filters" style="<?php if(!$this->input->post('calendar_filters')){ echo 'display:none;'; } ?>">
    <?php echo form_open(); ?>
    <?php echo form_hidden('calendar_filters',true); ?>
    <div class="row">
        <div class="col-md-3">
            <!-- <div class="checkbox">
                <input type="checkbox" value="1" name="events" id="cf_events"<?php //if($this->input->post('events')){echo ' checked';} ?>>
                <label for="cf_events"><?php //echo _l('events'); ?></label>
            </div> -->
            <?php if(get_brand_option('show_tasks_on_calendar') == 1){ ?>
            <div class="checkbox">
                <input type="checkbox" value="1" name="tasks" id="cf_tasks"<?php if($this->input->post('tasks')){echo ' checked';} ?>>
                <label for="cf_tasks"><?php echo _l('tasks'); ?></label>
            </div>
            <?php } ?>
            
            <?php if(get_brand_option('show_invoices_on_calendar') == 1){ ?>
            <div class="checkbox">
                <input type="checkbox" value="1" name="invoices" id="cf_invoices"<?php if($this->input->post('invoices')){echo ' checked';} ?>>
                <label for="cf_invoices"><?php echo _l('invoices'); ?></label>
            </div>
            <?php } ?>
            <!-- <?php //if(get_brand_option('show_proposal_reminders_on_calendar') == 1){ ?>
            <div class="checkbox">
                <input type="checkbox" value="1" name="proposal_reminders" id="cf_proposal_reminders"<?php //if($this->input->post('proposal_reminders')){echo ' checked';} ?>>
                <label for="cf_proposal_reminders"><?php //echo _l('show_proposal_reminders_on_calendar'); ?></label>
            </div>
            <?php //} ?> -->
            <!-- <?php //if(get_brand_option('show_estimates_on_calendar') == 1){ ?>
            <div class="checkbox">
                <input type="checkbox" value="1" name="estimates" id="cf_estimates"<?php //if($this->input->post('estimates')){echo ' checked';} ?>>
                <label for="cf_estimates"><?php //echo _l('estimates'); ?></label>
            </div>
            <?php //} ?> -->
        </div>

    <div class="col-md-3">
        <?php if(get_brand_option('show_lead_on_calendar') == 1){ ?>
        <div class="checkbox">
            <input type="checkbox" value="1" name="leads" id="cf_leads"<?php if($this->input->post('leads')){echo ' checked';} ?>>
            <label for="cf_leads"><?php echo _l('show_lead_on_calendar'); ?></label>
        </div>
        <?php } ?>
        
        <?php if(get_brand_option('show_proposals_on_calendar') == 1){ ?>
           <div class="checkbox">
            <input type="checkbox" value="1" name="proposals" id="cf_proposals"<?php if($this->input->post('proposals')){echo ' checked';} ?>>
            <label for="cf_proposals"><?php echo _l('proposals'); ?></label>
        </div>
        <?php } ?>
        <!-- <?php //if(get_brand_option('show_estimate_reminders_on_calendar') == 1){ ?>
        <div class="checkbox">
            <input type="checkbox" value="1" name="estimate_reminders" id="cf_estimates_reminders"<?php //if($this->input->post('estimate_reminders')){echo ' checked';} ?>>
            <label for="cf_estimates_reminders"><?php //echo _l('show_estimate_reminders_on_calendar'); ?></label>
        </div>
        <?php //} ?> -->

    </div>
        <div class="col-md-3">
            <!-- <?php //if(get_brand_option('show_task_reminder_on_calendar') == 1){ ?>
            <div class="checkbox">
                <input type="checkbox" value="1" name="task_reminder" id="cf_task_reminders"<?php //if($this->input->post('task_reminder')){echo ' checked';} ?>>
                <label for="cf_task_reminder"><?php //echo _l('calendar_task_reminder'); ?></label>
            </div>
            <?php //} ?> -->
            <?php if(get_brand_option('show_meetings_on_calendar') == 1){ ?>
                <div class="checkbox">
                    <input type="checkbox" value="1" name="meeting" id="cf_meeting"<?php if($this->input->post('meeting')){echo ' checked';} ?>>
                    <label for="cf_meeting"><?php echo _l('meeting'); ?></label>
                </div>
            <?php } ?>
            <?php if(get_brand_option('show_projects_on_calendar') == 1){ ?>
                <div class="checkbox">
                    <input type="checkbox" value="1" name="projects" id="cf_projects"<?php if($this->input->post('projects')){echo ' checked';} ?>>
                    <label for="cf_projects"><?php echo _l('projects'); ?></label>
                </div>
            <?php } ?>

            <!-- <?php //if(get_brand_option('show_meeting_reminder_on_calendar') == 1){ ?>
                <div class="checkbox">
                    <input type="checkbox" value="1" name="meeting_reminder" id="cf_meeting_reminder"<?php //if($this->input->post('meeting_reminder')){echo ' checked';} ?>>
                    <label for="cf_meeting_reminder"><?php //echo _l('calendar_meeting_reminder'); ?></label>
                </div>
            <?php //} ?> -->
            <!-- <?php //if(get_brand_option('show_invoice_reminders_on_calendar') == 1){ ?>
                <div class="checkbox">
                    <input type="checkbox" value="1" name="invoice_reminder" id="cf_invoice_reminders"<?php //if($this->input->post('invoice_reminder')){echo ' checked';} ?>>
                    <label for="cf_invoice_reminder"><?php //echo _l('show_invoice_reminders_on_calendar'); ?></label>
                </div>
            <?php //} ?>
            -->
            <!-- <?php //if(get_brand_option('show_contracts_on_calendar') == 1){ ?>
        <div class="checkbox">
            <input type="checkbox" value="1" name="contracts" id="cf_contracts"<?php //if($this->input->post('contracts')){echo ' checked';} ?>>
            <label for="cf_contracts"><?php //echo _l('contracts'); ?></label>
        </div>
        <?php //} ?>
        <?php //if(get_brand_option('show_customer_reminders_on_calendar') == 1){ ?>
        <div class="checkbox">
            <input type="checkbox" value="1" name="customer_reminders" id="cf_customers_reminders"<?php //if($this->input->post('customer_reminders')){echo ' checked';} ?>>
            <label for="cf_customers_reminders"><?php //echo _l('show_customer_reminders_on_calendar'); ?></label>
        </div>
        <?php //} ?>

        <?php //if(get_brand_option('show_expense_reminders_on_calendar') == 1){ ?>
        <div class="checkbox">
            <input type="checkbox" value="1" name="expense_reminders" id="cf_expenses_reminders"<?php //if($this->input->post('expense_reminders')){echo ' checked';} ?>>
            <label for="cf_expenses_reminders"><?php //echo _l('calendar_expense_reminder'); ?></label>
        </div>
        <?php //} ?> -->
        </div>
    <div class="col-md-3 text-right">
        <a class="btn btn-default" href="<?php echo site_url($this->uri->uri_string()); ?>"><?php echo _l('clear'); ?></a>
        <button class="btn btn-success" type="submit"><?php echo _l('apply'); ?></button>
    </div>

</div>
<hr class="mbot15" />
<div class="clearfix"></div>
<?php echo form_close(); ?>
</div>
