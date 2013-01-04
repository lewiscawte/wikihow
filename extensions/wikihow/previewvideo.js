
var pv_request; 

function pv_Handler() {
    if ( pv_request.readyState == 4) {
        if ( pv_request.status == 200) {
            var e = document.getElementById('viewpreview_innards');
            e.innerHTML = pv_request.responseText;
        }
    }
}

function pv_Preview() {
    try {       
        pv_request = new XMLHttpRequest();
    } catch (error) {
        try {   
            pv_request = new ActiveXObject('Microsoft.XMLHTTP');
        } catch (error) {
            return false;
        }       
    }   
    pv_request.open('GET', vp_URL,true);
    pv_request.send('');
    pv_request.onreadystatechange = pv_Handler;
    
    var e = document.getElementById('viewpreview_innards');
    e.innerHTML = "<img src='/extensions/wikihow/rotate.gif'/>";
}

function showVideoPreview() {
    var e = document.getElementById('viewpreview');
    var cls = e.getAttribute('class');
    if (cls == 'hide')  showHideVideoPreview();
}

function showHideVideoPreview() {
    var e = document.getElementById('viewpreview');
    var m = document.getElementById('show_preview_button');
    var cls = e.getAttribute('class');
    if (cls == 'show') {
        e.setAttribute('class', 'hide');
        m.setAttribute('style', 'display:inline;');
    } else {
        e.setAttribute('class', 'show');
        m.setAttribute('style', 'display:none;');
    }

}
