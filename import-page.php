		<div class="wrap ">
			<div id="icon-options-general" class="icon32"><br /></div>
			<h2><?php _e( 'POI Mapper' , 'poi-mapper' ); ?></h2>
            <?php if ($message) : ?>
                <div class="updated <?php if ($error) echo 'error'; ?>">
                    <p><?php _e($message); ?></p>
                </div>
            <?php endif; ?>
            <div id="output" class="updated hidden"></div>
			<form action="" method="post" enctype="multipart/form-data" id="upload_form" onsubmit="return false">
                <input type="hidden" name="action" value="import_csv" />
                <h3><?php _e( 'CSV Import' , 'poi-mapper' ); ?></h3>

				<table class="form-table">
                    <tr valign="top">
                        <td scope="row">
                            <?php _e( 'CSV File' , 'poi-mapper' ); ?>
                        </td>
                        <td>
                            <input name="file" type="file" />
                        </td>
                    </tr>
					<tr valign="top">
						<td scope="row" width="200">
							<?php _e( 'Use LOCAL' , 'poi-mapper' ); ?>
						</td>
						<td>
							<input type="checkbox" name="use-local" value="1" <?php echo ($this->get_option('use-local') ? 'checked="checked"' : '' )?> />
						</td>
					</tr>
					<tr valign="top">
						<td scope="row">
							<?php _e( 'Skip first rows' , 'poi-mapper' ); ?>
						</td>
						<td>
                            <input type="number" name="skip-rows" value="<?php echo $this->get_option('skip-rows'); ?>" size="100" />
						</td>
					</tr>

				</table>
				<p class="submit">
                    <img src="images/loading.gif" id="loading-img" style="display:none;" alt="Please Wait"/>
					<input type="submit" class="button-primary" value="<?php _e( 'Upload' , 'poi-mapper' ) ?>" id="upload_btn" />
				</p>
			</form>
            <div id="progress-wrp"><div class="progress-bar"></div ><div class="status">0%</div></div>
		</div>
<script>
    function confirmResetPoiMapperData() {
        return confirm("<?php _e( 'Are you sure to reset POI Mapper settings?' , 'poi-mapper' ) ?>");
    }

    var max_file_size 			= <?php echo $maxFileSize; ?>; //allowed file size. (1 MB = 1048576)
    var allowed_file_types 		= ['text/csv','application/csv']; //allowed file types
    var result_output 			= '#output'; //ID of an element for response output
    var my_form_id 				= '#upload_form'; //ID of an element for response output
    var my_button_id 			= '#upload_btn';
    var total_files_allowed 	= 1; //Number files allowed to upload
    var progress_bar_id 		= '#progress-wrp'; //ID of an element for response output

    //on form submit
    jQuery(my_button_id).on( "click", function(event) {
        event.preventDefault();
        jQuery(result_output).addClass('hidden').removeClass('error');
        jQuery('div.status').html('0%');
        var proceed = true; //set proceed flag
        var error = [];	//errors
        var total_files_size = 0;

        if(!window.File && window.FileReader && window.FileList && window.Blob){ //if browser doesn't supports File API
            error.push("<?php _e( 'Your browser does not support new File API! Please upgrade.' , 'poi-mapper' ) ?>");
        }else{
            var frm = jQuery(my_form_id)[0];
            var total_selected_files = frm.elements['file'].files.length; //number of files

            //limit number of files allowed
            if(total_selected_files > total_files_allowed){
                error.push("<?php _e('Limit Exceeded!', 'poi-mapper'); ?>");
                proceed = false; //set proceed flag to false
            }
            //iterate files in file input field
            jQuery(frm.elements['file'].files).each(function(i, ifile){
                if(ifile.value !== ""){ //continue only if file(s) are selected
                    if(allowed_file_types.indexOf(ifile.type) === -1){ //check unsupported file
                        error.push("<?php _e('Unsupported File!', 'poi-mapper'); ?>"); //push error text
                        proceed = false; //set proceed flag to false
                    }

                    total_files_size = total_files_size + ifile.size; //add file size to total size
                }
            });

            //if total file size is greater than max file size
            if(total_files_size > max_file_size){
                error.push("<?php _e('File size is too big!', 'poi-mapper'); ?>"); //push error text
                proceed = false; //set proceed flag to false
            }

            //if everything looks good, proceed with jQuery Ajax
            if(proceed && total_files_size>0){
                jQuery(this).val("<?php _e('Please Wait...', 'poi-mapper'); ?>").prop( "disabled", true); //disable submit button
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
                                jQuery(progress_bar_id +" .progress-bar").css("width", + percent +"%");
                                jQuery(progress_bar_id + " .status").text(percent +"%");
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
                    jQuery(my_button_id).val("<?php _e( 'Upload' , 'poi-mapper' ) ?>").prop( "disabled", false);
                });
            }
        }

        jQuery(result_output).html(""); //reset output
        jQuery(error).each(function(i){ //output any error to output element
            jQuery(result_output).replaceWith('<div id="output" class="updated error">'+error[i]+"</div>");
        });

        //function to format bites bit.ly/19yoIPO
        function bytesToSize(bytes) {
            var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            if (bytes == 0) return '0 Bytes';
            var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
            return Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[i];
        }
    });

</script>