var rcElements = [];
var rcReset = true;
var rcCurrent = 0;
var rcElementCount = 0;
var rcMaxDisplay = 3;
var rcServertime;
var dpointer = 0;
var togglediv = true;
var rcInterval = '';
var rcReloadInterval = '';
var gcInterval = '';

var isFull = 0;
var direction = 'down';
var rcDefaultURL = "/Special:RCWidget";
var rcPause = false;
var rcLoadCounter = 0;
var rcLoadCounterMax = 4;
var rcwDebugFlag = false;
var rcwPadUrl = wgServer != 'http://www.wikihow.com' ? wgServer : 'http://pad1.whstatic.com';
var rcwTestStatusOn = false;

function rcTest() {
	alert($('#rcwidget_divid').innerHTML);
}

function getNextSpot() {
	if (dpointer >= rcMaxDisplay) {
		return 0;
	} else {
		return dpointer;
	}
}

function getRCElem(listid, type) {

	if (typeof(rcElements) != "undefined") {
		var elem;

		var newelem = $('<div></div>');
		var newid = getNextSpot();
		var newdivid = 'welement'+newid;
		newelem.attr('id', newdivid);
		newelem.css('display', 'none');
		newelem.css('overflow', '');
		newelem.css('padding', '0px');
		newelem.css('margin', '0px');
		if (togglediv) {
			newelem.attr('class', 'rc_widget_line rounded_corners tan');
			togglediv = false;
		} else {
			newelem.attr('class', 'rc_widget_line');
			togglediv = true;
		}

		elem = "<div class='rc_widget_line_inner'>";

		elem += rcElements[ rcCurrent ].text + "<br />";
		//elem += "<span style='color: #AAAAAA;font-size: 11px;'>" + rcElements[ rcCurrent ].ts +" ("+rcCurrent+")</span>";
		elem += "<span class='rc_widget_time'>" + rcElements[ rcCurrent ].ts + "</span>";
		elem += "</div>";

		newelem.html($(elem));

		dpointer = newid + 1;

		if (direction == 'down') {
			var firstChild = listid.children()[0];
			newelem.insertBefore(firstChild);
		} else {
			listid.append(newelem);
		}

		if (type == 'blind') {
			if (direction == 'down') {
				//new Effect.SlideDown(newelem);
				//newelem.show('blind', {direction: 'vertical'});
				//newelem.show('slide', {direction: 'up'});
				newelem.slideDown();
			} else {
				//new Effect.BlindDown(newelem);
				//newelem.show('blind', {direction: 'vertical'});
			}
		} else {
			//new Effect.Appear(newelem);
			newelem.fadeIn();
		}

		if (rcCurrent < rcElementCount) {
			rcCurrent++;
		} else {
			rcCurrent = 0;
		}

		return newelem;
	} else {
		return "undefined";
	}
}

function rcUpdate() {
	if (rcPause) {
		return false;
	}

	var listid = $('#rcElement_list');

	if (isFull == rcMaxDisplay) {
		var oldid = getNextSpot();
		var olddivid = $('#welement'+oldid);
	
		if (direction == 'down') {
			//new Effect.BlindUp(olddivid);
			//olddivid.effect('blind', {direction: 'up'});
			//olddivid.show('blind', {direction: 'vertical'});
		} else {
			//new Effect.SlideUp(olddivid);
			//olddivid.effect('slide', {direction: 'up'});
		}
		olddivid.attr('id','rcw_deleteme');
	}

	var elem = getRCElem(listid, 'blind');
	if (isFull < rcMaxDisplay) { isFull++ }

}

var running = true;
function rcTransport(obj) {
	var rcwScrollCookie = getCookie('rcScroll');

	obj = $(obj);
	if (running) {
		setRCWidgetCookie('rcScroll','stop',1);
		rcStop();
		running = false;
		obj.addClass('play');
		background_x_position = obj.css('background-position').split(" ")[0];
		obj.css('background-position', background_x_position + " -78px");
	} else {
		deleteCookie('rcScroll');
		rcStart();
		obj.removeClass('play');
		background_x_position = obj.css('background-position').split(" ")[0];
		obj.css('background-position',  background_x_position + ' 0');
		running = true;
   }
    
}
   

function rcStop() {
	clearInterval(rcInterval);
	clearInterval(rcReloadInterval);
	clearInterval(gcInterval);

	rcInterval = '';
	rcReloadInterval = '';
	gcInterval = '';
	rcGC();
	var obj = $('#play_pause_button');
	obj.addClass('play');
	background_x_position = obj.css('background-position').split(" ")[0];
	obj.css('background-position', background_x_position + " -78px");
	running = false;
}

