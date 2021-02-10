<?php
/**
 * Class to standardize icons.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2021 Lee Garner <lee@leegarner.com>
 * @package     links
 * @version     v3.0.0
 * @since       v3.0.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Links;


/**
 * Class for product and category sales.
 * @package shop
 */
class Icon
{
    /** Icon designations.
     * @var array */
    static $uikit = array(
        'edit'      => 'uk-icon uk-icon-edit',
        'delete'    => 'uk-icon uk-icon-remove uk-text-danger',
        'add'       => 'uk-icon uk-icon-plus-circle uk-text-success',
    );


    /**
     * Get the base icon text for a particular type.
     *
     * @param   string  $str    Key string, index into self::$icons
     * @return  string      Icon string
     */
    public static function getIcon($str)
    {
        global $_SYSTEM;
        static $icons = NULL;    // cache images if used

        $str = strtolower($str);
        if ($_SYSTEM['framework'] == 'uikit') {
            $icons = self::$uikit;
        } else{
            if ($icons === NULL) {
                USES_lib_admin();
                $icons = ADMIN_getIcons();
            }
        }
        if (array_key_exists($str, $icons)) {
            return $icons[$str];
        } else {
            return '';
        }
    }


    /**
     * Get the HTML string for an icon.
     * If the requested icon is not found, returns nothing.
     *
     * @uses    self::getIcon()
     * @param   string  $str    Key string, index into self::$icons
     * @param   string  $cls    Additional class strings to insert
     * @param   array   $extra  Array of any extra HTML to add, e.g. style, etc.
     * @return  string      Complete HTML string to create the icon
     */
    public static function getHTML($str, $cls = '', $extra = array())
    {
        global $_SYSTEM;

        $html = '';
        $icon = self::getIcon($str);
        if ($icon != '') {
            if ($cls != '') {
                // If addition class values are included, add them
                $icon .= ' ' . $cls;
            }
            $extras = '';
            // Assemble the extra HTML, if any, into the string
            foreach ($extra as $key=>$val) {
                $extras .= ' ' . $key . '="' . $val . '"';
            }
            if ($_SYSTEM['framework'] == 'uikit') {
                $html = '<i class="' . $icon . '" ' . $extras . '></i>';
            } else {
                $html = $icon;
            }
        }
        return $html;
    }

}
