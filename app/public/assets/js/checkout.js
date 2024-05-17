import {notify} from "./global";

/**
 * Create payment intent
 * @param event
 * @returns {Promise}
 */
async function createPaymentIntent (event) {
    const formData = new FormData(event.target);

    const response = await fetch('/checkout/create/payment-intent', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
        },
        body: formData
    });

    return await response.json();
}

/**
 * Confirm card (3d secure card)
 * @returns {Promise<void>}
 */
async function confirmCard(pi) {
    return await stripe.confirmCardPayment(pi.client_secret, {
        payment_method: pi.payment_method,
    });
}

/**
 * Complete order
 * @returns {Promise<void>}
 */
function completeOrder(pi, csrfToken) {
    const formData = new FormData();
    formData.set('pi', pi.id);
    formData.set('_token', csrfToken);

    fetch('/checkout/complete', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
        },
        body: formData
    })
        .then((response) => response.json())
        .then((response) => {
            location.href = '/receipt/' + response.uid;
        }).catch((error) => {
            console.log(error);
        });
}

/**
 * Handle errors
 * @param response
 */
function handleErrors(response) {
    if (response.hasOwnProperty('input_errors')) {
        Object.keys(response.input_errors).forEach((input) => {
            const $input = document.querySelector('#checkout_' + input);
            const $error = document.createElement('div');
            $error.classList.add('invalid-feedback');
            $error.classList.add('d-block')
            $error.innerHTML = response.input_errors[input]

            $input.parentNode.appendChild($error)
        });
    }

    if (response.hasOwnProperty('errors')) {
        response.errors.forEach((error) => {
            notify('error', error);
        });
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

/**
 * Checkout form submit event
 * @type {Element}
 */
const checkoutForm = document.querySelector('#checkout_form');

if (checkoutForm) {
    checkoutForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        const token = this.querySelector('#checkout__token').value;

        removeErrorMessages();
        this.querySelector('#checkout_save').disabled = true;

        try {
            let pi = await createPaymentIntent(event);

            if (pi.hasOwnProperty('input_errors') || pi.hasOwnProperty('errors')) {
                handleErrors(pi);
                this.querySelector('#checkout_save').disabled = false;

                return;
            }

            if (pi.status === 'requires_action') {
                await confirmCard(pi).then(response => {
                    if (response.error) {
                        handleErrors({
                            errors: [response.error.message]
                        });
                    } else {
                        completeOrder(pi, token);
                    }
                })
            } else if (pi.status === 'succeeded') {
                completeOrder(pi, token);
            } else {
                console.log(pi);
                notify('error', 'Payment has failed');
            }
        } catch (error) {
            notify('error', error.message);
        }
    });
}
