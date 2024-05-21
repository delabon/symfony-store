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

document.querySelectorAll('.app-refund-confirm').forEach((button) => {
    button.addEventListener('click', (e) => {
        if (!confirm('Are you sure you want to refund this order?')) {
            e.preventDefault();
        }
    });
});