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
 * Privacy Subsystem implementation for paygw_razorpay.
 *
 * @package    paygw_razorpay
 * @category   privacy
 * @copyright  2024 Santosh N.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_razorpay\privacy;

use core_payment\privacy\paygw_provider;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\writer;

/**
 * Privacy Subsystem implementation for paygw_razorpay.
 *
 * @package    paygw_razorpay
 * @category   privacy
 * @copyright  2024 Santosh N.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\data_provider,
    paygw_provider {
    /**
     * Returns metadata about this plugin.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data shared from this plugin.
     */
    public static function get_metadata(collection $collection): collection {
        // Data shared with Razorpay.
        $collection->add_external_location_link(
            'razorpay_com',
            [
                'name' => 'privacy:metadata:paygw_razorpay_com:name',
                'email' => 'privacy:metadata:paygw_razorpay_com:email',
                'contact' => 'privacy:metadata:paygw_razorpay_com:phone1',
            ],
            'privacy:metadata:paygw_razorpay_com'
        );
        return $collection;
    }

    /**
     * Export all user data for the specified payment record, and the given context.
     *
     * @param \context $context Context
     * @param array $subcontext The location within the current context that the payment data belongs
     * @param \stdClass $payment The payment record
     */
    public static function export_payment_data(\context $context, array $subcontext, \stdClass $payment) {
        global $DB;

        $subcontext[] = get_string('gatewayname', 'paygw_razorpay');
        $record = $DB->get_record('paygw_razorpay', ['paymentid' => $payment->id]);

        $data = (object) [
            'orderid' => $record->rp_orderid,
        ];
        writer::with_context($context)->export_data(
            $subcontext,
            $data
        );
    }

    /**
     * Delete all user data related to the given payments.
     *
     * @param string $paymentsql SQL query that selects payment.id field for the payments
     * @param array $paymentparams Array of parameters for $paymentsql
     */
    public static function delete_data_for_payment_sql(string $paymentsql, array $paymentparams) {
        global $DB;

        $DB->delete_records_select('paygw_razorpay', "paymentid IN ({$paymentsql})", $paymentparams);
    }
}
