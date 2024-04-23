/**
 * Delete confirmation event
 */

let deleteButtons = document.querySelectorAll('.app-delete-confirm');

deleteButtons.forEach((button) => {
    button.addEventListener('click', (e) => {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
        }
    });
});