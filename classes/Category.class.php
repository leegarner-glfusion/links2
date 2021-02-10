<?php

namespace Links;

class Category
{
    use \Links\Instance;

    private $cid = '';
    private $pid = '';
    private $category = '';
    private $dscp = '';
    private $tid = 'all';
    private $created = '';
    private $modified = '';
    private $owner_id = 2;
    private $group_id = 1;
    private $perm_owner = 3;
    private $perm_group = 2;
    private $perm_members = 2;
    private $perm_anon = 2;

    public function __construct($cid = '')
    {
        global $_TABLES, $_LI_CONF;
        if (is_array($cid)) {
            $this->setVars($cid);
        } else {
            if ($cid != '') {
                $sql = "SELECT * FROM {$_TABLES['linkcategories']}
                    WHERE cid = '" . DB_escapeString($cid) . "'";
                $res = DB_query($sql);
                if (DB_numRows($res) == 1) {
                    $A = DB_fetchArray($res, false);
                    $this->setVars($A);
                }
            }
        }
    }


    /**
     * Set all the category properties.
     *
     * @param   array   $A      Key=>Value array from DB or form
     * @return  object  $this
     */
    public function setVars($A, $fromDB=true)
    {
        $this->withCid($A['cid'])
             ->withPid($A['pid'])
             ->withCategory($A['category'])
             ->withDscp($A['description'])
             ->withTid($A['tid'])
             ->withOwnerId($A['owner_id']);
         if ($fromDB) {
            $this->withModified($A['modified'])
                 ->withCreated($A['created'])
                 ->withGroupId($A['group_id'])
                 ->withPermOwner($A['perm_owner'])
                 ->withPermGroup($A['perm_group'])
                 ->withPermMembers($A['perm_members'])
                 ->withPermAnon($A['perm_anon']);
         } else {
            $B = SEC_getPermissionValues(
                $A['perm_owner'], $A['perm_group'],
                $A['perm_members'], $A['perm_anon']
            );
            $this->withModified()
                 ->withPermOwner($B[0])
                 ->withPermGroup($B[1])
                 ->withPermMembers($B[2])
                 ->withPermAnon($B[3]);
         }
         return $this;
    }


    /*public function withPid($id)
    {
        $this->pid = $id;
        return $this;
    }*/

    public function withCategory($cat)
    {
        $this->category = $cat;
        return $this;
    }

    public function getCategory()
    {
        return $this->category;
    }


    public function withTid($id)
    {
        $this->tid = $id;
        return $this;
    }


    public function withCreated($date='')
    {
        global $_CONF;

        if ($date == '') {
            $date = $_CONF['_now']->toMySQL(true);
        }
        $this->created = $date;
        return $this;
    }


    public function withModified($date='')
    {
        global $_CONF;

        if ($date == '') {
            $date = $_CONF['_now']->toMySQL(true);
        }
        $this->modified = $date;
        return $this;
    }


    public function getChildren()
    {
        global $_TABLES;

        $retval = array();
        $sql = "SELECT * FROM {$_TABLES['linkcategories']}
            WHERE pid='{$this->cid}' " . 
            COM_getLangSQL('cid', 'AND') .
            COM_getPermSQL('AND') .
            ' ORDER BY category';
        $res = DB_query($sql);
        while ($A = DB_fetchArray($res, false)) {
            $retval[] = new self($A);
        }
        return $retval;
    }

    public function countLinks()
    {
        global $_TABLES;

        $res = DB_query(
            "SELECT COUNT(*) AS count FROM {$_TABLES['links']}
            WHERE cid='{$this->cid}'" . COM_getPermSQL('AND')
        );
        $A = DB_fetchArray($res, false);
        return $A['count'];
    }
    


