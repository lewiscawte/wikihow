var title_to_open;
var title_to_add;
var requester;

function add(key) {
  document.temp.related.options[document.temp.related.length] = new Option(key, key);
  document.temp.related.selectedIndex = document.temp.related.length - 1;
}

function alertContents() {
    if (requester.readyState == 4) {
      if (requester.status == 200) {
        var string = requester.responseText;
        var results = document.getElementById('lucene_results');
        arr = string.split('\n');
        var count = 0;
	    var html = '<b>Results</b><br/>2. Click on a link to preview the article. To add the article as a related wikiHow, click the + symbol in the list or the "add this" link in the preview. When you are finished, hit the "Save" button.<br/><ul id="results">';
		for (i = 0; i < arr.length; i++) {
            y = arr[i].replace(/^\s+|\s+$/, '');
            key = y.replace(/^http:\/\//, '');
			if (key.indexOf('/') > 0) {
				key = key.substr(key.indexOf('/') + 1);
			}
            y = key.replace(/-/g, ' ');
			y = unescape(y);
            key = key.replace(/-/g, ' ');
            key = key.replace(/%27/g, "\\'");
			if (y != '') {
                html += '<li><a href="javascript:preview(\'' + key + '\');">' + y + '</a> [<a href="javascript:add(\'' + key + '\');">+</a>]</li>';
                count++;
            }
        }
		if (count == 0) 
        	html += 'Sorry - no results.';
		results.innerHTML=html;
		} else {
		}
	}
}




function submitform() {
    document.temp.related_list.value = '';
    if (document.temp.related) {
        for(var f=0; f<document.temp.related.length; ++f)
          document.temp.related_list.value += document.temp.related.options[f].value + '|';
    }
    document.temp.submit();
}

function remove_related() {
    for(var f=0; f<document.temp.related.length; ++f){
     if (document.temp.related.options[f].selected) {
        document.temp.related.options[f] = null;
        break;
     }       
    }   
   return false;
}

function preview(key, title){
    if (title == null)
        title = key.replace(/-/g, ' ');
    
    try {
        requester = new XMLHttpRequest();
    } catch (error) {
        try {
            requester = new ActiveXObject('Microsoft.XMLHTTP');
        } catch (error) {
            return false;
        }
    }
    requester.onreadystatechange = previewContents;
    requester.open('GET', wgServer + '/Special:PreviewPage?target=' + key);
    requester.send(null);
    var results = document.getElementById('preview');
    title_to_open = wgServer + '/' + key;
    title_to_add = key;
	results.innerHTML = '[<a href="javascript:add(title_to_add);">add</a>] [<a onclick="javascript:window.open(title_to_open);">open in a new window]</a> <br/>';
}

function previewContents() {
    if (requester.readyState == 4) {
      if (requester.status == 200) {
        var string = requester.responseText;

        // replace links
/*
        string = string.replace(/href=[\'\"]\/([^\'\"]*)[\'\"]/, 'href=\'#\' onclick=\"preview(\'$1\');\"/');
*/
        var results = document.getElementById('preview');
        results.innerHTML += string;
        //results.className = 'previewTd';

	results.scrollTo();
       }
    }
}

function check(){
    try {
        requester = new XMLHttpRequest();
    } catch (error) {
        try {
            requester = new ActiveXObject('Microsoft.XMLHTTP');
        } catch (error) {
            return false;
        }
    }
    requester.onreadystatechange = alertContents;
    requester.open('GET', wgServer + '/Special:LSearch?raw=true&search=' + document.temp.title.value, true);
    var results = document.getElementById('lucene_results');
    results.innerHTML = 'Retrieving results...';
    requester.send(null);
    return false;
}

function viewRelated() {
       for(var f=0; f<document.temp.related.length; ++f){
           if (document.temp.related.options[f].selected) { 
            //window.open('http://www.wikihow.com/' + document.temp.related.options[f].value);
            window.open('http://www.wikihow.com/' + document.temp.related.options[f].value);
             break;
           }                         
     }   
}   

function moveRelated(bDir) {
  var el = document.temp.related;
  var idx = el.selectedIndex
  if (idx==-1) {
        return;
  } else {
    var nxidx = idx+( bDir? -1 : 1)
    if (nxidx<0) nxidx=el.length-1
    if (nxidx>=el.length) nxidx=0
    var oldVal = el[idx].value
    var oldText = el[idx].text
    el[idx].value = el[nxidx].value
    el[idx].text = el[nxidx].text
    el[nxidx].value = oldVal
    el[nxidx].text = oldText
    el.selectedIndex = nxidx
  }
}


