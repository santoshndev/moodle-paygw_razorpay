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
 * This module is responsible for razorpay content in the gateways modal.
 *
 * @module     paygw_razorpay/gateway_modal
 * @copyright  2025 Santosh N.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as Repository from './repository';
import Templates from 'core/templates';
import Modal from 'core/modal';
import ModalEvents from 'core/modal_events';
import {getString} from 'core/str';


/**
 * Creates and shows a modal that contains a placeholder.
 *
 * @returns {Promise<Modal>}
 */
const showModalWithPlaceholder = async() => await Modal.create({
    body: await Templates.render('paygw_razorpay/razorpay_button_placeholder', {}),
    show: true,
    removeOnClose: true,
});

/**
 * Process the payment.
 *
 * @param {string} component Name of the component that the itemId belongs to
 * @param {string} paymentArea The area of the component that the itemId belongs to
 * @param {number} itemId An internal identifier that is used by the component
 * @returns {Promise<string>}
 */
export const process = (component, paymentArea, itemId) => {
    return Promise.all([
        showModalWithPlaceholder(),
        Repository.getConfigForJs(component, paymentArea, itemId),
    ])
        .then(([modal, razorpayConfig]) => {
            modal.getRoot().on(ModalEvents.hidden, () => {
                // Destroy when hidden.
                modal.destroy();
            });

            return Promise.all([
                modal,
                razorpayConfig,
                switchSdk(),
            ]);
        })
        .then(([modal, razorpayConfig]) => {
            modal.setBody('');

            return new Promise(resolve => {
                razorpayConfig.handler = function(response) {
                    modal.setBody(getString('authorising', 'paygw_razorpay'));
                    // eslint-disable-next-line promise/catch-or-return,promise/no-nesting
                    Repository.markTransactionComplete(component, paymentArea, itemId, response.razorpay_order_id,
                        response.razorpay_payment_id, response.razorpay_signature)
                        .then(res => {
                            modal.hide();
                            return res;
                        })
                        .then(resolve);
                };
                // eslint-disable-next-line no-undef
                var rzp1 = new Razorpay(razorpayConfig);
                rzp1.open();
            });
        })
        .then(res => {
            if (res.success) {
                // eslint-disable-next-line promise/no-return-wrap
                return Promise.resolve(res.message);
            }

            // eslint-disable-next-line promise/no-return-wrap
            return Promise.reject(res.message);
        });
};

/**
 * Unloads the previously loaded razorpay JavaScript SDK, and loads a new one.
 *
 * @returns {Promise}
 */
const switchSdk = () => {
    const sdkUrl = 'https://checkout.razorpay.com/v1/checkout.js';

    // Check to see if this file has already been loaded. If so just go straight to the func.
    if (switchSdk.currentlyloaded === sdkUrl) {
        return Promise.resolve();
    }
    if (switchSdk.currentlyloaded) {
        const suspectedScript = document.querySelector(`script[src="${switchSdk.currentlyloaded}"]`);
        if (suspectedScript) {
            suspectedScript.parentNode.removeChild(suspectedScript);
        }
    }

    const script = document.createElement('script');

    return new Promise(resolve => {
        if (script.readyState) {
            script.onreadystatechange = function() {
                if (this.readyState === 'complete' || this.readyState === 'loaded') {
                    this.onreadystatechange = null;
                    resolve();
                }
            };
        } else {
            script.onload = function() {
                resolve();
            };
        }

        script.setAttribute('src', sdkUrl);
        document.head.appendChild(script);

        switchSdk.currentlyloaded = sdkUrl;
    });
};

/**
 * Holds the full url of loaded razorpay JavaScript SDK.
 *
 * @static
 * @type {string}
 */
switchSdk.currentlyloaded = '';
