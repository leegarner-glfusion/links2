<?php
/**
 * glFusion links administration page.
 *
 * @package Links
 * @subpackage admin
 * @filesource
 * @version 2.0
 * @since GL 1.4.0
 * @copyright Copyright &copy; 2005-2007
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @author Trinity Bays <trinity93@gmail.com>
 * @author Tony Bibbs <tony@tonybibbs.com>
 * @author Tom Willett <twillett@users.sourceforge.net>
 * @author Blaine Lang <langmail@sympatico.ca>
 * @author Dirk Haun <dirk@haun-online.de>
 */

require_once '../../../lib-common.php';
require_once '../../auth.inc.php';

// Uncomment the lines below if you need to debug the HTTP variables being passed
// to the script.  This will sometimes cause errors but it will allow you to see
// the data being passed in a POST operation
// echo COM_debug($_POST);
// exit;

$display = '';

if (!SEC_hasRights ('links.edit')) {
    $display .= COM_siteHeader ('menu', $MESSAGE[30]);
    $display .= COM_startBlock ($MESSAGE[30], '',
                                COM_getBlockTemplate ('_msg_block', 'header'));
    $display .= $MESSAGE[34];
    $display .= COM_endBlock (COM_getBlockTemplate ('_msg_block', 'footer'));
    $display .= COM_siteFooter ();
    COM_accessLog ("User {$_USER['username']} tried to access the links administration screen.");
    echo $display;
    exit;
}

