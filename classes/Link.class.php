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
namespace Links;
use glFusion\Formatter;


class Link
{
    use \Links\Instance;

    /** Link database record ID.
     * @var string */
    private $lid = '';

    /** Category record ID.
     * @var string */
    private $cid = '';

    /** Target URL.
     * @var string */
    private $url = '';

    /** Description.
     * @var string */
    private $dscp = '';

    /** Link title.
     * @var string *?
     private $title = '';

    /** Number of hits.
     * @var integer */
    private $hits = 0;

    /** Date updated.
     * @var string */
    private $date = '';

    /** Owner user ID.
     * @var integer */
    private $owner_id = 0;

    /** Group ID for access.
     * @var integer */
    private $group_id = 2;

    /** Owner permission level.
     * @var integer */
    private $perm_owner = 3;

    /** Group permission level.
     * @var integer */
    private $perm_group = 2;

    /** Members permission level.
     * @var integer */
    private $perm_members = 2;

    /** Anonymous permision level.
     * @var integer */
    private $perm_anon = 2;

    /** Table key, `links` or `linksubmission`.
     * @var string */
    private $table = 'links';


    /**
     * Read a link and optionally set the table key.
     *
     * @param   string  $lid    Link ID
     * @param   string  $table  Table key
     */
    public function __construct($lid = '', $table='')
    {
        global $_TABLES;

        if (is_array($lid)) {
            $this->setVars($lid);
        } elseif ($lid != '') {
            if ($table != '') {
                $this->withTable($table);
            }
            $sql = "SELECT * FROM {$_TABLES[$this->table]}
                WHERE lid = '" . DB_escapeString($lid) . "'";
            $res = DB_query($sql);
            if (DB_numRows($res) == 1) {
                $A = DB_fetchArray($res, false);
                $this->setVars($A);
            }
        }
    }


    /**
     * Sets all property values from an array.
     *
     * @param   array   $A      Key=>Value array, from DB or form
     * @param   boolean $fromDB True if the record is from the database
     * @return  object  $this
     */
    public function setVars($A, $fromDB=true)
    {
        $this->withLid($A['lid'])
             ->withCid($A['cid'])
             ->withUrl($A['url'])
             ->withDscp($A['description'])
             ->withTitle($A['title'])
             ->withHits($A['hits'])
             ->withOwnerId($A['owner_id']);

        // Additional fields only apply when the table is 'links'.
        if ($this->table == 'links') {
            $this->withGroupId($A['group_id']);
            if (!$fromDB) {
                // coming from the form
                $B = SEC_getPermissionValues(
                    $A['perm_owner'], $A['perm_group'],
                    $A['perm_members'], $A['perm_anon']
                );
                $this->withDate()
                     ->withPermOwner($B[0])
                     ->withPermGroup($B[1])
                     ->withPermMembers($B[2])
                     ->withPermAnon($B[3]);
            } else {
                $this->withDate($A['date'])
                     ->withPermOwner($A['perm_owner'])
                     ->withPermGroup($A['perm_group'])
                     ->withPermMembers($A['perm_members'])
                     ->withPermAnon($A['perm_anon']);
            }
        }
        return $this;
    }


    /**
     * Set the table key.
     *
     * @param   string  $table  Either `links` or `linksubmission`
     * @return  object  $this
     */
    public function withTable($table)
    {
        $this->table = $table;
        return $this;
    }


    /**
     * Set the link ID.
     *
     * @param   string  $id     Link record ID
     * @return  object  $this
     */
    public function withLid($id)
    {
        $this->lid = $id;
        return $this;
    }


    /**
     * Get the link ID.
     *
     * @return  string      Link record ID
     */
    public function getLid()
    {
        return $this->lid;
    }


    /**
     * Set the target URL.
     *
     * @param   string  $url    Target URL
     * @return  object  $this
     */
    public function withUrl($url)
    {
        $this->url = $url;
        return $this;
    }


    /**
     * Get the target URL.
     *
     * @return  string      Target URL
     */
    public function getUrl()
    {
        return $this->url;
    }


