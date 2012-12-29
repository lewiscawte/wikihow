<?

class PopBox {
	
	function getGuidedEditorButton( ) {
		global $wgUser;
		return "<a class='button white_button_150 " . ($wgUser->getID() == 0 ? " disabled" : "") . "' style='float:left;' onmouseover='button_swap(this);' onmouseout='button_unswap(this);' id='weave_button' accesskey='" .wfMsg('popbox_accesskey') ."'  onclick='PopItFromGuided(); return false;' href='#' " . ($wgUser->getID() == 0 ? " disabled=\"disabled\" " : "") . ">" . wfMsg('popbox_add_related') . "</a>";
	           
	}
	
	function getToolbarButton( ) {
		 return "<div style='float:left; '><input id='weave_button' type='image' src='/extensions/PopBox/button_related.png' accesskey='" .wfMsg('popbox_accesskey') ."' onclick='javascript:PopIt(document.editform.wpTextbox1)'></div>\n"  ;		
	}
	
	function getPopBoxJSAdvanced() {
	
		$js = "
	<script type=\"text/javascript\" src=\"/extensions/PopBox/PopBox.js\"></script>
		<script language='javascript'>
			var activeElement = null;
		</script>
		"  . PopBox::getPopBoxJS();
		return $js;
	}
	
