<?php
/**
 * Created by PhpStorm.
 * User: wlady2001
 * Date: 05.05.17
 * Time: 12:28
 */

namespace CSV2DB\Models;

class Table
{
    const TABLE_NAME = 'csv_to_db';

    /**
     * Create DB table from saved fields settings
     * @return string/bool On error returns error message
     */
    public static function createTable($fields)
    {
        global $wpdb;

        $wpdb->query('DROP TABLE IF EXISTS `' . $wpdb->get_blog_prefix() . self::TABLE_NAME . '`');
        try {
            $schema = self::createSchema($fields);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
        $wpdb->query($schema);

        return $wpdb->last_error !== '' ? $wpdb->last_error : true;
    }

    /**
     * @param $fields
     * @return string
     * @throws \Exception
     */
    public static function createSchema($fields)
    {
        global $wpdb;

        $columns = array();
        $indexes = array();
        foreach ($fields as $field) {
            $type = $field['type'];
            $null = $field['null'] == 0 ? 'NOT NULL' : 'NULL';
            $ai = $field['ai'] == 1 ? 'AUTO_INCREMENT' : '';
            if (!in_array($type, array('TEXT', 'BLOB'))) {
                $type = "{$field['type']}({$field['size']}) {$null} {$ai}";
            }
            $columns[] = "`{$field['name']}` {$type}";
            if (!empty($field['index'])) {
                if ($field['index'] == 'PRIMARY') {
                    $curIndex = 'PRIMARY KEY ';
                } else if ($field['index'] == 'UNIQUE') {
                    $curIndex = "UNIQUE KEY `{$field['name']}`";
                } else {
                    $curIndex = "KEY `{$field['name']}`";
                }
                $indexes[] = $curIndex . "(`{$field['name']}`)";
            }
        }
        if (count($indexes)) {
            $columns = array_merge($columns, $indexes);
        }
        if (!count($columns)) {
            throw new \Exception(\__('Column configuration is empty', 'csv-to-db'));
        }

        return 'CREATE TABLE IF NOT EXISTS `' . $wpdb->get_blog_prefix() . self::TABLE_NAME . '` (' . implode(',', $columns) . ')';
    }

    /**
     * @param $fileName
     * @return mix On error returns error message
     */
    public static function importFile($fileName, $options)
    {
        global $wpdb;

        $use_local = $options['use-local'] == 1 ? 'LOCAL' : '';
        $fields_params = array();
        $lines_params = array();
        if (!empty($options['fields-terminated'])) {
            $fields_params[] = 'TERMINATED BY \'' . $options['fields-terminated'] . '\'';
        }
        if (!empty($options['fields-enclosed'])) {
            $symbol = $options['fields-enclosed'];
            if (in_array($symbol, array('"', "'"))) {
                $fields_params[] = 'ENCLOSED BY \'\\' . $symbol . '\'';
            }
        }
        if (!empty($options['fields-escaped'])) {
            $fields_params[] = 'ESCAPED BY \'' . $options['fields-escaped'] . '\'';
        }
        if (!empty($options['lines-starting'])) {
            $lines_params[] = 'STARTING BY \'' . $options['lines-starting'] . '\'';
        }
        if (!empty($options['lines-terminated'])) {
            $lines_params[] = 'TERMINATED BY \'' . $options['lines-terminated'] . '\'';
        }
        $query = 'LOAD DATA ' . $use_local . ' INFILE \'' . $fileName . '\' INTO TABLE `' . $wpdb->get_blog_prefix() . self::TABLE_NAME . '`';
        if (count($fields_params)) {
            $query .= ' FIELDS ' . implode(' ', $fields_params);
        }
        if (count($lines_params)) {
            $query .= ' LINES ' . implode(' ', $lines_params);
        }
        if (intval($_POST['skip-rows']) > 0) {
            $query .= ' IGNORE ' . intval($_POST['skip-rows']) . ' LINES';
        }
        $wpdb->query($query);

        return $wpdb->last_error !== '' ? $wpdb->last_error : true;
    }

    /**
     * @param $columns
     * @param $records
     * @return array
     */
    public static function convertFields($columns, $records)
    {
        $rows = array_map(function ($item) use ($columns) {
            $row = (array)$item;
            foreach ($row as $field => $value) {
                $column = array_filter($columns, function ($col) use ($field) {
                    return $col['name'] == $field ? $col : null;
                });
                $col = array_pop($column);
                switch ($col['type']) {
                    case 'INT':
                        $item->{$field} = (int)$value;
                        break;
                    case 'FLOAT':
                        $item->{$field} = (float)$value;
                        break;
                    case 'DOUBLE':
                    case 'DECIMAL':
                        $item->{$field} = (double)$value;
                        break;
                }
                if ($col['index'] == 'PRIMARY') {
                    $item->id = (int)$value;
                }
            }
            return $item;
        }, $records);

        return $rows;
    }

    /**
     * @param $columns
     * @param $fields
     * @param int $start
     * @param int $limit
     * @param string $order
     * @return array
     */
    public static function getItems($columns, $fields, $start = 0, $limit = 10, $order = 'asc')
    {
        global $wpdb;

        $res = $wpdb->get_results('SELECT SQL_CALC_FOUND_ROWS `' . implode('`,`', $fields) . '` FROM `' . $wpdb->get_blog_prefix() . self::TABLE_NAME . '` LIMIT ' . "{$start}, {$limit}");
        $total = $wpdb->get_var('SELECT FOUND_ROWS() AS total');
        $rows = self::convertFields($columns, $res);

        return array($total, $rows);
    }

    /**
     * Called on uninstall
     */
    public static function dropTables()
    {
        global $wpdb;

        $wpdb->query('DROP TABLE IF EXISTS `' . $wpdb->get_blog_prefix() . self::TABLE_NAME . '`');
    }
}