    /**
     * Set the link title.
     *
     * @param   string  $txt    Title text
     * @return  object  $this
     */
    public function withTitle($txt)
    {
        $this->title = $txt;
        return $this;
    }


    /**
     * Get the link title.
     *
     * @return  string      Title text
     */
    public function getTitle()
    {
        return $this->title;
    }


    /**
     * Set the submission or update date.
     *
     * @param   string  $txt    Date text, now if empty
     * @return  object  $this
     */
    public function withDate($txt='')
    {
        global $_CONF;

        if ($txt == '') {
            $txt = $_CONF['_now']->toMySQL(true);
        }
        $this->date = $txt;
        return $this;
    }


    /**
     * Set the number of hits received for this link.
     *
     * @param   integer $num    Number of hits
     * @return  object  $this
     */
    public function withHits($num)
    {
        $this->hits = (int)$num;
        return $this;
    }


    /**
     * Get the number of hits received for this link.
     *
     * @return  integer     Number of hits received
     */
    public function getHits()
    {
        return (int)$this->hits;
    }


    /**
     * Set the link's owner ID.
     *
     * @param   integer $id     Owner's user ID
     * @return  object  $this
     */
    public function withOwnerID($id)
    {
        $this->owner_id = (int)$id;
        return $this;
    }


    /**
     * Make the portal URL for this link.
     *
     * @return  string      Portal URL
     */
    public function makeUrl()
    {
        global $_CONF;

        return COM_buildUrl(
            Config::get('url') . '/portal.php?item=' . $this->lid
        );
    }


    /**
     * Create the complete tag for the portal link.
     *
     * @return  string      Complete HTML for the portal link
     */
    public function makeLink()
    {
        global $_CONF;

        $content = $this->getTitle();
        $url = $this->getUrl();     // actual URL
        $attr = array(
            'title' => $url,
            'class' => 'ext-link',
        );

        // If the link is external to the site, set the external rel tag
        // and the blank target.
        if (stristr($url, $_CONF['site_url']) === false) {
            $attr['rel'] = Config::get('ext_rel');
            if (
                Config::get('target_blank') ||
                (
                    isset($_CONF['open_ext_url_new_window']) &&
                    $_CONF['open_ext_url_new_window'] == true
                )
            ) {
                $attr['target'] = '_blank';
            }
        }
        return COM_createLink($content, $this->makeUrl(), $attr);
    }


    /**
     * Get all links which relate to a topic.
     *
     * @param   string  $tid    Topic ID
     * @return  array   Array of Link objects
     */
    public static function getByTopic($tid)
    {
        global $_TABLES;

        $retval = array();
        $tid = DB_escapeString($tid);
        $sql = "SELECT l.*, c.cid FROM {$_TABLES[$this->table]} AS l
            LEFT JOIN {$_TABLES['linkcategories']} AS c
            ON l.cid=c.cid
            WHERE c.tid='{$tid}' OR c.tid='all'" .
            COM_getPermSQL('AND', 0, 2, 'c');
        $res = DB_query($sql);
        while ($A = DB_fetchArray($res, false)) {
            $retval[] = new self($A);
        }
        return $retval;
    }


    /**
     * Get all the links under a specific category, paging as needed.
     *
     * @param   string  $cid    Category ID
     * @param   integer $page   Optional starting page number
     */
    public static function getByCategory($cid, $page=0)
    {
        global $_TABLES, $_LI_CONF;

        $retval = array();
        $sql = "SELECT * FROM {$_TABLES['links']} ";
        if ($_LI_CONF['linkcols'] > 0) {
            if (!empty($cid)) {
                $from_where = " WHERE cid='" . DB_escapeString($cid) . "'";
            } else {
                $from_where = " WHERE cid=''";
            }
            $from_where .= COM_getPermSQL ('AND');
        } else {
            $from_where = COM_getPermSQL ();
        }
        $order = ' ORDER BY cid ASC,title';
        $limit = '';
        if ($_LI_CONF['linksperpage'] > 0) {
            if ($page < 1) {
                $start = 0;
            } else {
                $start = ($page - 1) * $_LI_CONF['linksperpage'];
            }
            $limit = ' LIMIT ' . $start . ',' . $_LI_CONF['linksperpage'];
        }
        //echo $sql . $from_where . $order . $limit;die;
        $res = DB_query($sql . $from_where . $order . $limit);
        while ($A = DB_fetchArray($res, false)) {
            $retval[$A['lid']] = new self($A);
        }
        return $retval;
    }


