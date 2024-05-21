/**
 * Delete confirmation event
 */

import {notify} from "./global";

document.querySelectorAll('.app-delete-confirm').forEach((button) => {
    button.addEventListener('click', (e) => {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
        }
    });
});

/**
 * Refund confirmation event
 */

document.querySelectorAll('.app-refund-confirm').forEach((button) => {
    button.addEventListener('click', (e) => {
        if (!confirm('Are you sure you want to refund this order?')) {
            e.preventDefault();
        }
    });
});

/**
 * Delete product file
 */
/**
 * Delete confirmation event
 */

document.querySelectorAll('.app-delete-confirm').forEach((button) => {
    button.addEventListener('click', (e) => {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
        }
    });
});

/**
 * Refund confirmation event
 */

document.querySelectorAll('.btn-delete-file').forEach((button) => {
    button.addEventListener('click', (e) => {
        e.preventDefault();

        if (!confirm('Are you sure you want to delete this file?')) {
            return;
        }

        fetch('/admin/products/delete/product/' + button.getAttribute('data-productid') + '/file/' + button.getAttribute('data-id'), {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': deleteFileCsrfToken
            },
        }).then(function(response) {
            response.json().then(function(data) {
                if (!response.ok) {
                    notify('error', data, 'popup-cart');
                } else {
                    button.parentElement.remove();
                }
            });
        }).catch(function(error) {
            console.log(error);
        });
    });
});