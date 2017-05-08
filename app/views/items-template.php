<div class="wrap ">
    <div id="icon-options-general" class="icon32"><br/></div>
    <h2><?php _e( 'CSV to DB Items', 'csv-to-db' ); ?></h2>
    <table id="table"
           data-toggle="table"
           data-ajax="ajaxRequest"
           data-show-refresh="true"
           data-show-columns="true"
           data-show-export="true"
           data-id-field="<?php echo $this->data_id_field; ?>"
           data-striped="true"
           data-side-pagination="server"
           data-pagination="true"
           data-page-list="[5, 10, 20, 50, 100, 200]"
           data-search="true">
        <thead>
        <tr>
			<?php foreach ( $data['columns'] as $column ) : ?>
                <th data-field="<?php echo $column['name']; ?>"
				    <?php if ( isset( $column['check'] ) ) : ?>data-checkbox="true"<?php endif; ?>
                    data-align="<?php echo $column['align']; ?>"><?php echo $column['title']; ?></th>
			<?php endforeach; ?>
        </tr>
        </thead>
    </table>
</div>
<script>
    var $table = jQuery('#table'),
        $remove = jQuery('#remove'),
        selections;

    $table.on('check.bs.table uncheck.bs.table ' +
        'check-all.bs.table uncheck-all.bs.table', function () {
        $remove.prop('disabled', !$table.bootstrapTable('getSelections').length);
        selections = getIdSelections();
    });

    // your custom ajax request here
    function ajaxRequest(params) {
        params.data['action'] = 'get_items';
        var jsonDataCall = jQuery.ajax({
            url: ajaxurl,
            method: "POST",
            dataType: "json",
            data: params.data,
            success: function (response) {
                params.success(response);
            }
        });
    }

    function getIdSelections() {
        return jQuery.map($table.bootstrapTable('getSelections'), function (row) {
            return row.id
        });
    }
</script>