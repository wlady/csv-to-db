<div class="wrap ">
    <h2><?php _e( 'CSV to DB', 'csv-to-db' ); ?></h2>
	<?php if ( $this->message ) : ?>
        <div class="updated <?php if ( $error ) {
			echo 'error';
		} ?>">
            <p><?php _e( $this->message ); ?></p>
        </div>
	<?php endif; ?>
    <div id="output" class="updated hidden"></div>
    <form action="" method="post" enctype="multipart/form-data" id="upload_form" onsubmit="return false">
        <input type="hidden" name="action" value="import_csv"/>
        <h3><?php _e( 'CSV Import', 'csv-to-db' ); ?></h3>
        <table class="form-table">
            <tr valign="top">
                <td scope="row" width="200">
					<?php _e( 'CSV File', 'csv-to-db' ); ?>
                </td>
                <td>
                    <input name="file" type="file"/>
                </td>
            </tr>
            <tr valign="top">
                <td scope="row">
					<?php _e( 'Skip first rows', 'csv-to-db' ); ?>
                </td>
                <td>
                    <input type="number" name="skip-rows" value="1" size="100"/>
                </td>
            </tr>
            <tr valign="top">
                <td scope="row">
					<?php _e( 'Re-Create table', 'csv-to-db' ); ?>
                </td>
                <td>
                    <input type="checkbox" name="re-create" value="1" checked="checked"/>
                </td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e( 'Import', 'csv-to-db' ) ?>" id="upload_btn"
                   data-toggle="tooltip" title="<?php _e( 'Import CSV file', 'csv-to-db' ) ?>"/>
        </p>
    </form>
    <div id="progress-wrp" class="progress progress-striped active">
        <div class="progress-bar pbwrapper" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"
             style="width:0%"></div>
    </div>
</div>
<script>
    var labels = {
        are_you_sure: "<?php _e( 'Are you sure?', 'csv-to-db' ) ?>",
        import: "<?php _e( 'Import', 'csv-to-db' ) ?>",
        old_browser: "<?php _e( 'Your browser does not support new File API! Please upgrade.', 'csv-to-db' ) ?>",
        unsupported: "<?php _e( 'Unsupported File!', 'csv-to-db' ); ?>",
        limit_exceeded: "<?php _e( 'Limit Exceeded!', 'csv-to-db' ); ?>",
        file_too_big: "<?php _e( 'File size is too big!', 'csv-to-db' ); ?>",
        wait: "<?php _e( 'Please Wait...', 'csv-to-db' ); ?>"
    };

    var max_file_size = <?php echo $this->upload_max_filesize; ?>; //allowed file size. (1 MB = 1048576)
    var allowed_file_types = ['text/csv', 'text/plain']; //allowed file types
    var result_output = '#output'; //ID of an element for response output
    var total_files_allowed = 1; //Number files allowed to upload
    var frm;
    var my_form_id;
    var button_id;
    var progress_bar;
    var progress_bar_wrapper;

    jQuery(document).ready(function () {
        jQuery('[data-toggle="tooltip"]').tooltip();
    });

    jQuery('#upload_btn').on("click", function (event) {
        event.preventDefault();
        my_form_id = '#upload_form'; //ID of an element for response output
        button_id = '#upload_btn';
        progress_bar = '.pbwrapper';
        progress_bar_wrapper = '#progress-wrp';
        frm = jQuery(my_form_id)[0];
        uploadFile(importItemsBtnCallback);
    });

</script>