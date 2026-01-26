<?php

namespace Cookbook\Helpers;

use Cookbook\DB\Database;

/**
 * Description of ListOptions
 *
 * @author AlexK
 */
class ListOptions
{
    static function category(array $ids = [])
    {
        Database::dsn()->exec('SET NAMES \'utf8\'');
        $data = Database::dsn()->select('SELECT category_id, category_name FROM category ORDER BY category_name');
        
        if (!$data) {
            return null;
        }
        
        $list = '';
        
        foreach ($data as $value) {
            $selected = in_array($value['category_id'], $ids) ? ' selected' : '';
            $list .= '<option value="' . $value['category_id'] . '"' . $selected . '>' . $value['category_name'] . '</option>';
        }
        
        return $list;
    }
    
    static function categoryBadge($location = 'recipe',  $id = null)
    {
        Database::dsn()->exec('SET NAMES \'utf8\'');
        $data = Database::dsn()->select('SELECT category_id, category_name FROM category ORDER BY category_name');
        
        if (!$data) {
            return null;
        }
        
        $list = '';
        
        foreach ($data as $value) {
            if ($value['category_id'] == $id) {
                $list .= '<span class="lead"><a href="/' . $location . '/?c=' . $value['category_id'] . '" class="badge badge-success">' . $value['category_name'] . '</a></span>&nbsp;';
            } else {
                $list .= '<span class="lead"><a href="/' . $location . '/?c=' . $value['category_id'] . '" class="badge badge-info">' . $value['category_name'] . '</a></span>&nbsp;';
            }
        }
        
        return $list;
    }
    
    static function unit(array $ids = [], $yieldType = false)
    {
        if ($yieldType == 'Cover Count') {
            $data = Database::dsn()->select('SELECT unit_id, unit_name, unit FROM units WHERE unit_name LIKE ? ORDER BY unit_id', ['Portions']);
        }
        elseif ($yieldType) {
            $data = Database::dsn()->select('SELECT unit_id, unit_name, unit FROM units WHERE unit_name <> \'\' AND type = ? ORDER BY unit_id', [$yieldType]);
        } else {
            $data = Database::dsn()->select('SELECT unit_id, unit_name, unit FROM units WHERE unit_name <> \'\' ORDER BY unit_id');
        }
        
        if (!$data) {
            return null;
        }
        
        $list = '';
        
        foreach ($data as $value) {
            $selected = in_array($value['unit_id'], $ids) ? ' selected' : '';
            $list .= '<option short-name="' . $value['unit'] . '" value="' . $value['unit_id'] . '"' . $selected . '>' . $value['unit_name'] . '</option>';
        }
        
        return $list;
    }
    
    static function equipment(array $ids = [])
    {
        $data = Database::dsn()->select('SELECT equipment_id, equipment FROM equipment WHERE equipment <> \'\' ORDER BY equipment');
        
        if (!$data) {
            return null;
        }
        
        $list = '';
        
        foreach ($data as $value) {
            $selected = in_array($value['equipment_id'], $ids) ? ' selected' : '';
            $list .= '<option value="' . $value['equipment_id'] . '"' . $selected . '>' . $value['equipment'] . '</option>';
        }
        
        return $list;
    }

    static function yield(array $ids = [])
    {
        $data = Database::dsn()->select('SELECT DISTINCT `type` FROM units WHERE `type` <> ?', ['']);

        if (! $data) {
            return null;
        }

        $list = '';

        foreach ($data as $value) {
            $selected = in_array($value['type'], $ids) ? ' selected' : '';
            $list .= '<option value="' . $value['type'] . '"' . $selected . '>' . $value['type'] . '</option>';
        }

        return $list;
    }

    static function restrictionType(array $ids = [])
    {
        $data = Database::dsn()->selectRow('SELECT SUBSTRING(COLUMN_TYPE, 5) AS setField FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=\'' . DATABASE_DEFAULT . '\' AND TABLE_NAME=\'dietary_restrictions\' AND COLUMN_NAME=\'type\'');

        if (!$data) {
            return null;
        }

        preg_match_all("/[^\'),]+/", $data['setField'], $matches);
        $list = '';

        foreach ($matches[0] as $value) {
            $selected = in_array($value, $ids) ? ' selected' : '';
            $list .= '<option value="' . $value . '"' . $selected . '>' . ucfirst($value) . '</option>';
        }

        return $list;
    }
}