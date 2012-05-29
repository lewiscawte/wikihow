<link rel="stylesheet" type="text/css" href="/extensions/wikihow/titus/jquery.sqlbuilder.css" />
<style type="text/css">
.urls {
	margin-top: 5px;
	height: 300px;
}
</style>

<script type="text/javascript">

$(document).ready(function() { 

    $('.sqlbuild').sqlquerybuilder({ 
		fields: <?=$dbfields?>,
        showgroup:false,
        showcolumn:true,
        showsort:false,
		showwhere:true
    }); 

    $('.fetch').click(function(){
		var sql = $('.sqlbuild').getSQBClause('all');
		if (!sql.length && (!sql.length && (!$('#urls').val().length && $('#filter_urls').is(':checked')))) {
			var answer = confirm("WARNING: You have not given me any conditions to filter this report.  Repeated intensive queries make me angry and cause me to destroy temples in holy lands. \n\n Click the OK button if this is really what you want.");
			if (!answer) {
				return false;
			}
		}
		var data = {
			'sql' : sql,
			'urls': $('.urls').val(),
			'page-filter': $('input[name=page-filter]:checked').val(),
			'csvtype' : $('input[name=csvtype]:checked').val()
		};
		$.download('/' + wgPageName, data);           
    
		return false;
    }); 


    $('#getsql').click(function(){
     	alert($('.sqlbuild').getSQBClause('all')); 
     	return false;
    }); 
	
    $('#page_title').click(function(){
		var ti_page_title = '<span class="sqlcolumn" style="height: 20px; ">' +
			'<a class="addnewsqlcolumndelete ti_page_title_column" href="#1">[remove]</a>&nbsp;<a class="addnewsqlcolumn" id="0" href="#1">ti_page_title</a>' + 
			'&nbsp;labeled as&nbsp;<span class="addnewsqlcolumnvalue" href="#0" id="0">ti_page_title</span>&nbsp;</span>';
		$('.addnewsqlcolumn:last').before(ti_page_title);
     	return false;
    }); 

	$('.sqlbuildercolumn').on('click', '.ti_page_title_column', function(e) {
		e.preventDefault();
		$(this).parent().slideUp(200, function() {
			$(this).remove();
			
		});
	});

	
	$('input[value=all]').click(function() {
		$('.urls').slideUp('fast');
	});

	$('input[value=urls]').click(function() {
		$('.urls').slideDown('slow');
	});

    
});


</script>


<div id=sqlreport>
<div class="sqlbuild"></div>
</div>
<div style="margin-top: 10px">
		<input id="filter_all" type="radio" name="page-filter" value="all"> Across All Pages
		<input id="filter_urls" type="radio" name="page-filter" value="urls" checked="checked"> Given the following URLs
</div>
<textarea class="urls" rows="500" name="urls" id="urls"></textarea>
<div style="margin-top: 10px">
<button class="fetch" style="padding: 5px;" value="CSV">Gimme</button>
<a id='getsql' href='#'>SQL</a>
</div>

