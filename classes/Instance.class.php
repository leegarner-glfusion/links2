<?php
/**
 * Common functions used by Links and Categories.
 *
 * @author      Lee Garner <lee AT leegarner.com>
 * @copyright   Copyright (c) 2021 Lee Garner
 * @package     links
 * @version     v3.0.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Links;

/**
 * Trait containing common functions shared by Links and Categories.
 */
trait Instance
{
    /**
     * Set the category ID.
     *
     * @param   string  $id     Category ID
     * @return  object  $this
     */
    public function withCid($id)
    {
        $this->cid = $id;
        return $this;
    }


    /**
     * Get the category ID.
     *
     * @return  string      Category ID
     */
    public function getCid()
    {
        return $this->cid;
    }


    /**
     * Set the parent category ID.
     *
     * @param   string  $id     Parent category ID
     * @return  object  $this
     */
    public function withPid($id)
    {
        $this->pid = $id;
        return $this;
    }


    /**
     * Set the category text.
     *
     * @param   string  $cat    Category name
     * @return  object  $this
     */
    public function withCategory($cat)
    {
        $this->category = $cat;
        return $this;
    }


    /**
     * Set the description.
     *
     * @param   string  $txt    Description text
     * @return  object  $this
     */
    public function withDscp($txt)
    {
        $this->dscp = $txt;
        return $this;
    }


    /**
     * Get the description.
     *
     * @return  string      Description text
     */
    public function getDscp()
    {
        return $this->dscp;
    }


    /**
     * Set the topic ID.
     *
     * @param   string  $id     Topic ID
     * @return  object  $this
     */
    public function withTid($id)
    {
        $this->tid = $id;
        return $this;
    }


    /**
     * Set the creation date.
     *
     * @param   string  $date   Creation date
     * @return  object  $this
     */
    public function withCreated($date)
    {
        $this->created = $date;
        return $this;
    }


    /**
     * Set the date modified.
     *
     * @param   string  $date   Modification date
     * @return  object  $this
     */
    public function withModified($date)
    {
        $this->modified = $date;
        return $this;
    }


    /**
     * Set the owner's user ID.
     *
     * @param   integer $id     Owner ID
     * @return  object  $this
     */
    public function withOwnerID($id)
    {
        $this->owner_id = (int)$id;
        return $this;
    }


    /**
     * Set the group ID.
     *
     * @param   integer $id     Group ID
     * @return  object  $this
     */
    public function withGroupId($id)
    {
        $this->group_id = (int)$id;
        return $this;
    }


    /**
     * Set the owner's permission level.
     *
     * @param   integer $val    Permission level
     * @return  object  $this
     */
    public function withPermOwner($val)
    {
        $this->perm_owner = (int)$val;
        return $this;
    }


    /**
     * Set the group's permission level.
     *
     * @param   integer $val    Permission level
     * @return  object  $this
     */
    public function withPermGroup($val)
    {
        $this->perm_group = (int)$val;
        return $this;
    }


    /**
     * Set the member's permission level.
     *
     * @param   integer $val    Permission level
     * @return  object  $this
     */
    public function withPermMembers($val)
    {
        $this->perm_members = (int)$val;
        return $this;
    }


    /**
     * Set Anonymous's permission level.
     *
     * @param   integer $val    Permission level
     * @return  object  $this
     */
    public function withPermAnon($val)
    {
        $this->perm_anon = (int)$val;
        return $this;
    }


    /**
     * Check if the current user has a specified level of access.
     *
     * @param   integer $required   Minimum access
     * @return  boolean     True if the user has access, False if not
     */
    public function hasAccess($required=2)
    {
        return SEC_hasAccess(
            $this->owner_id,
            $this->group_id,
            $this->perm_owner,
            $this->perm_group,
            $this->perm_members,
            $this->perm_anon
        ) >= $required;
    }

}
