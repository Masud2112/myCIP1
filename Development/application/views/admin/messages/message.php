<?php

init_head();
$to="";
if(isset($_GET['to'])){
    $to=$_GET['to'];
}
?>
<div id="wrapper">
    <div class="content message-page">
        <div class="row">
            <?php echo form_open_multipart('admin/messages/message',array('id'=>'message')); ?>
            <div class="col-md-12">


                <div class="breadcrumb">
                    <?php /*if (isset($pg) && $pg == 'home') { */?>
                    <a href="<?php echo admin_url(); ?>"><i class="fa fa-home"></i></a>
                    <i class="fa fa-angle-right breadcrumb-arrow"></i>
                    <?php /*} */?>
                    <?php if (isset($lid)) { ?>
                        <a href="<?php echo admin_url('leads/'); ?>">Leads</a>
                        <i class="fa fa-angle-right breadcrumb-arrow"></i>
                        <a href="<?php echo admin_url('leads/dashboard/' . $lid); ?>"><?php echo ($lname); ?></a>
                        <i class="fa fa-angle-right breadcrumb-arrow"></i>
                        <a href="<?php echo admin_url('messages').'?lid='.$lid; ?>">Messages</a>
                        <i class="fa fa-angle-right breadcrumb-arrow"></i>
                    <?php } elseif (isset($pid)) { ?>
                        <a href="<?php echo admin_url('projects/'); ?>">Projects</a>
                        <i class="fa fa-angle-right breadcrumb-arrow"></i>
                        <a href="<?php echo admin_url('projects/dashboard/' . $pid); ?>"><?php echo ($lname); ?></a>
                        <i class="fa fa-angle-right breadcrumb-arrow"></i>
                        <a href="<?php echo admin_url('messages').'?pid='.$pid; ?>">Messages</a>
                        <i class="fa fa-angle-right breadcrumb-arrow"></i>
                    <?php }else{ ?>
                        <a href="<?php echo admin_url('messages'); ?>">Messages</a>
                        <i class="fa fa-angle-right breadcrumb-arrow"></i>
                    <?php } ?>
                    <span><?php echo isset($message)?$message->name:"New Message"?></span>
                </div>
                <h1 class="pageTitleH1"><i class="fa fa-envelope-o"></i><?php echo $title; ?></h1>
                <div class="clearfix"></div>
                <div class="panel_s btmbrd">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="subject" class="control-label">Subject <small class="req text-danger">* </small></label>
                                    <input id="subject" name="subject" class="form-control" autofocus="1" value="" type="text">
                                </div>

                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="content" class="control-label">Content <small class="req text-danger">* </small></label>
                                    <textarea id="content" name="content" class="form-control message" rows="4" aria-hidden="true"></textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="privacy" class="control-label">Message To <small class="req text-danger">* </small></label>
                                    <select id="privacy" class="selectpicker" name="message_to[]" data-width="100%" data-none-selected-text="Select Users" multiple data-live-search="true">
                                        <optgroup label="Team Member">
                                            <?php foreach ($teammember as $t) { ?>
                                                <option value="tm_<?php echo $t['staffid'] ?>"><?php echo $t['staff_name'] ?></option>
                                            <?php } ?>
                                        </optgroup>
                                        <optgroup label="Contacts">
                                            <?php foreach ($contacts as $c) { ?>
                                                <option value="cn_<?php echo $c['addressbookid'] ?>" <?php echo $to==$c['addressbookid']? "selected":""?>><?php echo $c['contact_name'] ?></option>
                                            <?php } ?>
                                        </optgroup>
                                    </select>

                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="message_to" class="control-label">Privacy <i class="fa fa-question-circle" data-toggle="tooltip" data-title="Message visible to"></i></label>
                                    <select id="message_to" class="selectpicker" name="privacy[]" data-width="100%" data-none-selected-text="Select Users" multiple data-live-search="true">
                                        <optgroup label="Team Member">
                                            <?php foreach ($teammember as $t) { ?>
                                                <option value="tm_<?php echo $t['staffid'] ?>"><?php echo $t['staff_name'] ?></option>
                                            <?php } ?>
                                        </optgroup>
                                        <optgroup label="Contacts">
                                            <?php foreach ($contacts as $c) { ?>
                                                <option value="cn_<?php echo $c['addressbookid'] ?>"><?php echo $c['contact_name'] ?></option>
                                            <?php } ?>
                                        </optgroup>
                                    </select>

                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="tags" class="control-label">Tags</label>
                                    <select name="tags[]" id="tags[]" class="form-control selectpicker" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>" data-live-search="true" multiple>
                                        <?php
                                        foreach($tags as $tag){
                                            $tselected = '';
                                            if(in_array($tag['id'],$messages->tags_id)){
                                                $tselected = "selected='selected'";
                                            }
                                            echo '<option value="'.$tag['id'].'" '.$tselected.'>'.$tag['name'].'</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <?php
                            $rel_type = '';
                            $rel_id = '';
                            if(isset($message) || ($this->input->get('rel_id') && $this->input->get('rel_type'))){
                                if($this->input->get('rel_id')){
                                    $rel_id = $this->input->get('rel_id');
                                    $rel_type = $this->input->get('rel_type');
                                }  else {
                                    $rel_id = $message->rel_id;
                                    $rel_type = $message->rel_type;
                                }
                            }elseif(isset($lid)) {
                                $rel_id = $lid;
                                $rel_type = 'lead';
                            }elseif(isset($pid)) {
                                $rel_id = $pid;
                                $rel_type = 'project';
                            }elseif(isset($eid)) {
                                $rel_id = $eid;
                                $rel_type = 'event';
                            }
                            ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="rel_type" class="control-label"><?php echo _l('task_related_to'); ?></label>
                                        <select name="rel_type" class="selectpicker" id="rel_type" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                            <option value=""></option>
                                            <?php if(isset($lid) || (!isset($eid) && !isset($pid))){?>
                                                <option value="lead" <?php if(isset($message) || isset($lid) || $this->input->get('rel_type')){if($rel_type == 'lead'){echo 'selected';}} ?>>
                                                    <?php echo _l('lead'); ?>
                                                </option>
                                            <?php } ?>
                                            <?php if(isset($pid) || (!isset($eid) && !isset($lid))){?>
                                                <option value="project" <?php if(isset($message) || isset($pid) || $this->input->get('rel_type')){if($rel_type == 'project'){echo 'selected';}} ?>>
                                                    <?php echo _l('project'); ?>
                                                </option>
                                            <?php } ?>
                                            <?php if((isset($pid) || isset($eid)) || !isset($lid)){?>
                                                <option value="event" <?php if(isset($message) || isset($eid) || $this->input->get('rel_type')){if($rel_type == 'event'){echo 'selected';}} ?>>
                                                    Sub-Projects
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <?php if(isset($lid) || (!isset($eid) && !isset($pid))){?>
                                    <div class="col-md-6 lead-search <?php echo $rel_type == "lead" ? "" : "hide"; ?>">
                                        <?php $selectedleads = array();
                                        $selectedleads = $rel_id != "" ? $rel_id : "";
                                        //echo '<pre>'; print_r($selectedleads);exit;
                                        echo render_select('lead',$leads,array('id','name'),'Leads',$selectedleads,array(),array(),'','',false);
                                        ?>
                                    </div>
                                <?php } ?>
                                <?php if(isset($pid) || (!isset($eid) && !isset($lid))){?>
                                    <div class="col-md-6 project-search <?php echo $rel_type == "project" ? "" : "hide"; ?>">
                                        <?php $selectedprojects = array();
                                        $selectedprojects = $rel_id != "" ? $rel_id : "";
                                        echo render_select('project',$projects,array('id','name'),'Projects',$selectedprojects,array(),array(),'','',false);
                                        ?>
                                    </div>
                                <?php } ?>
                                <?php if((isset($pid) || isset($eid)) || !isset($lid)){?>
                                    <div class="col-md-6 event-search <?php echo $rel_type == "event" ? "" : "hide"; ?>">
                                        <?php $selectedevents = array();
                                        $selectedevents = $rel_id != "" ? $rel_id : "";
                                        echo render_select('event',$events,array('id','name'),'Sub-Projects',$selectedevents,array(),array(),'','',false);
                                        ?>
                                    </div>
                                <?php } ?>
                            </div>
                            <div class="col-md-12">
                                <?php if(!isset($messages)){ ?>
                                    <!-- <hr /> -->
                                    <label><?php echo _l('attach_files'); ?> <i class="fa fa-question-circle" data-toggle="tooltip" data-title="Allowed extensions - <?php echo str_replace('.','',get_option('allowed_files')); ?>"></i></label>

                                    <div id="new-message-attachments">
                                        <div class="attachments">
                                            <div class="row attachment">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <div class="input-group" id="attachments[0]">
                                                        <span class="input-group-btn">
                                                          <span class="btn btn-primary" onclick="$(this).parent().find('input[type=file]').click();">Browse</span>
                                                          <input name="attachments[0]" onchange="$(this).parent().parent().find('.form-control').html($(this).val().split(/[\\|/]/).pop());" style="display: none;" filesize="<?php echo file_upload_max_size(); ?>" extension="<?php echo str_replace('.','',get_option('allowed_files')); ?>"  type="file">
                                                        </span>
                                                            <span class="form-control"></span>
                                                        </div>
                                                    </div>

                                                </div>
                                                <div class="col-md-6">
                                                    <div class="text-right">
                                                        <button class="btn btn-primary add_more_attachments" type="button"><i class="fa fa-plus"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="topButton">
                            <button class="btn btn-default" type="button" onclick="fncancel();"><?php echo _l( 'Cancel'); ?></button>
                            <button type="submit" class="btn btn-info">Send</button>
                        </div>
                    </div>
                </div>
            </div>
            <input type="hidden" name="hdnlid" value="<?php echo isset($lid) ? $lid : '';?>">
            <input type="hidden" name="hdnpid" value="<?php echo isset($pid) ? $pid : '';?>">
            <input type="hidden" name="hdneid" value="<?php echo isset($eid) ? $eid : '';?>">
            <?php echo form_close(); ?>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    function fncancel(){
        var id=<?php if(isset($lid)) { echo $lid;} else { echo '0';}  ?>;
        var pid=<?php if(isset($pid)) { echo $pid;} else { echo '0';}  ?>;
        if( id > '0') {
            location.href='<?php echo base_url(); ?>admin/messages?lid=' + id;
        }else if( pid > '0') {
            location.href='<?php echo base_url(); ?>admin/messages?pid=' + pid;
        } else {
            window.history.go(-1);
        }
    }
</script>
<script>
    $(function() {
        init_editor('.message');
        var validator = $('#message').submit(function() {
            // update underlying textarea before submit validation
            var content = tinyMCE.activeEditor.getContent();
            $("#content").val(content);
            tinyMCE.triggerSave();
            if($("#content").val() == ""){
                $(".mce-tinymce").css({'border-color': '#fc2d42'});
            } else {
                $(".mce-tinymce").css({'border-color': ''});
            }
        }).validate({
            ignore: "",
            rules: {
                subject: "required",
                content: "required",
                'message_to[]':'required'
            }
        });
        $("#rel_type").on('change',function() {
            var selected = $(this).val();
            if(selected == "lead"){
                $(".lead-search").removeClass("hide");
                $(".project-search").addClass("hide");
                $(".event-search").addClass("hide");
            }else if(selected == "project"){
                $(".project-search").removeClass("hide");
                $(".lead-search").addClass("hide");
                $(".event-search").addClass("hide");
            }else if(selected == "event"){
                $(".event-search").removeClass("hide");
                $(".lead-search").addClass("hide");
                $(".project-search").addClass("hide");
            }
        });
    });
    //_validate_form($('form'),{subject:'required',content:'required', 'message_to[]':'required'});
</script>
</body>
</html>