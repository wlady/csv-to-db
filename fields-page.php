<div class="wrap ">
    <div id="icon-options-general" class="icon32"><br /></div>
    <h2><?php _e( 'CSV to DB' , 'csv-to-db' ); ?></h2>
    <?php if ($message) : ?>
        <div class="updated <?php if ($error) echo 'error'; ?>">
            <p><?php _e($message); ?></p>
        </div>
    <?php endif; ?>
    <div id="output" class="updated hidden"></div>
    <?php if (count($this->get_option('fields'))) : ?>
        <form action="" method="post" id="schema-table">
            <input type="hidden" name="action" value="save_schema" />
            <h3><?php _e( 'Fields' , 'csv-to-db' ); ?></h3>
            <table class="form-table table table-striped">
                <tr>
                    <th>
                        <?php _e( 'Name' , 'csv-to-db' ); ?>
                    </th>
                    <th>
                        <?php _e( 'Type' , 'csv-to-db' ); ?>
                    </th>
                    <th>
                        <?php _e( 'Size' , 'csv-to-db' ); ?>
                    </th>
                    <th>
                        <?php _e( 'Null' , 'csv-to-db' ); ?>
                    </th>
                    <th>
                        <?php _e( 'AI' , 'csv-to-db' ); ?>
                    </th>
                    <th>
                        <?php _e( 'Index' , 'csv-to-db' ); ?>
                    </th>
                    <th>
                        <?php _e( 'Title' , 'csv-to-db' ); ?>
                    </th>
                    <th>
                        <?php _e( 'Show' , 'csv-to-db' ); ?>
                    </th>
                    <th>
                        <?php _e( 'Align' , 'csv-to-db' ); ?>
                    </th>
                    <th>
                        <?php _e( 'Check' , 'csv-to-db' ); ?>
                    </th>
                </tr>
                <?php foreach ($this->get_option('fields') as $key=>$field) : ?>
                    <tr valign="top">
                        <td scope="row" >
                            <?php echo $field['name']; ?>
                            <input type="hidden" name="csv-to-db[fields][<?php echo $key; ?>][name]" value="<?php echo $field['name']; ?>" />
                        </td>
                        <td>
                            <select name="csv-to-db[fields][<?php echo $key; ?>][type]" style="width:100%" onchange="changeSize(this.value, <?php echo $key; ?>)">
                                <option <?php if ($field['type']=='VARCHAR') echo 'selected'; ?>>VARCHAR</option>
                                <option <?php if ($field['type']=='TEXT') echo 'selected'; ?>>TEXT</option>
                                <option <?php if ($field['type']=='BLOB') echo 'selected'; ?>>BLOB</option>
                                <option <?php if ($field['type']=='INT') echo 'selected'; ?>>INT</option>
                                <option <?php if ($field['type']=='FLOAT') echo 'selected'; ?>>FLOAT</option>
                                <option <?php if ($field['type']=='DOUBLE') echo 'selected'; ?>>DOUBLE</option>
                                <option <?php if ($field['type']=='DECIMAL') echo 'selected'; ?>>DECIMAL</option>
                            </select>
                        </td>
                        <td>
                            <input name="csv-to-db[fields][<?php echo $key; ?>][size]" type="text" value="<?php echo $field['size']; ?>"  style="width:100%" />
                        </td>
                        <td>
                            <input type="checkbox" name="csv-to-db[fields][<?php echo $key; ?>][null]" value="1" <?php echo $field['null']==1 ? 'checked="checked"' : ''; ?> />
                        </td>
                        <td>
                            <input type="checkbox" name="csv-to-db[fields][<?php echo $key; ?>][ai]" value="1" <?php echo $field['ai']==1 ? 'checked="checked"' : ''; ?> />
                        </td>
                        <td>
                            <select class="indexSelector" name="csv-to-db[fields][<?php echo $key; ?>][index]" style="width:100%" onchange="checkIndex(this.value, <?php echo $key; ?>)">
                                <option></option>
                                <option <?php if ($field['index']=='PRIMARY') echo 'selected'; ?>>PRIMARY</option>
                                <option <?php if ($field['index']=='UNIQUE') echo 'selected'; ?>>UNIQUE</option>
                                <option <?php if ($field['index']=='INDEX') echo 'selected'; ?>>INDEX</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" name="csv-to-db[fields][<?php echo $key; ?>][title]" value="<?php echo $field['title']; ?>" />
                        </td>
                        <td>
                            <input type="checkbox" name="csv-to-db[fields][<?php echo $key; ?>][show]" value="1" <?php echo $field['show']==1 ? 'checked="checked"' : ''; ?> />
                        </td>
                        <td>
                            <select name="csv-to-db[fields][<?php echo $key; ?>][align]" style="width:100%" >
                                <option></option>
                                <option value="left" <?php if ($field['align']=='left') echo 'selected'; ?>><?php _e('Left', 'csv-to-db'); ?></option>
                                <option value="center" <?php if ($field['align']=='center') echo 'selected'; ?>><?php _e('Center', 'csv-to-db'); ?></option>
                                <option value="right" <?php if ($field['align']=='right') echo 'selected'; ?>><?php _e('Right', 'csv-to-db'); ?></option>
                            </select>
                        </td>
                        <td>
                            <input class="checkSelector" type="checkbox" name="csv-to-db[fields][<?php echo $key; ?>][check]" value="1" <?php echo $field['check']==1 ? 'checked="checked"' : ''; ?> onchange="checkOtherCheckboxs(<?php echo $key; ?>)" />
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <p class="submit">
                <input type="submit" class="button-primary pull-left submitBtn" value="<?php _e( 'Save Changes' , 'csv-to-db' ) ?>" data-action="save_fields" data-toggle="tooltip" title="<?php _e( 'Save fields configuration' , 'csv-to-db' ) ?>" />
                <input type="submit" class="button pull-left submitBtn" value="<?php _e( 'Export Fields' , 'csv-to-db' ) ?>" data-action="export_fields" data-toggle="tooltip" title="<?php _e( 'Export fields configuration' , 'csv-to-db' ) ?>" />
                <input type="submit" class="button pull-left submitBtn" value="<?php _e( 'Clear Fields' , 'csv-to-db' ) ?>" data-action="clear_fields" data-toggle="tooltip" title="<?php _e( 'Clear fields' , 'csv-to-db' ) ?>" />
                <input type="submit" class="button pull-right submitBtn" value="<?php _e( 'Create DB Table' , 'csv-to-db' ) ?>" data-action="create_table" data-toggle="tooltip" title="<?php _e( 'Create DB Table from current fields configuration' , 'csv-to-db' ) ?>" />
                <input type="submit" class="button pull-right submitBtn" value="<?php _e( 'Import Schema' , 'csv-to-db' ) ?>" data-action="import_schema" data-toggle="tooltip" title="<?php _e( 'Import DB schema' , 'csv-to-db' ) ?>" />
                <input type="submit" class="button pull-right submitBtn" value="<?php _e( 'Export Schema' , 'csv-to-db' ) ?>" data-action="export_schema" data-toggle="tooltip" title="<?php _e( 'Export DB schema' , 'csv-to-db' ) ?>" />
            </p>
        </form>
    <?php endif; ?>
    <div class="clearfix"></div>
    <hr />
    <form action="" method="post" enctype="multipart/form-data" id="import_fields_form">
        <input type="hidden" name="action" value="import_fields" />
        <h3><?php _e( 'Import Fields' , 'csv-to-db' ); ?></h3>
        <table class="form-table" id="import-fields">
            <tr valign="top">
                <td scope="row" width="200">
                    <?php _e( 'Data File' , 'csv-to-db' ); ?>
                </td>
                <td>
                    <input name="file" type="file" />
                </td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e( 'Import' , 'csv-to-db' ) ?>" id="import_fields_btn" data-toggle="tooltip" title="<?php _e( 'Import fields configuration' , 'csv-to-db' ) ?>" onclick="return confirmImportFields()" />
        </p>
    </form>
    <div id="progress-wrp2" class="progress progress-striped active">
        <div class="progress-bar pbwrapper2" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%"></div>
    </div>
    <div class="clearfix"></div>
    <hr />
    <form action="" method="post" enctype="multipart/form-data" id="upload_form" onsubmit="return false">
        <input type="hidden" name="action" value="analyze_csv" />
        <h3><?php _e( 'Analyze CSV' , 'csv-to-db' ); ?></h3>
        <table class="form-table">
            <tr valign="top">
                <td scope="row" width="200">
                    <?php _e( 'CSV File' , 'csv-to-db' ); ?>
                </td>
                <td>
                    <input name="file" type="file" />
                </td>
            </tr>
        </table>
        <p class="submit">
            <input type="button" class="button-primary" value="<?php _e( 'Analyze' , 'csv-to-db' ) ?>" id="upload_btn" data-toggle="tooltip" title="<?php _e( 'Analyze CSV file and create the fields configuration' , 'csv-to-db' ) ?>" />
        </p>
    </form>
    <div id="progress-wrp" class="progress progress-striped active">
        <div class="progress-bar pbwrapper" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%"></div>
    </div>