/**
* Shows the links editor
*
* @param  string  $action   'edit' or 'moderate'
* @param  string  $lid    ID of link to edit
* @global array core config vars
* @global array core group data
* @global array core table data
* @global array core user data
* @global array links plugin config vars
* @global array links plugin lang vars
* @global array core lang access vars
* @return string HTML for the link editor form
*
*/
function XXLINK_edit($action, $lid = '')
{
    global $_CONF, $_GROUPS, $_TABLES, $_USER, $_LI_CONF,
           $LANG_LINKS_ADMIN, $LANG_ACCESS, $LANG_ADMIN, $MESSAGE;

    USES_lib_admin();

    $retval = '';
    $editFlag = false;

    switch ($action) {
        case 'edit':
            $blocktitle = $LANG_LINKS_ADMIN[1];     // Link Editor
            $saveoption = $LANG_ADMIN['save'];      // Save
            break;
        case 'moderate':
            $blocktitle = $LANG_LINKS_ADMIN[65];    // Moderate Link
            $saveoption = $LANG_ADMIN['moderate'];  // Save & Approve
            break;
    }

    $link_templates = new Template($_CONF['path'] . 'plugins/links/templates/admin/');
    $link_templates->set_file('editor','linkeditor.thtml');

    $link_templates->set_var('lang_pagetitle', $LANG_LINKS_ADMIN[28]);
    $link_templates->set_var('lang_link_list', $LANG_LINKS_ADMIN[53]);
    $link_templates->set_var('lang_new_link', $LANG_LINKS_ADMIN[51]);
    $link_templates->set_var('lang_validate_links', $LANG_LINKS_ADMIN[26]);
    $link_templates->set_var('lang_list_categories', $LANG_LINKS_ADMIN[50]);
    $link_templates->set_var('lang_new_category', $LANG_LINKS_ADMIN[52]);
    $link_templates->set_var('lang_admin_home', $LANG_ADMIN['admin_home']);
    $link_templates->set_var('instructions', $LANG_LINKS_ADMIN[29]);

    if ($action <> 'moderate' AND !empty($lid)) {
        $result = DB_query("SELECT * FROM {$_TABLES['links']} WHERE lid ='$lid'");
        if (DB_numRows($result) !== 1) {
            $msg = COM_startBlock ($LANG_LINKS_ADMIN[24], '',
                COM_getBlockTemplate ('_msg_block', 'header'));
            $msg .= $LANG_LINKS_ADMIN[25];
            $msg .= COM_endBlock (COM_getBlockTemplate ('_msg_block', 'footer'));
            return $msg;
        }
        $A = DB_fetchArray($result);
        $access = SEC_hasAccess($A['owner_id'],$A['group_id'],$A['perm_owner'],$A['perm_group'],$A['perm_members'],$A['perm_anon']);
        if ($access == 0 OR $access == 2) {
            $retval .= COM_startBlock($LANG_LINKS_ADMIN[16], '',
                               COM_getBlockTemplate ('_msg_block', 'header'));
            $retval .= $LANG_LINKS_ADMIN[17];
            $retval .= COM_endBlock (COM_getBlockTemplate ('_msg_block', 'footer'));
            COM_accessLog("User {$_USER['username']} tried to illegally submit or edit link $lid.");
            return $retval;
        }
        $editFlag = true;
    } else {
        if ($action == 'moderate') {
            $result = DB_query ("SELECT * FROM {$_TABLES['linksubmission']} WHERE lid = '$lid'");
            $A = DB_fetchArray($result);
        } else {
            $A['lid'] = COM_makesid();
            $A['cid'] = '';
            $A['url'] = '';
            $A['description'] = '';
            $A['title']= '';
            $A['owner_id'] = $_USER['uid'];
        }
        $A['hits'] = 0;
        if (isset ($_GROUPS['Links Admin'])) {
            $A['group_id'] = $_GROUPS['Links Admin'];
        } else {
            $A['group_id'] = SEC_getFeatureGroup ('links.edit');
        }
        SEC_setDefaultPermissions ($A, $_LI_CONF['default_permissions']);
        $access = 3;
    }
    $retval .= COM_startBlock ($blocktitle, '',
                               COM_getBlockTemplate ('_admin_block', 'header'));

    if ( $editFlag ) {
        $lang_create_or_edit = $LANG_ADMIN['edit'];
    } else {
        $lang_create_or_edit = $LANG_LINKS_ADMIN[51];
    }
    $menu_arr = array(
        array('url' => $_CONF['site_admin_url'] . '/plugins/links/index.php',
                'text' => $LANG_LINKS_ADMIN[53]),
        array('url' => $_CONF['site_admin_url'] . '/plugins/links/index.php?edit=x',
                'text' => $lang_create_or_edit,'active'=>true),
        array('url' => $_CONF['site_admin_url'] . '/plugins/links/category.php',
                'text' => $LANG_LINKS_ADMIN[50]),
        array('url' => $_CONF['site_admin_url'] . '/plugins/links/index.php?validate=enabled',
            'text' => $LANG_LINKS_ADMIN[26]),
        array('url' => $_CONF['site_admin_url'],
                'text' => $LANG_ADMIN['admin_home'])
    );



    $retval .= ADMIN_createMenu($menu_arr, $LANG_LINKS_ADMIN[66], plugin_geticon_links());

    $link_templates->set_var('link_id', $A['lid']);
    if (!empty($lid) && SEC_hasRights('links.edit')) {
        $delbutton = '<input type="submit" value="' . $LANG_ADMIN['delete']
                   . '" name="delete"%s>';
        $jsconfirm = ' onclick="return confirm(\'' . $MESSAGE[76] . '\');"';
        $link_templates->set_var ('delete_option',
                                  sprintf ($delbutton, $jsconfirm));
        $link_templates->set_var ('delete_option_no_confirmation',
                                  sprintf ($delbutton, ''));
        $link_templates->set_var ('delete_confirm_msg',$MESSAGE[76]);
        if ($action == 'moderate') {
            $link_templates->set_var('submission_option',
                '<input type="hidden" name="type" value="submission">');
        }
    }
    $link_templates->set_var('lang_linktitle', $LANG_LINKS_ADMIN[3]);
    $link_templates->set_var('link_title',
                             htmlspecialchars ($A['title']));
    $link_templates->set_var('lang_linkid', $LANG_LINKS_ADMIN[2]);
    $link_templates->set_var('lang_linkurl', $LANG_LINKS_ADMIN[4]);
    $link_templates->set_var('max_url_length', 255);
    $link_templates->set_var('link_url', $A['url']);
    $link_templates->set_var('lang_includehttp', $LANG_LINKS_ADMIN[6]);
    $link_templates->set_var('lang_category', $LANG_LINKS_ADMIN[5]);
    $othercategory = links_select_box (3,$A['cid']);
    $link_templates->set_var('category_options', $othercategory);
    $link_templates->set_var('lang_ifotherspecify', $LANG_LINKS_ADMIN[20]);
    $link_templates->set_var('category', $othercategory);
    $link_templates->set_var('lang_linkhits', $LANG_LINKS_ADMIN[8]);
    $link_templates->set_var('link_hits', $A['hits']);
    $link_templates->set_var('lang_linkdescription', $LANG_LINKS_ADMIN[9]);
    $link_templates->set_var('link_description', $A['description']);
    $link_templates->set_var('lang_save', $saveoption);
    $link_templates->set_var('lang_cancel', $LANG_ADMIN['cancel']);

    // user access info
    $link_templates->set_var('lang_accessrights', $LANG_ACCESS['accessrights']);
    $link_templates->set_var('lang_owner', $LANG_ACCESS['owner']);
    $ownername = COM_getDisplayName ($A['owner_id']);
    $link_templates->set_var('owner_username', DB_getItem($_TABLES['users'],
                             'username', "uid = {$A['owner_id']}"));
    $link_templates->set_var('owner_name', $ownername);
    $link_templates->set_var('owner', $ownername);
    $link_templates->set_var('link_ownerid', $A['owner_id']);
    $link_templates->set_var('lang_group', $LANG_ACCESS['group']);
    $link_templates->set_var('group_dropdown',
                             SEC_getGroupDropdown ($A['group_id'], $access));
    $link_templates->set_var('lang_permissions', $LANG_ACCESS['permissions']);
    $link_templates->set_var('lang_permissionskey', $LANG_ACCESS['permissionskey']);
    $link_templates->set_var('permissions_editor', SEC_getPermissionsHTML($A['perm_owner'],$A['perm_group'],$A['perm_members'],$A['perm_anon']));
    $link_templates->set_var('lang_lockmsg', $LANG_ACCESS['permmsg']);
    $link_templates->set_var('gltoken_name', CSRF_TOKEN);
    $link_templates->set_var('gltoken', SEC_createToken());
    $link_templates->parse('output', 'editor');
    $retval .= $link_templates->finish($link_templates->get_var('output'));

    $retval .= COM_endBlock (COM_getBlockTemplate ('_admin_block', 'footer'));

    return $retval;
}

