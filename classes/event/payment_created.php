<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * trigger event upon successful payment
 *
 * @package     paygw_razorpay
 * @copyright   2025 Santosh N.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace paygw_razorpay\event;

/**
 * Event class
 *
 * @package     paygw_razorpay
 * @copyright   2025 Santosh N.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class payment_created extends \core\event\base {

    /**
     * Intitial data
     */
    protected function init() {
        $this->data['objecttable'] = 'payments';
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventpaymentdone', 'paygw_razorpay');
    }
    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' done payment with id '$this->objectid' and amount ".$this->other['currency']." "
            .$this->other['amount']." through the payment gateway 'razorpay'.";
    }
}
