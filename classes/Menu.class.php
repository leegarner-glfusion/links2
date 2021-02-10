<?php
/**
 * Class to provide admin and user-facing menus.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2019 Lee Garner <lee@leegarner.com>
 * @package     shop
 * @version     v1.0.0
 * @since       v0.7.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Links;
use Shop\Template;


/**
 * Class to provide admin and user-facing menus.
 * @package shop
 */
class Menu
{
    /**
     * Create the user menu.
     *
     * @param   string  $view   View being shown, so set the help text
     * @return  string      Administrator menu
     */
    public static function User($view='')
    {
        global $_CONF, $LANG_SHOP;

        USES_lib_admin();

        $hdr_txt = SHOP_getVar($LANG_SHOP, 'user_hdr_' . $view);
        $menu_arr = array(
            array(
                'url'  => SHOP_URL . '/index.php',
                'text' => $LANG_SHOP['back_to_catalog'],
            ),
            array(
                'url'  => COM_buildUrl(SHOP_URL . '/account.php?mode=orderhist'),
                'text' => $LANG_SHOP['purchase_history'],
                'active' => $view == 'orderhist' ? true : false,
            ),
            array(
                'url' => COM_buildUrl(SHOP_URL . '/account.php?mode=addresses'),
                'text' => $LANG_SHOP['addresses'],
                'active' => $view == 'addresses' ? true : false,
            ),
        );

        // Show the Gift Cards menu item only if enabled.
        if (Config::get('gc_enabled')) {
            $active = $view == 'couponlog' ? true : false;
            $menu_arr[] = array(
                'url'  => COM_buildUrl(SHOP_URL . '/account.php?mode=couponlog'),
                'text' => $LANG_SHOP['gc_activity'],
                'active' => $active,
                'link_admin' => plugin_ismoderator_shop(),
            );
        }
        return \ADMIN_createMenu($menu_arr, '');
    }


    /**
     * Create the administrator menu.
     *
     * @param   string  $view   View being shown, so set the help text
     * @return  string      Administrator menu
     */
    public static function Admin($view='')
    {
        global $_CONF, $LANG_ADMIN, $LANG_LINKS_ADMIN;

        USES_lib_admin();
        $admin_url = Config::get('admin_url');
        if ($view == 'categories') {
            $new_link = $admin_url . '/category.php?edit';
        } else {
            $new_link = $admin_url . '/index.php?edit=x';
        }
        $menu_arr = array(
            array(
                'url' => $admin_url . '/index.php',
                'text' => $LANG_LINKS_ADMIN[53],
                'active'=> $view == 'links' ? true : false,
            ),
            array(
                'url' => $new_link,
                'text' => $LANG_ADMIN['create_new'],
                'active'=> $view == 'edit' ? true : false,
            ),
            array(
                'url' => $admin_url . '/category.php',
                'text' => $LANG_LINKS_ADMIN[50],
                'active'=> $view == 'categories' ? true : false,
            ),
        );
        if ($view == '' || $view == 'links' || $view == 'validate') {
            $menu_arr[] = array(
                'url' => $admin_url . '/index.php?validate=enabled',
                'text' => $LANG_LINKS_ADMIN[26],
                'active'=> $view == 'validate' ? true : false,
            );
        }
        $menu_arr[] = array(
            'url' => $_CONF['site_admin_url'],
            'text' => $LANG_ADMIN['admin_home'],
        );

        $T = new \Template(__DIR__ . '/../templates/admin');
        $T->set_file('title', 'title.thtml');
        $T->set_var(array(
            'title' => Config::get('pi_display_name') . ' (' . Config::get('pi_version') . ')',
            'icon'  => plugin_geticon_links2(),
            'is_admin' => true,
        ) );
        $retval = $T->parse('', 'title');
        $retval .= \ADMIN_createMenu(
            $menu_arr,
            '',
            plugin_geticon_shop()
        );
        return $retval;
    }

    /**
     * Display only the page title.
     * Used for pages that do not feature a menu, such as the catalog.
     *
     * @param   string  $page_title     Page title text
     * @param   string  $page           Page name being displayed
     * @return  string      HTML for page title section
     */
    public static function pageTitle($page_title = '', $page='')
    {
        global $_USER;

        $T = new Template;
        $T->set_file('title', 'shop_title.thtml');
        $T->set_var(array(
            'title' => $page_title,
            'is_admin' => plugin_ismoderator_shop(),
            'link_admin' => plugin_ismoderator_shop(),
            'link_account' => ($page != 'account' && $_USER['uid'] > 1),
        ) );
        if ($page != 'cart' && Cart::getCartID()) {
            $item_count = Cart::getInstance()->hasItems();
            if ($item_count) {
                $T->set_var('link_cart', $item_count);
            }
        }
        return $T->parse('', 'title');
    }


    /**
     * Display the site header, with or without blocks according to configuration.
     *
     * @param   string  $title  Title to put in header
     * @param   string  $meta   Optional header code
     * @return  string          HTML for site header, from COM_siteHeader()
     */
    public static function siteHeader($title='', $meta='')
    {
        global $_LI_CONF;

        $retval = '';

        switch($_LI_CONF['displayblocks']) {
        case 2:     // right only
        case 0:     // none
            $retval .= COM_siteHeader('none', $title, $meta);
            break;

        case 1:     // left only
        case 3:     // both
        default :
            $retval .= COM_siteHeader('menu', $title, $meta);
            break;
        }
        return $retval;
    }


    /**
     * Display the site footer, with or without blocks as configured.
     *
     * @return  string      HTML for site footer, from COM_siteFooter()
     */
    public static function siteFooter()
    {
        global $_LI_CONF;

        $retval = '';
        switch($_LI_CONF['displayblocks']) {
        case 2 : // right only
        case 3 : // left and right
            $retval .= COM_siteFooter();
            break;

        case 0: // none
        case 1: // left only
        default :
            $retval .= COM_siteFooter();
            break;
        }
        return $retval;
    }


    /**
     * Show the submenu for the checkout workflow.
     *
     * @param   object  $Cart   Cart object, to see what steps are needed
     * @param   string  $step   Current step name
     * @return  string      HTML for workflow menu
     */
    public static function checkoutFlow($Cart, $step = 'viewcart')
    {
        $Flows = Workflow::getAll();
        $flow_count = 0;
        $T = new Template('workflow/');
        $T->set_file('menu', 'menu.thtml');
        $T->set_block('menu', 'Flows', 'Flow');
        foreach ($Flows as $Flow) {
            if (!$Flow->isNeeded($Cart)) {
                continue;
            }
            $flow_count++;
            $T->set_var(array(
                'mnu_cls' => 'completed',
                'wf_name' => $Flow->getName(),
                'wf_title' => $Flow->getTitle(),
                'is_done' => $Flow->isSatisfied($Cart) ? 1 : 0,
                'is_active' => $Flow->getName() == $step ? 1 : 0,
                'current_wf' => $step,
            ) );
            $T->parse('Flow', 'Flows', true);
        }
        $T->set_var(array(
            'wrap_form' => $step != 'confirm',
            'flow_count' => $flow_count,
        ) );
        $T->parse('output', 'menu');
        return $T->finish($T->get_var('output'));
    }


}

?>