/**
* Saves link to the database
*
* @param    string  $lid            ID for link
* @param    string  $old_lid        old ID for link
* @param    string  $cid            cid of category link belongs to
* @param    string  $categorydd     Category links belong to
* @param    string  $url            URL of link to save
* @param    string  $description    Description of link
* @param    string  $title          Title of link
* @param    int     $hits           Number of hits for link
* @param    int     $owner_id       ID of owner
* @param    int     $group_id       ID of group link belongs to
* @param    int     $perm_owner     Permissions the owner has
* @param    int     $perm_group     Permissions the group has
* @param    int     $perm_members   Permissions members have
* @param    int     $perm_anon      Permissions anonymous users have
* @return   string                  HTML redirect or error message
* @global array core config vars
* @global array core group data
* @global array core table data
* @global array core user data
* @global array core msg data
* @global array links plugin lang admin vars
*
*/
function LINK_save($lid, $old_lid, $cid, $categorydd, $url, $description, $title, $hits, $owner_id, $group_id, $perm_owner, $perm_group, $perm_members, $perm_anon, $type)
{
    global $_CONF, $_GROUPS, $_TABLES, $_USER, $MESSAGE, $LANG_LINKS_ADMIN, $_LI_CONF;

    $retval = '';

    // Convert array values to numeric permission values
    if (is_array($perm_owner) OR is_array($perm_group) OR is_array($perm_members) OR is_array($perm_anon)) {
        list($perm_owner,$perm_group,$perm_members,$perm_anon) = SEC_getPermissionValues($perm_owner,$perm_group,$perm_members,$perm_anon);
    }

    // clean 'em up
    $description = DB_escapeString (COM_checkHTML (COM_checkWords (trim($description))));
    $title = DB_escapeString (COM_checkHTML (COM_checkWords (trim($title))));
    $cid = DB_escapeString (trim($cid));
    $url = DB_escapeString(trim($url));

    if (empty ($owner_id)) {
        // this is new link from admin, set default values
        $owner_id = $_USER['uid'];
        if (isset ($_GROUPS['Links Admin'])) {
            $group_id = $_GROUPS['Links Admin'];
        } else {
            $group_id = SEC_getFeatureGroup ('links.edit');
        }
        $perm_owner = 3;
        $perm_group = 2;
        $perm_members = 2;
        $perm_anon = 2;
    }

    $lid = COM_sanitizeID($lid);
    $old_lid = COM_sanitizeID($old_lid);
    if (empty($lid)) {
        if (empty($old_lid)) {
            $lid = COM_makeSid();
        } else {
            $lid = $old_lid;
        }
    }

    // check for link id change
    if (!empty($old_lid) && ($lid != $old_lid)) {
        // check if new lid is already in use
        if (DB_count($_TABLES['links'], 'lid', $lid) > 0) {
            // TBD: abort, display editor with all content intact again
            $lid = $old_lid; // for now ...
        }
    }

    $access = 0;
    $old_lid = DB_escapeString ($old_lid);
    if (DB_count ($_TABLES['links'], 'lid', $old_lid) > 0) {
        $result = DB_query ("SELECT owner_id,group_id,perm_owner,perm_group,perm_members,perm_anon FROM {$_TABLES['links']} WHERE lid = '{$old_lid}'");
        $A = DB_fetchArray ($result);
        $access = SEC_hasAccess ($A['owner_id'], $A['group_id'],
                $A['perm_owner'], $A['perm_group'], $A['perm_members'],
                $A['perm_anon']);
    } else {
        $access = SEC_hasAccess ($owner_id, $group_id, $perm_owner, $perm_group,
                $perm_members, $perm_anon);
    }
    if (($access < 3) || !SEC_inGroup ($group_id)) {
        $display .= COM_siteHeader ('menu', $MESSAGE[30]);
        $display .= COM_startBlock ($MESSAGE[30], '',
                            COM_getBlockTemplate ('_msg_block', 'header'));
        $display .= $MESSAGE[31];
        $display .= COM_endBlock (COM_getBlockTemplate ('_msg_block', 'footer'));
        $display .= COM_siteFooter ();
        COM_accessLog("User {$_USER['username']} tried to illegally submit or edit link $lid.");
        echo $display;
        exit;
    } elseif (!empty($title) && !empty($description) && !empty($url)) {

        if ($categorydd != $LANG_LINKS_ADMIN[7] && !empty($categorydd)) {
            $cid = DB_escapeString ($categorydd);
        } else if ($categorydd != $LANG_LINKS_ADMIN[7]) {
            echo COM_refresh($_CONF['site_admin_url'] . '/plugins/links/index.php');
        }

        DB_delete ($_TABLES['linksubmission'], 'lid', $old_lid);
        DB_delete ($_TABLES['links'], 'lid', $old_lid);

        DB_save ($_TABLES['links'], 'lid,cid,url,description,title,date,hits,owner_id,group_id,perm_owner,perm_group,perm_members,perm_anon', "'$lid','$cid','$url','$description','$title','".$_CONF['_now']->toMySQL(true)."','$hits',$owner_id,$group_id,$perm_owner,$perm_group,$perm_members,$perm_anon");

        if (empty($old_lid) || ($old_lid == $lid)) {
            PLG_itemSaved($lid, 'links');
        } else {
            PLG_itemSaved($lid, 'links', $old_lid);
        }

        // Get category for rdf check
        $category = DB_getItem ($_TABLES['linkcategories'],"category","cid='{$cid}'");
        COM_rdfUpToDateCheck ('links', $category, $lid);
        $c = glFusion\Cache::getInstance()->deleteItemsByTag('whatsnew');

        if ($type == 'submission') {
            return COM_refresh($_CONF['site_admin_url'] . '/moderation.php');
        } else {
            return PLG_afterSaveSwitch (
                    $_LI_CONF['aftersave'],
                    COM_buildURL ("{$_CONF['site_url']}/links/portal.php?what=link&item=$lid"),
                    'links',
                    2
                    );
        }
    } else { // missing fields
        $retval .= COM_siteHeader('menu', $LANG_LINKS_ADMIN[1]);
        $retval .= COM_errorLog($LANG_LINKS_ADMIN[10],2);
        if (DB_count ($_TABLES['links'], 'lid', $old_lid) > 0) {
            $retval .= LINK_edit('edit', $old_lid);
        } else {
            $retval .= LINK_edit('edit', '');
        }
        $retval .= COM_siteFooter();

        return $retval;
    }
}


