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
namespace Links\Views;
use Links\Config;
use Links\Category;
use Links\Link;
use Template;
use glFusion\Formatter;


class publicList
{
    private $cid = '';
    private $page = 0;

    public function __construct()
    {
        global $_CONF;

        // Make sure category ID is not empty
        $this->withCid('');
    }


    public function withCid($id)
    {
        global $_LI_CONF;

        if (empty($id)) {
            $this->cid = $_LI_CONF['root'];
        } else {
            $this->cid = $id;
        }
        return $this;
    }


    public function withPage($num)
    {
        $this->page = (int)$num;
        return $this;
    }


    public function Render()
    {
        global $_CONF, $_TABLES, $_LI_CONF, $LANG_LINKS_ADMIN, $LANG_LINKS,
           $LANG_LINKS_STATS, $LANG_ADMIN;

        $display = '';
        $linkCounter = 0;

        $Cat = new Category($this->cid);
        if (empty($this->cid)) {
            if ($this->page > 1) {
                $page_title = sprintf ($LANG_LINKS[114] . ' (%d)', $this->page);
            } else {
                $page_title = $LANG_LINKS[114];
            }
        } else {
            if ($this->cid == $_LI_CONF['root']) {
                $category = $LANG_LINKS['root'];
            } else {
                $category = $Cat->getCategory();
            }
            if ($this->page > 1) {
                $page_title = sprintf(
                    $LANG_LINKS[114] . ': %s (%d)',
                    $category,
                    $this->page
                );
            } else {
                $page_title = sprintf($LANG_LINKS[114] . ': %s', $category);
            }
        }

        // Check has access to this category
        if ($this->cid != $_LI_CONF['root']) {
            if (!$Cat->hasAccess(2)) {
                return "NO Access";
            }
        }

        $T = new Template(__DIR__ . '/../../templates/');
        $T->set_file (array(
            'linklist' => 'links.thtml',
        ) );

        $T->set_var(array(
            'title' => $LANG_LINKS[114],
            'cid' =>  $this->cid,
            'cid_plain' => $this->cid,
            'cid_encoded' => urlencode($this->cid),
            'pi_url' => Config::get('url'),
        ) );

        // Create breadcrumb trail
        $T->set_var(array(
            'breadcrumbs'=> Category::breadCrumbs($_LI_CONF['root'], $this->cid),
            'lang_go' => $LANG_LINKS[124],
            'link_dropdown' => Category::optionList(2, $this->cid),
        ) );

        // Show categories
        if ($_LI_CONF['linkcols'] > 0) {
            $cCats = $Cat->getChildren();
            if (count($cCats) > 0) {
                $T->set_var('lang_categories', $LANG_LINKS_ADMIN[14]);
                $T->set_block('linklist', 'category_navigation', 'catNav');
                foreach ($cCats as $C) {
                    // Get number of child links user can see in this category
                    $linkcount = $C->countLinks();

                    // Get number of child categories user can see in this category
                    $cat_count = count($C->getChildren());

                    // Format numbers for display
                    $display_count = '';
                    // don't show zeroes
                    if ($cat_count > 0) {
                        $display_count = COM_numberFormat($cat_count);
                    }
                    if (($cat_count > 0) && ($linkcount > 0)) {
                        $display_count .= ', ';
                    }
                    if ($linkcount > 0) {
                        $display_count .= COM_numberFormat($linkcount);
                    }
                    // add brackets if child items exist
                    /*if ($display_count<>'') {
                        $display_count = '('.$display_count.')';
                    }*/

                    $T->set_var ('category_name', $C->getCategory());
                    if ($_LI_CONF['show_category_descriptions']) {
                        $T->set_var ('category_description', $C->getDscp());
                    } else {
                        $T->set_var ('category_description', '');
                    }
                    $T->set_var(array(
                        'category_link' => Category::getUrl($C->getCid()),
                        'category_count' => $display_count,
                        'width' => floor (100 / $_LI_CONF['linkcols']),
                        'link_cols' => $_LI_CONF['linkcols'],
                    ) );
                    $T->parse('catNav', 'category_navigation', true);
                }
            }
        }
        if (Link::canSubmit()) {
            $T->set_var('lang_addalink', $LANG_LINKS[116]);
        }
        $Links = Link::getByCategory($this->cid, $this->page);
        $currentcid = '';
        $i = 0;
  
        $format = new Formatter();
        $format->setNamespace('links');
        $format->setAction('description');
        $format->setType('text');
        $format->setProcessBBCode(false);
        $format->setParseURLs(false);
        $format->setProcessSmilies(false);
        $format->setCensor(true);
        $format->setParseAutoTags(true);

        $T->set_block('linklist', 'link_details', 'linkRow');
        foreach ($Links as $Link) {
            if (strcasecmp ($Link->getCid(), $currentcid) != 0) {
                $currentcid = $Link->getCid();
                $Cat = new Category($currentcid);
                $T->set_var('link_category', $Cat->getCategory());
            }

            $T->set_var(array(
                'link_url' => $Link->makeUrl(),
                'link_actual_url' =>  $Link->getUrl(),
                'link_name' => $Link->getTitle(),
                'link_hits' => COM_numberFormat($Link->getHits()),
                'link_description' => $format->parse(htmlspecialchars_decode($Link->getDscp())),
                'link_html' => $Link->makeLink(),
            ) );

            if (!COM_isAnonUser() && !SEC_hasRights('links.edit')) {
                $reporturl = Config::get('url') . '/index.php?mode=report&amp;lid=' . $Link->getLid();
                $T->set_var('link_broken',
                    COM_createLink(
                        $LANG_LINKS[117],
                        $reporturl,
                        array(
                            'class' => 'pluginSmallText',
                            'rel'   => 'nofollow noindex noopener',
                        )
                    )
                );
            } else {
                $T->set_var ('link_broken', '');
            }

            if ($Link->hasAccess(3) && SEC_hasRights('links.edit')) {
                $editurl = Config::get('admin_url') . '/index.php?edit=x&amp;lid=' . $Link->getLid();
                $T->set_var ('edit_url',$editurl);
                $T->set_var ('link_edit', COM_createLink($LANG_ADMIN['edit'],$editurl));
                $edit_icon = '<i class="uk-icon-edit tooldip" title="' . $LANG_ADMIN['edit'] . '"/>';
                $T->set_var ('edit_icon', COM_createLink($edit_icon, $editurl));
            } else {
                $T->set_var ('link_edit', '');
                $T->set_var ('edit_icon', '');
            }

            $T->parse('linkRow', 'link_details', true);
            $i++;
        }

        $numlinks = $Cat->countLinks();
        $linkCounter += $numlinks;
        $pages = 0;
        if ($_LI_CONF['linksperpage'] > 0) {
            $pages = (int) ($numlinks / $_LI_CONF['linksperpage']);
            if (($numlinks % $_LI_CONF['linksperpage']) > 0 ) {
                $pages++;
            }
        }
        if ($pages > 0) {
            if (($_LI_CONF['linkcols'] > 0) && isset($currentcid)) {
                $catlink = Category::getUrl($currentcid);
            } else {
                $catlink = Config::get('url');
            }
            $T->set_var(
                'page_navigation',
                COM_printPageNavigation(
                    $catlink,
                    $this->page,
                    $pages
                )
            );
        } else {
            $T->set_var('page_navigation', '');
        }

        if ( $_LI_CONF['linksperpage'] == 'x' ) {
            $social_icons = \glFusion\Social\Social::getShareIcons();
            $T->set_var('social_share',$social_icons);
        }

        if ($linkCounter == 0) {
            $T->set_var('nolinks',true);
        }

        $T->parse('output', 'linklist');
        $display .= $T->finish($T->get_var ('output'));
        return $display;
    }

}


