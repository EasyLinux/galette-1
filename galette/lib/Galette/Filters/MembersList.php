<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Members list filters and paginator
 *
 * PHP version 5
 *
 * Copyright © 2009-2012 The Galette Team
 *
 * This file is part of Galette (http://galette.tuxfamily.org).
 *
 * Galette is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Galette is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Galette. If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Classes
 * @package   Galette
 *
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2009-2012 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @version   SVN: $Id$
 * @link      http://galette.tuxfamily.org
 * @since     march, 3rd 2009
 */

namespace Galette\Filters;

use Galette\Common\KLogger as KLogger;
use Galette\Core\Pagination as Pagination;
use Galette\Entity\Group as Group;
use Galette\Repository\Members as Members;

/**
 * Members list filters and paginator
 *
 * @name      MembersList
 * @category  Classes
 * @package   Galette
 *
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2009-2012 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 */

class MembersList extends Pagination
{
    //filters
    private $_filter_str;
    private $_field_filter;
    private $_membership_filter;
    private $_account_status_filter;
    private $_email_filter;
    private $_group_filter;

    private $_selected;
    private $_unreachable;

    /**
    * Default constructor
    */
    public function __construct()
    {
        $this->reinit();
    }

    /**
    * Returns the field we want to default set order to
    *
    * @return string field name
    */
    protected function getDefaultOrder()
    {
        return 'nom_adh';
    }

    /**
    * Reinit default parameters
    *
    * @return void
    */
    public function reinit()
    {
        parent::reinit();
        $this->_filter_str = null;
        $this->_field_filter = null;
        $this->_membership_filter = null;
        $this->_account_status_filter = null;
        $this->_email_filter = Members::FILTER_DC_EMAIL;
        $this->_group_filter = null;
        $this->_selected = array();
    }

    /**
    * Global getter method
    *
    * @param string $name name of the property we want to retrive
    *
    * @return object the called property
    */
    public function __get($name)
    {
        global $log;

        $log->log(
            '[MembersList] Getting property `' . $name . '`',
            KLogger::DEBUG
        );

        if ( in_array($name, $this->pagination_fields) ) {
            return parent::__get($name);
        } else {
            $return_ok = array(
                'filter_str',
                'field_filter',
                'membership_filter',
                'account_status_filter',
                'email_filter',
                'group_filter',
                'selected',
                'unreachable'
            );
            if (in_array($name, $return_ok)) {
                $name = '_' . $name;
                return $this->$name;
            } else {
                $log->log(
                    '[MembersList] Unable to get proprety `' .$name . '`',
                    KLogger::WARN
                );
            }
        }
    }

    /**
    * Global setter method
    *
    * @param string $name  name of the property we want to assign a value to
    * @param object $value a relevant value for the property
    *
    * @return void
    */
    public function __set($name, $value)
    {
        global $log;

        if ( in_array($name, $this->pagination_fields) ) {
            parent::__set($name, $value);
        } else {
            $log->log(
                '[MembersList] Setting property `' . $name . '`',
                KLogger::DEBUG
            );

            switch($name) {
            case 'selected':
            case 'unreachable':
                if (is_array($value)) {
                    $name = '_' . $name;
                    $this->$name = $value;
                } else {
                    $log->log(
                        '[MembersList] Value for property `' . $name .
                        '` should be an array (' . gettype($value) . ' given)',
                        KLogger::WARN
                    );
                }
                break;
            case 'filter_str':
                $name = '_' . $name;
                $this->$name = $value;
                break;
            case 'field_filter':
            case 'membership_filter':
            case 'account_status_filter':
                if ( is_numeric($value) ) {
                    $name = '_' . $name;
                    $this->$name = $value;
                } else {
                    $log->log(
                        '[MembersList] Value for property `' . $name .
                        '` should be an integer (' . gettype($value) . ' given)',
                        KLogger::WARN
                    );
                }
                break;
            case 'email_filter':
                switch ($value) {
                case Members::FILTER_DC_EMAIL:
                case Members::FILTER_W_EMAIL:
                case Members::FILTER_WO_EMAIL:
                    $this->_email_filter = $value;
                    break;
                default:
                    $log->log(
                        '[MembersList] Value for email filter should be either ' .
                        Members::FILTER_DC_EMAIL . ', ' .
                        Members::FILTER_W_EMAIL . ' or ' .
                        Members::FILTER_WO_EMAIL . ' (' . $value . ' given)',
                        KLogger::WARN
                    );
                    break;
                }
                break;
            case 'group_filter':
                if ( is_numeric($value) ) {
                    //check group existence
                    $g = new Group();
                    $res = $g->load($value);
                    if ( $res === true ) {
                        $this->_group_filter = $value;
                    } else {
                        $log->log(
                            'Group #' . $value . ' does not exists!',
                            KLogger::WARN
                        );
                    }
                } else {
                    $log->log(
                        '[MembersList] Value for group filter should be an '
                        .'integer (' . gettype($value) . ' given',
                        KLogger::WARN
                    );
                }
                break;
            default:
                $log->log(
                    '[MembersList] Unable to set proprety `' . $name . '`',
                    KLogger::WARN
                );
                break;
            }
        }
    }

    /**
     * Add SQL limit
     *
     * @param Zend_Db_Select $select Original select
     *
     * @return <type>
     */
    public function setLimit($select)
    {
        return $this->setLimits($select);
    }

    /**
     * Set counter
     *
     * @param int $c Count
     *
     * @return void
     */
    public function setCounter($c)
    {
        $this->counter = (int)$c;
        $this->countPages();
    }
}
?>