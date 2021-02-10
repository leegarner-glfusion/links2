<?php
// +--------------------------------------------------------------------------+
// | Links Plugin - glFusion CMS                                              |
// +--------------------------------------------------------------------------+
// | category.php                                                             |
// |                                                                          |
// | glFusion links category administration page.                             |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2000-2008 by the following authors:                        |
// |                                                                          |
// | Authors: Tony Bibbs        - tony AT tonybibbs DOT com                   |
// |          Mark Limburg      - mlimburg AT users.sourceforge DOT net       |
// |          Jason Whittenburg - jwhitten AT securitygeeks DOT com           |
// |          Dirk Haun         - dirk AT haun-online DOT de                  |
// |          Euan McKay        - info AT heatherengineering DOT com          |
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

require_once '../../../lib-common.php';
require_once '../../auth.inc.php';

// Uncomment the line below if you need to debug the HTTP variables being passed
// to the script.  This will sometimes cause errors but it will allow you to see
// the data being passed in a POST operation
// echo COM_debug($_POST);

$display = '';

if (!SEC_hasRights('links.edit')) {
    $display .= COM_siteHeader ('menu');
    $display .= COM_startBlock ($MESSAGE[30], '',
                                COM_getBlockTemplate ('_msg_block', 'header'));
    $display .= $MESSAGE[34];
    $display .= COM_endBlock (COM_getBlockTemplate ('_msg_block', 'footer'));
    $display .= COM_siteFooter ();
    COM_accessLog("User {$_USER['username']} tried to illegally access the link administration screen.");
    echo $display;
    exit;
}


// MAIN ========================================================================

$action = '';
$expected = array('edit','save','delete','cancel');
foreach($expected as $provided) {
    if (isset($_POST[$provided])) {
        $action = $provided;
    } elseif (isset($_GET[$provided])) {
	$action = $provided;
    }
}

$cid = '';
if (isset($_POST['cid'])) {
    $cid = COM_applyFilter($_POST['cid']);
} elseif (isset($_GET['cid'])) {
    $cid = COM_applyFilter($_GET['cid']);
}

$pid = '';
if (isset($_POST['pid'])) {
    $pid = COM_applyFilter($_POST['pid']);
} elseif (isset($_GET['pid'])) {
    $pid = COM_applyFilter($_GET['pid']);
}

$msg = (isset($_GET['msg'])) ? COM_applyFilter($_GET['msg']) : '';

switch ($action) {
case 'edit':
    $display .= Links\Menu::siteHeader('menu', $LANG_LINKS_ADMIN[56]);
    $Cat = new Links\Category($cid);
    if ($Cat->getCid() == '') {     // new category, force the parent ID
        $Cat->withPid($pid);
    }
    $display .= $Cat->edit();
    $display .= Links\Menu::siteFooter();
    break;

case 'save':
    $Cat = new Links\Category($_POST['old_cid']);
    $status = $Cat->save($_POST);
    if ($status) {
        COM_setMsg($PLG_links_MESSAGE10);
        COM_refresh(Links\Config::get('admin_url') . '/category.php');
    } else {
        COM_setMsg("ERROR");
        COM_refresh(Links\Config::get('admin_url') . '/category.php?edit=x&cid=' . $_POST['old_cid']);
    }
    break;

case 'delete':
    if (!isset($cid) || empty($cid)) {
        COM_errorLog('User ' . $_USER['username'] . ' attempted to delete link category, cid is null');
        $display .= COM_refresh ($_CONF['site_admin_url'] . '/plugins/links/category.php');
    } else {
        $Cat = new Links\Category($cid);
        $msg = $Cat->delete();
        COM_refresh(Links\Config::get('admin_url') . '/category.php?msg=' . $msg);
    }
    break;

default:
    $display .= Links\Menu::siteHeader('menu', $LANG_LINKS_ADMIN[11]);
    if (isset($msg)) {
        $display .= (is_numeric($msg)) ? COM_showMessage($msg, 'links') : COM_showMessageText( $msg );
    }
    $display .= Links\Menu::Admin('categories');
    $display .= Links\Views\Admin\categoryList::Render();
    $display .= Links\Menu::siteFooter();
    break;
}

echo $display;

?>
