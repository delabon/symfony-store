import { notify } from "./global.js";

/**
 * Load cart template when page is loaded
 */

window.addEventListener('load', () => {
    fetch('/cart', {
        method: 'GET',
        headers: {
            'Content-Type': 'text/html'
        }
    }).then(function(response) {
        response.text().then(function(data) {
            if (!response.ok) {
                notify('error', data, 'popup-cart');
            } else {
                document.querySelector('.app-main-header .nav-item-cart .dropdown-menu').innerHTML = data;
            }
        });
    }).catch(function(error) {
        console.log(error);
    });
});

/**
 * Add to cart
 */

document.querySelectorAll('.btn-add-to-cart').forEach((button) => {
    button.addEventListener('click', (event) => {
        event.preventDefault();

        const productId = event.target.dataset.id;

        fetch('/cart/add/' + productId, {
            method: 'POST',
            headers: {
                'Content-Type': 'text/html'
            }
        }).then(function(response) {
            response.text().then(function(data) {
                if (!response.ok) {
                    notify('error', data, 'popup-cart');
                } else {
                    document.querySelector('.app-main-header .nav-item-cart .dropdown-menu').innerHTML = data;
                    notify('success', 'Product has been added to cart', 'popup-cart');
                    document.querySelector('.app-main-header .nav-item-cart .nav-link').dispatchEvent(new Event('click'));
                }
            });
        }).catch(function(error) {
            console.log(error);
        });
    });
});

/**
 * Remove from cart
 */

document.addEventListener('click', function(event) {
    // Check if the clicked element matches your selector
    if (event.target.matches('.dropdown-menu .btn-remove-from-cart')) {
        event.preventDefault();

        const productId = event.target.dataset.id;

        fetch('/cart/remove/' + productId, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'text/html'
            },
        }).then(function(response) {
            response.text().then(function(data) {
                if (!response.ok) {
                    notify('error', data, 'popup-cart');
                } else {
                    document.querySelector('.app-main-header .nav-item-cart .dropdown-menu').innerHTML = data;
                    notify('success', 'Product has been deleted from cart', 'popup-cart');
                    document.querySelector('.app-main-header .nav-item-cart .nav-link').dispatchEvent(new Event('click'));
                }
            });
        }).catch(function(error) {
            console.log(error);
        });
    }
});

/**
 * Quantity change
 */

document.addEventListener('input', function(event) {
    if (event.target.matches('.input-quantity')) {
        const productId = event.target.dataset.id;
        const quantity = event.target.value;

        fetch('/cart/update/' + productId + '/' + quantity, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'text/html'
            },
        }).then(function(response) {
            if (!response.ok) {
                notify('error', 'Quantity must be a positive number', 'popup-cart');
                return;
            }

            response.text().then(function(data) {
                document.querySelector('.app-main-header .nav-item-cart .dropdown-menu').innerHTML = data;
                notify('success', 'Quantity has been updated', 'popup-cart');
            });
        }).catch(function(error) {
            console.log(error);
        });
    }
});