    /**
     * Check if the current user can submit a link.
     *
     * @return  boolean     True if submission is allowed, False if not
     */
    public static function canSubmit()
    {
        global $_CONF, $_LI_CONF;

        $retval = false;

        if (
            !isset($_LI_CONF['submission']) ||
            SEC_inGroup('Root') ||
            SEC_hasRights('links.edit')
        ) {
            return true;
        }

        if (COM_isAnonUser() && $_LI_CONF['submission'] == 2) {
            return true;
        } elseif ($_LI_CONF['submission'] > 0) {
            return true;
        }

        return false;
    }


    /**
     * Delete a link.
     *
     * @param   string  $lid    id of link to delete
     * @param   string  $type   'linksubmission' when attempting to delete a submission
     * @return  boolean     True on success, False on error or access denied
     */
    public function delete($lid, $type = '')
    {
        global $_CONF, $_TABLES, $_USER;

        if ($type == 'submission') {
            if (plugin_ismoderator_links()) {
                DB_delete($_TABLES['linksubmission'], 'lid', $this->lid);
                return true;
                /*return COM_refresh($_CONF['site_admin_url']
                    . '/moderation.php');*/
            } else {
                COM_accessLog("User {$_USER['username']} tried to illegally delete link submission $lid.");
                return false;
            }
        } elseif (empty($type)) { // delete regular link
            if ($this->hasAccess(3)) {
                DB_delete($_TABLES['links'], 'lid', $lid);
                PLG_itemDeleted($lid, 'links');
                $c = glFusion\Cache::getInstance()->deleteItemsByTag('whatsnew');
                return true;
            } else {
                COM_accessLog("User {$_USER['username']} tried to illegally delete link $lid.");
                return false;
            }
        } else {
            COM_errorLog("User {$_USER['username']} tried to illegally delete link $lid of type $type.");
            return false;
        }
        // all possible returns are handled above
    }


    /**
     * Verify that a URL is working.
     *
     * @param   string  $url    URL to check
     * @return  string      HTTP code and text
     */
    public static function validateUrl($url)
    {
        global $_CONF, $LANG_LINKS_STATUS;

        $retval = '';

        set_time_limit(0);
        $req=new \http_class;
        $req->timeout=0;
        $req->data_timeout=0;
        $req->debug=0;
        $req->html_debug=0;
        $req->accept = "*/*";
        $req->user_agent="Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)";
        $error=$req->GetRequestArguments($url,$arguments);
        $arguments["Headers"]["Pragma"]="nocache";
        $error=$req->Open($arguments);
        if ( $error != "" ) {
            $retval = $error;
        } else {
            $headers=array();
            $error=$req->SendRequest($arguments);
            if ( $error != "" ) {
                $retval = $error;
            } else {
                $error=$req->ReadReplyHeaders($headers);
                if ( $error != "") {
                    $retval = $error;
                } else {
                    $status_code = $req->response_status;
                    $retval = $status_code . ": " . $LANG_LINKS_STATUS[$status_code];
                }
            }
        }
        return $retval;
    }


