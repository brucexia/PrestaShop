/*

 */

function init() {
    var tag = $('#block_tag');
    tag.change(function (eventData) {
        $.cookie('tag', this.value);
    });

    var tagCookie = $.cookie('tag');
    if (tagCookie != '' && tagCookie != null) {
        tag.val(tagCookie);
    } else {//set the cookie to current selected tag
        $.cookie(tag.value);
    }

}
$(document).ready(function () {
    init();
});