	function getPopBoxJSGuided() {
	
		$js = "
	
	
	<script type=\"text/javascript\" src=\"/extensions/PopBox/PopBox.js?1\"></script>
		<script language='javascript'>
	function blurHandler(evt) {
		//activeElement = null;
	}
	function focusHandler(evt) {
		if (!resetAccessKey && navigator.userAgent.indexOf(\"MSIE\") >= 0) {
			document.getElementById('weave_button').accessKey  = '';
		}
		resetAccessKey = true;
		var e = evt ? evt : window.event;
		if (!e) return;
		if (e.target)
			activeElement = e.target;
		else if(e.srcElement) activeElement = e.srcElement;
	}
	function loadHandler() {
		for (var j = 0; j < document.editform.elements.length; j++) {
			document.editform.elements[j].onfocus = focusHandler;
			document.editform.elements[j].onblur  = blurHandler;
		}
	}
	var activeElement = null;
	var resetAccessKey = false;
	jQuery(window).load(loadHandler);
	//window.onload = loadHandler;
	
	function PopItFromGuided() {
	//	PopIt(document.editform.steps);
		if (activeElement == null) {
			alert(\"" . wfMsg('popbox_noelement') . "\");
			return;
		}
		PopIt(activeElement);
	}
	
	
			</script>
		";
		return $js . PopBox::getPopBoxJS();
	}
	
	function getPopBoxJS() {
		global $wgServer, $wgILPB_NumResults;
		return "
	<script language='javascript' type='text/javascript'>
	
	var response;
	var targetObj;
	var searchtext; 
	var lastKeyUpHandler;
	var requester;
	var sStart = -1;
	var sEnd = -1;
	
	function IEAccessKeyCheck(e) {
	  if( !e ) {
	    //if the browser did not pass the event information to the
	    //function, we will have to obtain it from the event register
	    if( window.event ) {
	      //Internet Explorer
	      e = window.event;
	    } else {
	      //total failure, we have no way of referencing the event
	      return;
	    }
	  }
	 
	       if ((e.altKey) && (e.keyCode == 82)) {
			document.getElementById('weave_button').onclick();
	       }
	}
	
	if (navigator.userAgent.indexOf(\"MSIE\")) {
		document.onkeyup = IEAccessKeyCheck;
	}
	
	
	function setSelectionRange(input, start, end) {
			// assumed IE
			input.focus()
			var range = input.createTextRange();
			range.collapse(true);
			range.moveStart('character', start);
			range.moveEnd('character', end - start);
			range.select();
	}
	
	function processResponse() {
		if (requester.status == 0 || requester.status == 200) {
	    	var theBox = document.getElementById('popbox_inner');
			var string = '';
			string = requester.responseText;
			var arr = string.split('\\n');
	        var count = 0;
	        //var obj = document.getElementById(targetObj);
	        var obj = targetObj;
	    	if (document.selection) {
				var range = document.selection.createRange();
	            text =  range.text;
				if (sStart < 0) {
					if (activeElement == null  && document.getElementById('wpTextbox1'))
						activeElement = document.getElementById('wpTextbox1');
					getSelectionStartEnd(activeElement);
				}
	    	} else {
	        	text  = (obj.value).substring(obj.selectionStart, obj.selectionEnd); 
	    	}	
	        html = 'Results for ' + searchtext + ':';" // WTF ? 
			. " 
			html += \"<ol>\";
	        for (i = 0; i < arr.length && count < $wgILPB_NumResults; i++) {
	            y = arr[i].replace(/^\s+|\s+$/, '');
	            key = y.replace(/^http:\/\/www.wikihow.com\//, '');
                if (key == wgPageName) {
                    continue;
                }
	            key = key.replace(/^" . str_replace("/", "\\/", $wgServer) . "\//, '');
	            x = unescape(key.replace(/-/g, ' '));
				y = x.replace(/'/g, '" . '\\\\' . '\\' . '\'' . "');
	            if (y != '') {
					if (y.indexOf('Category') == 0) {
						y = ':' + y;
					}
	                line = '<li><a id=\"link' + (count+1) + '\"  href=\"javascript:PopIt(); updateSummary(); insertTagsWH(targetObj, \'[[' +  y + '|\',\']]\', \'\'); \">' + x + '</a></li>\\n';
	                html += line;
	                count++;
	            }
	        }   
	        html += '</ol>';    
	        if (count == 0) {
	            html += '" . wfMsg('popbox_noresults') . "';
	        	html += '<p id=\'nothanks\'><a href=\'\' onclick=\'return Revise();\'>". wfMsg('popbox_revise') . "</a><br/>';
	            html += '<a href=\'\' onclick=\'return javascript:PopIt()\'>". wfMsg('popbox_close') . "</a></p>';
	        } else {
	        	html += '<p id=\'nothanks\'><a href=\'\' onclick=\'return Revise()\'>". wfMsg('popbox_revise') . "</a><br/>'
	            html +=  '<a href=\'javascript:PopIt()\'>" . wfMsg('popbox_nothanks') . "</a></p>';
	        }   
	        theBox.innerHTML=html;
		}
	}
	
	function handleResponse() {
		if (!requester) {
			alert('Error encountered.');
			return;
		}
	    if (requester.readyState == 4) {
			processResponse();
		}
	}
	
	function updateSummary() {
		var updateText = \"" . wfMsg('popbox_editdetails') ."\";
		if (updateText != '' && document.editform.wpSummary.value.indexOf(updateText) < 0) {
			if (document.editform.wpSummary.value != '') {
				document.editform.wpSummary.value += ', ';	
			}	
			document.editform.wpSummary.value += updateText;
		}
		return true;
	}
	function SelectLink(e) {
	  if( !e ) {
	    //if the browser did not pass the event information to the
	    //function, we will have to obtain it from the event register
	    if( window.event ) {
	      //Internet Explorer
	      e = window.event;
	    } else {
	      //total failure, we have no way of referencing the event
	      return;
	    }
	  }
	  if( typeof( e.keyCode ) == 'number'  ) {
	    e = e.keyCode;
	  } else if( typeof( e.which ) == 'number' ) {
	    e = e.which;
	  } else if( typeof( e.charCode ) == 'number'  ) {
	    e = e.charCode;
	  } else {
	    return;
	  }
	
	
		if (e >= 48 && e <= 57) {
			var i = e - 48;
			var link = document.getElementById('link' + i);
			if (link && link.href != '') {
				window.location = link.href;
				return;
			}
		} else if (e == 27) {
			PopIt(this);
		} else if (e == 86 && !document.getElementById('revise_text')) {
			//86 is v
			Revise();
		}
	}
	
	function searchFormSubmit() {
		search(document.getElementById('revise_text').value);
		return false;
	}
	function fakeSubmit(e) {
	        var key;
	        if(window.event) { 
	                // for IE, e.keyCode or window.event.keyCode can be used
	                key = e.keyCode;
	        }
	        else if(e.which) {
	                // netscape 
	                key = e.which;
	        }
	        else {
	                // no event, so pass through
	                return true;
	        }
	        if (key == '13') {
				searchFormSubmit();
			}
	}
	function Revise() {
		var agent = navigator.userAgent.toLowerCase();
		if (document.getElementById('wpTextbox1') && ( (agent.indexOf('firefox') >= 0) || (agent.indexOf('msie 8.0') >= 0) )) {
			document.getElementById('popbox_inner').innerHTML = '<input id=\"revise_text\" type=\"text\" name=\"revise\" onKeyUp=\"fakeSubmit(event);\" onclick=\"\" value=\"' + searchtext + '\"/><img src=\"http://www.wikihow.com/images/a/a8/Search_button.png\" onclick=\"return searchFormSubmit();\"><p id=\'nothanks\'><a href=\'javascript:PopIt()\'>". wfMsg('popbox_close') . "</a></p>';
		} else {
			document.getElementById('popbox_inner').innerHTML = '<input id=\"revise_text\" type=\"text\" name=\"revise\" onKeyUp=\"fakeSubmit(event);\" onclick=\"\" value=\"' + searchtext + '\"/><button onclick=\"return searchFormSubmit();\">"  .wfMsg('popbox_search') . "</button><p id=\'nothanks\'><a href=\'javascript:PopIt()\'>". wfMsg('popbox_close') . "</a></p>';
		document.getElementById('revise_text').focus();
		}
		return false;
	}
	
	
	function search(text) {
		requester = null;
	    try {
	        requester = new XMLHttpRequest();
	    } catch (error) {
	        try {
	            requester = new ActiveXObject('Microsoft.XMLHTTP');
	        } catch (error) {
	            return false;
	        }
	    }
	    requester.onreadystatechange =  handleResponse;
	    url = '" .wfMsg('popbox_searchurl', $wgServer) . "';
	    requester.open('GET', url); 
	    requester.send(' ');
		searchtext = text;
	}
	
	function PopIt(obj) {
	    var theBox = document.getElementById('popbox');
	    needToConfirm = false;
		if (obj != null) {
			targetObj = obj;
		}
	    if (theBox.style.display !== 'block') {
	        if (obj.offsetParent) {
	            //var coords = findPosPopBox(obj); no longer use this call because of css change
	            theBox.style.left = (obj.offsetLeft + 185) + 'px';  //5 pixels right of the right edge
	            theBox.style.top = obj.offsetTop + 20 + 'px';
	        }
	        else if (obj.x) {
	            theBox.style.left = (obj.y + 75);
	            theBox.style.top = (obj.x - 2);
	        }    
	        theBox.style.zIndex = '1';
	
	        document.getElementById('popbox_inner').innerHTML = '<i>"  . wfMsg('popbox_loading_results') . "</i>'
	        theBox.style.display = 'block';
			obj.disabled = true;
	    	for (var j = 0; j < document.editform.elements.length; j++) 
	        	document.editform.elements[j].disabled = true;
			lastKeyUpHandler = document.onkeyup;
			document.onkeyup = SelectLink;
	    } else {
	        theBox.style.display = 'none';    
	        needToConfirm = true;
			//document.editform.wpTextbox1.disabled = false;
			//document.editform.wpTextbox1.focus();
	    	for (var j = 0; j < document.editform.elements.length; j++) 
	        	document.editform.elements[j].disabled = false;
			targetObj.disabled = false;
			targetObj.focus();
			document.onkeyup = lastKeyUpHandler;
			lastKeyUpHandler = '';
			if (sEnd >= 0) {
				 setSelectionRange(activeElement,sStart, sEnd);
			}
			sStart = sEnd = -1;
	        return;
	    }
	    var text = '';
		if (document.selection) {
			text =  document.selection.createRange().text;      
		} else {
			text  = (obj.value).substring(obj.selectionStart, obj.selectionEnd); 
	    } 
		if (text == '') {
			pbinner = document.getElementById('popbox_inner');
			pbinner.innerHTML = '<i>"  . wfMsg('popbox_no_text_selected') . "</i><p id=\'nothanks\'><a href=\'javascript:PopIt()\'>". wfMsg('popbox_close') . "</a></p>';
			return;
		}
		search(text);
	}
	
	function findPosPopBox(obj) {
	    var curleft = curtop = 0;
	    if (obj.offsetParent) {
	        curleft = obj.offsetLeft
	        curtop = obj.offsetTop
	        while (obj = obj.offsetParent) {
	            curleft += obj.offsetLeft
	            curtop += obj.offsetTop
	        }
	    }
	    return [curleft - 480,curtop + 20];
	}
	</script>
	";
	}
	
	function getPopBoxCSS() {
		return "
	<style type='text/css'>
	.poplink { background-color: #FFC; }
	
	#popbox {
	    position: absolute;
	    width: 254px;
	    display: none;
	    margin: 0;
	    padding: 0;
	    background-color: transparent;
	}
	
	#popbox_hdr {
	    background: url(/extensions/PopBox/WeaveLinks_hdr.gif) 0 0 no-repeat;
	    font-size: 120%;
	    font-weight: bold;
	    width: 195px;
	    height: 44px;
	    padding: 8px 5px 5px 55px;
	    margin-bottom: -13px;
	}
	
	#popbox_inner {
	    padding: 8px;
	    border: 1px solid #DDD;    
	    margin: 0;
	    background-color: #FFF;
	}
	
	#popbox_inner P#nothanks { 
	    width: 100%;
	    border-top: 1px solid #DDD;
	    text-align:center; 
	    margin: 2px auto;
	    padding-top: 8px;
	}
	
	#popbox_inner P#nothanks A { color: #000; }
	#popbox_inner OL LI { margin-bottom: 1px; }
	</style>
	";
	}
	function getPopBoxDiv() {
		global $wgStylePath, $wgILPB_HeaderImage;
	  return "
	
	<div id='popbox'>
	    <div id='popbox_hdr'>" . wfMsg('popbox_related_articles') . "</div>
	    <div id='popbox_inner'></div>
	</div>	
		";
	}
}
	
