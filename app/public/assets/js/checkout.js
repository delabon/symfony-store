import {message, notify} from "./global";

/**
 * Create payment intent
 * @param data
 * @returns {Promise}
 */
async function createPaymentIntent (data) {
    try {
        const response = await fetch('/checkout/create/payment-intent', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(data)
        });

        const jsonResponse = await response.json();

        if (!response.ok) {
            const error = new Error(`HTTP error! status: ${response.status}`);
            error.jsonResponse = jsonResponse;

            throw error;
        }

        return jsonResponse;
    } catch (error) {
        throw error;
    }
}

/**
 * Confirm card (3d secure card)
 * @returns {Promise<void>}
 */
async function confirmPayment(pi) {
    return await stripe.confirmCardPayment(pi.client_secret, {
        payment_method: pi.payment_method,
    });
}

/**
 * Complete paid order
 * @returns {Promise<void>}
 */
async function completePaidOrder(pi, csrfToken, btn) {
    const formData = new FormData();
    formData.set('pi', pi.id);
    formData.set('_token', csrfToken);

    try {
        const response = await fetch('/checkout/complete/paid', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
            },
            body: formData
        });

        const jsonResponse = await response.json();

        if (!response.ok) {
            const error = new Error(`HTTP error! status: ${response.status}`);
            error.jsonResponse = jsonResponse;

            throw error;
        }

        location.href = '/receipt/' + jsonResponse.uid;

        return jsonResponse.uid;
    } catch (error) {
        btn.disabled = false;
        handleErrors(error);
        throw error;
    }
}

/**
 * Complete free order
 * @returns {Promise<void>}
 */
async function completeFreeOrder(data, btn) {
    try {
        const response = await fetch('/checkout/complete/free', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(data)
        });

        const jsonResponse = await response.json();

        if (!response.ok) {
            const error = new Error(`HTTP error! status: ${response.status}`);
            error.jsonResponse = jsonResponse;

            throw error;
        }

        location.href = '/receipt/' + jsonResponse.uid;

        return jsonResponse.uid
    } catch (error) {
        btn.disabled = false;
        handleErrors(error);
        throw error
    }
}

/**
 * Handle errors
 * @param response
 */
function handleErrors(response) {
    if (!response.hasOwnProperty('jsonResponse')){
        notify('error', response.message);

        return;
    }

    const jsonResponse = response.jsonResponse;

    if (jsonResponse.hasOwnProperty('input_errors')) {
        Object.keys(jsonResponse.input_errors).forEach((input) => {
            const keyParts = input.split('.');
            const key = keyParts[keyParts.length - 1];
            const $input = document.querySelector('.checkout-' + key);
            const $error = document.createElement('div');
            $error.classList.add('invalid-feedback');
            $error.classList.add('d-block')
            $error.innerHTML = jsonResponse.input_errors[input]
            $input.parentNode.appendChild($error)
        });
    }

    if (jsonResponse.hasOwnProperty('message')) {
        notify('error', jsonResponse.message);
    }
}

/**
 * Remove error messages
 */
function removeErrorMessages() {
    document.querySelectorAll('.invalid-feedback').forEach(el => {
        el.remove();
    });
}

function getInputs(form) {
    return {
        _token: form.querySelector('.checkout-token').value,
        firstName: form.querySelector('.checkout-firstName').value,
        lastName: form.querySelector('.checkout-lastName').value,
        email: form.querySelector('.checkout-email').value,
        address: form.querySelector('.checkout-address').value,
        city: form.querySelector('.checkout-city').value,
        zipCode: form.querySelector('.checkout-zipCode').value,
        country: form.querySelector('.checkout-country').value,
        ccNumber: '',
        ccDate: '',
        ccCvc: '',
    };
}

/**
 * Checkout form submit event
 * @type {Element}
 */
const checkoutForm = document.querySelector('#checkout_form');

if (checkoutForm) {
    checkoutForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        const btn = this.querySelector('.btn-checkout');
        const data = getInputs(this);

        removeErrorMessages();
        btn.disabled = true;

        if (orderData.total == 0) {
            completeFreeOrder(data, btn);
        } else {
            data.ccNumber = this.querySelector('.checkout-ccNumber').value;
            data.ccDate = this.querySelector('.checkout-ccDate').value;
            data.ccCvc = this.querySelector('.checkout-ccCvc').value;

            const pi = await createPaymentIntent(data)
                .then(pi => {
                    return pi;
                }).catch(error => {
                    btn.disabled = false;
                    handleErrors(error);
                    throw error;
                });

            // reset token
            data._token = pi.csrfToken;
            this.querySelector('.checkout-token').value = data._token;

            // Confirm payment
            if (pi.status === 'requires_action') {
                await confirmPayment(pi)
                    .then(response => {
                        if (response.error) {
                            btn.disabled = false;
                            handleErrors({
                                message: response.error.message
                            });
                        } else {
                            completePaidOrder(pi, data._token, btn);
                        }
                    });
            } else if (pi.status === 'succeeded') {
                await completePaidOrder(pi, data._token, btn);
            } else {
                btn.disabled = false;
                notify('error', 'Payment has failed');
            }
        }
    });
}
