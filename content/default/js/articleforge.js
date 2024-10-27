window.onload = function() {
    var sn = document.getElementById('section_name');
    if (sn) {
        var sec = document.getElementById(sn.value);
        if (sec) {
           sec.scrollIntoView(true);
        }
    }
}