    /**
     * Shows the links editor.
     *
     * @param   string  $action   'edit' or 'moderate'
     * @return  string      HTML for the link editor form
     */
    public function Edit($action)
    {
        global $_CONF, $_GROUPS, $_TABLES, $_USER, $_LI_CONF,
           $LANG_LINKS_ADMIN, $LANG_ACCESS, $LANG_ADMIN, $MESSAGE;

        USES_lib_admin();

        $retval = '';
        $editFlag = false;
        $old_link_id = $this->lid;

        $T = new \Template(__DIR__ . '/../templates/admin/');
        $T->set_file('editor', 'linkeditor.thtml');

        switch ($action) {
        case 'edit':
            $blocktitle = $LANG_LINKS_ADMIN[1];     // Link Editor
            $saveoption = $LANG_ADMIN['save'];      // Save
            $table = 'links';
            break;
        case 'moderate':
            if (empty($this->lid)) {
                // Must have an existing link ID to moderate
                return false;
            }
            $blocktitle = $LANG_LINKS_ADMIN[65];    // Moderate Link
            $saveoption = $LANG_ADMIN['moderate'];  // Save & Approve
            $table = 'linksubmission';
            break;
        }

        $T->set_var(array(
            'lang_pagetitle' => $LANG_LINKS_ADMIN[28],
            'lang_link_list' => $LANG_LINKS_ADMIN[53],
            'lang_new_link' => $LANG_LINKS_ADMIN[51],
            'lang_validate_links' => $LANG_LINKS_ADMIN[26],
            'lang_list_categories' => $LANG_LINKS_ADMIN[50],
            'lang_new_category' => $LANG_LINKS_ADMIN[52],
            'lang_admin_home'=> $LANG_ADMIN['admin_home'],
            'instructions' => $LANG_LINKS_ADMIN[29],
            'admin_url' => Config::get('admin_url'),
            'type' => $table,
        ) );

        if ($action <> 'moderate' && !empty($this->lid)) {
            if (!$this->hasAccess(3)) {
                return false;
            }
            $editFlag = true;
        } elseif ($this->lid == '') {
            $this->lid = COM_makeSid();
            $access = 3;
        }
        $retval .= COM_startBlock(
            $blocktitle,
            '',
            COM_getBlockTemplate('_admin_block', 'header')
        );

        $access = 3;
        if ( $editFlag ) {
            $lang_create_or_edit = $LANG_ADMIN['edit'];
        } else {
            $lang_create_or_edit = $LANG_LINKS_ADMIN[51];
        }

        if (!empty($lid) && SEC_hasRights('links.edit')) {
            $delbutton = '<input type="submit" value="' . $LANG_ADMIN['delete']
                   . '" name="delete"%s>';
            $jsconfirm = ' onclick="return confirm(\'' . $MESSAGE[76] . '\');"';
            $T->set_var(array(
                'delete_option' => sprintf($delbutton, $jsconfirm),
                'delete_option_no_confirmation' => sprintf ($delbutton, ''),
                'delete_confirm_msg' => $MESSAGE[76],
            ) );
        }

        $cat_optlist = Category::optionList(3,$this->cid);
        $ownername = COM_getDisplayName ($this->owner_id);
        $T->set_var(array(
            'link_id' => $this->lid,
            'old_lid' => $old_link_id,
            'lang_linktitle' => $LANG_LINKS_ADMIN[3],
            'link_title' => htmlspecialchars ($this->title),
            'lang_linkid' => $LANG_LINKS_ADMIN[2],
            'lang_linkurl' => $LANG_LINKS_ADMIN[4],
            'max_url_length' => 255,
            'link_url' => $this->url,
            'lang_includehttp' => $LANG_LINKS_ADMIN[6],
            'lang_category' => $LANG_LINKS_ADMIN[5],
            'category_options' => $cat_optlist,
            'lang_ifotherspecify' => $LANG_LINKS_ADMIN[20],
            'category' => $cat_optlist,
            'lang_linkhits' => $LANG_LINKS_ADMIN[8],
            'link_hits' => $this->hits,
            'lang_linkdescription' => $LANG_LINKS_ADMIN[9],
            'link_description' => $this->dscp,
            'lang_save' => $saveoption,
            'lang_cancel' => $LANG_ADMIN['cancel'],
            // user access info
            'lang_accessrights' => $LANG_ACCESS['accessrights'],
            'lang_owner' => $LANG_ACCESS['owner'],
            'owner_username' => DB_getItem($_TABLES['users'], 'username', "uid = {$this->owner_id}"),
            'owner_name' => $ownername,
            'owner' => $ownername,
            'link_ownerid' => $this->owner_id,
            'lang_group' => $LANG_ACCESS['group'],
            'group_dropdown' => SEC_getGroupDropdown ($this->group_id, $access),
            'lang_permissions' => $LANG_ACCESS['permissions'],
            'lang_permissionskey' => $LANG_ACCESS['permissionskey'],
            'permissions_editor' => SEC_getPermissionsHTML(
                $this->perm_owner, $this->perm_group, $this->perm_members, $this->perm_anon
            ),
            'lang_lockmsg' => $LANG_ACCESS['permmsg'],
            'gltoken_name' => CSRF_TOKEN,
            'gltoken' => SEC_createToken(),
        ) );
        $T->parse('output', 'editor');
        $retval .= $T->finish($T->get_var('output'));
        $retval .= COM_endBlock(COM_getBlockTemplate ('_admin_block', 'footer'));
        return $retval;
    }


