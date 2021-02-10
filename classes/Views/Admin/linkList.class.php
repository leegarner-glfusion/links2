<?php
/**
 * Link handler class.
 *
 * @package     Links
 * @version     2.0
 * @since       GL 1.4.0
 * @copyright   Copyright &copy; 2005-2008
 * @license     http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author      Tony Bibbs <tony AT tonybibbs DOT com>
 * @author      Mark Limburg <mlimburg AT users DOT sourceforge DOT net>
 * @author      Jason Whittenburg <jwhitten AT securitygeeks DOT com>
 * @author      Tom Willett <tomw AT pigstye DOT net>
 * @author      Trinity Bays <trinity93 AT gmail DOT com>
 * @author      Dirk Haun <dirk AT haun-online DOT de>
 * @filesource
 *
 */
namespace Links\Views\Admin;
use Links\Config;
use Links\Category;
use Links\Link;
use Links\Icon;
use Template;
use glFusion\Formatter;


class linkList
{
    /**
     * List links.
     *
     * @global array core config vars
     * @global array core table data
     * @global array core user data
     * @global array core lang admin vars
     * @global array links plugin lang vars
     * @global array core lang access vars
     */
    public static function Render($validate)
    {
        global $_CONF, $_TABLES, $LANG_ADMIN, $LANG_LINKS_ADMIN, $LANG_ACCESS,
           $_SYSTEM;

        USES_lib_admin();

        $retval = '';
        $token = SEC_createToken();

        $header_arr = array(
            array(
                'text' => $LANG_ADMIN['edit'],
                'field' => 'edit',
                'sort' => false,
                'align' => 'center',
            ),
            array(
                'text' => $LANG_LINKS_ADMIN[2],
                'field' => 'lid',
                'sort' => true,
            ),
            array(
                'text' => $LANG_ADMIN['title'],
                'field' => 'title',
                'sort' => true,
            ),
            array(
                'text' => $LANG_LINKS_ADMIN[14],
                'field' => 'category',
                'sort' => true,
                'align' => 'center',
            ),
            array(
                'text' => $LANG_LINKS_ADMIN[61],
                'field' => 'owner',
                'sort' => true,
            ),
            array(
                'text' => $LANG_LINKS_ADMIN[62],
                'field' => 'unixdate',
                'sort' => true,
                'align' => 'center',
            ),
            array(
                'text' => $LANG_ADMIN['delete'],
                'field' => 'delete',
                'sort' => false,
                'align' => 'center',
            ),
        );

        $dovalidate_text = $LANG_LINKS_ADMIN[58];
        $validate_link = '';
        $validate_help = '';
        if ($validate == 'enabled') {
            $dovalidate_url = Config::get('admin_url') .
                '/index.php?validate=validate' . '&amp;'.CSRF_TOKEN.'='.$token;
            $attrs = array(
                'style' => 'padding-right:20px;',
            );
            if ($_SYSTEM['framework'] == 'uikit' ) {
                $attrs['class'] = "uk-button uk-button-success";
            }
            $validate_link = COM_createLink(
                $dovalidate_text,
                $dovalidate_url,
                $attrs
            );
            $header_arr[] = array(
                'text' => $LANG_LINKS_ADMIN[27],
                'field' => 'beforevalidate',
                'sort' => false,
                'align' => 'center',
            );
        } elseif ($validate == 'validate') {
            $header_arr[] = array(
                'text' => $LANG_LINKS_ADMIN[27],
                'field' => 'dovalidate',
                'sort' => false,
                'align' => 'center',
            );
        }
        $validate_help = $LANG_LINKS_ADMIN[59];

        $defsort_arr = array('field' => 'title', 'direction' => 'asc');
        $text_arr = array(
            'has_extras' => true,
            'form_url' => Config::get('admin_url') . "/index.php$validate"
        );

        $query_arr = array(
            'table' => 'links',
            'sql' => "SELECT l.*, UNIX_TIMESTAMP(l.date) AS unixdate,
                    c.category AS category
                    FROM {$_TABLES['links']} AS l
                    LEFT JOIN {$_TABLES['linkcategories']} AS c
                    ON l.cid=c.cid WHERE 1=1",
            'query_fields' => array('title', 'category', 'url', 'l.description'),
            'default_filter' => COM_getPermSql('AND', 0, 3, 'l')
        );

        $retval .= ADMIN_list(
            Config::PI_NAME . 'adminList',
            array(__CLASS__, 'getListField'),
            $header_arr,
            $text_arr, $query_arr, $defsort_arr, $validate_link, $token, '', ''
        );
        return $retval;
    }


    public static function getListField($fieldname, $fieldvalue, $A, $icon_arr, $token)
    {
        global $_CONF, $_USER, $LANG_ACCESS, $LANG_LINKS_ADMIN, $LANG_ADMIN;

        $retval = '';
        $dt = new \Date('now', $_USER['tzid']);

        /*$access = SEC_hasAccess(
            $A['owner_id'],$A['group_id'],$A['perm_owner'],$A['perm_group'],$A['perm_members'],$A['perm_anon']);
        if ($access > 0) {*/
            switch($fieldname) {
            case 'edit':
                //if ($access == 3) {
                    $attr['title'] = $LANG_ADMIN['edit'];
                    $retval = COM_createLink(
                        Icon::getHTML('edit'),
                        Config::get('admin_url') . '/index.php?edit=x&amp;lid=' . $A['lid'],
                        $attr);
                //}
                break;

            case "owner":
                $retval = COM_getDisplayName ($A['owner_id']);
                break;

            case 'access':
                //if ($access == 3) {
                   $retval = $LANG_ACCESS['edit'];
                /*} else {
                   $retval = $LANG_ACCESS['readonly'];
                   }*/
                break;

            case "unixdate":
                $dt->setTimestamp($A['unixdate']);
                $retval = $dt->format($_CONF['daytime'],true);
                break;

            case 'title':
                $retval = COM_createLink($A['title'], $A['url'],array('target'=>'_blank'));
                break;

            case 'dovalidate';
                $retval = Link::validateUrl($A['url']);
                break;

            case 'beforevalidate';
                $retval = $LANG_LINKS_ADMIN[57];
                break;

            case 'category':
                if (isset($A['indent'])) {
                    $indent = ($A['indent'] - 1) * 20;
                } else {
                    $indent = 0;
                }
                $cat = COM_createLink(
                    $A['category'],
                    Category::getUrl($A['cid'])
                );
                $retval = "<span style=\"padding-left:{$indent}px;\">$cat</span>";
                break;

            case 'delete':
                $attr['title'] = $LANG_ADMIN['delete'];
                $attr['onclick'] = "return confirm('" . $LANG_LINKS_ADMIN[63] . "');";
                $retval = COM_createLink(
                    Icon::getHTML('delete'),
                    Config::get('admin_url') . '/index.php?delete=x&amp;lid=' .
                        $A['lid'] . '&amp;' . CSRF_TOKEN . '=' . $token,
                    $attr
                );
                break;

            default:
                $retval = $fieldvalue;
                break;
            }
        //}
        return $retval;
    }

}