/**
* Delete a link
*
* @param    string  $lid    id of link to delete
* @param    string  $type   'submission' when attempting to delete a submission
* @return   string          HTML redirect
*
*/
function LINK_delete($lid, $type = '')
{
    global $_CONF, $_TABLES, $_USER;

    if (empty($type)) { // delete regular link
        $result = DB_query("SELECT owner_id,group_id,perm_owner,perm_group,perm_members,perm_anon FROM {$_TABLES['links']} WHERE lid ='$lid'");
        $A = DB_fetchArray($result);
        $access = SEC_hasAccess($A['owner_id'], $A['group_id'],
                    $A['perm_owner'], $A['perm_group'], $A['perm_members'],
                    $A['perm_anon']);
        if ($access < 3) {
            COM_accessLog("User {$_USER['username']} tried to illegally delete link $lid.");
            return COM_refresh($_CONF['site_admin_url']
                               . '/plugins/links/index.php');
        }

        DB_delete($_TABLES['links'], 'lid', $lid);
        PLG_itemDeleted($lid, 'links');
        $c = glFusion\Cache::getInstance()->deleteItemsByTag('whatsnew');
        return COM_refresh($_CONF['site_admin_url']
                           . '/plugins/links/index.php?msg=3');
    } elseif ($type == 'submission') {
        if (plugin_ismoderator_links()) {
            DB_delete($_TABLES['linksubmission'], 'lid', $lid);

            return COM_refresh($_CONF['site_admin_url']
                               . '/moderation.php');
        } else {
            COM_accessLog("User {$_USER['username']} tried to illegally delete link submission $lid.");
        }
    } else {
        COM_errorLog("User {$_USER['username']} tried to illegally delete link $lid of type $type.");
    }

    return COM_refresh($_CONF['site_admin_url'] . '/plugins/links/index.php');
}

