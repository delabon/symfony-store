import { notify } from "./global.js";

/**
 * Update cart count
 */
function updateCartCount () {
    document.querySelector('.app-cart-btn span').innerHTML = document.querySelectorAll('.cart-items .dropdown-item').length;
}

/**
 * Load cart count when page is loaded
 */

window.addEventListener('load', () => {
    if (!document.querySelector('.app-cart-btn')) {
        return;
    }

    fetch('/cart', {
        method: 'GET',
        headers: {
            'Content-Type': 'text/html',
            'X-CSRF-TOKEN': cartCsrfToken
        }
    }).then(function(response) {
        response.text().then(function(data) {
            if (!response.ok) {
                notify('error', data, 'popup-cart');
            } else {
                document.querySelector('body').insertAdjacentHTML('beforeend', data);
                updateCartCount();
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
        event.stopPropagation();

        let target = event.target;

        if (target.tagName.toLowerCase() === 'img') {
            target = target.parentElement;
        }

        const productId = target.dataset.id;

        fetch('/cart/add/' + productId, {
            method: 'POST',
            headers: {
                'Content-Type': 'text/html',
                'X-CSRF-TOKEN': cartCsrfToken
            }
        }).then(function(response) {
            response.text().then(function(data) {
                if (!response.ok) {
                    notify('error', data, 'popup-cart');
                } else {
                    notify('success', 'Product has been added to cart', 'popup-cart');
                    document.querySelector('body').dispatchEvent(new Event('cart-update'));

                    if (document.querySelector('.cart-shadow')) {
                        document.querySelector('.cart-shadow').remove();
                        document.querySelector('.cart-wrapper').remove();
                    }

                    document.querySelector('body').insertAdjacentHTML('beforeend', data);
                    updateCartCount();
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
    if (event.target.matches('.btn-remove-from-cart')) {
        event.preventDefault();

        const productId = event.target.dataset.id;

        fetch('/cart/remove/' + productId, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'text/html',
                'X-CSRF-TOKEN': cartCsrfToken
            },
        }).then(function(response) {
            response.text().then(function(data) {
                if (!response.ok) {
                    notify('error', data, 'popup-cart');
                } else {
                    notify('success', 'Product has been deleted from cart', 'popup-cart');
                    document.querySelector('body').dispatchEvent(new Event('cart-update'));

                    if (document.querySelector('.cart-shadow')) {
                        document.querySelector('.cart-shadow').remove();
                        document.querySelector('.cart-wrapper').remove();
                    }

                    document.querySelector('body').insertAdjacentHTML('beforeend', data);
                    updateCartCount();
                }
            });
        }).catch(function(error) {
            console.log(error);
        });
    }
});

/**
 * Open cart
 */

document.querySelector('.app-cart-btn').addEventListener('click', (event) => {
    event.preventDefault();

    document.querySelector('body').classList.add('show-cart');
});


/**
 * Close cart
 */

document.addEventListener('click', (event) => {
    if (event.target.classList.contains('app-cart-close-btn')) {
        event.preventDefault();
        document.querySelector('body').classList.remove('show-cart');
    }
});
