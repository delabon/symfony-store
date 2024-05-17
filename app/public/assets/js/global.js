/**
 * Function to display alert message
 * @param type
 * @param message
 * @param cssClass
 * @returns {string}
 */
export function message (type, message, cssClass) {
  return '<div class="alert alert-' + type + ' alert-dismissible fade show ' + cssClass + '" role="alert">' + message + '</div>';
}

/**
 * Function to display notification message
 * @param type
 * @param message
 * @param cssClass
 */
export function notify (type, message, cssClass) {
  document.querySelectorAll('.popup').forEach((popup) => {
    popup.remove();
  });

  let markup = '<div class="popup popup--' + type + ' ' + cssClass + '" role="alert">' + message + '</div>';
  document.body.insertAdjacentHTML('beforeend', markup);

  setTimeout(() => {
    document.querySelector('.' + cssClass).remove();
  }, 5000);
}
