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
 * This class contains a list of webservice functions related to the razorpay payment gateway.
 *
 * @package    paygw_razorpay
 * @copyright  2025 Santosh N.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace paygw_razorpay\external;

use core_payment\helper;
use paygw_razorpay\razorpay_helper;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;

/**
 * This class contains a list of webservice functions related to the razorpay payment gateway.
 *
 * @package    paygw_razorpay
 * @copyright  2025 Santosh N.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_config_for_js extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'component' => new external_value(PARAM_COMPONENT, 'Component'),
            'paymentarea' => new external_value(PARAM_AREA, 'Payment area in the component'),
            'itemid' => new external_value(PARAM_INT, 'An identifier for payment area in the component'),
        ]);
    }

    /**
     * Returns the config values required by the razorpay JavaScript SDK.
     *
     * @param string $component
     * @param string $paymentarea
     * @param int $itemid
     * @return string[]
     */
    public static function execute(string $component, string $paymentarea, int $itemid): array {
        global $DB;
        self::validate_parameters(self::execute_parameters(), [
            'component' => $component,
            'paymentarea' => $paymentarea,
            'itemid' => $itemid,
        ]);

        $config = helper::get_gateway_configuration($component, $paymentarea, $itemid, 'razorpay');
        $payable = helper::get_payable($component, $paymentarea, $itemid);
        $surcharge = helper::get_gateway_surcharge('razorpay');
        $cost = helper::get_rounded_cost($payable->get_amount(), $payable->get_currency(), $surcharge);

        $courseid = $DB->get_field('enrol', 'courseid', ['id' => $itemid]);
        $course = get_course($courseid);
        $razorpayhelper = new razorpay_helper($config['clientid'], $config['secret']);
        $order = $razorpayhelper->create_order($course, $cost, $payable->get_currency());

        return $order;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'key' => new external_value(PARAM_TEXT, 'razorpay client ID'),
            'amount' => new external_value(PARAM_INT, 'Cost with gateway surcharge'),
            'name' => new external_value(PARAM_TEXT, 'Name'),
            'description' => new external_value(PARAM_TEXT, 'Description'),
            'image' => new external_value(PARAM_TEXT, 'Image'),
            'prefill' =>
                new external_single_structure([
                    'name' => new external_value(PARAM_TEXT, 'Name'),
                    'email' => new external_value(PARAM_EMAIL, 'Email'),
                    'contact' => new external_value(PARAM_TEXT, 'Contact'),
                ]),
            'notes' =>
                new external_single_structure([
                    'course_id' => new external_value(PARAM_TEXT, 'Notes'),
                ]),
            'order_id' => new external_value(PARAM_TEXT, 'Order Id'),
        ]);
    }
}