    /**
     * Create the options for a category dropdown selection.
     *
     * @param   integer $access Required access level
     * @param   string  $sel    Selected option
     */
    public static function optionList($access, $sel = '')
    {
        global $_CONF, $LANG_LINKS, $_LI_CONF;

        // set root value
        $menu = '<option value="' . $_LI_CONF['root'] . '">' . $LANG_LINKS['root'] . '</option>';
        // get option values
        $list = self::_optionListRecursive($menu, $_LI_CONF['root'], $sel, '&nbsp;&nbsp;', 3);
        // return list of options
        return $list;
    }


    public static function getUrl($cid)
    {
        return COM_buildUrl(Config::Get('url') . '/index.php?mode=list&cid=' . urlencode($cid));
    }


    public function hasAccess($required=2)
    {
        return SEC_hasAccess(
            $this->owner_id, $this->group_id,
            $this->perm_owner, $this->perm_group,
            $this->perm_members, $this->perm_anon
        ) >= $required;
    }


    /**
     * Build recursive tree.
     *
     * @param   string  $menu   Menu string to update
     * @param   string  $cid    Category for which to get the children
     * @param   string  $sel    Currently-selected option
     * @param   string  $indent Characters to use for indenting
     * @param   integer $access Access level required
     * @return  string      Updated menu string
     */
    private static function _optionListRecursive(&$menu, $cid, $sel, $indent, $access)
    {
        global $_CONF, $_TABLES;

        $cat = DB_escapeString($cid);
        $sql = "SELECT cid,category
            FROM {$_TABLES['linkcategories']}
            WHERE (pid='{$cat}') " . COM_getPermSQL('AND', 0, $access) . "
            ORDER BY category";
        $query = DB_query($sql);
        while (list($cid, $category) = DB_fetchArray($query)) {
            // set selected item
            if ($cid == $sel) {
                // yes, selected
                $menu .= '<option value="' . @htmlspecialchars($cid)
                  . '" selected="selected">' . $indent . $category
                  . '</option>';
            } else {
                // no, not selected
                $menu .= '<option value="' . @htmlspecialchars($cid) . '">'
                  . $indent . $category . '</option>';
            }
            // Check and see if this category has any sub categories
            if (DB_count($_TABLES['linkcategories'], 'pid', DB_escapeString($cid)) > 0) {
                // yes, call self
                $dum = self::_optionListRecursive($menu, $cid, $sel,
                        $indent . '&nbsp;&nbsp;', $access);
            }
       }
       return $menu;
    }
    
    
    /**
     * Build breadcrumb trail
     *
     * Breadcrumb trail does not use the "root" category in the database: the top
     * level category is set from the language file using $LANG_LINKS['root']
    */
    public static function breadCrumbs($root, $cid)
    {
        global $_CONF, $_TABLES, $LANG_LINKS;

        $breadcrumb = '';
        $separator  = ' &gt; ';

        if ($root != $cid) {
            $pid = '';
            $c = $cid;
            $cat = DB_escapeString($cid);
            while ($pid != $root) {
                $parent = DB_query("SELECT cid,pid,category FROM {$_TABLES['linkcategories']} WHERE cid='{$cat}'");
                if ( DB_numRows($parent) == 0 ) {
                    break;
                }
                $A = DB_fetchArray($parent, false);
                if ($cid != $c) {
                    $content = $A['category'];
                    $url = self::getUrl($A['cid']);
                    $breadcrumb = COM_createLink($content, $url) .
                        $separator .
                        $breadcrumb;
                } else {
                    $breadcrumb = '<b>' . $A['category'] . '</b>' . $breadcrumb;
                }
                $pid = $A['pid'];
                $c = $A['pid'];
                $cat = DB_escapeString($c);
            }
        }

        if (empty($breadcrumb)) {
            $breadcrumb = '<b>' . $LANG_LINKS['root'] . '</b>';
        } else {
            $url = Config::get('url') . '/index.php';
            $breadcrumb = COM_createLink($LANG_LINKS['root'], $url) . $separator . $breadcrumb;
        }
        $breadcrumb = '<span class="links-breadcrumb">' . $LANG_LINKS[126] . ' '
                . $breadcrumb . '</span>';
        return $breadcrumb;
    }
    
    
    /**
     * Return SQL expression to check for allowed categories.
     *
     * Creates part of an SQL expression that can be used to only request links
     * from categories to which the user has access to.
     *
     * Note that this function does SQL requests, so you should cache
     * the resulting SQL expression if you need it more than once.
     *
     * @param    string  $type   part of the SQL expr. e.g. 'WHERE', 'AND'
     * @param    int     $u_id   user id or 0 = current user
     * @param    string  $table  table name if ambiguous (e.g. in JOINs)
     * @return   string          SQL expression string (may be empty)
     * @see      COM_getTopicSQL
     *
     */
    public static function getSQL($type = 'WHERE', $u_id = 0, $table = '')
    {
        global $_TABLES, $_USER, $_GROUPS;

        if (SEC_inGroup('Root', $_USER['uid'])) {
            // No limit for root users
            return '';
        }

        $categorysql = ' ' . $type . ' ';

        if (!empty($table)) {
            $table .= '.';
        }

        $UserGroups = array();
        if (($u_id <= 0) || (isset($_USER['uid']) && ($u_id == $_USER['uid']))) {
            if (!COM_isAnonUser()) {
                $uid = $_USER['uid'];
            } else {
                $uid = 1;
            }
            $UserGroups = $_GROUPS;
        } else {
            $uid = $u_id;
            $UserGroups = SEC_getUserGroups($uid);
        }

        if (empty($UserGroups)) {
            // this shouldn't really happen, but if it does, handle user
            // like an anonymous user
            $uid = 1;
        }

        $parents = array('root');
        $cids = array();
        do {
            $result = DB_query(
                "SELECT cid FROM {$_TABLES['linkcategories']}"
                . COM_getPermSQL('WHERE', $uid) . " AND pid IN ('"
                . implode("','", $parents) . "')"
            );
            $parents = array();
            while ($C = DB_fetchArray($result)) {
                $parents[] = $C['cid'];
                $cids[] = $C['cid'];
            }
        } while (count($parents) > 0);

        if (count($cids) > 0) {
            $categorysql .= "({$table}cid IN ('" . implode("','", $cids) . "'))";
        } else {
            $categorysql .= '0';
        }
        return $categorysql;
    }
    
    
    public function edit()
    {
        global $_CONF, $_TABLES, $_USER, $MESSAGE,
           $LANG_LINKS_ADMIN, $LANG_ADMIN, $LANG_ACCESS, $_LI_CONF;

        USES_lib_admin();

        $retval = '';
        $editFlag = false;
        $cid = DB_escapeString($this->cid);

        /*if (!empty($pid)) {
            // have parent id, so making a new subcategory
            // get parent access rights
            $result = DB_query("SELECT group_id,perm_owner,perm_group,perm_members,perm_anon FROM {$_TABLES['linkcategories']} WHERE cid='" . DB_escapeString($pid) . "'");
            $A = DB_fetchArray($result);
            $A['owner_id'] = $_USER['uid'];
            $A['pid'] = $pid;
        } elseif (!empty($cid)) {
            // have category id, so editing a category
            $sql = "SELECT * FROM {$_TABLES['linkcategories']} WHERE cid='{$cid}'"
             . COM_getPermSQL('AND');
            $result = DB_query($sql);
            $A = DB_fetchArray($result);
            $editFlag = true;
        } else {
            // nothing, so making a new top-level category
            // get default access rights
            $A['group_id'] = DB_getItem($_TABLES['groups'], 'grp_id', "grp_name='Links Admin'");
            SEC_setDefaultPermissions($A, $_LI_CONF['default_permissions']);
            $A['owner_id'] = $_USER['uid'];
            $A['pid']      = $_LI_CONF['root'];
        }*/

        if (!$this->hasAccess(3)) {
            return COM_showMessage(6, 'links');
        }

        if ( $editFlag ) {
            $lang_edit_or_create = $LANG_ADMIN['edit'];
        } else {
            $lang_edit_or_create = $LANG_LINKS_ADMIN[52];
        }

        $T = new \Template(__DIR__ . '/../templates/admin');
        $T->set_file(array('page' => 'categoryeditor.thtml'));

        $T->set_var(array(
            'pi_admin_url' => Config::get('admin_url'),
            'lang_pagetitle' => $LANG_LINKS_ADMIN[28],
            'lang_link_list' => $LANG_LINKS_ADMIN[53],
            'lang_new_link' => $LANG_LINKS_ADMIN[51],
            'lang_validate_links' => $LANG_LINKS_ADMIN[26],
            'lang_list_categories' => $LANG_LINKS_ADMIN[50],
            'lang_new_category' => $LANG_LINKS_ADMIN[52],
            'lang_admin_home' => $LANG_ADMIN['admin_home'],
            'instructions' => $LANG_LINKS_ADMIN[29],
            'lang_category' => $LANG_LINKS_ADMIN[30],
            'lang_cid' => $LANG_LINKS_ADMIN[32],
            'lang_description' => $LANG_LINKS_ADMIN[31],
            'lang_topic' => $LANG_LINKS_ADMIN[33],
            'lang_parent' => $LANG_LINKS_ADMIN[34],
            'lang_save' => $LANG_ADMIN['save'],
        ) );
        if (!empty($this->cid)) {
            $delbutton = '<input type="submit" value="' . $LANG_ADMIN['delete']
                   . '" name="delete"%s>';
            $jsconfirm = ' onclick="return confirm(\'' . $MESSAGE[76] . '\');"';
            $T->set_var(array(
                'delete_option' => sprintf($delbutton, $jsconfirm),
                'delete_option_no_confirmation' => sprintf($delbutton, ''),
                'delete_confirm_msg' => $MESSAGE[76],
            ) );
        } else {
            $T->set_var('delete_option', '');
        }
        $T->set_var('lang_cancel', $LANG_ADMIN['cancel']);

        if (!empty($cid)) {
            $T->set_var(array(
                'cid_value' => $this->cid,
                'old_cid_value' => $this->cid,
                'category_options' => self::optionList(3, $this->pid),
                'category_value' => $this->category,
                'description_value' => $this->dscp,
            ) );
        } else {
            $this->cid = COM_makeSid();
            $T->set_var('cid_value', $this->cid);
            $T->set_var('category_options', self::optionList(3, $this->pid));
        }

        if (!isset($this->tid)) {
            $this->tid = 'all';
        }
        $topics = COM_topicList('tid,topic,sortnum', $this->tid, 2, true);
        $T->set_var('topic_list', $topics);
        $alltopics = '<option value="all"';
        if ($this->tid == 'all') {
            $alltopics .= ' selected="selected"';
        }
        $alltopics .= '>' . $LANG_LINKS_ADMIN[35] . '</option>' . LB;
        $T->set_var('topic_selection', '<select name="tid">' . $alltopics
                                   . $topics . '</select>');

        // user access info
        $T->set_var(array(
            'lang_accessrights' => $LANG_ACCESS['accessrights'],
            'lang_owner' => $LANG_ACCESS['owner'],
            'owner_name' => COM_getDisplayName($this->owner_id),
            'cat_ownerid' => $this->owner_id,
            'lang_group' => $LANG_ACCESS['group'],
            'group_dropdown' => SEC_getGroupDropdown($this->group_id, 2),
            'lang_permissions' => $LANG_ACCESS['permissions'],
            'lang_permissionskey' => $LANG_ACCESS['permissionskey'],
            'permissions_editor' => SEC_getPermissionsHTML(
                $this->perm_owner, $this->perm_group, $this->perm_members, $this->perm_anon
            ),
            'lang_lockmsg' => $LANG_ACCESS['permmsg'],
            'gltoken_name' => CSRF_TOKEN,
            'gltoken' => SEC_createToken(),
        ) );

        $T->parse('output', 'page');
        $retval .= $T->finish($T->get_var('output'));
        $retval .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
        return $retval;
    }
    
    
    /**
     * Save changes to category information
     *
     * @param   array   $A  Values from form (unvalidated, unsafe)
     * @return  boolean     True on success, False on error
     */
    public function save($A)
    {
        global $_CONF, $_TABLES, $_USER, $LANG_LINKS, $LANG_LINKS_ADMIN, $_LI_CONF,
           $PLG_links_MESSAGE17;

        $old_cid = DB_escapeString(strip_tags($this->cid));
        if (is_array($A)) {
            $this->setVars($A, false);
        }

        // clean 'em up
        $description = DB_escapeString(COM_checkHTML(COM_checkWords($this->dscp)));
        $category    = DB_escapeString(COM_checkHTML(COM_checkWords($this->category)));
        $pid     = DB_escapeString(strip_tags($this->pid));
        $cid     = DB_escapeString(strip_tags($this->cid));

        if (empty($category) || empty($description)) {
            return false;
        }
        if (empty($cid)) {
            $cid = COM_makeSid();
        }
        if (!empty($cid) && ($cid != $old_cid)) {
            // this is either a new category or an attempt to change the cid
            // - check that cid doesn't exist yet
            $ctrl = DB_getItem($_TABLES['linkcategories'], 'cid', "cid = '$cid'");
            if (!empty($ctrl)) {
                if (isset($PLG_links_MESSAGE17)) {
                    return 17;
                } else {
                    return 11;
                }
            }
        }

        // Check that they didn't delete the cid. If so, get the hidden one
        if (empty($cid) && !empty($old_cid)) {
            $cid = $old_cid;
        }

        // Make sure they aren't making a parent category child of one of it's own
        // children. This would create orphans.
        if ($cid == DB_getItem($_TABLES['linkcategories'], 'pid', "cid='{$pid}'")) {
            return 12;
        }

        if (empty($old_cid)) {      // Creating a new record
            $sql1 = "INSERT INTO {$_TABLES['linkcategories']} SET ";
            $sql3 = '';
        } else {
            $sql1 = "UPDATE {$_TABLES['linkcategories']} SET ";
            $sql3 = " WHERE cid = '$old_cid'";
        }
        $sql2 = "cid = '$cid',
            pid = '{$pid}',
            tid = '{$this->tid}',
            category = '{$category}',
            description = '{$description}',
            modified = '" . $_CONF['_now']->toMySQL(true) . "',
            owner_id = '{$this->owner_id}',
            group_id = '{$this->group_id}',
            perm_owner = '{$this->perm_owner}',
            perm_group = '{$this->perm_group}',
            perm_members = '{$this->perm_members}',
            perm_anon = '{$this->perm_anon}'";
        $sql = $sql1 . $sql2 . $sql3;
        //echo $sql;die;
        $res = DB_query($sql);
        if (!DB_error()) {
            PLG_itemSaved($cid, 'links.category', $old_cid);
            return true;
        } else {
            return false;
        }
    }
    
    
    /**
     * Delete a category
     *
     * @return  integer     Message ID to show
     */
    public function delete()
    {
        global $_TABLES, $LANG_LINKS_ADMIN;

        $cid = DB_escapeString($this->cid);
        if (!empty($cid)) {
            if ($this->hasAccess(3)) {
                $sf = DB_count($_TABLES['linkcategories'], 'pid', $cid);
                $sl = DB_count($_TABLES['links'], 'cid', $cid);
                if (($sf == 0) && ($sl == 0)) {
                    // No subfolder/links so OK to delete
                    DB_delete($_TABLES['linkcategories'], 'cid', $cid);
                    PLG_itemDeleted($cid, 'links.category');
                    return 13;
                } else {
                    // Subfolders and/or sublinks exist so return a message
                    return 14;
                }
            } else {
                // no access
                return 15;
                COM_accessLog(sprintf($LANG_LINKS_ADMIN[46], $_USER['username']));
            }
        } else {
            // no such category
            return 16;
        }
    }

}
