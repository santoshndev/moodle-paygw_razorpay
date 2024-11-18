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
 * @copyright  2024 Santosh N.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace paygw_razorpay\external;

use core_payment\helper;
use external_api;
use external_function_parameters;
use external_value;
use core_payment\helper as payment_helper;
use paygw_razorpay\event\payment_created;
use paygw_razorpay\razorpay_helper;

/**
 * webservice for transaction complete operation
 *
 * @package    paygw_razorpay
 * @copyright  2024 Santosh N.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class transaction_complete extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'component' => new external_value(PARAM_COMPONENT, 'The component name'),
            'paymentarea' => new external_value(PARAM_AREA, 'Payment area in the component'),
            'itemid' => new external_value(PARAM_INT, 'The item id in the context of the component area'),
            'orderid' => new external_value(PARAM_TEXT, 'The order id coming back from razorpay'),
            'paymentid' => new external_value(PARAM_TEXT, 'The payment id coming back from razorpay'),
            'signature' => new external_value(PARAM_RAW, 'The signature coming back from razorpay'),
        ]);
    }

    /**
     * Perform what needs to be done when a transaction is reported to be complete.
     * This function does not take cost as a parameter as we cannot rely on any provided value.
     *
     * @param string $component Name of the component that the itemid belongs to
     * @param string $paymentarea
     * @param int $itemid An internal identifier that is used by the component
     * @param string $orderid razorpay order ID
     * @param string $paymentid razorpay payment ID
     * @param string $signature razorpay signature
     * @return array
     */
    public static function execute(string $component, string $paymentarea, int $itemid,
                                   string $orderid, string $paymentid, string $signature): array {
        global $USER, $DB;

        self::validate_parameters(self::execute_parameters(), [
            'component' => $component,
            'paymentarea' => $paymentarea,
            'itemid' => $itemid,
            'orderid' => $orderid,
            'paymentid' => $paymentid,
            'signature' => $signature,
        ]);

        // Attributes required for verifying payment signature.
        $orderdata = [
            'razorpay_signature' => $signature,
            'razorpay_payment_id' => $paymentid,
            'razorpay_order_id' => $orderid,
        ];

        $config = (object)helper::get_gateway_configuration($component, $paymentarea, $itemid, 'razorpay');

        $payable = payment_helper::get_payable($component, $paymentarea, $itemid);
        $currency = $payable->get_currency();

        // Add surcharge if there is any.
        $surcharge = helper::get_gateway_surcharge('razorpay');
        $amount = helper::get_rounded_cost($payable->get_amount(), $currency, $surcharge);

        $razorpayhelper = new razorpay_helper($config->clientid, $config->secret);
        // Get order details from razorpay.
        $orderdetails = $razorpayhelper->get_order_details($orderid);
        // Verify the signature on razorpay.
        $verifysignature = $razorpayhelper->verify_signature($orderdata);

        $success = false;
        $message = '';

        if ($orderdetails && $verifysignature) {
            if ($orderdetails['status'] == razorpay_helper::ORDER_STATUS_PAID) {

                $capture = $razorpayhelper->fetch_payment_details($paymentid);
                if ($capture && ($capture['status'] == razorpay_helper::PAYMENT_STATUS_CAPTURED)) {
                    $success = true;
                    // Everything is correct. Let's give them what they paid for.
                    try {

                        $paymentid1 = payment_helper::save_payment($payable->get_account_id(), $component, $paymentarea,
                            $itemid, (int)$USER->id, $amount, $currency, 'razorpay');

                        // Store razorpay extra information.
                        $record = new \stdClass();
                        $record->paymentid = $paymentid1;
                        $record->rp_orderid = $orderid;
                        $record->rp_paymentid = $paymentid;
                        $record->rp_signature = $signature;

                        $DB->insert_record('paygw_razorpay', $record);

                        // Trigger the event when payment successful.
                        $instance = $DB->get_record('enrol', ['id' => $itemid]);
                        $eventparams = [
                            'objectid' => $paymentid1,
                            'userid' => $USER->id,
                            'context' => \context_course::instance($instance->courseid),
                            'other' => [
                                'currency' => $payable->get_currency(),
                                'amount' => $amount,
                            ],
                        ];
                        $event = payment_created::create($eventparams);
                        $event->trigger();

                        payment_helper::deliver_order($component, $paymentarea, $itemid, $paymentid1, (int)$USER->id);
                    } catch (\Exception $e) {
                        debugging('Exception while trying to process payment: ' . $e->getMessage(), DEBUG_DEVELOPER);
                        $success = false;
                        $message = get_string('internalerror', 'paygw_razorpay');
                    }
                } else {
                    $success = false;
                    $message = get_string('paymentnotcleared', 'paygw_razorpay');
                }

            } else {
                $success = false;
                $message = get_string('paymentnotcleared', 'paygw_razorpay');
            }
        } else {
            // Could not capture authorization!.
            $success = false;
            $message = get_string('cannotfetchorderdatails', 'paygw_razorpay');
        }

        return [
            'success' => $success,
            'message' => $message,
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_function_parameters
     */
    public static function execute_returns() {
        return new external_function_parameters([
            'success' => new external_value(PARAM_BOOL, 'Whether everything was successful or not.'),
            'message' => new external_value(PARAM_RAW, 'Message (usually the error message).'),
        ]);
    }
}
