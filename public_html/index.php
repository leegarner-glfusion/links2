<?php
// +--------------------------------------------------------------------------+
// | Links Plugin - glFusion CMS                                              |
// +--------------------------------------------------------------------------+
// | index.php                                                                |
// |                                                                          |
// | This is the main page for the glFusion Links Plugin                      |
// +--------------------------------------------------------------------------+
// |                                                                          |
// | Copyright (C) 2000-2008 by the following authors:                        |
// |                                                                          |
// | Authors: Tony Bibbs        - tony AT tonybibbs DOT com                   |
// |          Mark Limburg      - mlimburg AT users DOT sourceforge DOT net   |
// |          Jason Whittenburg - jwhitten AT securitygeeks DOT com           |
// |          Tom Willett       - tomw AT pigstye DOT net                     |
// |          Trinity Bays      - trinity93 AT gmail DOT com                  |
// |          Dirk Haun         - dirk AT haun-online DOT de                  |
// +--------------------------------------------------------------------------+
// |                                                                          |
// | This program is free software; you can redistribute it and/or            |
// | modify it under the terms of the GNU General Public License              |
// | as published by the Free Software Foundation; either version 2           |
// | of the License, or (at your option) any later version.                   |
// |                                                                          |
// | This program is distributed in the hope that it will be useful,          |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of           |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            |
// | GNU General Public License for more details.                             |
// |                                                                          |
// | You should have received a copy of the GNU General Public License        |
// | along with this program; if not, write to the Free Software Foundation,  |
// | Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.          |
// |                                                                          |
// +--------------------------------------------------------------------------+

/**
 * This is the links page
 *
 * @package Links
 * @subpackage public_html
 * @filesource
 * @version 2.0
 * @since GL 1.4.0
 * @copyright Copyright &copy; 2005-2008
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Tony Bibbs <tony AT tonybibbs DOT com>
 * @author Mark Limburg <mlimburg AT users DOT sourceforge DOT net>
 * @author Jason Whittenburg <jwhitten AT securitygeeks DOT com>
 * @author Tom Willett <tomw AT pigstye DOT net>
 * @author Trinity Bays <trinity93 AT gmail DOT com>
 * @author Dirk Haun <dirk AT haun-online DOT de>
 *
 */

require_once '../lib-common.php';

if (!in_array('links', $_PLUGINS)) {
    COM_404();
    exit;
}

USES_lib_social();

/*

    if ( $_LI_CONF['linksperpage'] == 1 ) {
        $outputHandle = outputHandler::getInstance();
        $outputHandle->addMeta('property','og:site_name',$_CONF['site_name']);
        $outputHandle->addMeta('property','og:locale',isset($LANG_LOCALE) ? $LANG_LOCALE : 'en_US');
        $outputHandle->addMeta('property','og:title',$A['title']);
        $outputHandle->addMeta('property','og:type','website');
        $outputHandle->addMeta('property','og:url',$A['url']);
        if (preg_match('/<img[^>]+src=([\'"])?((?(1).+?|[^\s>]+))(?(1)\1)/si', $linkDesc, $arrResult)) {
            $outputHandle->addMeta('property','og:image',$arrResult[2]);
        }
    }
}
 */

// MAIN
COM_setArgNames(array('mode', 'cid'));
$mode = COM_getArgument('mode');
$cid = COM_applyFilter(COM_getArgument('cid'));
$display = Links\Menu::siteHeader();
switch ($mode) {
case 'submit':
    if (
        COM_isAnonUser() &&
        (
            ($_CONF['loginrequired'] == 1) || ($_CONF['submitloginrequired'] == 1)
        )
    ) {
        $display .= LINKS_siteHeader($LANG_LINKS[114]);
        $display .= SEC_loginRequiredForm();
        $display .= LINKS_siteFooter();
        echo $display;
        exit;
    }
    if (SEC_hasRights ("links.edit") || SEC_hasRights ("links.admin"))  {
        echo COM_refresh(Links\Config::get('admin_url') . '/index.php?edit');
        exit;
    }
    if (Links\Link::canSubmit()) {
        echo COM_refresh(Links\Config::get('url') . '/index.php');
    }

    $slerror = '';
    COM_clearSpeedlimit($_CONF['speedlimit'], 'submit');
    $last = COM_checkSpeedlimit('submit');
    if ($last > 0) {
        $slerror .= COM_showMessageText(
            $LANG12[30] . $last . sprintf($LANG12[31], $_CONF['speedlimit']),
            $LANG12[26],
            true,
            'error'
        );
    }
    if ($slerror != '') {
        echo $slerror;
    } else {
        echo plugin_submit_links();
    }
    echo LINKS_siteFooter();
    break;
case 'list':
default:
    $display = (new Links\Views\publicList())
        ->withCid($cid)
        ->Render();
    break;
}
//$display .= Links\Link::publicLIst($cid);
$display .= Links\Menu::siteFooter();
echo $display;
exit;

$mode = '';
$root = $_LI_CONF['root'];
if (isset ($_REQUEST['mode'])) {
    $mode = $_REQUEST['mode'];
}

$message = array();

if ( $mode == $LANG12[8] && !empty($LANG12[8]) ) {
    $A = array();
    if ( isset($_POST['url']) ) {
        $A['url'] = $_POST['url'];
    }
    if ( isset($_POST['title']) ) {
        $A['title'] = $_POST['title'];
    }
    if ( isset($_POST['description']) ) {
        $A['description'] = $_POST['description'];
    }
    if ( isset($_POST['categorydd']) ) {
        $A['categorydd'] = $_POST['categorydd'];
    }
    echo LINKS_siteHeader();
    echo plugin_savesubmission_links($A);
    echo LINKS_siteFooter();
    exit;
}


if (($mode == 'report') && (isset($_USER['uid']) && ($_USER['uid'] > 1))) {
    if (isset ($_GET['lid'])) {
        $lid = COM_sanitizeID(COM_applyFilter($_GET['lid']));
    }
    if (!empty($lid)) {
        $lidsl = DB_escapeString($lid);
        $result = DB_query("SELECT url, title FROM {$_TABLES['links']} WHERE lid = '$lidsl'");
        list($url, $title) = DB_fetchArray($result);

        $editurl = $_CONF['site_admin_url']
                 . '/plugins/links/index.php?edit=x&lid=' . $lid;
        $msg = $LANG_LINKS[119] . LB . LB . "$title, <$url>". LB . LB
             .  $LANG_LINKS[120] . LB . '<' . $editurl . '>' . LB . LB
             .  $LANG_LINKS[121] . $_USER['username'] . ', IP: '
             . $_SERVER['REMOTE_ADDR'];
        $to = array();
        $to = COM_formatEmailAddress('',$_CONF['site_mail']);
        COM_mail($to, $LANG_LINKS[118], $msg);
        $message = array($LANG_LINKS[123], $LANG_LINKS[122]);
    }
}

if (COM_isAnonUser() && (($_CONF['loginrequired'] == 1) || ($_LI_CONF['linksloginrequired'] == 1))) {
    $display .= LINKS_siteHeader($LANG_LINKS[114]);
    $display .= SEC_loginRequiredForm();
    $display .= LINKS_siteFooter();
    echo $display;
    exit;
} else {
    $display .= links_list($message);
}

$display .= LINKS_siteFooter ();

echo $display;

?>
