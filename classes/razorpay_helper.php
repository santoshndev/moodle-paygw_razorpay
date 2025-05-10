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
 * Contains helper class to work with razorpay REST API.
 *
 * @package    paygw_razorpay
 * @copyright  2025 Santosh N.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_razorpay;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');
require_once(__DIR__ . '/../.extlib/razorpay-php/Razorpay.php');

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

/**
 * helper class.
 *
 * @package    paygw_razorpay
 * @copyright  2025 Santosh N.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class razorpay_helper {

    /**
     * @var string The payment was paid successfully for the order.
     */
    public const ORDER_STATUS_PAID = 'paid';

    /**
     * @var string The payment was captured successfully for the order.
     */
    public const PAYMENT_STATUS_CAPTURED = 'captured';

    /**
     * @var string razorpay API Client ID
     */
    private $clientid;

    /**
     * @var string razorpay API secret
     */
    private $secret;

    /**
     * @var object APi object
     */
    private $api;


    /**
     * helper constructor.
     *
     * @param string $clientid The client id.
     * @param string $secret razorpay secret.
     */
    public function __construct(string $clientid, string $secret) {
        $this->clientid = $clientid;
        $this->secret = $secret;
        $this->api = new Api($this->clientid, $this->secret);
    }

    /**
     * Create an order on Razorpay.
     * @param object $course
     * @param int $amount
     * @param string $currency
     * @return array order details
     */
    public function create_order($course, $amount, $currency): array {
        global $SESSION, $USER, $DB;
        $courseinfo = $course->shortname . " " . $course->id;
        $description = get_string('description', 'paygw_razorpay', $course->shortname);
        $receipt = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $courseinfo)));
        $orderdetails = [
            'receipt' => $receipt,
            'amount' => $amount * 100,
            'currency' => $currency,
        ];
        $razorpayorder = $this->api->order->create($orderdetails);
        $razorpayorderid = $razorpayorder['id'];
        $SESSION->razorpay_order_id = $razorpayorderid;
        $data = [
            "key" => $this->clientid,
            "amount" => $razorpayorder['amount'],
            "name" => $course->shortname,
            "description" => $description,
            "image" => "",
            "prefill" => [
                "name" => \fullname($USER),
                "email" => $USER->email,
                "contact" => $DB->get_field('user', 'phone1', ['id' => $USER->id]),
            ],
            "notes" => [
                "course_id" => $receipt,
            ],
            "order_id" => $razorpayorderid,
        ];
        return $data;
    }

    /**
     * Verify the signature of an order on Razorpay.
     * @param array $attributes
     * @return bool status
     */
    public function verify_signature($attributes): bool {
        try {
            $this->api->utility->verifyPaymentSignature($attributes);
            $success = true;
        } catch (SignatureVerificationError $e) {
            $success = false;
        }
        return $success;
    }

    /**
     * Get the order details from Razorpay
     * @param string $orderid
     * @return array orderdetails
     */
    public function get_order_details($orderid) {
        $orderdata = $this->api->order->fetch($orderid);
        return $orderdata;
    }

    /**
     * Get the Payment details from Razorpay
     * @param string $paymentid
     * @return array paymentdetails
     */
    public function fetch_payment_details($paymentid) {
        $paymentdata = $this->api->payment->fetch($paymentid);
        return $paymentdata;
    }
}
