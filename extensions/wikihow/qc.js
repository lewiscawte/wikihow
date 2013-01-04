
var qc_vote = 0; 
var qc_skip = 0;
var qc_id   = 0;
var QC_STANDINGS_TABLE_REFRESH = 600; 

$("document").ready(function() {
			getNextQC();
		}
	);

function getNextQC() {
	//grab options

	$.get('/Special:QC',
		{ fetchInnards: true,
		  qc_type: getCookie('qcrule_choices'),
		},
		function (result) {
			loadResult(result);
		},
		'json'
	);
}

//keep hidden input list of QC choices
function updateChoices() {
	var choices = [];
	$("#qc_options input:checked").each(function() {
		choices.push($(this).attr('id'));
	});
	setCookie('qcrule_choices',choices.join());
}

function loadResult(result) {
	//clear stuff out
	$('#qccontents').remove();
	$('#qcrules').remove();
	
	//add in stuff
	$(".firstHeading").html(result['title']);
	$(".firstHeading").before(result['qcrules']);
	
	if (result['done']) {
		$("#bodycontents").before("<div id='qccontents'>"+result['msg']+"</div>");
	}
	else {
		$("#bodycontents").before("<div id='qccontents'>"+result['choices']+result['html']+"</div>");
	}
	
	qc_id	= result['qc_id'];
	document.title = result['title'];

	$("#question").html(result['question']);	
	$("#quickeditlink").html(result['quickedit']);
	
	//action for change link
	$('.qc_options_link').click( function(e) {
		e.preventDefault();
		displayQCOptions();
	});
	
	//yes button
	$('#qc_yes').click( function(e) {
		e.preventDefault();
		qcVote(true);
	});
	
	//no button
	$('#qc_no').click( function(e) {
		e.preventDefault();
		qcVote(false);
	});	
	
	//skip
	$('#qc_skip').click( function(e) {
		e.preventDefault();
		qcSkip();
	});
}

function submitResponse() {
	$.post('/Special:QC',
		{ 
		  qc_vote: qc_vote,
		  qc_skip: qc_skip,
		  qc_type: getCookie('qcrule_choices'),
		  qc_id: qc_id
		},
		function (result) {
			loadResult(result);
		},
		'json'
	);
}

// show/hide checkboxes
function displayQCOptions() {

	if ($('#qc_options').css('display') == 'none') {
		//show it!
		$.get('/Special:QC',
			{ getOptions: true,
			  choices: getCookie('qcrule_choices'),
			},
			function (result) {
				$('#qc_options').html(result);
				$('#qc_options').slideDown();
				$('input:checkbox').click( function() {
					updateChoices();
				});
				$('#qcrules_submit').click( function(e) {
					e.preventDefault();
					$('#qc_options').slideUp();
					getNextQC();
				});
			}
		);	
	}
	else {
		//hide it!
		$('#qc_options').slideUp();
	}
}

function qcVote(vote) {
	(vote) ? (qc_vote = 1) :(qc_vote = 0);
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
	//reset timer
	window.setTimeout(updateStandingsTable, 1000 * QC_STANDINGS_TABLE_REFRESH);
}

window.setTimeout(updateWidgetTimer, 60*1000);
window.setTimeout(updateStandingsTable, 1000 * QC_STANDINGS_TABLE_REFRESH);

function updateWidgetTimer() {
    updateTimer('stup');
    window.setTimeout(updateWidgetTimer, 60*1000);
}

function incCounters() {
	$("#iia_stats_week_qc, #iia_stats_today_qc, #iia_stats_all_qc, #iia_stats_group").each(function (index, elem) {
			$(this).fadeOut(function () {
				val = parseInt($(this).html()) + 1;
				$(this).html(val);
				$(this).fadeIn(); 
			});
		}
	); 
}
