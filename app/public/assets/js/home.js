/**
 * Sort change event
 */

const $sort = document.querySelector('#store-sort');

if ($sort) {
    $sort.addEventListener('change', function (event) {
        const sort = this.value;
        const urlParts = location.href.split('?');
        const query = new URLSearchParams(urlParts[1]);

        query.set('sort', sort);
        location.href = urlParts[0] + '?' + query.toString();
    });
}