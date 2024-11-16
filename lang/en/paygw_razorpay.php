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
 * Strings for component 'paygw_razorpay', language 'en'
 *
 * @package    paygw_razorpay
 * @copyright  2024 Santosh N.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['amountmismatch'] = 'The amount you attempted to pay does not match the required fee. Your account has not been debited.';
$string['authorising'] = 'Authorising the payment. Please wait...';
$string['brandname'] = 'Brand name';
$string['brandname_help'] = 'An optional label that overrides the business name for the razorpay account on the razorpay site.';
$string['cannotfetchorderdatails'] = 'Could not fetch payment details from razorpay. Your account has not been debited.';
$string['clientid'] = 'Client ID';
$string['clientid_help'] = 'The client ID that razorpay generated for your application.';
$string['description'] = 'Enrolment Fees for the Course: {$a}';
$string['environment'] = 'Environment';
$string['environment_help'] = 'You can set this to Sandbox if you are using sandbox accounts (for testing purpose only).';
$string['eventordercreated'] = 'Razorpay Order Created';
$string['eventpaymentdone'] = 'Online Payment Successful';
$string['gatewaydescription'] = 'Choose this option if you want to pay the fee in online mode';
$string['gatewayname'] = 'razorpay';
$string['internalerror'] = 'An internal error has occurred. Please contact us.';
$string['live'] = 'Live';
$string['paymentnotcleared'] = 'payment not cleared by razorpay.';
$string['pluginname'] = 'Razorpay';
$string['pluginname_desc'] = 'The razorpay plugin allows you to receive payments via razorpay.';
$string['privacy:metadata:paygw_razorpay_com'] = 'Shares the required user data with Razorpay for processing payments';
$string['privacy:metadata:paygw_razorpay_com:email'] = 'Email of the user requesting a transaction';
$string['privacy:metadata:paygw_razorpay_com:name'] = 'Full name of the user requesting a transaction';
$string['privacy:metadata:paygw_razorpay_com:phone1'] = 'Phone number of the user requesting a transaction';
$string['repeatedorder'] = 'This order has already been processed earlier.';
$string['sandbox'] = 'Sandbox';
$string['secret'] = 'Secret';
$string['secret_help'] = 'The secret that razorpay generated for your application.';
