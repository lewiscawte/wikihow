
var qc_vote = 0; 
var qc_skip = 0;
var qc_id   = 0;
var QC_STANDINGS_TABLE_REFRESH = 600; 

$("document").ready(function() {
			getNextQC();
		}
	);

function changeQCTarget() {
	window.location = "/Special:QC/" + $("#qcrule_select").val();
}

function getNextQC() {
	$.get('/Special:QC',
		{ fetchInnards: true,
		  qc_type: $("#qcrule_select").val(),
		},
		function (result) {
			loadResult(result);
		},
		'json'
	);
}

function loadResult(result) {
	qc_id	= result['qc_id'];
	document.title = result['title'];
	$(".firstHeading").html(result['title']);
	$("#qccontents").html(result['html']);	
	$("#quickeditlink").html(result['quickedit']);
	
}

function submitResponse() {
	$.post('/Special:QC',
		{ 
		  qc_vote: qc_vote,
		  qc_skip: qc_skip,
		  qc_type: $("#qcrule_select").val(),
		  qc_id: qc_id
		},
		function (result) {
			loadResult(result);
		},
		'json'
	);
}


function qcVote(vote) {
	if (vote) {
		qc_vote = 1; 
	} else {
		qc_vote = 0; 
	}
	qc_skip = 0; 
	incCounters(); 
	submitResponse();
}

function qcSkip() {
	qc_skip = 1; 
	submitResponse();
}

updateStandingsTable = function() {
    var url = '/Special:Standings/QCStandingsGroup';
    jQuery.get(url, function (data) {
        jQuery('#iia_standings_table').html(data['html']);
    },
	'json'
	);
	$("#stup").html(QC_STANDINGS_TABLE_REFRESH / 60);
}

window.setTimeout(updateWidgetTimer, 60*1000);
window.setTimeout(updateStandingsTable, 1000 * QC_STANDINGS_TABLE_REFRESH);

function updateWidgetTimer() {
    updateTimer('stup');
    window.setTimeout(updateWidgetTimer, 60*1000);
}

function updateTimer(id) {
    var e = jQuery("#" + id);
    var i = parseInt(e.html());
    if (i > 1) {
       e.fadeOut(400, function() {
           i--;
           e.html(i);
           e.fadeIn();
        });
    }
}

function incCounters() {
	$("#iia_stats_week_qc, #iia_stats_today_qc, #iia_stats_all_qc, #iia_stats_group").each(function (index, elem) {
			$(this).fadeOut();
			val = parseInt($(this).html()) + 1;
			$(this).html(val);
			$(this).fadeIn(); 
		}
	); 
}