function rcStart() {
	rcUpdate();
	rcLoadCounter = 0;
	if (rcReloadInterval == '') { rcReloadInterval = setInterval('rcwReload()', rc_ReloadInterval); }
	if (rcInterval == '') { rcInterval = setInterval('rcUpdate()', 3000); }
	if (gcInterval == '') { gcInterval = setInterval('rcGC()', 30000); }
}

function rcwReadElements(nelem) {
	var Current = 0;
	var Elements = [];
	var Servertime = 0;
	var ElementCount = 0;

	for (var i in nelem) {
		if (typeof(i) != "undefined") {
			if (i == 'servertime'){
				Servertime = nelem[i];
			} else {
				Elements.push(nelem[i]);
				ElementCount++;
			}
		}
	}
	Current = 0;

	rcServertime = Servertime;
	rcElements = Elements;
	rcElementCount = ElementCount;
	rcCurrent = Current;
	rcReset = true;
}

function rcwReload() {
	if (rc_URL == '') { rc_URL = rcDefaultURL; }
	rcLoadCounter++;

	if (rcLoadCounter > rcLoadCounterMax) {
		rcStop();
		if (rcwTestStatusOn) $('#teststatus').innerHTML = "Reload Counter...Stopped:"+rcLoadCounter;
		return true;
	} else {
		if (rcwTestStatusOn) $('#teststatus').innerHTML = "Reload Counter..."+rcLoadCounter;
	}

	var url = rcwPadUrl + rc_URL + '?function=rcwOnReloadData';
	rcwLoadUrl(url);
}

function rcwOnReloadData(data) {
	rcwReadElements(data);
}

function rcwLoad() {
	if (rc_URL == '') { rc_URL = rcDefaultURL; }

	var listid = $('#rcElement_list');
	listid.css('height', (rcMaxDisplay * 65) + 'px');
	listid.css('overflow', 'hidden');
	if (rcwDebugFlag) { $('#rcwDebug').css('display', 'block'); }

	if (listid) {
		listid.mouseover(function(e) {
			rcPause = true;
		});
		listid.mouseout(function(e) {
			rcPause = false;
		});
	}


	var url = rcwPadUrl + rc_URL + '?function=rcwOnLoadData';
	rcwLoadUrl(url);
}

function rcwLoadUrl(url) {
	$('#rcwidget_divid').after( $('<script src="' + url + '"></script>') );
}

function rcwOnLoadData(data) {
	rcwReadElements(data);

	var listid = $('#rcElement_list');
	if (rcwTestStatusOn) $('#teststatus').innerHTML = "Nodes..."+listid.childNodes.length;
	var rcwScrollCookie = getCookie('rcScroll');

	if (!rcwScrollCookie) {
		var elem = getRCElem(listid, 'new');
		if (isFull < rcMaxDisplay) { isFull++ }

		rcStart();
	} else {
		for (i = 0; i < rcMaxDisplay; i++) {
			var elem = getRCElem(listid, 'new');
			if (isFull < rcMaxDisplay) { isFull++ }
		}
		rcStop();
	}
}

function rcGC() {
	if (rcwTestStatusOn) {
		var tmpHTML = $('#teststatus').innerHTML;
		$('#teststatus').innerHTML = "Garbage collecting...";
	}
	$('#rcElement_list #rcw_deleteme').remove();

	/*var listid = $('#rcElement_list');
	var listcontents = $('#rcElement_list div');
	for (i = 0; i < listcontents.length; i++) {
		if ($(listcontents[i]).attr('id') == 'rcw_deleteme') {
			//listid.removeChild( listcontents[i] );
			$(listcontents[i]).remove();
		}
	}*/
	if (rcwTestStatusOn) $('#teststatus').innerHTML = tmpHTML;
}

function setRCWidgetCookie(c_name,value,expiredays)
{
	var exdate = new Date();
	exdate.setDate(exdate.getDate()+expiredays);
	document.cookie = c_name+ "=" +escape(value)+ ((expiredays==null) ? "" : ";expires="+exdate.toGMTString());
}

function getCookie(c_name)
{
	if (document.cookie.length>0) {
		c_start=document.cookie.indexOf(c_name + "=");
		if (c_start!=-1) {
			c_start=c_start + c_name.length+1;
			c_end=document.cookie.indexOf(";",c_start);
			if (c_end==-1) 
				c_end=document.cookie.length;
			return unescape(document.cookie.substring(c_start,c_end));
		}
	}
	return "";
}

function deleteCookie( name ) {
	if ( getCookie( name ) ) 
		document.cookie = name + "=" + ";expires=Thu, 01-Jan-1970 00:00:01 GMT";
}

