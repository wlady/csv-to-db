<div class="wrap ">
    <div id="icon-options-general" class="icon32"><br /></div>
    <h2><?php _e( 'POI Mapper Items' , 'poi-mapper' ); ?></h2>
    <table id="table"
           data-toggle="table"
           data-ajax="ajaxRequest"
           data-height="400"
           data-side-pagination="server"
           data-pagination="true"
           data-page-list="[5, 10, 20, 50, 100, 200]"
           data-search="true">
        <thead>
        <tr>
            <?php foreach ($columns as $column) : ?>
                <th data-field="<?php echo $column['name']; ?>" <?php if ($column['index']=='PRIMARY') : ?>data-checkbox="true"<?php endif; ?> style="text-align: <?php echo $column['align']; ?>;"><?php echo $column['title']; ?></th>
            <?php endforeach; ?>
        </tr>
        </thead>
    </table>
</div>
<script>
    var $table = jQuery('#table');

    // your custom ajax request here
    function ajaxRequest(params) {
        params.data['action'] = 'items_paginated';
        jQuery.post(ajaxurl, params.data, function(response) {
            alert('Got this from the server: ' + response);
        });
        /*
        // data you need
        console.log(params.data);
        // just use setTimeout
        setTimeout(function () {
            params.success({
                total: 100,
                rows: [{
                    "id": 0,
                    "name": "Item 0",
                    "price": "$0"
                }]
            });
        }, 1000);
        */
    }
</script>