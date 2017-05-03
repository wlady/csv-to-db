<div class="wrap ">
    <div id="icon-options-general" class="icon32"><br /></div>
    <h2><?php _e( 'POI Mapper' , 'poi-mapper' ); ?></h2>
    <form action="options.php" method="post">
        <?php settings_fields ( 'poi-mapper' ); ?>
        <h3><?php _e( 'CSV Import Options' , 'poi-mapper' ); ?></h3>
        <table class="form-table">
            <tr valign="top">
                <td scope="row" width="200">
                    <?php _e( 'Use LOCAL' , 'poi-mapper' ); ?>
                </td>
                <td>
                    <input type="checkbox" name="poi-mapper[use-local]" value="1" <?php echo ($this->get_option('use-local') ? 'checked="checked"' : '' )?> />
                </td>
            </tr>
            <tr valign="top">
                <td scope="row">
                    <?php _e( 'Fields Terminated By' , 'poi-mapper' ); ?>
                </td>
                <td>
                    <input type="text" name="poi-mapper[fields-terminated]" value="<?php echo htmlspecialchars($this->get_option('fields-terminated'), ENT_QUOTES); ?>" size="100" />
                </td>
            </tr>
            <tr valign="top">
                <td scope="row">
                    <?php _e( 'Fields Enclosed By' , 'poi-mapper' ); ?>
                </td>
                <td>
                    <input type="text" name="poi-mapper[fields-enclosed]" value="<?php echo htmlspecialchars($this->get_option('fields-enclosed'), ENT_QUOTES); ?>" size="100" />
                </td>
            </tr>
            <tr valign="top">
                <td scope="row">
                    <?php _e( 'Fields Escaped By' , 'poi-mapper' ); ?>
                </td>
                <td>
                    <input type="text" name="poi-mapper[fields-escaped]" value="<?php echo htmlspecialchars($this->get_option('fields-escaped'), ENT_QUOTES); ?>" size="100" />
                </td>
            </tr>
            <tr valign="top">
                <td scope="row">
                    <?php _e( 'Lines Starting By' , 'poi-mapper' ); ?>
                </td>
                <td>
                    <input type="text" name="poi-mapper[lines-starting]" value="<?php echo $this->get_option('lines-starting'); ?>" size="100" />
                </td>
            </tr>
            <tr valign="top">
                <td scope="row">
                    <?php _e( 'Lines Terminated By' , 'poi-mapper' ); ?>
                </td>
                <td>
                    <input type="text" name="poi-mapper[lines-terminated]" value="<?php echo htmlspecialchars($this->get_option('lines-terminated'), ENT_QUOTES); ?>" size="100" />
                </td>
            </tr>
        </table>
        <h3><?php _e( 'Other Options' , 'poi-mapper' ); ?></h3>
        <table class="form-table">
            <tr valign="top">
                <td scope="row" width="200">
                    <?php _e( 'Google Map API Key' , 'poi-mapper' ); ?>
                </td>
                <td>
                    <input type="text" name="poi-mapper[gmap-key]" value="<?php echo $this->get_option('gmap-key'); ?>" />
                </td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e( 'Save Changes' , 'poi-mapper' ) ?>" />
            <input type="submit" name="wp-poi-mapper-defaults" id="wp-poi-mapper-defaults" class="button-primary" value="<?php _e( 'Reset to Defaults' , 'poi-mapper' ) ?>" onclick="return confirmResetPoiMapperData()" />
        </p>
    </form>
</div>
<script>
    function confirmResetPoiMapperData() {
        return confirm("<?php _e( 'Are you sure to reset POI Mapper settings?' , 'poi-mapper' ) ?>");
    }
</script>