function confirmImportFields() {
    return confirm(labels.are_you_sure);
}

function checkOtherCheckboxes(key) {
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

function importItemsBtnCallback(res){
    frm.reset();
    if (res.success) {
        jQuery(result_output).html(res.message); //output response from server
    } else {
        jQuery(result_output).addClass('error').html(res.message);
    }
    jQuery(result_output).removeClass('hidden');
    jQuery(my_button_id).val(labels.import).prop( "disabled", false);
}

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
    jQuery(button_id).val(labels.upload).prop( "disabled", false);
    jQuery(progress_bar_wrapper).addClass('hidden');
}

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
    jQuery(button_id).val(labels.upload).prop( "disabled", false);
}

function uploadFile(callbackFn) {
    jQuery(result_output).addClass('hidden').removeClass('error');
    jQuery(progress_bar_wrapper).removeClass('hidden');
    jQuery('div.status').html('0%');
    var proceed = true; //set proceed flag
    var error = [];	//errors
    var total_files_size = 0;

    if(!window.File && window.FileReader && window.FileList && window.Blob){ //if browser doesn't supports File API
        error.push(labels.old_browser);
    }else{

        var total_selected_files = frm.elements['file'].files.length; //number of files

        //limit number of files allowed
        if(total_selected_files > total_files_allowed){
            error.push(labels.limit_exceeded);
            proceed = false; //set proceed flag to false
        }
        //iterate files in file input field
        jQuery(frm.elements['file'].files).each(function(i, ifile){
            if(ifile.value !== ""){ //continue only if file(s) are selected
                if(allowed_file_types.indexOf(ifile.type) === -1){ //check unsupported file
                    error.push(labels.unsupported); //push error text
                    proceed = false; //set proceed flag to false
                }

                total_files_size = total_files_size + ifile.size; //add file size to total size
            }
        });

        //if total file size is greater than max file size
        if(total_files_size > max_file_size){
            error.push(labels.file_too_big); //push error text
            proceed = false; //set proceed flag to false
        }

        //if everything looks good, proceed with jQuery Ajax
        if(proceed && total_files_size>0){
            jQuery(this).val(labels.wait).prop( "disabled", true); //disable submit button
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