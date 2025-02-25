<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Tasks_model extends CRM_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('projects_model');
        $this->load->model('staff_model');
    }

    public function get_user_tasks_assigned()
    {
        $this->db->where('id IN (SELECT taskid FROM tblstafftaskassignees WHERE staffid = ' . get_staff_user_id() . ')');
        $this->db->where('status !=', 5);
        $this->db->order_by('duedate', 'asc');

        return $this->db->get('tblstafftasks')->result_array();
    }

    public function get_statuses()
    {
        $statuses = do_action('before_get_task_statuses', array(
            array(
                'id'=>1,
                'color'=>'#989898',
                'name'=>_l('task_status_1'),
                'order'=>1,
                'filter_default'=>true,
                ),
             array(
                'id'=>4,
                'color'=>'#03A9F4',
                'name'=>_l('task_status_4'),
                'order'=>2,
                'filter_default'=>true,
                ),
             array(
                'id'=>3,
                'color'=>'#2d2d2d',
                'name'=>_l('task_status_3'),
                'order'=>3,
                'filter_default'=>true,
                ),
              array(
                'id'=>2,
                'color'=>'#adca65',
                'name'=>_l('task_status_2'),
                'order'=>4,
                'filter_default'=>true,
                ),
            array(
                'id'=>5,
                'color'=>'#84c529',
                'name'=>_l('task_status_5'),
                'order'=>100,
                'filter_default'=>false,
                )
            ));

        usort($statuses, function ($a, $b) {
            return $a['order'] - $b['order'];
        });

        return $statuses;
    }

    /**
     * Get task by id
     * @param  mixed $id task id
     * @return object
     */
    public function get($id, $where = array())
    {
        $is_admin = is_admin();
        $this->db->where('id', $id);
        if(!is_sido_admin()){
            $this->db->where($where);
        }
        $task = $this->db->get('tblstafftasks')->row();
        if ($task) {
            $this->db->select('tbltaskstags.tagid');
            $this->db->where('taskid', $id);
            $tasktags = $this->db->get('tbltaskstags')->result_array();
            $tasktags = array_column($tasktags, 'tagid');

            $task->comments        = $this->get_task_comments($id);
            $task->assignees       = $this->get_task_assignees($id);
            $task->followers       = $this->get_task_followers($id);
            $task->attachments     = $this->get_task_attachments($id);
            $task->timesheets      = $this->get_timesheeets($id);
            $task->checklist_items = $this->get_checklist_items($id);
            $task->tags_id          = $tasktags;
            
            $assignees_data = array();
            foreach ($task->assignees as $v) {
                $assignees_data[] = $v['assigneeid'];
            }
            $task->assigned = $assignees_data;

            $task->current_user_is_assigned = $this->is_task_assignee(get_staff_user_id(),$id);
            $task->current_user_is_creator = $this->is_task_creator(get_staff_user_id(),$id);

            $task->milestone_name = '';

            if ($task->rel_type == 'project') {
                $task->project_data = $this->projects_model->get($task->rel_id);
                if ($task->milestone != 0) {
                    $milestone = $this->get_milestone($task->milestone);
                    if($milestone){
                        $task->milestone_name = $milestone->name;
                    }
                }
            }
        }

        return do_action('get_task', $task);
    }

    public function get_milestone($id)
    {
        $this->db->where('id', $id);

        return $this->db->get('tblmilestones')->row();
    }

    public function do_kanban_query($status, $search = '', $page = 1, $count = false, $where = array())
    {
        $lid = $this->input->get('lid');
        $pid = $this->input->get('pid');
        $eid = $this->input->get('eid');
        if($pid != ""){
            $this->db->select('id');
            $this->db->where('(parent = '.$pid.' OR id = '.$pid.')');
            $this->db->where('deleted', 0);
            $related_project_ids = $this->db->get('tblprojects')->result_array();
        }else{
            $related_project_id = array();
        }
        $brandid = get_user_session();
        $session_data   = get_session_data();
        $is_sido_admin  = $session_data['is_sido_admin'];
        $tasks_where = '';
        if (!has_permission('tasks', '', 'view')) {
            $tasks_where = get_tasks_where_string(false);
        }

        $this->db->select('*');
        $this->db->from('tblstafftasks');
        $this->db->where('status', $status);
        $this->db->where('deleted = 0');
        if($brandid > 0){
            $this->db->where('brandid = '. $brandid);
        }else if($is_sido_admin > 0){
            $this->db->where('brandid = 0');
        } 
        
        if($lid != "") {
            $leadid = $lid;
            $this->db->where('rel_type ="lead"');
            $this->db->where('rel_id = '. $leadid);
        }

        if($pid != "") {
            $related_project_ids = array_column($related_project_ids, 'id');
            if(!empty($related_project_ids)){
                $related_project_ids = implode(",", $related_project_ids);
                $this->db->where('rel_id in(' . $related_project_ids .')');
                $this->db->where('rel_type in("project", "event")');
            }else{
                $this->db->where('rel_id = ' . $pid);
                $this->db->where('rel_type = "project"');
            }
        }

        if($eid != "") {
            $this->db->where('rel_type ="event"');
            $this->db->where('rel_id = '. $eid);
        }

        $this->db->where($where);

        if ($tasks_where != '') {
            $this->db->where($tasks_where);
        }

        if ($search != '') {
            $this->db->where('(tblstafftasks.name LIKE "%' . $search . '%" OR tblstafftasks.description LIKE "%' . $search . '%")');
        }

        $this->db->order_by('kanban_order', 'asc');

        if($count == false){
            if ($page > 1) {
                $page--;
                $position = ($page * get_option('tasks_kanban_limit'));
                $this->db->limit(get_option('tasks_kanban_limit'), $position);
            } else {
                $this->db->limit(get_option('tasks_kanban_limit'));
            }
        }

        if ($count == false) {
            return $this->db->get()->result_array();
        } else {
            return $this->db->count_all_results();
        }
    }

    public function update_order($data)
    {
        foreach ($data['order'] as $order) {
            $this->db->where('id', $order[0]);
            $this->db->update('tblstafftasks', array(
                'kanban_order' => $order[1]
            ));
        }
    }

    public function get_distinct_tasks_years($get_from)
    {
        return $this->db->query('SELECT DISTINCT(YEAR(' . $get_from . ')) as year FROM tblstafftasks WHERE ' . $get_from . ' IS NOT NULL ORDER BY year DESC')->result_array();
    }

    public function is_task_billed($id)
    {
        return (total_rows('tblstafftasks', array(
            'id' => $id,
            'billed' => 1
        )) > 0 ? true : false);
    }

    public function copy($data, $overwrites = array())
    {
        $task           = $this->get($data['copy_from']);
        $fields_tasks   = $this->db->list_fields('tblstafftasks');
        $_new_task_data = array();
        foreach ($fields_tasks as $field) {
            if (isset($task->$field)) {
                $_new_task_data[$field] = $task->$field;
            }
        }

        $tags = array();
        $this->db->select('tagid');
        $this->db->where('taskid', $_new_task_data['id']);
        $_task_tags = $this->db->get('tbltaskstags')->result_array();
        $_task_id = $_new_task_data['id'];
        unset($_new_task_data['id']);
        if(isset($data['copy_task_status']) && is_numeric($data['copy_task_status'])) {
            $_new_task_data['status']            = $data['copy_task_status'];
        } else {
            /**
            * Added By : Vaidehi
            * Dt : 11/21/2017
            * to add default status as "pending" when no status is selected
            */
            $this->db->where('name', 'Pending');
            $this->db->where('brandid', get_user_session());
            $this->db->where('deleted', 0);
            $task = $this->db->get('tbltasksstatus')->row();

            // fallback in case no status is provided
            $_new_task_data['status']            = $task->id;
        }

        $_new_task_data['dateadded']         = date('Y-m-d H:i:s');
        $_new_task_data['startdate']         = date('Y-m-d');
        $_new_task_data['deadline_notified'] = 0;
        $_new_task_data['billed']            = 0;
        $_new_task_data['invoice_id']        = 0;

        if (!empty($task->duedate) && $task->startdate != "0000-00-00" && $task->startdate != "") {
            $dStart                    = new DateTime($task->startdate);
            $dEnd                      = new DateTime($task->duedate);
            $dDiff                     = $dStart->diff($dEnd);
            
            $_new_task_data['duedate'] = date('Y-m-d', strtotime(date('Y-m-d', strtotime('+' . $dDiff->days . 'DAY'))));
        }
        // Overwrite rel id and rel type - possible option to pass when copying project tasks in projects_model
        if (count($overwrites) > 0) {
            foreach ($overwrites as $key => $val) {
                $_new_task_data[$key] = $val;
            }
        }
        unset($_new_task_data['datefinished']);
        $this->db->insert('tblstafftasks', $_new_task_data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {

            if(!empty($_task_tags)){
                foreach ($_task_tags as $t) {
                    $this->db->insert('tbltaskstags', array(
                        'taskid' => $insert_id,
                        'tagid' => $t['tagid']
                    ));
                }
            }
            //$tags = get_tags_in($data['copy_from'], 'task');
            handle_tags_save($tags, $insert_id, 'task');
            if (isset($data['copy_task_assignees']) && $data['copy_task_assignees'] == 'true') {
                $this->copy_task_assignees($data['copy_from'], $insert_id);
            }
            if (isset($data['copy_task_followers']) && $data['copy_task_followers'] == 'true') {
                $this->copy_task_followers($data['copy_from'], $insert_id);
            }
            if (isset($data['copy_task_checklist_items']) && $data['copy_task_checklist_items'] == 'true') {
                $this->copy_task_checklist_items($data['copy_from'], $insert_id);
            }
            if (isset($data['copy_task_attachments']) && $data['copy_task_attachments'] == 'true') {
                $attachments = $this->get_task_attachments($data['copy_from']);
                if (is_dir(get_upload_path_by_type('task') . $data['copy_from'])) {
                    xcopy(get_upload_path_by_type('task') . $data['copy_from'], get_upload_path_by_type('task') . $insert_id);
                }
                foreach ($attachments as $at) {
                    $_at      = array();
                    $_at[]    = $at;
                    $external = false;
                    if (!empty($at['external'])) {
                        $external       = $at['external'];
                        $_at[0]['name'] = $at['file_name'];
                        $_at[0]['link'] = $at['external_link'];
                        if (!empty($at['thumbnail_link'])) {
                            $_at[0]['thumbnailLink'] = $at['thumbnail_link'];
                        }
                    }
                    $this->add_attachment_to_database($insert_id, $_at, $external, false);
                }
            }
            $this->copy_task_custom_fields($data['copy_from'], $insert_id);

            return $insert_id;
        }

        return false;
    }

    public function copy_task_followers($from_task, $to_task)
    {
        $followers = $this->tasks_model->get_task_followers($from_task);
        foreach ($followers as $follower) {
            $this->db->insert('tblstafftasksfollowers', array(
                'taskid' => $to_task,
                'staffid' => $follower['followerid']
            ));
        }
    }

    public function copy_task_assignees($from_task, $to_task)
    {
        $assignees = $this->tasks_model->get_task_assignees($from_task);
        foreach ($assignees as $assignee) {
            $this->db->insert('tblstafftaskassignees', array(
                'taskid' => $to_task,
                'staffid' => $assignee['assigneeid'],
                'assigned_from' => get_staff_user_id()
            ));
        }
    }

    public function copy_task_checklist_items($from_task, $to_task)
    {
        $checklists = $this->tasks_model->get_checklist_items($from_task);
        foreach ($checklists as $list) {
            $this->db->insert('tbltaskchecklists', array(
                'taskid' => $to_task,
                'finished' => 0,
                'description' => $list['description'],
                'dateadded' => date('Y-m-d H:i:s'),
                'addedfrom' => $list['addedfrom'],
                'list_order' => $list['list_order']
            ));
        }
    }

    public function copy_task_custom_fields($from_task, $to_task)
    {
        $custom_fields = get_custom_fields('tasks');
        foreach ($custom_fields as $field) {
            $value = get_custom_field_value($from_task, $field['id'], 'tasks');
            if ($value != '') {
                $this->db->insert('tblcustomfieldsvalues', array(
                    'relid' => $to_task,
                    'fieldid' => $field['id'],
                    'fieldto' => 'tasks',
                    'value' => $value
                ));
            }
        }
    }

    public function get_billable_tasks($customer_id = false)
    {
        $has_permission_view = has_permission('tasks', '', 'view');

        $this->db->where('billable', 1);
        $this->db->where('billed', 0);

        $this->db->where('rel_type != "project"');

        if ($customer_id != false) {
            $this->db->where('
                (
                (rel_id IN (SELECT id FROM tblinvoices WHERE clientid=' . $customer_id . ') AND rel_type="invoice")
                OR
                (rel_id IN (SELECT id FROM tblestimates WHERE clientid=' . $customer_id . ') AND rel_type="estimate")
                OR
                (rel_id IN (SELECT id FROM tblcontracts WHERE client=' . $customer_id . ') AND rel_type="contract")
                OR
                ( rel_id IN (SELECT ticketid FROM tbltickets WHERE userid=' . $customer_id . ') AND rel_type="ticket")
                OR
                (rel_id IN (SELECT id FROM tblexpenses WHERE clientid=' . $customer_id . ') AND rel_type="expense")
                OR
                (rel_id IN (SELECT id FROM tblproposals WHERE rel_id=' . $customer_id . ' AND rel_type="customer") AND rel_type="proposal")
                OR
                (rel_id IN (SELECT userid FROM tblclients WHERE userid=' . $customer_id . ') AND rel_type="customer")
                )'
                );
        }

        if (!$has_permission_view) {
            $this->db->where(get_tasks_where_string(false));
        }

        $tasks = $this->db->get('tblstafftasks')->result_array();
        $i     = 0;
        foreach ($tasks as $task) {
            $task_rel_data         = get_relation_data($task['rel_type'], $task['rel_id']);
            $task_rel_value        = get_relation_values($task_rel_data, $task['rel_type']);
            $tasks[$i]['rel_name'] = $task_rel_value['name'];
            if (total_rows('tbltaskstimers', array(
                'task_id' => $task['id'],
                'end_time' => null
            )) > 0) {
                $tasks[$i]['started_timers'] = true;
            } else {
                $tasks[$i]['started_timers'] = false;
            }
            $i++;
        }

        return $tasks;
    }

    public function get_billable_task_data($task_id)
    {
        $this->db->where('id', $task_id);
        $data                = $this->db->get('tblstafftasks')->row();
        $total_seconds       = $this->calc_task_total_time($task_id);
        $data->total_hours   = sec2qty($total_seconds);
        $data->total_seconds = $total_seconds;

        return $data;
    }

    public function get_tasks_by_staff_id($id, $where = array())
    {
        $this->db->where($where);
        $this->db->where('(id IN (SELECT taskid FROM tblstafftaskassignees WHERE staffid=' . $id . '))');

        return $this->db->get('tblstafftasks')->result_array();
    }

    /**
     * Add new staff task
     * @param array $data task $_POST data
     * @return mixed
     */
    public function add($data, $clientRequest = false)
    {
        if(isset($data['rel_type']) && $data['rel_type'] != ""){
            $data['rel_type'] = $data['rel_type'];
            $data['rel_id'] = $data[$data['rel_type']];
        }else{
            $data['rel_type'] = "";
            $data['rel_id'] = "";
        }
        unset($data['lead']);
        unset($data['project']);
        unset($data['event']);
        /**
        * Added By : Avni
        * Dt : 12/04/2017
        * for mulitple task reminders
        */

        $newtaskreminders = array_filter($data['reminder'], function ($var) {
            return ($var['duration'] != '');
        });
        
        $newtaskreminders = (count($newtaskreminders) > 0 ? $newtaskreminders : '');


        unset($data['reminder']);

        $data['startdate'] = ((isset($data['startdate']) && !empty($data['startdate'])) ? date("Y-m-d",strtotime(str_replace('/', '-', $data['startdate']))) : "");
       
        // $duedate = str_replace('/', '-', $data['duedate']);
        // if(preg_match("/^(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])-[0-9]{4}$/", $duedate)){
        //     $data['duedate']   = ((isset($data['duedate']) && !empty($data['duedate'])) ? date("Y-m-d",strtotime($data['duedate'])) : "");
        // } else {
        //     $data['duedate']   = ((isset($data['duedate']) && !empty($data['duedate'])) ? date("Y-m-d",strtotime(str_replace('/', '-', $data['duedate']))) : "");
        // }
        

        /*
        ** Modified By Sanjay on 02/09/2018 
        ** Save date in Y-m-d
        */
        // if(isset($data['duedate']) || !empty($data['duedate'])) {
        //     $data['duedate'] = date('Y-m-d', strtotime($data['duedate']));
        // }

        $duedate = str_replace('/', '-', $data['duedate']);
        if(preg_match("/^(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])-[0-9]{4}$/", $duedate)){
            $data['duedate']   = ((isset($data['duedate']) && !empty($data['duedate'])) ? date("Y-m-d H:i:s",strtotime($data['duedate'])) : "");
        } else {
            if (get_brand_option('dateformat') == 'd/m/Y|%d/%m/%Y') { 
                $data['duedate']   = ((isset($data['duedate']) && !empty($data['duedate'])) ? date("Y-m-d H:i:s",strtotime(str_replace('/', '-', $data['duedate']))) : "");
            } else { 
                $data['duedate']   = ((isset($data['duedate']) && !empty($data['duedate'])) ? date("Y-m-d H:i:s",strtotime($data['duedate'])) : "");
            }
        }

        $data['dateadded'] = date('Y-m-d H:i:s');
        $data['created_by']  = $this->session->userdata['staff_user_id'];
        $data['addedfrom'] = $clientRequest == false ? get_staff_user_id() : get_contact_user_id();
        $data['is_added_from_contact'] = $clientRequest == false ? 0 : 1;
        $brandid = get_user_session();
        $tags = array();
        
        $data['brandid'] = $brandid;
        $checklistItems = array();
        if(isset($data['checklist_items']) && count($data['checklist_items']) > 0){
            $checklistItems = $data['checklist_items'];
            unset($data['checklist_items']);
        }

        // if (date('Y-m-d') >= $data['startdate']) {
        //     $data['status'] = 4;
        // } else {
        //     $data['status'] = 1;
        // }
        // $data['status'] = 1;
        // // When client create task the default status is NOT STARTED
        // // After staff will get the task will change the status
        // if($clientRequest == true){
        //     $data['status'] = 1;
        // }

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            unset($data['custom_fields']);
        }
        if (isset($data['is_public'])) {
            $data['is_public'] = 1;
        } else {
            $data['is_public'] = 0;
        }

        if (isset($data['recurring_ends_on']) && $data['recurring_ends_on'] == '') {
            unset($data['recurring_ends_on']);
        } elseif (isset($data['recurring_ends_on']) && $data['recurring_ends_on'] != '') {
            $data['recurring_ends_on'] = to_sql_date($data['recurring_ends_on']);
        }

        if (isset($data['repeat_every']) && $data['repeat_every'] != '') {
            $data['recurring'] = 1;
            if ($data['repeat_every'] == 'custom') {
                $data['repeat_every']     = $data['repeat_every_custom'];
                $data['recurring_type']   = $data['repeat_type_custom'];
                $data['custom_recurring'] = 1;
            } else {
                $_temp                    = explode('-', $data['repeat_every']);
                $data['recurring_type']   = $_temp[1];
                $data['repeat_every']     = $_temp[0];
                $data['custom_recurring'] = 0;
            }
        } else {
            $data['recurring'] = 0;
        }

        if(isset($data['repeat_type_custom']) && isset($data['repeat_every_custom'])){
                unset($data['repeat_type_custom']);
                unset($data['repeat_every_custom']);
        }

        if (is_client_logged_in() || $clientRequest) {
            $data['visible_to_client'] = 1;
        } else {
            if (isset($data['visible_to_client'])) {
                $data['visible_to_client'] = 1;
            } else {
                $data['visible_to_client'] = 0;
            }
        }

        if (isset($data['billable'])) {
            $data['billable'] = 1;
        } else {
            $data['billable'] = 0;
        }

        if ((!isset($data['milestone']) || $data['milestone'] == '') || (isset($data['milestone']) && $data['milestone'] == '')) {
            $data['milestone'] = 0;
        } else {
            if ($data['rel_type'] != 'project') {
                $data['milestone'] = 0;
            }
        }
        if (empty($data['rel_type'])) {
            unset($data['rel_type']);
            unset($data['rel_id']);
        } else {
            if (empty($data['rel_id'])) {
                unset($data['rel_type']);
                unset($data['rel_id']);
            }
        }

        $data = do_action('before_add_task', $data);

        if (isset($data['tags'])) {
            $tags = $data['tags'];
            unset($data['tags']);
        }

        $assigned = array();
        if (isset($data['assigned'])) {
            $assigned  = $data['assigned'];
            unset($data['assigned']);
        }

        unset($data['hdnlid']);
        unset($data['hdnpid']);
        unset($data['hdneid']);
       
        $this->db->insert('tblstafftasks', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            if(!empty($tags)){
                foreach ($tags as $t) {
                    $this->db->insert('tbltaskstags', array(
                        'taskid' => $insert_id,
                        'tagid' => $t
                    ));
                }
            }

            if(!empty($assigned)){
                foreach ($assigned as $a) {
                    $this->db->insert('tblstafftaskassignees', array(
                        'taskid' => $insert_id,
                        'staffid' => $a,
                        'assigned_from' => get_staff_user_id()
                    ));
                    $this->task_new_created_notification($insert_id,$a);
                }
            }
            /**
            * Added By : Avni
            * Dt : 12/04/2017
            * for mulitple task reminders
            */
            if(isset($newtaskreminders) && !empty($newtaskreminders)){
                foreach ($newtaskreminders as $newtaskreminder) {
                    $newtaskreminder['taskid'] = $insert_id;
                    $this->db->insert('tbltaskreminders', $newtaskreminder);
                }
            }            
            foreach($checklistItems as $key => $chkID){
                $itemTemplate = $this->get_checklist_template($chkID);
                $this->db->insert('tbltaskchecklists',array(
                    'description'=>$itemTemplate->description,
                    'taskid'=>$insert_id,
                    'dateadded'=>date('Y-m-d H:i:s'),
                    'addedfrom'=>get_staff_user_id(),
                    'list_order'=>$key
                    ));
            }
           // handle_tags_save($tags, $insert_id, 'task');
            if (isset($custom_fields)) {
                handle_custom_fields_post($insert_id, $custom_fields);
            }

            if(isset($data['rel_type']) && $data['rel_type'] == 'lead'){
                $this->load->model('leads_model');
                $this->leads_model->log_lead_activity($data['rel_id'], 'not_activity_new_task_created',false,serialize(array(
                    '<a href="'.admin_url('tasks/dashboard/'.$insert_id).'" target="_blank">'.$data['name'].'</a>'
                    )));
            }

            if(isset($data['rel_type']) && ($data['rel_type'] == 'project' || $data['rel_type'] == 'event')){
                $this->load->model('projects_model');
                $this->projects_model->log_activity($data['rel_id'], 'not_activity_new_task_created',false,serialize(array(
                    '<a href="'.admin_url('tasks/dashboard/'.$insert_id).'" target="_blank">'.$data['name'].'</a>'
                    )));
            }

            
            // if($clientRequest == false){
            //     $new_task_auto_assign_creator = (get_option('new_task_auto_assign_current_member') == 1 ? true : false);

            //     if(isset($data['rel_type']) && $data['rel_type'] == 'project' && !$this->projects_model->is_member($data['rel_id'])){
            //         $new_task_auto_assign_creator = false;
            //     }
            //     if($new_task_auto_assign_creator == true){
            //         $this->add_task_assignees(array('taskid'=>$insert_id,'assignee'=>get_staff_user_id()));
            //     }
            // }

            // $this->add_task_assignees(array('taskid'=>$insert_id,'assignee'=>get_staff_user_id()));

            logActivity('New Task Added [ID:' . $insert_id . ', Name: ' . $data['name'] . ']');
            do_action('after_add_task', $insert_id);
            return $insert_id;
        }

        return false;
    }

    /**
     * Added By : Masud
     * Dt : 27/05/2018
     * to save extra form fields in db
     */

    public function task_new_created_notification($task_id, $assigned, $integration = false)
    {
        $name = $this->db->select('name')->from('tblstafftasks')->where('id', $task_id)->get()->row()->name;
        if ($assigned == "") {
            $assigned = 0;
        }

        $notification_data = array(
            'description' => ($integration == false) ? 'not_new_task_created' : 'not_task_assigned_from_form',
            'touserid' => $assigned,
            'eid' => $task_id,
            'brandid' => get_user_session(),
            'not_type' => 'tasks',
            'link' => 'tasks/dashboard/' . $task_id,
            'additional_data' => ($integration == false ? serialize(array(
                $name
            )) : serialize(array()))
        );

        if ($integration != false) {
            $notification_data['fromcompany'] = 1;
        }

        if (add_notification($notification_data)) {
            pusher_trigger_notification(array($assigned));
        }
    }
    /**
     * Update task data
     * @param  array $data task data $_POST
     * @param  mixed $id   task id
     * @return boolean
     */
    public function update($data, $id, $clientRequest = false)
    {     
        if(isset($data['rel_type']) && $data['rel_type'] != ""){
            $data['rel_type'] = $data['rel_type'];
            $data['rel_id'] = $data[$data['rel_type']];
        }else{
            $data['rel_type'] = "";
            $data['rel_id'] = "";
        }
        unset($data['lead']);
        unset($data['project']);
        unset($data['event']);   
        $affectedRows      = 0;

        /**
        * Added By : Avni
        * Dt : 12/04/2017
        * for mulitple task reminders
        */
        $newtaskreminders = array_filter($data['reminder'], function ($var) {
            return ($var['duration'] != '');
        });
            
        $newtaskreminders = (count($newtaskreminders) > 0 ? $newtaskreminders : '');
        unset($data['reminder']);

        //$data['startdate'] = ((isset($data['startdate']) && !empty($data['startdate'])) ? date("Y-m-d",strtotime(str_replace('/', '-', $data['startdate']))) : "");

        if(isset($data['startdate'])) {
            $startdate = str_replace('/', '-', $data['startdate']);
            if(preg_match("/^(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])-[0-9]{4}$/", $startdate)){
                $data['startdate']   = ((isset($data['duedate']) && !empty($data['startdate'])) ? date("Y-m-d",strtotime($data['startdate'])) : "");
            } else {
                if (get_brand_option('dateformat') == 'd/m/Y|%d/%m/%Y') { 
                    $data['startdate']   = ((isset($data['startdate']) && !empty($data['startdate'])) ? date("Y-m-d",strtotime(str_replace('/', '-', $data['startdate']))) : "");
                } else {
                    $data['startdate']   = ((isset($data['startdate']) && !empty($data['duedate'])) ? date("Y-m-d",strtotime($data['startdate'])) : "");
                } 
            }
        }
        
        $duedate = str_replace('/', '-', $data['duedate']);
        if(preg_match("/^(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])-[0-9]{4}$/", $duedate)){
            $data['duedate']   = ((isset($data['duedate']) && !empty($data['duedate'])) ? date("Y-m-d H:i",strtotime($data['duedate'])) : "");
        } else {
            if (get_brand_option('dateformat') == 'd/m/Y|%d/%m/%Y') { 
                $data['duedate']   = ((isset($data['duedate']) && !empty($data['duedate'])) ? date("Y-m-d H:i",strtotime(str_replace('/', '-', $data['duedate']))) : "");
            } else { 
                $data['duedate']   = ((isset($data['duedate']) && !empty($data['duedate'])) ? date("Y-m-d H:i",strtotime($data['duedate'])) : "");
            } 
        }
        
        /*
        ** Modified By Sanjay on 02/09/2018 
        ** Save date in Y-m-d
        */
        // if(isset($data['duedate']) || !empty($data['duedate'])) {
        //     $data['duedate'] = date('Y-m-d', strtotime($data['duedate']));
        // }

        $data['updated_by']     = $this->session->userdata['staff_user_id'];
        $data['dateupdated']    = date('Y-m-d H:i:s');
            
        $checklistItems = array();
        if(isset($data['checklist_items']) && count($data['checklist_items']) > 0){
            $checklistItems = $data['checklist_items'];
            unset($data['checklist_items']);
        }

        if (isset($data['datefinished'])) {
            $data['datefinished'] = to_sql_date($data['datefinished'], true);
        }

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            if (handle_custom_fields_post($id, $custom_fields)) {
                $affectedRows++;
            }
            unset($data['custom_fields']);
        }

        if($clientRequest == false){

            // if ($data['repeat_every'] != '') {
            //     $data['recurring'] = 1;
            //     if ($data['repeat_every'] == 'custom') {
            //         $data['repeat_every']     = $data['repeat_every_custom'];
            //         $data['recurring_type']   = $data['repeat_type_custom'];
            //         $data['custom_recurring'] = 1;
            //     } else {
            //         $_temp                    = explode('-', $data['repeat_every']);
            //         $data['recurring_type']   = $_temp[1];
            //         $data['repeat_every']     = $_temp[0];
            //         $data['custom_recurring'] = 0;
            //     }
            // } else {
            //     $data['recurring'] = 0;
            // }

            // if ($data['recurring_ends_on'] == '' || $data['recurring'] == 0) {
            //     $data['recurring_ends_on'] = null;
            // } else {
            //     $data['recurring_ends_on'] = to_sql_date($data['recurring_ends_on']);
            // }

            if(isset($data['repeat_type_custom']) && isset($data['repeat_every_custom'])){
                unset($data['repeat_type_custom']);
                unset($data['repeat_every_custom']);
            }

            if (isset($data['is_public'])) {
                $data['is_public'] = 1;
            } else {
                $data['is_public'] = 0;
            }
            // if (isset($data['billable'])) {
            //     $data['billable'] = 1;
            // } else {
            //     $data['billable'] = 0;
            // }

            // if (isset($data['visible_to_client'])) {
            //     $data['visible_to_client'] = 1;
            // } else {
            //     $data['visible_to_client'] = 0;
            // }

        }

        // if ((!isset($data['milestone']) || $data['milestone'] == '') || (isset($data['milestone']) && $data['milestone'] == '')) {
        //     $data['milestone'] = 0;
        // } else {
        //     if ($data['rel_type'] != 'project') {
        //         $data['milestone'] = 0;
        //     }
        // }


        if (empty($data['rel_type'])) {
            $data['rel_id']   = null;
            $data['rel_type'] = null;
        } else {
            if (empty($data['rel_id'])) {
                $data['rel_id']   = null;
                $data['rel_type'] = null;
            }
        }
        $tags = array();
        if (isset($data['tags'])) {
            $tags = $data['tags'];
            unset($data['tags']);
        }

        $assigned = array();
        if (isset($data['assigned'])) {
            $assigned = $data['assigned'];
            unset($data['assigned']);
        }

        $_data['data'] = $data;
        $_data['id']   = $id;

        $_data = do_action('before_update_task', $_data);

        $data = $_data['data'];
        //echo "<pre>";print_r($data);die();
        unset($data['hdnlid']);
        unset($data['hdnpid']);
        unset($data['hdneid']);
        foreach($checklistItems as $key => $chkID){
            $itemTemplate = $this->get_checklist_template($chkID);
            $this->db->insert('tbltaskchecklists',array(
                'description'=>$itemTemplate->description,
                'taskid'=>$id,
                'dateadded'=>date('Y-m-d H:i:s'),
                'addedfrom'=>get_staff_user_id(),
                'list_order'=>$key
                ));
            $affectedRows++;
        }

        $this->db->where('taskid',$id);
        $this->db->delete('tbltaskstags');
            
        $this->db->where('taskid',$id);
        $this->db->delete('tblstafftaskassignees');
        
        $this->db->where('taskid',$id);
        $this->db->delete('tbltaskstags');

        $this->db->where('id', $id);
        $this->db->update('tblstafftasks', $data);
        if ($this->db->affected_rows() > 0) {
            if(!empty($tags)){
                foreach ($tags as $t) {
                    $this->db->insert('tbltaskstags', array(
                        'taskid' => $id,
                        'tagid' => $t
                    ));
                }
            }

            if(!empty($assigned)){
                foreach ($assigned as $a) {
                    $this->db->insert('tblstafftaskassignees', array(
                        'taskid' => $id,
                        'staffid' => $a,
                        'assigned_from' => get_staff_user_id()
                    ));
                }
            }
            $affectedRows++;
            do_action('after_update_task', $id);
            logActivity('Task Updated [ID:' . $id . ', Name: ' . $data['name'] . ']');
        }

        /**
            * Added By : Avni
            * Dt : 12/04/2017
            * for mulitple task reminders
            */
            if(count($newtaskreminders) > 0){
                $this->db->where('taskid', $id);
                $this->db->delete('tbltaskreminders');

                foreach ($newtaskreminders as $newtaskreminder) {
                    $newtaskreminder['taskid'] = $id;
                    $this->db->insert('tbltaskreminders', $newtaskreminder);
                }
            }

        if ($affectedRows > 0) {
            return true;
        }
        return false;
    }

    public function get_checklist_item($id)
    {
        $this->db->where('id', $id);

        return $this->db->get('tbltaskchecklists')->row();
    }

    public function get_checklist_items($taskid,$finished="")
    {
        $this->db->where('taskid', $taskid);
        if($finished ==1){
            $this->db->where('finished', $finished);
        }
        $this->db->order_by('list_order', 'asc');

        return $this->db->get('tbltaskchecklists')->result_array();
    }

    public function add_checklist_template($description){
        $this->db->insert('tblcheckliststemplates',array(
            'description'=>$description
            ));
        return $this->db->insert_id();
    }
    public function remove_checklist_item_template($id){
        $this->db->where('id',$id);
        $this->db->delete('tblcheckliststemplates');
        if($this->db->affected_rows() > 0){
            return true;
        }

        return false;
    }

    public function get_checklist_templates(){
        $this->db->order_by('description','asc');
        return $this->db->get('tblcheckliststemplates')->result_array();
    }

    public function get_checklist_template($id){
        $this->db->where('id',$id);
        return $this->db->get('tblcheckliststemplates')->row();
    }

    /**
     * Add task new blank check list item
     * @param mixed $data $_POST data with taxid
     */
    public function add_checklist_item($data)
    {

        $this->db->insert('tbltaskchecklists', array(
            'taskid' => $data['taskid'],
            'description' => $data['description'],
            'dateadded' => date('Y-m-d H:i:s'),
            'addedfrom' => get_staff_user_id(),
            'list_order'=>0
        ));
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            return true;
        }

        return false;
    }

    public function delete_checklist_item($id)
    {
        $this->db->where('id', $id);
        $this->db->delete('tbltaskchecklists');
        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    public function update_checklist_order($data)
    {
        foreach ($data['order'] as $order) {
            $this->db->where('id', $order[0]);
            $this->db->update('tbltaskchecklists', array(
                'list_order' => $order[1]
            ));
        }
    }

    /**
     * Update checklist item
     * @param  mixed $id          check list id
     * @param  mixed $description checklist description
     * @return void
     */
    public function update_checklist_item($id, $description)
    {
        $description = strip_tags($description,'<br>,<br/>');
        if($description === ''){
            $this->db->where('id',$id);
            $this->db->delete('tbltaskchecklists');
        } else {
            $this->db->where('id', $id);
            $this->db->update('tbltaskchecklists', array(
                'description' => nl2br($description)
            ));
        }
    }

    /**
     * Make task public
     * @param  mixed $task_id task id
     * @return boolean
     */
    public function make_public($task_id)
    {
        $this->db->where('id', $task_id);
        $this->db->update('tblstafftasks', array(
            'is_public' => 1
        ));
        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    /**
     * Get task creator id
     * @param  mixed $taskid task id
     * @return mixed
     */
    public function get_task_creator_id($taskid)
    {
        return $this->get($taskid)->addedfrom;
    }

    /**
     * Add new task comment
     * @param array $data comment $_POST data
     * @return boolean
     */
    public function add_task_comment($data)
    {
        if ($data['content'] == '') {
            return false;
        }

        if (is_client_logged_in()) {
            $data['staffid']    = 0;
            $data['contact_id'] = get_contact_user_id();
        } else {
            $data['staffid']    = get_staff_user_id();
            $data['contact_id'] = 0;
        }

        if (isset($data['action'])) {
            unset($data['action']);
        }

        $data['dateadded'] = date('Y-m-d H:i:s');
        $data['content']   = $data['content'];
        if (is_client_logged_in()) {
            $data['content'] = _strip_tags($data['content']);
        }
        $this->db->insert('tblstafftaskcomments', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            $task            = $this->get($data['taskid']);
            $description     = 'not_task_new_comment';
            $additional_data = serialize(array(
                $task->name
            ));
            if ($task->rel_type == 'project') {
                $this->projects_model->log_activity($task->rel_id, 'project_activity_new_task_comment', $task->name, $task->visible_to_client);
            }
            $this->_send_task_responsible_users_notification($description, $data['taskid'], false, 'task-commented', $additional_data);
            $this->_send_customer_contacts_notification($data['taskid'], 'task-commented-to-contacts');

            do_action('task_comment_added',array('task_id'=>$task->id,'comment_id'=>$insert_id));
            return $insert_id;
        }

        return false;
    }

    /**
     * Add task followers
     * @param array $data followers $_POST data
     * @return boolean
     */
    public function add_task_followers($data)
    {
        $this->db->insert('tblstafftasksfollowers', array(
            'taskid' => $data['taskid'],
            'staffid' => $data['follower']
        ));
        if ($this->db->affected_rows() > 0) {
            $task = $this->get($data['taskid']);
            if (get_staff_user_id() != $data['follower']) {
                $notified = add_notification(array(
                    'description' => 'not_task_added_you_as_follower',
                    'touserid' => $data['follower'],
                    'link' => admin_url('tasks/dashboard/' . $task->id),//'#taskid=' . $task->id,
                    'additional_data' => serialize(array(
                        $task->name
                    ))
                ));
                if($notified){
                    pusher_trigger_notification(array($data['follower']));
                }
                $member = $this->staff_model->get($data['follower']);

                $merge_fields = array();
                $merge_fields = array_merge($merge_fields, get_staff_merge_fields($data['follower']));
                $merge_fields = array_merge($merge_fields, get_task_merge_fields($task->id));
                $this->load->model('emails_model');
                $this->emails_model->send_email_template('task-added-as-follower', $member->email, $merge_fields);
            }
            $description                  = 'not_task_added_someone_as_follower';
            $additional_notification_data = serialize(array(
                get_staff_full_name($data['follower']),
                $task->name
            ));
            if ($data['follower'] == get_staff_user_id()) {
                $additional_notification_data = serialize(array(
                    $task->name
                ));
                $description                  = 'not_task_added_himself_as_follower';
            }
            $this->_send_task_responsible_users_notification($description, $data['taskid'], $data['follower'], '', $additional_notification_data);

            return true;
        }

        return false;
    }

    /**
     * Assign task to staff
     * @param array $data task assignee $_POST data
     * @return boolean
     */
    public function add_task_assignees($data, $cronOrIntegration = false, $clientRequest = false)
    {
        $assignData = array(
            'taskid' => $data['taskid'],
            'staffid' => $data['assignee'],
        );
        if($cronOrIntegration){
            $assignData['assigned_from'] = $data['assignee'];
        } else if($clientRequest){
            $assignData['is_assigned_from_contact'] = 1;
            $assignData['assigned_from'] = get_contact_user_id();
        } else {
            $assignData['assigned_from'] = get_staff_user_id();
        }
        $this->db->insert('tblstafftaskassignees', $assignData);

        $assigneeId = $this->db->insert_id();

        if ($assigneeId) {
            $task = $this->get($data['taskid']);
            if (get_staff_user_id() != $data['assignee'] || $clientRequest) {
                $notification_data = array(
                    'description' => ($cronOrIntegration == false ? 'not_task_assigned_to_you' : 'new_task_assigned_non_user'),
                    'touserid' => $data['assignee'],
                    'link' => '#taskid=' . $task->id
                );

                $this->db->select('name');
                $this->db->where('id', $data['taskid']);
                $task_name                            = $this->db->get('tblstafftasks')->row()->name;
                $notification_data['additional_data'] = serialize(array(
                    $task_name
                ));

                if ($cronOrIntegration) {
                    $notification_data['fromcompany'] = 1;
                }

                if($clientRequest){
                    $notification_data['fromclientid'] = get_contact_user_id();
                }

                if(add_notification($notification_data)){
                    pusher_trigger_notification(array($data['assignee']));
                }
                $member = $this->staff_model->get($data['assignee']);

                $merge_fields = array();
                $merge_fields = array_merge($merge_fields, get_staff_merge_fields($data['assignee']));
                $merge_fields = array_merge($merge_fields, get_task_merge_fields($task->id));
                $this->load->model('emails_model');
                $this->emails_model->send_email_template('task-assigned', $member->email, $merge_fields);
            }

            $description                  = 'not_task_assigned_someone';
            $additional_notification_data = serialize(array(
                get_staff_full_name($data['assignee']),
                $task->name
            ));
            if ($data['assignee'] == get_staff_user_id()) {
                $description                  = 'not_task_will_do_user';
                $additional_notification_data = serialize(array(
                    $task->name
                ));
            }

            if ($task->rel_type == 'project') {
                $this->projects_model->log_activity($task->rel_id, 'project_activity_new_task_assignee', $task->name . ' - ' . get_staff_full_name($data['assignee']), $task->visible_to_client);
            }

            $this->_send_task_responsible_users_notification($description, $data['taskid'], $data['assignee'], '', $additional_notification_data);

            return $assigneeId;
        }

        return false;
    }

    /**
     * Get all task attachments
     * @param  mixed $taskid taskid
     * @return array
     */
    public function get_task_attachments($taskid)
    {
        $this->db->select(implode(', ', prefixed_table_fields_array('tblfiles')).', tblstafftaskcomments.id as comment_file_id');
        $this->db->where('rel_id', $taskid);
        $this->db->where('rel_type', 'task');
        $this->db->join('tblstafftaskcomments', 'tblstafftaskcomments.file_id = tblfiles.id', 'left');
        $this->db->order_by('dateadded', 'desc');

        return $this->db->get('tblfiles')->result_array();
    }

    /**
     * Remove task attachment from server and database
     * @param  mixed $id attachmentid
     * @return boolean
     */
    public function remove_task_attachment($id)
    {
        $deleted = false;
        // Get the attachment
        $this->db->where('id', $id);
        $attachment = $this->db->get('tblfiles')->row();

        if ($attachment) {
            if (empty($attachment->external)) {
                $relPath = get_upload_path_by_type('task') . $attachment->rel_id . '/';
                $fullPath =$relPath.$attachment->file_name;
                unlink($fullPath);
                $fname = pathinfo($fullPath, PATHINFO_FILENAME);
                $fext = pathinfo($fullPath, PATHINFO_EXTENSION);
                $thumbPath = $relPath.$fname.'_thumb.'.$fext;
                if(file_exists($thumbPath)) {
                    unlink($thumbPath);
                }
            }

            $this->db->where('id', $attachment->id);
            $this->db->delete('tblfiles');
            if ($this->db->affected_rows() > 0) {
                $deleted = true;
                logActivity('Task Attachment Deleted [TaskID: ' . $attachment->rel_id . ']');
            }

            if (is_dir(get_upload_path_by_type('task') . $attachment->rel_id)) {
                // Check if no attachments left, so we can delete the folder also
                    $other_attachments = list_files(get_upload_path_by_type('task') . $attachment->rel_id);
                if (count($other_attachments) == 0) {
                    // okey only index.html so we can delete the folder also
                        delete_dir(get_upload_path_by_type('task') . $attachment->rel_id);
                }
            }
        }

        if ($deleted) {
            $this->db->where('file_id', $id);
            $comment_attachment = $this->db->get('tblstafftaskcomments')->row();

            if ($comment_attachment) {
                $this->remove_comment($comment_attachment->id);
            }
        }

        return $deleted;
    }

    /**
     * Add uploaded attachments to database
     * @since  Version 1.0.1
     * @param mixed $taskid     task id
     * @param array $attachment attachment data
     */
    public function add_attachment_to_database($rel_id, $attachment, $external = false, $notification = true)
    {
        $file_id = $this->misc_model->add_attachment_to_database($rel_id, 'task', $attachment, $external);
        if ($file_id) {
            $task = $this->get($rel_id);
            if ($task->rel_type == 'project') {
                $this->projects_model->log_activity($task->rel_id, 'project_activity_new_task_attachment', $task->name, $task->visible_to_client);
            }
            if ($notification == true) {
                $description = 'not_task_new_attachment';
                $this->_send_task_responsible_users_notification($description, $rel_id, false, 'task-added-attachment');
                $this->_send_customer_contacts_notification($rel_id, 'task-added-attachment-to-contacts');
            }
            $task_attachment_as_comment = do_action('add_task_attachment_as_comment',false);
            if($task_attachment_as_comment){
                $file = $this->misc_model->get_file($file_id);
                $this->db->insert('tblstafftaskcomments', array(
                    'content'=>'[task_attachment]',
                    'taskid'=>$rel_id,
                    'staffid'=>$file->staffid,
                    'contact_id'=>$file->contact_id,
                    'file_id'=>$file_id,
                    'dateadded'=>date('Y-m-d H:i:s')
                    ));
            }

            return true;
        }

        return false;
    }

    /**
     * Get all task followers
     * @param  mixed $id task id
     * @return array
     */
    public function get_task_followers($id)
    {
        $this->db->select('id,tblstafftasksfollowers.staffid as followerid');
        $this->db->from('tblstafftasksfollowers');
        $this->db->join('tblstaff', 'tblstaff.staffid = tblstafftasksfollowers.staffid', 'left');
        $this->db->where('taskid', $id);

        return $this->db->get()->result_array();
    }

    /**
     * Get all task assigneed
     * @param  mixed $id task id
     * @return array
     */
    public function get_task_assignees($id)
    {
        $this->db->select('id,tblstafftaskassignees.staffid as assigneeid,assigned_from,firstname,lastname,is_assigned_from_contact');
        $this->db->from('tblstafftaskassignees');
        $this->db->join('tblstaff', 'tblstaff.staffid = tblstafftaskassignees.staffid', 'left');
        $this->db->where('taskid', $id);

        return $this->db->get()->result_array();
    }

    /**
     * Get task comment
     * @param  mixed $id task id
     * @return array
     */
    public function get_task_comments($id)
    {
        $task_comments_order = do_action('task_comments_order', 'DESC');

        $this->db->select('id,dateadded,content,tblstaff.firstname,tblstaff.lastname,tblstafftaskcomments.staffid,tblstafftaskcomments.contact_id as contact_id,file_id');
        $this->db->from('tblstafftaskcomments');
        $this->db->join('tblstaff', 'tblstaff.staffid = tblstafftaskcomments.staffid', 'left');
        $this->db->where('taskid', $id);
        $this->db->order_by('dateadded', $task_comments_order);

        return $this->db->get()->result_array();
    }

    public function edit_comment($data)
    {
        // Check if user really creator
        $this->db->where('id', $data['id']);
        $comment = $this->db->get('tblstafftaskcomments')->row();
        if ($comment->staffid == get_staff_user_id() || has_permission('tasks', '', 'edit') || $comment->contact_id == get_contact_user_id()) {
            $comment_added = strtotime($comment->dateadded);
            $minus_1_hour  = strtotime('-1 hours');
            $this->db->where('id', $data['id']);
            $this->db->update('tblstafftaskcomments', array(
                'content' => $data['content']
            ));
            if ($this->db->affected_rows() > 0) {
                return true;
            }
            

            return false;
        }
    }

    /**
     * Remove task comment from database
     * @param  mixed $id task id
     * @return boolean
     */
    public function remove_comment($id)
    {
        // Check if user really creator
        $this->db->where('id', $id);
        $comment = $this->db->get('tblstafftaskcomments')->row();
        if ($comment->staffid == get_staff_user_id() || has_permission('tasks', '', 'delete') || $comment->contact_id == get_contact_user_id()) {
            $comment_added = strtotime($comment->dateadded);
            $minus_1_hour  = strtotime('-1 hours');
            if (get_option('client_staff_add_edit_delete_task_comments_first_hour') == 0 || (get_option('client_staff_add_edit_delete_task_comments_first_hour') == 1 && $comment_added >= $minus_1_hour) || is_admin()) {
                $this->db->where('id', $id);
                $this->db->delete('tblstafftaskcomments');
                if ($this->db->affected_rows() > 0) {
                    if ($comment->file_id != 0) {
                        $this->remove_task_attachment($comment->file_id);
                    }

                    return true;
                }
            } else {
                return false;
            }
        }

        return false;
    }

    /**
     * Remove task assignee from database
     * @param  mixed $id     assignee id
     * @param  mixed $taskid task id
     * @return boolean
     */
    public function remove_assignee($id, $taskid)
    {
        $task = $this->get($taskid);
        $this->db->where('id', $id);
        $assignee_data = $this->db->get('tblstafftaskassignees')->row();

        // Delete timers
     //   $this->db->where('task_id', $taskid);
     ////   $this->db->where('staff_id', $assignee_data->staffid);
     ///   $this->db->delete('tbltaskstimers');

        // Stop all timers
        $this->db->where('task_id',$taskid);
        $this->db->where('staff_id', $assignee_data->staffid);
        $this->db->where('end_time IS NULL');
        $this->db->update('tbltaskstimers',array('end_time'=>time()));

        $this->db->where('id', $id);
        $this->db->delete('tblstafftaskassignees');
        if ($this->db->affected_rows() > 0) {
            if ($task->rel_type == 'project') {
                $this->projects_model->log_activity($task->rel_id, 'project_activity_task_assignee_removed', $task->name . ' - ' . get_staff_full_name($assignee_data->staffid), $task->visible_to_client);
            }

            return true;
        }

        return false;
    }

    /**
     * Remove task follower from database
     * @param  mixed $id     followerid
     * @param  mixed $taskid task id
     * @return boolean
     */
    public function remove_follower($id, $taskid)
    {
        $this->db->where('id', $taskid);
        $task = $this->db->get('tblstafftasks')->row();
        $this->db->where('id', $id);
        $this->db->delete('tblstafftasksfollowers');
        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    /**
     * Mark task as complete
     * @param  mixed $id task id
     * @return boolean
     */
    public function mark_complete($id)
    {
        $this->db->where('id', $id);
        $this->db->update('tblstafftasks', array(
            'datefinished' => date('Y-m-d H:i:s'),
            'status' => 5
        ));
        if ($this->db->affected_rows() > 0) {
            $task        = $this->get($id);
            $description = 'not_task_marked_as_complete';

            $this->db->where('end_time IS NULL');
            $this->db->where('task_id', $id);
            $this->db->update('tbltaskstimers', array(
                'end_time' => time()
            ));
            if ($task->rel_type == 'project') {
                $this->projects_model->log_activity($task->rel_id, 'project_activity_task_marked_complete', $task->name, $task->visible_to_client);
            }
            $this->_send_task_responsible_users_notification($description, $id, false, 'task-marked-as-finished', serialize(array(
                $task->name
            )));
            $this->_send_customer_contacts_notification($id, 'task-marked-as-finished-to-contacts');

            do_action('task_status_changed',array('status'=>5,'task_id'=>$id));

            return true;
        }

        return false;
    }

    public function mark_as($status, $task_id)
    {
        $this->db->select('status');
        $this->db->where('id', $task_id);
        $_task = $this->db->get('tblstafftasks')->row();
        if ($_task->status == 5) {
            return $this->unmark_complete($task_id, $status);
        } else {
            if ($status == 5) {
                return $this->mark_complete($task_id);
            } else {
                $this->db->where('id', $task_id);
                $this->db->update('tblstafftasks', array(
                    'status' => $status
                ));
                if ($this->db->affected_rows() > 0) {
                    do_action('task_status_changed',array('status'=>$status,'task_id'=>$task_id));
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Unmark task as complete
     * @param  mixed $id task id
     * @return boolean
     */
    public function unmark_complete($id, $force_to_status = false)
    {
        if ($force_to_status != false) {
            $status = $force_to_status;
        } else {
            $status = 1;
            $this->db->select('startdate');
            $this->db->where('id', $id);
            $_task = $this->db->get('tblstafftasks')->row();
            if (date('Y-m-d') > date('Y-m-d', strtotime($_task->startdate))) {
                $status = 4;
            }
        }

        $this->db->where('id', $id);
        $this->db->update('tblstafftasks', array(
            'datefinished' => null,
            'status' => $status
        ));

        if ($this->db->affected_rows() > 0) {
            $task = $this->get($id);
            if ($task->rel_type == 'project') {
                $this->projects_model->log_activity($task->rel_id, 'project_activity_task_unmarked_complete', $task->name, $task->visible_to_client);
            }
            $description = 'not_task_unmarked_as_complete';

            $this->_send_task_responsible_users_notification('not_task_unmarked_as_complete', $id, false, 'task-unmarked-as-finished', serialize(array(
                $task->name
            )));

            do_action('task_status_changed',array('status'=>$status,'task_id'=>$id));

            return true;
        }

        return false;
    }

    /**
     * Delete task and all connections
     * @param  mixed $id taskid
     * @return boolean
     */
    public function delete_task($id, $log_activity = true)
    {
        $task = $this->get($id);
        $data['deleted']        = 1;
        $this->db->where('id', $id);
        $this->db->update('tblstafftasks', $data);
        
        // $this->db->where('id', $id);
        // $this->db->delete('tblstafftasks');
        if ($this->db->affected_rows() > 0) {

            // Log activity only if task is deleted indivudual not when deleting all projects
            if ($task->rel_type == 'project' && $log_activity == true) {
                $this->projects_model->log_activity($task->rel_id, 'project_activity_task_deleted', $task->name, $task->visible_to_client);
            }

            // $this->db->where('taskid', $id);
            // $this->db->delete('tblstafftasksfollowers');

            // $this->db->where('taskid', $id);
            // $this->db->delete('tblstafftaskassignees');

            // $this->db->where('taskid', $id);
            // $this->db->delete('tblstafftaskcomments');

            // $this->db->where('taskid', $id);
            // $this->db->delete('tbltaskchecklists');
            // // Delete the custom field values
            // $this->db->where('relid', $id);
            // $this->db->where('fieldto', 'tasks');
            // $this->db->delete('tblcustomfieldsvalues');

            // $this->db->where('task_id', $id);
            // $this->db->delete('tbltaskstimers');


            // $this->db->where('rel_id', $id);
            // $this->db->where('rel_type', 'task');
            // $this->db->delete('tbltags_in');


            // $this->db->where('rel_id', $id);
            // $this->db->where('rel_type', 'task');
            // $attachments = $this->db->get('tblfiles')->result_array();
            // foreach ($attachments as $at) {
            //     $this->remove_task_attachment($at['id']);
            // }

            // $this->db->where('rel_id', $id);
            // $this->db->where('rel_type', 'task');
            // $this->db->delete('tblitemsrelated');

            // if (is_dir(get_upload_path_by_type('task') . $id)) {
            //     delete_dir(get_upload_path_by_type('task') . $id);
            // }

            return true;
        }

        return false;
    }

    /**
     * Send notification on task activity to creator,follower/s,assignee/s
     * @param  string  $description notification description
     * @param  mixed  $taskid      task id
     * @param  boolean $excludeid   excluded staff id to not send the notifications
     * @return boolean
     */
    private function _send_task_responsible_users_notification($description, $taskid, $excludeid = false, $email_template = '', $additional_notification_data = '')
    {
        $this->load->model('staff_model');
        $staff = $this->staff_model->get('', 1);
        $notifiedUsers = array();
        foreach ($staff as $member) {
            if (is_numeric($excludeid)) {
                if ($excludeid == $member['staffid']) {
                    continue;
                }
            }
            if (!is_client_logged_in()) {
                if ($member['staffid'] == get_staff_user_id()) {
                    continue;
                }
            }

            if ($this->is_task_follower($member['staffid'], $taskid) || $this->is_task_assignee($member['staffid'], $taskid) || $this->is_task_creator($member['staffid'], $taskid)) {
                $notified = add_notification(array(
                    'description' => $description,
                    'touserid' => $member['staffid'],
                    'link' => '#taskid=' . $taskid,
                    'additional_data' => $additional_notification_data
                ));

                if($notified) {
                    array_push($notifiedUsers,$member['staffid']);
                }

                if ($email_template != '') {
                    $merge_fields = array();
                    $merge_fields = array_merge($merge_fields, get_staff_merge_fields($member['staffid']));
                    $merge_fields = array_merge($merge_fields, get_task_merge_fields($taskid));
                    $this->load->model('emails_model');
                    $this->emails_model->send_email_template($email_template, $member['email'], $merge_fields);
                }
            }
        }

        pusher_trigger_notification($notifiedUsers);
    }

    public function _send_customer_contacts_notification($taskid, $template_name)
    {
        $this->db->select('rel_id,visible_to_client,rel_type');
        $this->db->from('tblstafftasks');
        $this->db->where('id', $taskid);
        $task = $this->db->get()->row();

        if ($task->rel_type == 'project') {
            $this->db->where('project_id', $task->rel_id);
            $this->db->where('name', 'view_tasks');
            $project_settings = $this->db->get('tblprojectsettings')->row();
            if ($project_settings) {
                if ($project_settings->value == 1 && $task->visible_to_client == 1) {
                    $this->db->select('clientid');
                    $this->db->from('tblprojects');
                    $this->db->where('id', $project_settings->project_id);
                    $project  = $this->db->get()->row();
                    $contacts = $this->clients_model->get_contacts($project->clientid);
                    $this->load->model('emails_model');
                    foreach ($contacts as $contact) {
                        if (is_client_logged_in() && get_contact_user_id() == $contact['id']) {
                            continue;
                        }
                        if (has_contact_permission('projects', $contact['id'])) {
                            $merge_fields = array();
                            $merge_fields = array_merge($merge_fields, get_client_contact_merge_fields($project->clientid, $contact['id']));
                            $merge_fields = array_merge($merge_fields, get_task_merge_fields($taskid, true));
                            $this->emails_model->send_email_template($template_name, $contact['email'], $merge_fields);
                        }
                    }
                }
            }
        }
    }

    /**
     * Check is user is task follower
     * @param  mixed  $userid staff id
     * @param  mixed  $taskid taskid
     * @return boolean
     */
    public function is_task_follower($userid, $taskid)
    {
        if (total_rows('tblstafftasksfollowers', array(
            'staffid' => $userid,
            'taskid' => $taskid
        )) == 0) {
            return false;
        }

        return true;
    }

    /**
     * Check is user is task assignee
     * @param  mixed  $userid staff id
     * @param  mixed  $taskid taskid
     * @return boolean
     */
    public function is_task_assignee($userid, $taskid)
    {
        if (total_rows('tblstafftaskassignees', array(
            'staffid' => $userid,
            'taskid' => $taskid
        )) == 0) {
            return false;
        }

        return true;
    }

    /**
     * Check is user is task creator
     * @param  mixed  $userid staff id
     * @param  mixed  $taskid taskid
     * @return boolean
     */
    public function is_task_creator($userid, $taskid)
    {
        if (total_rows('tblstafftasks', array(
            'addedfrom' => $userid,
            'id' => $taskid
        )) == 0) {
            return false;
        }

        return true;
    }

    public function timer_tracking($task_id = '', $timer_id = '', $note = '')
    {

        if ($task_id == '' && $timer_id == '') {
            return false;
        }
        if (!$this->is_task_assignee(get_staff_user_id(), $task_id)) {
            return false;
        } elseif ($this->is_task_billed($task_id)) {
            return false;
        }

        $timer = $this->get_task_timer(array(
            'id' => $timer_id
        ));

        if (total_rows('tbltaskstimers', array(
            'staff_id' => get_staff_user_id(),
            'task_id' => $task_id
        )) == 0 || $timer == null) {
            $this->db->select('hourly_rate');
            $this->db->from('tblstaff');
            $this->db->where('staffid', get_staff_user_id());
            $hourly_rate = $this->db->get()->row()->hourly_rate;

            $this->db->insert('tbltaskstimers', array(
                'start_time' => time(),
                'staff_id' => get_staff_user_id(),
                'task_id' => $task_id,
                'hourly_rate' => $hourly_rate,
                'note'=>($note != '' ? $note : NULL)
            ));

            $_new_timer_id = $this->db->insert_id();

            if (get_option('auto_stop_tasks_timers_on_new_timer') == 1) {
                $this->db->where('id !=', $_new_timer_id);
                $this->db->where('end_time IS NULL');
                $this->db->where('staff_id', get_staff_user_id());
                $this->db->update('tbltaskstimers', array(
                    'end_time' => time(),
                    'note'=>($note != '' ? $note : NULL)
                ));
            }

            if(get_option('timer_started_change_status_in_progress') == '1' && total_rows('tblstafftasks', array(
                    'id'=>$task_id,
                    'status'=>1)) > 0){

                $this->mark_as(4,$task_id);
            }

            do_action('task_timer_started',array('task_id'=>$task_id,'timer_id'=>$_new_timer_id));

            return true;
        } else {
            if ($timer) {
                // time already ended
                if ($timer->end_time != null) {
                    return false;
                }
                $this->db->where('id', $timer_id);
                $this->db->update('tbltaskstimers', array(
                    'end_time' => time(),
                    'note'=>($note != '' ? $note : NULL)
                ));
            }

            return true;
        }
    }

    public function timesheet($data)
    {
        $start_time = to_sql_date($data['start_time'], true);
        $end_time   = to_sql_date($data['end_time'], true);

        $start_time = strtotime($start_time);
        $end_time   = strtotime($end_time);

        if ($end_time < $start_time) {
            return array(
                'end_time_smaller' => true
            );
        }
        $timesheet_staff_id = get_staff_user_id();
        if (isset($data['timesheet_staff_id']) && $data['timesheet_staff_id'] != '') {
            $timesheet_staff_id = $data['timesheet_staff_id'];
        }

        if (!isset($data['timer_id']) || (isset($data['timer_id']) && $data['timer_id'] == '')) {

            // Stop all other timesheets when adding new timesheet
            $this->db->where('task_id', $data['timesheet_task_id']);
            $this->db->where('staff_id', $timesheet_staff_id);
            $this->db->where('end_time IS NULL');
            $this->db->update('tbltaskstimers', array(
                'end_time' => time()
            ));


            $this->db->select('hourly_rate');
            $this->db->from('tblstaff');
            $this->db->where('staffid', $timesheet_staff_id);
            $hourly_rate = $this->db->get()->row()->hourly_rate;

            $this->db->insert('tbltaskstimers', array(
                'start_time' => $start_time,
                'end_time' => $end_time,
                'staff_id' => $timesheet_staff_id,
                'task_id' => $data['timesheet_task_id'],
                'hourly_rate' => $hourly_rate,
                'note'=>(isset($data['note']) && $data['note'] != '' ? nl2br($data['note']) : NULL)
            ));

            $insert_id = $this->db->insert_id();
            $tags = '';

            if (isset($data['tags'])) {
                $tags = $data['tags'];
            }

            handle_tags_save($tags, $insert_id, 'timesheet');

            if ($insert_id) {
                $task = $this->get($data['timesheet_task_id']);
                if ($task->rel_type == 'project') {
                    $total      = $end_time - $start_time;
                    $additional = '<seconds>' . $total . '</seconds>';
                    $additional .= '<br />';
                    $additional .= '<lang>project_activity_task_name</lang> ' . $task->name;
                    $this->projects_model->log_activity($task->rel_id, 'project_activity_recorded_timesheet', $additional, $task->visible_to_client);
                }

                return true;
            } else {
                return false;
            }
        } else {

            $affectedRows = 0;
            $this->db->where('id', $data['timer_id']);
            $this->db->update('tbltaskstimers', array(
                'start_time' => $start_time,
                'end_time' => $end_time,
                'staff_id' => $timesheet_staff_id,
                'task_id' => $data['timesheet_task_id'],
                'note'=>(isset($data['note']) && $data['note'] != '' ? nl2br($data['note']) : NULL)
            ));

            if ($this->db->affected_rows() > 0) {
                $affectedRows++;
            }

            if (isset($data['tags'])) {
                if (handle_tags_save($data['tags'], $data['timer_id'], 'timesheet')) {
                    $affectedRows++;
                }
            }

            return ($affectedRows > 0 ? true : false);
        }
    }

    public function get_timers($task_id,$where = array()){
        $this->db->where($where);
        $this->db->where('task_id',$task_id);
        $this->db->order_by('start_time','DESC');
        return $this->db->get('tbltaskstimers')->result_array();
    }

    public function get_task_timer($where)
    {
        $this->db->where($where);

        return $this->db->get('tbltaskstimers')->row();
    }

    public function is_timer_started($task_id, $staff_id = '')
    {
        if ($staff_id == '') {
            $staff_id = get_staff_user_id();
        }
        $timer = $this->get_last_timer($task_id, $staff_id);
        if (!$timer) {
            return false;
        }
        if ($timer->end_time != null) {
            return false;
        }
        return $timer;
    }

    public function is_timer_started_for_task($id, $where = array())
    {
        $this->db->where('task_id', $id);
        $this->db->where('end_time IS NULL');
        $this->db->where($where);
        $results = $this->db->count_all_results('tbltaskstimers');

        return $results > 0 ? true : false;
    }

    public function get_last_timer($task_id, $staff_id = '')
    {
        if ($staff_id == '') {
            $staff_id = get_staff_user_id();
        }
        $this->db->where('staff_id', $staff_id);
        $this->db->where('task_id', $task_id);
        $this->db->order_by('id', 'desc');
        $this->db->limit(1);
        $timer = $this->db->get('tbltaskstimers')->row();

        return $timer;
    }

    public function task_tracking_stats($id)
    {
        $loggers = $this->db->query("SELECT DISTINCT(staff_id) FROM tbltaskstimers WHERE task_id=".$id)->result_array();
        $labels     = array();
        $labels_ids = array();
        foreach ($loggers as $assignee) {
            array_push($labels, get_staff_full_name($assignee['staff_id']));
            array_push($labels_ids, $assignee['staff_id']);
        }
        $chart = array(
            'labels' => $labels,
            'datasets' => array(
                array(
                    'label' => _l('task_stats_logged_hours'),
                    'data' => array()
                )
            )
        );
        $i     = 0;
        foreach ($labels_ids as $staffid) {
            $chart['datasets'][0]['data'][$i] = sec2qty($this->calc_task_total_time($id, ' AND staff_id=' . $staffid));
            $i++;
        }

        return $chart;
    }

    public function get_timesheeets($task_id)
    {
        return $this->db->query("SELECT id,note,start_time,end_time,task_id,staff_id,
        end_time - start_time time_spent FROM tbltaskstimers WHERE task_id = '$task_id' ORDER BY start_time DESC")->result_array();
    }

    public function get_time_spent($seconds)
    {
        $minutes = $seconds / 60;
        $hours   = $minutes / 60;
        if ($minutes >= 60) {
            return round($hours, 2);
        } elseif ($seconds > 60) {
            return round($minutes, 2);
        } else {
            return $seconds;
        }
    }

    public function calc_task_total_time($task_id, $where = '')
    {
        $sql = "SELECT SUM(CASE
            WHEN end_time is NULL THEN ".time()."-start_time
            ELSE end_time-start_time
            END) as total_logged_time FROM tbltaskstimers WHERE task_id =" . $task_id . $where;

        $result = $this->db->query($sql)->row();

        if($result){
            return $result->total_logged_time;
        }

        return 0;
    }

    public function get_unique_member_logged_task_ids($staff_id, $where = '')
    {
        $sql    = "SELECT DISTINCT(task_id)
        FROM tbltaskstimers WHERE staff_id =" . $staff_id.$where;

        return $this->db->query($sql)->result();
    }
    /**
     * @deprecated
     */
    private function _cal_total_logged_array_from_timers($timers)
    {
        $total = array();
        foreach ($timers as $key => $timer) {
            $_tspent = 0;
            if (is_null($timer->end_time)) {
                $_tspent = time() - $timer->start_time;
            } else {
                $_tspent = $timer->end_time - $timer->start_time;
            }
            $total[] = $_tspent;
        }

        return array_sum($total);
    }

    public function delete_timesheet($id)
    {
        $this->db->where('id', $id);
        $timesheet = $this->db->get('tbltaskstimers')->row();
        $this->db->where('id', $id);
        $this->db->delete('tbltaskstimers');
        if ($this->db->affected_rows() > 0) {
            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'timesheet');
            $this->db->delete('tbltags_in');

            $task = $this->get($timesheet->task_id);

            if ($task->rel_type == 'project') {
                $additional_data = $task->name;
                $total           = $timesheet->end_time - $timesheet->start_time;
                $additional_data .= '<br /><seconds>' . $total . '</seconds>';
                $this->projects_model->log_activity($task->rel_id, 'project_activity_task_timesheet_deleted', $additional_data, $task->visible_to_client);
            }

            logActivity('Timesheet Deleted [' . $id . ']');

            return true;
        }

        return false;
    }

    /**
     * Get task statuses
     * @param  mixed $id status id
     * @return mixed      object if id passed else array
     */
    public function get_status($id = '', $where = array())
    {
        $brandid = get_user_session();
        $session_data = get_session_data();
        //$is_sido_admin = $session_data['is_sido_admin'];
        $is_admin = $session_data['is_admin'];        
            

        $where   = "";
        $where .= 'deleted=0';
        if($is_admin == false && !is_sido_admin()){
            $where .= ' and brandid =' . $brandid;
        }       
       
                
        if (is_numeric($id)) {
            $where .= ' and id=' . $id;
            $this->db->where($where);   
            $this->db->order_by("statusorder", "asc");

            return $this->db->get('tbltasksstatus')->row();
        }       

        $this->db->where($where);   
        $this->db->order_by("statusorder", "asc");
        $statuses = $this->db->get('tbltasksstatus')->result_array();
        /*echo $this->db->last_query();
        echo "<pre>";
        print_r($statuses);
        die('<--here');*/
        return $statuses;
    }
    /**
     * Add new task status
     * @param array $data task status data
     */
    public function add_status($data)
    {
        $this->db->insert('tbltasksstatus', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            logActivity('New Tasks Status Added [StatusID: ' . $insert_id . ', Name: ' . $data['name'] . ']');

            return true;
        }

        return false;
    }

    public function update_status($data, $id)
    {        
        $data['updated_by']     = $this->session->userdata['staff_user_id'];
        $data['dateupdated']    = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        $this->db->update('tbltasksstatus', $data);
        
        if ($this->db->affected_rows() > 0) {
            logActivity('Tasks Status Updated [StatusID: ' . $id . ', Name: ' . $data['name'] . ']');

            return true;
        }

        return false;
    }

    /**
     * Delete task status from database
     * @param  mixed $id status id
     * @return boolean
     */
    public function delete_status($id)
    {
        $current = $this->get_status($id);

         if (is_reference_in_table('status', 'tblstafftasks', $id)) {
            return array(
                'referenced' => true
            );
        }
        
        $data['deleted']        = 1;
        $data['updated_by']     = $this->session->userdata['staff_user_id'];
        $data['dateupdated']    = date('Y-m-d H:i:s');

        $this->db->where('id', $id);
        $this->db->update('tbltasksstatus', $data);  
        
        if ($this->db->affected_rows() > 0) {
            if (get_option('tasks_default_status') == $id) {
                update_option('tasks_default_status', '');
            }
            logActivity('Tasks Status Deleted [StatusID: ' . $id . ']');

            return true;
        }

        return false;
    }

    //Added by Avni on 11/10/2017
    public function statuschange($task_id, $status_id){   

        $data['status'] = $status_id;
        $data['updated_by']     = $this->session->userdata['staff_user_id'];
        $data['dateupdated']    = date('Y-m-d H:i:s');
        $this->db->where('id', $task_id);
        $this->db->update('tblstafftasks', $data);
        
        if ($this->db->affected_rows() > 0) {
            logActivity('Tasks Status Updated Successfully');

            return true;
        }

        return false;     
           
    }
    /**
        Added By Purvi on 11-09-2017 For Pin/Unpin Tasks
    */
    public function pintask($task_id){
        $session_data   = get_session_data();
        $user_id = $session_data['staff_user_id'];

        $pinexist = $this->db->select('pinid')->from('tblpins')->where('pintype = "Task" AND pintypeid=' . $task_id . ' AND userid=' . $user_id)->get()->row();
        if(!empty($pinexist)){
            $this->db->where('userid', $user_id);
            $this->db->where('pintypeid', $task_id);
            $this->db->where('pintype', "Task");
            $this->db->delete('tblpins');
            return 0;
        }else{
            $this->db->insert('tblpins', array(
                    'pintype' => "Task",
                    'pintypeid' => $task_id,
                    'userid' => $user_id
                ));
            return 1;
        }

    }

    /**
    * Added By : Avni
    * Dt : 12/04/2017
    * to get task reminders
    */
    public function get_task_usersreminder($id = '')
    {
        $this->db->where('taskid', $id);
        $result_array = $this->db->get('tbltaskreminders')->result_array();
        return $result_array;
    }

    public function get_leads()
    {
        $this->db->select('id,name');
        $this->db->where('brandid',  get_user_session());
        $this->db->where('deleted', 0);
        $this->db->where('converted', 0);

        //$this->db->query("SET GLOBAL sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
                        
        return $this->db->get('tblleads')->result_array();
    }

     public function get_projects()
    {
		/*
        $this->db->select('id,name');
        $this->db->where('brandid',  get_user_session());
        $this->db->where('deleted', 0);
        $this->db->where('parent', 0);
        $this->db->where('addedfrom', get_staff_user_id());
        //$this->db->query("SET GLOBAL sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
                        
        return $this->db->get('tblprojects')->result_array();*/
		
		$getProject = 'SELECT DISTINCT tblprojects.id as id, tblprojects.name FROM tblprojects LEFT JOIN tblprojectcontact ON tblprojectcontact.projectid = tblprojects.id WHERE tblprojects.deleted = 0 AND tblprojects.parent = 0 AND tblprojects.brandid = '.get_user_session().' AND ( assigned = '.get_staff_user_id().' OR addedfrom = '.get_staff_user_id().' OR tblprojectcontact.contactid = '.get_staff_user_id().')'; 			
		return $this->db->query($getProject)->result_array();
		
    }

    public function get_events($pid)
    {
        $this->db->select('id,name');
        $this->db->where('brandid',  get_user_session());
        $this->db->where('addedfrom', get_staff_user_id());
        $this->db->where('deleted', 0);
        if($pid != ""){
            $this->db->where('parent', $pid);
        }else{
             $this->db->where('parent != ""');
        }
        //$this->db->query("SET GLOBAL sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
        $res = $this->db->get('tblprojects')->result_array();
        return $res;
    }

    /**
    * Added By: Vaidehi
    * Dt: 03/03/2018
    * get cron tasks
    */
    public function get_crontasks()
    {
        $query = $this->db->query('SELECT `tblstafftasks`.*, DATE_FORMAT(`tblstafftasks`.`duedate`, "%m/%d/%Y %H:%i") AS due_date,`tblstafftaskassignees`.`staffid`, `tblstafftaskassignees`.`assigned_from`, `tblstaff`.`firstname`, `tblstaff`.`lastname`, `tblstaff`.`email`, s1.`firstname` AS assigned_firstname, s1.`lastname` AS assigned_lastname, s1.`email` AS assigned_email FROM `tblstafftasks` LEFT JOIN `tblstafftaskassignees` ON `tblstafftasks`.`id` = `tblstafftaskassignees`.`taskid` LEFT JOIN `tblstaff` ON `tblstaff`.`staffid` = `tblstafftaskassignees`.`staffid`  LEFT JOIN `tblstaff` s1 ON s1.`staffid` = `tblstafftaskassignees`.`assigned_from` WHERE DATE(`tblstafftasks`.`duedate`) = DATE_ADD(CURRENT_DATE(), INTERVAL 1 DAY) AND `tblstaff`.`deleted` = 0  AND `tblstaff`.`active` = 1 AND `tblstafftasks`.`deleted` = 0');
        $response = $query->result_array();

        return $response;
    }
    public function get_task_by_rel($rel_type, $rel_id)
    {
        $brandid = get_user_session();
        $session_data   = get_session_data();
        $is_sido_admin  = $session_data['is_sido_admin'];
        $tasks_where = '';
        if (!has_permission('tasks', '', 'view')) {
            $tasks_where = get_tasks_where_string(false);
        }

        $this->db->select('*');
        $this->db->from('tblstafftasks');
        $this->db->where('deleted = 0');
        if($brandid > 0){
            $this->db->where('brandid = '. $brandid);
        }else if($is_sido_admin > 0){
            $this->db->where('brandid = 0');
        }

        if($rel_type != "" && $rel_id > 0) {
            $this->db->where('rel_type',$rel_type);
            $this->db->where('rel_id = '. $rel_id);
        }
        return $this->db->get()->result_array();
    }
    function progressmanaual($taskid,$data){
        $this->db->where('id', $taskid);
        $this->db->update('tblstafftasks', $data);
        if ($this->db->affected_rows() > 0) {
            return 1;
        }
        return 0;
    }
    function task_name_exists($name){
        $this->db->select('*');
        $this->db->from('tblstafftasks');
        $this->db->where('name', $name);
        $this->db->where('brandid', get_user_session());
        $result = $this->db->get()->result_array();
        if(count($result) > 0){
            return json_encode(false);
        }else{
            return json_encode(true);
        }
    }
}