// MAIN ========================================================================
$menu = 'links';
$action = '';
$actionval = '';
$expected = array(
    'edit', 'moderate', 'save', 'delete', 'cancel', 'validate',
);
foreach($expected as $provided) {
    if (isset($_POST[$provided])) {
        $action = $provided;
        $actionval = $_POST[$provided];
    } elseif (isset($_GET[$provided])) {
	$action = $provided;
        $actionval = $_GET[$provided];
    }
}

$lid = '';
if (isset($_POST['lid'])) {
    $lid = COM_sanitizeID(COM_applyFilter($_POST['lid']));
} elseif (isset($_GET['lid'])) {
    $lid = COM_sanitizeID(COM_applyFilter($_GET['lid']));
}

$cid = '';
if (isset($_POST['cid'])) {
    $cid = COM_sanitizeID(COM_applyFilter($_POST['cid']));
} elseif (isset($_GET['cid'])) {
    $cid = COM_sanitizeID(COM_applyFilter($_GET['cid']));
}

$msg = (isset($_GET['msg'])) ? COM_applyFilter($_GET['msg']) : '';
$validate = (isset($_GET['validate'])) ? true : false;
$type = (isset($_POST['type'])) ? COM_applyFilter($_POST['type']) : '';

switch ($action) {
case 'edit':
case 'moderate':
    $blocktitle = ($action == 'edit') ? $LANG_LINKS_ADMIN[1] : $LANG_LINKS_ADMIN[65];
    $display .= COM_siteHeader('menu', $blocktitle);
    $Link = new Links\Link($lid);
    $display .= $Link->Edit($action);
    $display .= COM_siteFooter();
    break;

case 'save':
    if (SEC_checkToken()) {
        $Link = new Links\Link($_POST['old_lid']);
        $status = $Link->save($_POST);
        if ($status) {
            COM_setMsg($PLG_links_MESSAGE2);
        } else {
            COM_setMsg("ERROR", 'error');
        }
        COM_refresh(Links\Config::get('admin_url') . '/index.php?links');
    } else {
        COM_accessLog('User ' . $_USER['username'] . ' tried to illegally edit link ' . $lid . ' and failed CSRF checks.');
        echo COM_refresh($_CONF['site_admin_url'] . '/index.php');
    }
    break;

case 'delete':
    if (!isset ($lid) || empty ($lid)) {
        COM_errorLog ('User ' . $_USER['username'] . ' attempted to delete link, lid is null');
        $display .= COM_refresh ($_CONF['site_admin_url'] . '/plugins/links/index.php');
    } elseif (SEC_checkToken()) {
        $display .= LINK_delete($lid, $type);
    } else {
        COM_accessLog("User {$_USER['username']} tried to illegally delete link $lid and failed CSRF checks.");
        echo COM_refresh($_CONF['site_admin_url'] . '/index.php');
    }
    break;

case 'validate':
    $menu = 'validate';
default:
    if (($action == 'cancel') && ($type == 'submission')) {
        $display = COM_refresh($_CONF['site_admin_url'] . '/moderation.php');
    } else {
        $display .= Links\Menu::siteHeader('menu');
        if(isset($msg)) {
            $display .= (is_numeric($msg)) ? COM_showMessage($msg, Config::PI_NAME) : COM_showMessageText( $msg );
        }
        $display .= Links\Menu::Admin($menu);
        $display .= Links\Views\Admin\linkList::Render($actionval);
        $display .= Links\Menu::siteFooter();
    }
    break;
}

echo $display;

?>
