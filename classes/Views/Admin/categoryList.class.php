<?php
/**
 * Admin list for categories.
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


class categoryList
{

    public static function Render()
    {
        global $_CONF, $_TABLES, $_USER, $_IMAGE_TYPE, $LANG_ADMIN, $LANG_ACCESS,
           $LANG_LINKS_ADMIN, $LANG_LINKS, $_LI_CONF;

        USES_lib_admin();

        $retval = '';

        $header_arr = array(      # display 'text' and use table field 'field'
            array(
                'text' => $LANG_ADMIN['edit'],
                'field' => 'edit',
                'sort' => false,
                'align' => 'center',
                'width' => '25px',
            ),
            array(
                'text' => $LANG_LINKS_ADMIN[41],
                'field' => 'addchild',
                'sort' => false,
                'align' => 'center',
                'width' => '25px',
            ),
            array(
                'text' => $LANG_LINKS_ADMIN[30],
                'field' => 'category',
                'sort' => true,
            ),
            array(
                'text' => $LANG_LINKS_ADMIN[33],
                'field' => 'tid',
                'sort' => true,
                'align' => 'center',
            ),
            array(
                'text' => $LANG_LINKS_ADMIN[61],
                'field' => 'owner',
                'sort' => true,
                'align' => 'center',
            ),
            array(
                'text' => $LANG_ACCESS['access'],
                'field' => 'access',
                'sort' => false,
                'align' => 'center',
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
                'width' => '25px',
            )
        );


        $defsort_arr = array('field' => 'category', 'direction' => 'asc');
        $text_arr = array(
            'has_extras' => true,
            'form_url'   => $_CONF['site_admin_url'] . '/plugins/links/category.php'
        );
        $dummy = array();
        $data_arr = self::listRecursive($dummy, $_LI_CONF['root'], 0);
        $retval .= ADMIN_simpleList(
            array(__CLASS__, 'getListField'),
            $header_arr, $text_arr, $data_arr
        );
        $retval .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
        return $retval;
    }

    
    public static function getListField($fieldname, $fieldvalue, $A, $icon_arr)
    {
        global $_CONF, $_USER, $_TABLES, $LANG_ACCESS, $LANG_ADMIN, $LANG_LINKS_ADMIN;

        $retval = '';
        static $dt = NULL;
        if ($dt === NULL) {
            $dt = clone $_CONF['_now'];
        }

        switch($fieldname) {
        case 'edit':
            $attr['title'] = $LANG_ADMIN['edit'];
            $retval = COM_createLink(
                Icon::getHTML('edit'),
                Config::get('admin_url') . '/category.php?edit=x&amp;cid=' . urlencode($A['cid']),
                array(
                    'title' => $LANG_ADMIN['edit'],
                )
            );
            break;

        case 'addchild':
            $attr['title'] = $LANG_LINKS_ADMIN[44];
            $retval .= COM_createLink(
                Icon::getHTML('add'),
                Config::get('admin_url') . '/category.php?edit=x&amp;pid=' . urlencode($A['cid']),
                array(
                    'title' => $LANG_LINKS_ADMIN[44],
                )
            );
            break;

        case "owner":
            $retval = COM_getDisplayName ($A['owner_id']);
            break;

        case "unixdate":
            $dt->setTimestamp($A['unixdate']);
            $retval = $dt->format($_CONF['daytime'],true);
            break;

        case 'category':
            $indent = ($A['indent'] - 1) * 20;
            $cat = COM_createLink(
                $A['category'],
                Config::get('url') . '/index.php?mode=list&cid=' . urlencode($A['cid'])
            );
            $retval = "<span style=\"padding-left:{$indent}px;\">$cat</span>";
            break;

        case 'tid';
            if ($fieldvalue == 'all') {
                $retval = $LANG_LINKS_ADMIN[35];
            } else {
                if (!isset($topics[$fieldvalue])) {
                    $topics[$fieldvalue] = DB_getItem($_TABLES['topics'], 'topic', "tid = '{$A['tid']}'");
                }
                $retval = $topics[$fieldvalue];
            }
            if (empty($retval)) {
                $retval = $fieldvalue;
            }
            break;

        case 'delete':
            $attr['title'] = $LANG_ADMIN['delete'];
            $attr['onclick'] = "return confirm('" . $LANG_LINKS_ADMIN[64] . "');";
            $retval = COM_createLink(
                Icon::getHTML('delete'),
                Config::get('admin_url') . '/category.php?delete=x&amp;cid=' . $A['cid'] . '&amp;' . CSRF_TOKEN . '=' . SEC_createToken(),
                array(
                    'title' => $LANG_ADMIN['delete'],
                    'onclick' => "return confirm('" . $LANG_LINKS_ADMIN[64] . "');",
                )
            );
            break;

        default:
            $retval = $fieldvalue;
            break;
        }
        return $retval;
    }


    private static function listRecursive($data_arr, $cid, $indent)
    {
        global $_CONF, $_TABLES, $_LI_CONF, $LANG_LINKS_ADMIN;

        $indent = $indent + 1;
        $cid = DB_escapeString($cid);

        // get all children of present category
        $sql = "SELECT cid,category,tid,UNIX_TIMESTAMP(modified) AS unixdate,owner_id,group_id,perm_owner,perm_group,perm_members,perm_anon "
            . "FROM {$_TABLES['linkcategories']} "
            . "WHERE (pid='{$cid}')" . COM_getPermSQL('AND', 0, 3)
            . "ORDER BY pid,category";
        $result = DB_query($sql);
        $nrows = DB_numRows($result);
        if ($nrows > 0) {
            for ($i = 0; $i < $nrows; $i++) {
                $A = DB_fetchArray($result);
                $topic = DB_getItem($_TABLES['topics'], 'topic', "tid='{$A['tid']}'");
                $A['topic_text'] = $topic;
                $A['indent'] = $indent;
                $data_arr[] = $A;
                if (DB_count($_TABLES['linkcategories'], 'pid', DB_escapeString($A['cid'])) > 0) {
                    $data_arr = self::listRecursive($data_arr, $A['cid'], $indent);
                }
            }
        }
        return $data_arr;
    }

}


