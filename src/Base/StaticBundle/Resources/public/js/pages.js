// Warns before leaving the page editor with unsaved changes (also set by the TinyMCE "change" event).
var unsaved = false;

// delegated, so fields rendered after this script are tracked too
$(document).on('change input', ':input', function () {
    unsaved = true;
});

// saving the form is not "leaving with unsaved changes" (the prompt used to appear on every save)
$(document).on('submit', 'form', function () {
    unsaved = false;
});

window.addEventListener('beforeunload', function (event) {
    if (!unsaved) {
        return undefined;
    }
    // browsers show their own text; the message is kept for the ones that still display it
    var message = Translator.trans('You have unsaved changes on this page.', {}, 'StaticBundle');
    event.preventDefault();
    event.returnValue = message;
    return message;
});