</div>
<style>
    .tooltip {
        z-index: 10000;
    }
</style>
<script>
    function confirmImportFields() {
        return confirm("<?php _e( 'Are you sure?' , 'csv-to-db' ) ?>");
    }
    jQuery(document).ready(function(){
        jQuery('[data-toggle="tooltip"]').tooltip();
    });
    jQuery('.submitBtn').on('click', function() {
        var action = jQuery(this).data('action');
        if (action=='create_table' || action=='clear_fields') {
            if (!confirm("<?php _e( 'Are you sure?' , 'csv-to-db' ) ?>")) {
                return false;
            }
        }
        jQuery('input[name=action]').val(action);
    });
    function checkOtherCheckboxs(key) {
        jQuery('.checkSelector').each(function(idx) {
            if (idx!=key) {
                jQuery(this).attr('checked', false);
            }
        });
    }
    function checkIndex(val, key) {
        if (val=='PRIMARY') {
            jQuery('[name="csv-to-db[fields]['+key+'][type]"]').val('INT');
            changeSize('INT', key);
            jQuery('.indexSelector').each(function(idx) {
                if (idx!=key && jQuery(this).val()=='PRIMARY') {
                    jQuery(this).val('');
                }
            });
        }
    }
    function changeSize(val, key) {
        switch (val) {
            case 'TEXT':
            case 'BLOB':
                jQuery('[name="csv-to-db[fields]['+key+'][size]"]').val('');
                break;
            case 'INT':
                jQuery('[name="csv-to-db[fields]['+key+'][size]"]').val('11');
                break;
            case 'FLOAT':
                jQuery('[name="csv-to-db[fields]['+key+'][size]"]').val('7,3');
                break;
            case 'DOUBLE':
                jQuery('[name="csv-to-db[fields]['+key+'][size]"]').val('24,10');
                break;
            case 'DECIMAL':
                jQuery('[name="csv-to-db[fields]['+key+'][size]"]').val('15,4');
                break;
            default:
                jQuery('[name="csv-to-db[fields]['+key+'][size]"]').val('255');
                break;
        }
    }

    var max_file_size 			= <?php echo $maxFileSize; ?>; //allowed file size. (1 MB = 1048576)
    var allowed_file_types 		= ['text/csv','text/plain']; //allowed file types
    var result_output 			= '#output'; //ID of an element for response output
    var total_files_allowed 	= 1; //Number files allowed to upload
    var frm;
    var my_form_id;
    var button_id;
    var progress_bar;
    var progress_bar_wrapper;

    jQuery('#upload_btn').on( "click", function(event) {
        event.preventDefault();
        my_form_id 				= '#upload_form'; //ID of an element for response output
        button_id 			    = '#upload_btn';
        progress_bar 		    = '.pbwrapper';
        progress_bar_wrapper    = '#progress-wrp';
        frm                     = jQuery(my_form_id)[0];
        uploadFile(uploadBtnCallback);
    });

    function uploadBtnCallback(res){
        frm.reset();
        if (res.success) {
            if (res.data) {
                window.location.href = window.location.href;
            }
            if (res.message) {
                jQuery(result_output).html(res.message); //output response from server
            }
        } else {
            jQuery(result_output).addClass('error').html(res.message);
        }
        jQuery(result_output).removeClass('hidden');
        jQuery(button_id).val("<?php _e( 'Upload' , 'csv-to-db' ) ?>").prop( "disabled", false);
        jQuery(progress_bar_wrapper).addClass('hidden');
    }

    jQuery('#import_fields_btn').on( "click", function(event) {
        event.preventDefault();
        my_form_id 				= '#import_fields_form'; //ID of an element for response output
        button_id 			    = '#import_fields_btn';
        progress_bar 		    = '.pbwrapper2';
        progress_bar_wrapper    = '#progress-wrp2';
        frm                     = jQuery(my_form_id)[0];
        uploadFile(importFieldsBtnCallback);
    });

    function importFieldsBtnCallback(res){
        frm.reset();
        if (res.success) {
            if (res.message) {
                jQuery(result_output).html(res.message); //output response from server
                window.location.href = window.location.href;
            }
        } else {
            jQuery(result_output).addClass('error').html(res.message);
        }
        jQuery(result_output).removeClass('hidden');
        jQuery(button_id).val("<?php _e( 'Upload' , 'csv-to-db' ) ?>").prop( "disabled", false);
    }

    function uploadFile(callbackFn) {
        jQuery(result_output).addClass('hidden').removeClass('error');
        jQuery(progress_bar_wrapper).removeClass('hidden');
        jQuery('div.status').html('0%');
        var proceed = true; //set proceed flag
        var error = [];	//errors
        var total_files_size = 0;

        if(!window.File && window.FileReader && window.FileList && window.Blob){ //if browser doesn't supports File API
            error.push("<?php _e( 'Your browser does not support new File API! Please upgrade.' , 'csv-to-db' ) ?>");
        }else{

            var total_selected_files = frm.elements['file'].files.length; //number of files

            //limit number of files allowed
            if(total_selected_files > total_files_allowed){
                error.push("<?php _e('Limit Exceeded!', 'csv-to-db'); ?>");
                proceed = false; //set proceed flag to false
            }
            //iterate files in file input field
            jQuery(frm.elements['file'].files).each(function(i, ifile){
                if(ifile.value !== ""){ //continue only if file(s) are selected
                    if(allowed_file_types.indexOf(ifile.type) === -1){ //check unsupported file
                        error.push("<?php _e('Unsupported File!', 'csv-to-db'); ?>"); //push error text
                        proceed = false; //set proceed flag to false
                    }

                    total_files_size = total_files_size + ifile.size; //add file size to total size
                }
            });

            //if total file size is greater than max file size
            if(total_files_size > max_file_size){
                error.push("<?php _e('File size is too big!', 'csv-to-db'); ?>"); //push error text
                proceed = false; //set proceed flag to false
            }

            //if everything looks good, proceed with jQuery Ajax
            if(proceed && total_files_size>0){
                jQuery(this).val("<?php _e('Please Wait...', 'csv-to-db'); ?>").prop( "disabled", true); //disable submit button
                var form_data = new FormData(frm); //Creates new FormData object

                //jQuery Ajax to Post form data
                jQuery.ajax({
                    url : ajaxurl,
                    type: "POST",
                    data : form_data,
                    dataType: "json",
                    contentType: false,
                    cache: false,
                    processData:false,
                    xhr: function(){
                        //upload Progress
                        var xhr = jQuery.ajaxSettings.xhr();
                        if (xhr.upload) {
                            xhr.upload.addEventListener('progress', function(event) {
                                var percent = 0;
                                var position = event.loaded || event.position;
                                var total = event.total;
                                if (event.lengthComputable) {
                                    percent = Math.ceil(position / total * 100);
                                }
                                //update progressbar
                                jQuery(progress_bar).css('width', percent+'%').attr('aria-valuenow', percent).html(percent+'%');
                            }, true);
                        }
                        return xhr;
                    },
                    mimeType:"multipart/form-data"
                }).done(callbackFn);
            }
        }

        jQuery(result_output).html(""); //reset output
        jQuery(error).each(function(i){ //output any error to output element
            jQuery(result_output).replaceWith('<div id="output" class="updated error">'+error[i]+"</div>");
        });
    }
</script>