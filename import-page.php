<div class="wrap ">
    <div id="icon-options-general" class="icon32"><br /></div>
    <h2><?php _e( 'CSV to DB' , 'csv-to-db' ); ?></h2>
    <?php if ($message) : ?>
        <div class="updated <?php if ($error) echo 'error'; ?>">
            <p><?php _e($message); ?></p>
        </div>
    <?php endif; ?>
    <div id="output" class="updated hidden"></div>
    <form action="" method="post" enctype="multipart/form-data" id="upload_form" onsubmit="return false">
        <input type="hidden" name="action" value="import_csv" />
        <h3><?php _e( 'CSV Import' , 'csv-to-db' ); ?></h3>
        <table class="form-table">
            <tr valign="top">
                <td scope="row" width="200">
                    <?php _e( 'CSV File' , 'csv-to-db' ); ?>
                </td>
                <td>
                    <input name="file" type="file" />
                </td>
            </tr>
            <tr valign="top">
                <td scope="row">
                    <?php _e( 'Skip first rows' , 'csv-to-db' ); ?>
                </td>
                <td>
                    <input type="number" name="skip-rows" value="1" size="100" />
                </td>
            </tr>
            <tr valign="top">
                <td scope="row">
                    <?php _e( 'Re-create table' , 'csv-to-db' ); ?>
                </td>
                <td>
                    <input type="checkbox" name="re-create" value="1" />
                </td>
            </tr>
        </table>
        <p class="submit">
            <img src="images/loading.gif" id="loading-img" style="display:none;" alt="Please Wait"/>
            <input type="submit" class="button-primary" value="<?php _e( 'Upload' , 'csv-to-db' ) ?>" id="upload_btn" />
        </p>
    </form>
    <div id="progress-wrp" class="progress progress-striped active">
        <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%"></div>
    </div>
</div>
<script>
    var max_file_size 			= <?php echo $maxFileSize; ?>; //allowed file size. (1 MB = 1048576)
    var allowed_file_types 		= ['text/csv','application/csv']; //allowed file types
    var result_output 			= '#output'; //ID of an element for response output
    var my_form_id 				= '#upload_form'; //ID of an element for response output
    var my_button_id 			= '#upload_btn';
    var total_files_allowed 	= 1; //Number files allowed to upload
    var progress_bar 		    = '.progress-bar'; //ID of an element for response output

    //on form submit
    jQuery(my_button_id).on( "click", function(event) {
        event.preventDefault();
        jQuery(result_output).addClass('hidden').removeClass('error');
        jQuery('div.status').html('0%');
        var proceed = true; //set proceed flag
        var error = [];	//errors
        var total_files_size = 0;

        if(!window.File && window.FileReader && window.FileList && window.Blob){ //if browser doesn't supports File API
            error.push("<?php _e( 'Your browser does not support new File API! Please upgrade.' , 'csv-to-db' ) ?>");
        }else{
            var frm = jQuery(my_form_id)[0];
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
                }).done(function(res){
                    frm.reset();
                    if (res.success) {
                        jQuery(result_output).html(res.message); //output response from server
                    } else {
                        jQuery(result_output).addClass('error').html(res.message);
                    }
                    jQuery(result_output).removeClass('hidden');
                    jQuery(my_button_id).val("<?php _e( 'Upload' , 'csv-to-db' ) ?>").prop( "disabled", false);
                });
            }
        }

        jQuery(result_output).html(""); //reset output
        jQuery(error).each(function(i){ //output any error to output element
            jQuery(result_output).replaceWith('<div id="output" class="updated error">'+error[i]+"</div>");
        });
    });

</script>