    /**
     * Saves link to the database.
     *
     * @param   array   $A      Array from form
     * @return  boolean     True on success, False on error
     */
    public function save($A)
    {
        global $_CONF, $_GROUPS, $_TABLES, $_USER, $MESSAGE, $LANG_LINKS_ADMIN, $_LI_CONF;

        if (is_array($A)) {
            $this->setVars($A, false);
        }
        if (isset($A['type'])) {
            $this->withTable($A['type']);
        }

        // clean 'em up
        $description = DB_escapeString(COM_checkHTML(COM_checkWords (trim($this->dscp))));
        $title = DB_escapeString(COM_checkHTML(COM_checkWords (trim($this->title))));
        $cid = DB_escapeString(trim($this->cid));
        $url = DB_escapeString(trim($this->url));

        if (empty($thius->owner_id)) {
            // this is new link from admin, set default values
            $owner_id = $_USER['uid'];
            if (isset($_GROUPS['Links Admin'])) {
                $group_id = $_GROUPS['Links Admin'];
            } else {
                $group_id = SEC_getFeatureGroup ('links.edit');
            }
            $perm_owner = 3;
            $perm_group = 2;
            $perm_members = 2;
            $perm_anon = 2;
        }

        $lid = COM_sanitizeID($this->lid);
        $old_lid = COM_sanitizeID($A['old_lid']);
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

        if ($old_lid == '') {
            $sql1 = "INSERT INTO {$_TABLES[$this->table]} SET ";
            $sql3 = '';
        } else {
            $sql1 = "UPDATE {$_TABLES[$this->table]} SET ";
            $sql3 = " WHERE lid = '{$old_lid}'";
        }
        $flds = array(
            "lid = '$lid'",
            "cid = '$cid'",
            "url = '$url'",
            "title = '$title'",
            "description = '$description'",
            "hits = " . (int)$this->hits,
            "owner_id = " . (int)$this->owner_id,
            "`date` = '" . DB_escapeString($this->date) . "'",
        );
        if ($this->table == 'linksubmission') {
            $flds[] = "group_id = $this->group_id";
            $flds[] = "perm_owner = $this->perm_owner";
            $flds[] = "perm_group = $this->perm_group";
            $flds[] = "perm_members = $this->perm_members";
            $flds[] = "perm_anon = $this->perm_anon";
        }
        $sql2 = implode(',', $flds);
        $sql = $sql1 . $sql2 . $sql3;
        DB_query($sql);
        if (!DB_error()) {
            $Cat = new Category($this->cid);
            COM_rdfUpToDateCheck ('links', $Cat->getCategory(), $lid);
            \glFusion\Cache::getInstance()->deleteItemsByTag('whatsnew');
            if ($old_lid != $lid) {
                PLG_itemSaved($lid, Config::PI_NAME, $old_lid);
            } else {
                PLG_itemSaved($lid, Config::PI_NAME);
            }
            return true;
        } else {
            return false;
        }
    }

}
