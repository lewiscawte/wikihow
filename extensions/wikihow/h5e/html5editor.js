
if (typeof WH == 'undefined') {
	var WH = {};
}

// wikiHow's HTML 5 editor
WH.h5e = (function ($) {

	/**
	 * Re-numbers steps after a new step has been inserted, etc.
	 */
	function renumberSteps() {
		var steps = $('ol.steps_list_2', '#steps');
		steps.each(function(i, list) {
			var divs = $('div.step_num', list);
			var step = 1;
			divs.each( function(i, div) {
				$(div).html(step);
				step++;
			});
		});
	}

	/**
	 * Make the actual step numbers non-editable.
	 */
	function makeStepNumbersNonEditable() {
		$('div.step_num').attr('contenteditable', false);
	}

	/**
	 * Move the keyboard cursor to the start of a specified node (or at the 
	 * specified position if one is present).
	 */
	function setCursorNode(node, position) {
		var range = document.createRange();
		if (typeof position == 'undefined') {
			range.selectNodeContents(node);
		} else {
			range.setStart(node, position);
			range.setEnd(node, position);
		}
		var select = window.getSelection();
		select.removeAllRanges();
		select.addRange(range);
	}

	/**
	 * User has hit 'Enter' key, so we want to create a new list item or new
	 * step.
	 */
	function createNewStepOnEnter(evt) {
		// if we're inside a UL element, don't create a new step
		if (!isStepsCursorTopLevel()) {
			return true;
		}

		var li = getCursorLi();

		var newstep_tmpl = '<li class="steps_li"><div class="step_num" contenteditable="false">1</div><b class="whb"></b><div class="clearall"></div>';
		li.after(newstep_tmpl);
		var newli = li.next();

		if (li.hasClass('final_li')) {
			li.removeClass('final_li');
			newli.addClass('final_li');
		}

		var first = $('b', newli).first();
		if (first.length) {
			var node = first[0];
			setCursorNode(node);
		}

		renumberSteps();

		return false;
	}

	/**
	 * Test whether two text nodes are equal.  Returns true iff they are equal.
	 */
	function textNodesEqual(a, b) {
		return a && b &&
			typeof a.nodeType != 'undefined' &&
			typeof b.nodeType != 'undefined' &&
			a.nodeType == b.nodeType &&
			a.textContent == b.textContent;
	}

	/**
	 * In the steps section, get the step where the keyboard cursor is 
	 * currently placed.
	 */
	function getCursorLi(anchor) {
		if (typeof anchor == 'undefined') {
			var select = window.getSelection();
			anchor = select.anchorNode;
		}
		anchor = $(anchor)
		var currentLi = anchor.parentsUntil('#steps ol').last();
		if (!currentLi.length && anchor.is('li')) {
			currentLi = anchor;
		}
		return currentLi;
	}

	/**
	 * Return the text node where the keyboard cursor is currently placed.
	 */
	function getCursorTextNode() {
		var select = window.getSelection();
		var anchor = select.anchorNode;
		if (anchor.nodeName == '#text') {
			return anchor;
		} else {
			var firstTextNode = $(anchor).textNodes(true).first();
			if (firstTextNode.length) {
				return firstTextNode[0];
			} else {
				return anchor.nextSibling;
			}
		}
	}

	/**
	 * Remove the current step if the backspace key is hit and the keyboard
	 * cursor is at the start of a step.
	 */
	function removeStepOnBackspace(evt) {
		var select = document.getSelection();
		if (select.length > 0) {
			var selSteps = getSelectionSteps();
			if (selSteps['start'] != selSteps['end']) {
				// need to re-number steps AFTER delete event has propagated
				window.setTimeout(function() {
					renumberSteps();
				}, 250);
			}

			// propagate 'delete'
			return true;
		}

		if (!isCursorAtListItemStart()) {
			// if we're in the middle of the step, don't remove step, just 
			// backspace
			return true;
		}

		// get current li under cursor
		var currentli = getCursorLi();
		var prevli = currentli.prev();

		if (!prevli.length) {
			// if we're at the start of the first li, don't do anything
			return false;
		}

		// position cursor at preview li
		var textNodes = prevli.textNodes(true);
		if (textNodes.length >= 2) {
			// position cursor at end of last text node of previous li
			var node = textNodes.last().get(0);
			var position = node.length;
			setCursorNode(node, position);
		} else {
			var first = $('b', prevli).first();
			if (first.length) {
				var node = first[0];
				setCursorNode(node, 0);
			} else {
				// do something if the li has no text nodes
			}
		}

		$('.clearall',prevli).remove();

		if (currentli.hasClass('final_li')) {
			prevli.addClass('final_li');
		}

		// store the rest of the content of li which the cursor is on, 
		// then remove it
		$('div.step_num', currentli).remove();
		var extraStepContents = currentli.contents();
		extraStepContents = extraStepContents.filter(function(i) {
			return !$(this).is('br');
		});
		prevli.append(extraStepContents);
		currentli.remove();

		renumberSteps();

		evt.preventDefault();
		return true;
	}

	/**
	 * Check to see whether a keyboard event was a "special" key like the
	 * arrow keys or Esc
	 */
	function isEditingKeyEvent(evt) {
		var ret = true;

		// When some special key is used, such as Esc or "screen brighten"
		if (typeof evt.keyCode != 'undefined' && evt.keyCode > 0 &&
			typeof evt.charCode != 'undefined' && evt.charCode == 0)
		{
			// Exception for alt-backspace
			if (evt.keyCode != 8 &&
				evt.keyCode != 9 &&
				evt.keyCode != 10 &&
				evt.keyCode != 13)
			{
				ret = false;
			}
		}

		// When the Mac command key is pushed
		if (typeof evt.ctrlKey != 'undefined' && !evt.ctrlKey &&
			typeof evt.altKey != 'undefined' && !evt.altKey &&
			typeof evt.metaKey != 'undefined' && evt.metaKey)
		{
			// Except if paste, undo or cut are being used
			if (typeof evt.charCode != 'undefined' &&
				evt.charCode != 118 &&
				evt.charCode != 120 &&
				evt.charCode != 122)
			{
				ret = false;
			}
		}

		return ret;
	}

	/**
	 * Tracks keystrokes and inserts new step numbers when a user presses enter,
	 * or deletes them on backspace.  This method is only called for certain 
	 * keystrokes for efficiency.
	 */
	function onKeystroke(evt) {
		var key = evt.which;
		var propagate = true;

		if (isEditingKeyEvent(evt)) {
			// if array key was not hit, mark document changed so user
			// gets a warning when leaving page without saving
			setPageDirty();
		}

		if (key == 13 && typeof evt.isSimulated == 'undefined') { // 'Enter' key
			if (getCurrentCursorSection() == 'steps') {
				propagate = createNewStepOnEnter(evt);
			}
		} else if (key == 8) { // 'Backspace' key
			if (getCurrentCursorSection() == 'steps') {
				propagate = removeStepOnBackspace(evt);
			}

			// check if user deleted all steps -- recreate first one if they
			// did
			window.setTimeout(function() {
				var html = $('#steps').html();
				// check if steps are empty, after backspace event propagation
				if (html == '' || html == '<br>') {
					var inSteps = getCurrentCursorSection() == 'steps';
					var tmpl = '<ol class="steps_list_2"><li class="steps_li final_li"><div class="step_num" contenteditable="false">1</div><b id="h5e-new-first-step">' + wfMsg('h5e-first-step') + '</b></li></ol>';
					$('#steps').html(tmpl);
					var firstStep = $('#h5e-new-first-step');
					if (inSteps) {
						var len = wfMsg('h5e-first-step').length;
						loadCursorPos({'node': firstStep.contents().get(0), 'offset': len});
					}

					firstStep.attr('id', '');
				}
			}, 250);
		} else {
			var chr = String.fromCharCode(key);
			if (chr == '.' || chr == '?' || chr == ':' || chr == '!') {
				if (getCurrentCursorSection() == 'steps' &&
					document.queryCommandState('bold'))
				{
					document.execCommand('bold', false, '');
				}
			}
		}

		return propagate;
	}

	/**
	 * Check whether the current li element is in fact a steps-level element.
	 * Returns true iff it is.
	 */
	function isStepsCursorTopLevel() {
		var select = window.getSelection();
		var anchor = $(select.anchorNode);
		var parents = anchor.parentsUntil('#bodycontents * ol');
		var gotUL = false;
		parents.each(function(i, node) {
			if ($(node).is('ul')) {
				gotUL = true;
				return false;
			}
		});
		if (gotUL && anchor.is('li') && parents.length == 2 && $(parents[0]).is('ul')) {
			return true;
		} else {
			return !gotUL;
		}
	}

	/**
	 * Determine's the high-level section where the cursor currently resides.
	 *
	 * @return the section, as a word, where the cursor currently resides. 
	 *   e.g. 'intro', 'steps', 'tips', etc.
	 */
	function getCurrentCursorSection() {
		var select = window.getSelection();
		var anchor = $(select.anchorNode);
		var parents = anchor.parentsUntil('#bodycontents');
		if (parents.length) {
			var sectionDiv = parents.last();
		} else {
			var sectionDiv = anchor;
		}
		var id = sectionDiv.attr('id');
		if (id !== '') {
			return id;
		} else {
			return 'intro';
		}
	}

	/**
	 * Returns true if and only if the keyboard cursor is at the start of a 
	 * top level li in a step.  Used so that we can collapse 2 steps into one
	 * when user hits backspace at the start of a step.
	 */
	function isCursorAtListItemStart() {
		var select = window.getSelection();
		var li = getCursorLi();
		//if (select.anchorNode == li.get(0) && select.anchorOffset == 1) {
		//	return true;
		//} else {
			var clone = li.clone();
			$('div.step_num', clone).remove();
			var firstTextNodes = clone.textNodes(true).first();
			if (firstTextNodes.length) {
				var firstTextNode = firstTextNodes[0];
				var cursorTextNode = getCursorTextNode();
				if (textNodesEqual(firstTextNode, cursorTextNode)) {
					return select.anchorOffset == 0;
				} else {
					return false;
				}
			} else {
				return true;
			}
		//}
	}

	/**
	 * Returns true if and only if the start of the selection is on
	 * one step and the end of the selection is on another.
	 */
	function getSelectionSteps() {
		var select = window.getSelection();
		var start = getCursorLi(select.anchorNode).get(0);
		var end = getCursorLi(select.focusNode).get(0);
		return {'start' : start, 'end' : end};
	}

	/**
	 * Brings the cursor back to the section we're editing after a toolbar
	 * button was pressed.
	 */
	function focusCurrentSection(currentNode) {
		var node = !currentNode ? getCursorNode() : currentNode;
		var parents = $(node).parentsUntil('#bodycontents');
		// note: the 'parents' set is empty when cursor is in intro -- use
		// current node instead
		var focusNode = parents.length ? parents.last() : $(node);
		if (settings['needs-section-focus']) {
			focusNode.focus();
		}
	}

	/**
	 * Move the keyboard cursor to the start of a section specified.  Make
	 * sure the browser view scrolls to that section if it's not there.
	 */
	function focusSection(section) {
		var sectionDiv, firstText;
		if (section == 'intro') {
			sectionDiv = $('#bodycontents .article_inner:first');
			// TODO: need to pull out first real non-blank text node (ignoring 
			// all div.mwimg)
			firstText = $('p', sectionDiv).textNodes(true).first();
		} else if (section == 'relatedwikihows') {
			sectionDiv = [];
		} else if (section == 'steps') {
			sectionDiv = $('#steps');
			var div = $('#steps ol.steps_list_2 li');
			div = div.children().not('div.mwimg').not('div.step_num');
			firstText = div.textNodes(true).first();
		} else {
			sectionDiv = $('#' + section);
			var div = $('ul li', sectionDiv);
			if (!div.length) {
				div = sectionDiv;
			}
			firstText = div.textNodes(true).first();
		}
		if (sectionDiv.length) {
			if (settings['needs-section-focus']) {
				sectionDiv.focus();
			}
			if (firstText.length) {
				setCursorNode(firstText[0], 0);
			}
		}
	}

	/**
	 * Return the current cursor position in the document, so that it can be
	 * saved and loaded later.
	 *
	 * @return A tuple to be loaded with the loadCursorPos() function.
	 */
	function saveCursorPos() {
		var sel = window.getSelection();
		var savedNode = sel.anchorNode;
		var savedOffset = sel.anchorOffset;
		var range = sel.getRangeAt(0);
		if (range.startOffset == range.endOffset) {
			range = null;
		}
		return { 'node': savedNode, 'offset': savedOffset, 'range': range };
	}

	/**
	 * Load the current cursor position from a save tuple.
	 *
	 * @param pos A tuple which was saved with saveCursorPos()
	 */
	function loadCursorPos(pos) {
		if (pos && pos['node']) {
			setCursorNode(pos['node'], pos['offset']);
			if (pos['range']) {
				var sel = window.getSelection();
				sel.addRange(pos['range']);
			}
		}
	}


	/**
	 * Toolbar add link button clicked.  Converts selected text into a link,
	 * or inserts a new link with default text if no text is selected.
	 */
	function tbAddLink() {
		focusCurrentSection();

		var cursorText = document.getSelection();
		showEditLinkDialog('add', cursorText, '',
			function(text, link) { // when 'Change' button is clicked
				var title = getArticleFromLink(link);
				var html = '<a href="' + link + '" title="' + title + '">' + text + '</a> ';
				document.execCommand('inserthtml', false, html);
			}
		);

	}

	/**
	 * Stores the position of the cursor before entering the Image Upload
	 * dialog.
	 */
	var preEIUCursorLi = null;
	var preEIUCursorNode = null;
	var preEIUSection = null;

	/**
	 * Store the position of the cursor and call the Image Upload dialog.
	 */
	function tbAddImage() {
		focusCurrentSection();
		preEIUSection = getCurrentCursorSection();
		preEIUCursorLi = getCursorLi();
		preEIUCursorNode = getCursorNode();

		easyImageUpload.setCompletionCallback(
	 		// this callback is called by the image upload dialog finishes
			function (details) {
				var html = generateImageHtml(details);
				insertImageHtml(details, html);
			}
		);
		easyImageUpload.doEIUModal();
	}

	/**
	 * Converts the html encodable entities in a string into a string.  For
	 * example, converts the string "Bob & Linda" into "Bob &amp; Linda".
	 */
	function htmlEntitiesEncode(str) {
		return $('<div/>').text(str).html();
	}

	/**
	 * Builds the HTML for the image to be inserted from the details of a
	 * wikitext image tag.
	 *
	 * @param details all of the image tag
	 * @return the html to be inserted
	 */
	function generateImageHtml(details) {
		var width = details['chosen-width'];
		var height = details['chosen-height'];
		var encCaption = htmlEntitiesEncode(details['caption']);
		var isThumb = encCaption != '';
		var encFilename = htmlEntitiesEncode(encodeURIComponent(details['filename']));
		var encTag = htmlEntitiesEncode(details['tag']);
		var rlayout = details['layout'] == 'right';
		if (isThumb) {
			var ltag1 = '<div style="width: ' + (parseInt(width, 10) + 2) + 'px;" class="thumb ' + (rlayout ? 'tright' : 'tnone') + '">';
			var rtag1 = '</div>';
			var ltag2 = '';
			var rtag2 = '';
		} else {
			var ltag1 = '<div class="' + (rlayout ? 'floatright' : 'floatnone') + '">';
			var rtag1 = '</div>';
			var ltag2 = '<span>';
			var rtag2 = '</span>';
		}
		var captionHtml = encCaption ? '<a title="Enlarge" class="internal" href="/Image:' + encFilename + '"><img width="16" height="16" alt="" src="/skins/common/images/magnify-clip.png"></a> <span contenteditable="true" class="caption">' + encCaption + '</span>' : '';
		var html = '<div contenteditable="false" class="mwimg">' + ltag1 + '<div style="width: ' + width + 'px; height: ' + height + 'px;" class="rounders">' + ltag2 + '<a title="' + encCaption + '" class="image" href="/Image:' + encFilename + '">' + details['html'] + '</a>' + rtag2 + '<div class="corner top_left"></div><div class="corner top_right"></div><div class="corner bottom_left"></div><div class="corner bottom_right"></div></div>' + captionHtml + '<input type="hidden" name="h5e_image" value="' + encTag + '" />' + rtag1 + '</div>';

		return html;
	}

	/**
	 * Inserts an image into the DOM, using the image details (the layout) and
	 * the previously generated html for the image.
	 *
	 * @note globals used: preEIUSection, preEIUCursorLi, preEIUCursorNode
	 */
	function insertImageHtml(details, html) {
		setPageDirty();
		var newHtml = $(html);

		// for the "steps" section
		if (preEIUSection == 'steps') {
			var currentli = preEIUCursorLi;

			if (details['layout'] == 'center') {
				var lastdiv = $('div.clearall', currentli).last();
				if (lastdiv.length) {
					lastdiv.before(newHtml);
				} else {
					currentli.append(newHtml);
				}
			} else {
				var currentli = preEIUCursorLi;
				var thisStep = $('div.step_num', currentli).first();
				thisStep.after(newHtml);
			}

		} else {
			var node = $(preEIUCursorNode);
			var container = null;
			if (node.is('li')) {
				container = node;
			} else {
				var parents = node.parentsUntil('#bodycontents');
				parents.each( function(i, node) {
					var par = $(node);
					if (par.is('li')) {
						container = par;
					}
				});
				if (!container) {
					container = parents.last();
				}
			}

			if (details['layout'] == 'center') {
				container.append(newHtml);
			} else {
				container.prepend(newHtml);
			}
		}

		$('.rounders', newHtml).mouseenter(imageHoverIn);
		$('a.internal', newHtml).click(function() { return false; });
	}

	/**
	 * Called when toolbar indent button is used.
	 */
	function tbIndent() {
		focusCurrentSection();
		setPageDirty();

		// get the existing list for this section, if there is one
		var currentSection = getCurrentCursorSection();
		if (currentSection != 'intro') {
			var appendNode = getCursorLi();
			var existingList = $('ul', appendNode).first();
		} else {
			var node = $( getCursorNode() );
			var parents = node.parentsUntil('#bodycontents * ul');
			if (parents.length) {
				var rootNode = $(parents).last();
				if (rootNode.is('li')) {
					var existingList = rootNode.parent();
				} else {
					var existingList = [];
				}
			} else {
				var existingList = node.parent();
			}

			var parents = node.parentsUntil('#bodycontents');
			var appendNode = $(parents).last();
		}

		// create a new list node
		var newItem = $('<li></li>');
		var newList = $('<ul></ul>');
		newList.append(newItem);

		// if there's an existing list in the section, we want to append to
		// this list rather than creating a new <ul> after it
		if (existingList.length) {
			var node = $( getCursorNode() );
			var li, parentLi = node.parentsUntil('ul').last();
			if (parentLi.length) {
				li = parentLi.first();
			} else {
				li = node;
			}
			// are we already inside a list?
			if (li.is('li')) {
				// if so, add bullet point to that list
				li.append(newList);
			} else {
				// if not, append to top level bullets
				existingList.append(newItem);
			}
		} else {
			$(appendNode).append(newList);
		}
		setCursorNode(newItem[0]);
	}

	/**
	 * Set the page being edited as dirty so that a browser warning pops
	 * up about the doc not being saved before the user leaves (via
	 * closing the window, a refresh, following a link, hitting back, etc).
	 */
	function setPageDirty() {
		if (!window.onbeforeunload) {
			$('#h5e-discard-changes')
				.add('#h5e-toolbar-publish')
				.removeClass('disabled');
			window.onbeforeunload = function() {
				return wfMsg('h5e-changes-to-be-discarded');
			};
		}
	}

	/**
	 * Set the page being edited as clean, so there's no browser warning.
	 */
	function setPageClean() {
		$('#h5e-discard-changes')
			.add('#h5e-toolbar-publish')
			.addClass('disabled');
		window.onbeforeunload = null;
	}

	/**
	 * Check to see whether we've flagged the page being edited as dirty.
	 */
	function isPageDirty() {
		return !!window.onbeforeunload;
	}

	/**
	 * Toolbar italics button
	 */
	function tbAddItalics() {
		focusCurrentSection();
		setPageDirty();
		document.execCommand('italic', false, '');
	}

	/**
	 * Toolbar "outdent" button
	 */
	function tbOutdent() {
		focusCurrentSection();
		setPageDirty();

		if (!isStepsCursorTopLevel()) {
			document.execCommand('outdent', false, '');
		}
	}

	/**
	 * Toolbar add a reference button was clicked.
	 */
	function tbAddReference() {
		focusCurrentSection();
		setPageDirty();

		var tmpl = '<a id="h5e-new-ref" class="h5e-button edit-reference" href="#">' + wfMsg('h5e-edit-ref') + '</a> ';
		var refhtml = wfTemplate(tmpl, wfMsg('h5e-ref'));
		document.execCommand('inserthtml', false, refhtml);
		var newref = $('#h5e-new-ref');
		prepEditRefNode(newref, '');
		newref.attr('id', '');
		newref.click();
	}

	/**
	 * Pull the original reference text out of the button of the article's
	 * reference list.
	 */
	function getOrigRefText(refnode) {
		var refid = $('a', refnode).attr('href').replace(/^#/, '');
		var li = $('li#' + refid).clone();
		$('a:first', li).remove();
		var reftext = '';
		var specialFlattenRef = function(i,n) {
			if (n.nodeName == '#text') {
				reftext += n.textContent;
			} else if ($(n).is('a')) {
				reftext += $(n).attr('href');
			} else {
				$(n).contents().each(specialFlattenRef);
			}
		};
		li.contents().each(specialFlattenRef);
		reftext = $.trim(reftext);
		return reftext;
	}

	/**
	 * Add click listeners, etc, to add/edit reference dialog
	 */
	function prepEditRefNode(newref, reftext) {
		newref.data('editref', reftext);

		newref.click(function() {
			var button = this;
			var reftext = $(button).data('editref');
			$('#ref-edit')
				.val(reftext)
				.unbind('keypress')
				.keypress(function(evt) {
					if (evt.which == 13) { // 'Enter' pressed
						$('#ref-edit-change').click();
						return false;
					}
				});


			$('#ref-edit-change')
				.unbind('click')
				.click(function() {
					setPageDirty();
					$(button).data('editref', $('#ref-edit').val() );
					$('#edit-ref-dialog').dialog('close');
				});

			$('#ref-edit-cancel')
				.unbind('click')
				.click(function() {
					$('#edit-ref-dialog').dialog('close');
					return false;
				});

			var pos = saveCursorPos();
			$('#edit-ref-dialog').dialog({
				width: 400,
				minWidth: 400,
				modal: true,
				open: function() {
					// Set correct dialog title
					if ($('#ref-edit').val() == '') {
						var title = wfMsg('h5e-add-reference');
						var button = wfMsg('h5e-add');
					} else {
						var title = wfMsg('h5e-edit-reference');
						var button = wfMsg('h5e-change');
					}
					$('#edit-ref-dialog').dialog('option', 'title', title);
					$('#ref-edit-change').val(button);

					$('#ref-edit').focus();
				},
				close: function(evt, ui) { 
					focusCurrentSection(pos['node']);
					loadCursorPos(pos);
					if ($.trim( $(button).data('editref') ) == '') {
						$(button).remove();
					}
				}
			});

			return false;
		});

		newref.attr('contenteditable', 'false');
	}

	/**
	 * Toolbar button to show Related wikiHows dialog.
	 */
	function tbRelatedWikihows() {
		$('.h5e-related-sortable li').remove();
		var related = loadRelatedWikihows();
		$(related).each(function() {
			var title = this;
			var node = createRelatedWikihowSortableNode(title);
			$('.h5e-related-sortable').append(node);
		});
		$('.h5e-related-sortable')
			.sortable()
			.disableSelection();
		$('#related-wh-dialog').dialog({
			width: 500,
			modal: true,
			open: function() {
				// remove any previous completedivs
				clearAutocompleteResults();

				// google-style auto-complete for related
				InstallAC(
					document['h5e-ac'],
					document['h5e-ac']['h5e-related-new'],
					document['h5e-ac']['h5e-related-add'],
					"/Special:TitleSearch?lim=10",
					"en");

				// customize ac results
				$('#completeDiv')
					.css('z-index', '10000')
					.css('border', '1px solid #aaaaaa');
			},
			close: function(evt, ui) { 
				clearAutocompleteResults();
			}
		});
	}

	/**
	 * In the Related wikiHows dialog, we use a jQuery UI sortable element
	 * to be able to add new related wikihows.  This method creates a list
	 * item for that sortable list.
	 */
	function createRelatedWikihowSortableNode(title) {
		var tmpl = '<li class="h5e-related-li"><span class="related-wh-title">$1</span><div class="trash-icon"><a href="#"><img src="/skins/WikiHow/images/tiny_trash.gif" /></div></li>';
		var howto = wfMsg('howto', title);
		var shortened = getArticleDisplay(howto, 30);
		var node = $( wfTemplate(tmpl, shortened) );
		$('a', node).click(function() {
			var li = $(this).parentsUntil('li').last().parent();
			li.remove();
		});
		node.data('title', title);
		return node;
	}

	/**
	 * Return the possible wikihow sections
	 */
	function getSections() {
		var sections = [
			{'key': 'ingredients', 'name': wfMsg('Ingredients'), 'editable': true},
			{'key': 'steps', 'name': wfMsg('Steps'), 'editable': false}, 
			{'key': 'video', 'name': wfMsg('Video'), 'editable': false}, 
			{'key': 'tips', 'name': wfMsg('Tips'), 'editable': true},
			{'key': 'warnings', 'name': wfMsg('Warnings'), 'editable': true},
			{'key': 'thingsyoullneed', 'name': wfMsg('thingsyoullneed'), 'editable': true}, 
			{'key': 'relatedwikihows', 'name': wfMsg('relatedwikihows'), 'editable': false}, 
			{'key': 'sources', 'name': wfMsg('sourcescitations'), 'editable': true}
		];
		return sections;
	}

	/**
	 * Brings up the section add pop up
	 */
	function tbAddSection() {

		$('#h5e-sections-dialog').dialog({
			width: 400,
			minWidth: 400,
			modal: true,
			close: function(evt, ui) { 
				focusCurrentSection();
			}
		});
		var div = $('#h5e-sections').html('');
		var sections = getSections();
		$(sections).each(function(i, section) {
			var id = 'h5e-sections-' + section['key'];
			var isSteps = section['key'] == 'steps';
			var disabled = !section['editable'] ? ' disabled="disabled"' : '';
			var sectionPresent = $('#' + section['key']).length > 0;
			var checked = sectionPresent ? ' checked="checked"' : '';
			var input = $('<input type="checkbox" id="' + id + '" name="sections" value="' + section['key'] + '"' + disabled + checked + ' /> <label for="' + id + '">'  + section['name'] + '</label><br/>');
			input.appendTo(div);

			if (isSteps) {
				var amDiv = $('<div id="h5e-sections-am" class="h5e-alternate-methods"></div>');
				amDiv.appendTo(div);

				drawAlternateMethodsHTML();
			}
		});
	}
	
	/**
	 * Get the list of sections to add or remove from the section edit
	 * dialog.
	 */
	function getSectionsToAddOrRemove() {
		var toRemove = [];
		var toAdd = [];
		var firstNewSection = null;

		var sections = getSections();
		$(sections).each(function(i, section) {
			var sectionNode = $('#' + section['key']);
			var sectionPresent = sectionNode.length > 0;
			var id = 'h5e-sections-' + section['key'];
			var checked = $('#' + id + ':checked').val();
			if (sectionPresent && !checked) {
				toRemove.push( section['key'] );
			} else if (!sectionPresent && checked) {
				if (!firstNewSection) {
					firstNewSection = section['key'];
				}
				toAdd.push( section['key'] );
			}
		});

		return {
			'add': toAdd,
			'remove': toRemove,
			'first': firstNewSection
		};
	}

	/**
	 * A list of content of removed sections, so the content can be retrieved
	 * and re-inserted into the DOM if the user re-adds the section and the 
	 * page hasn't been reloaded.
	 */
	var removedSections = {};

	/**
	 * Inserts the new section into the HTML, deletes removed ones
	 */
	function addOrRemoveSections(toAdd, toRemove) {
		var sections = getSections();
		$(sections).each(function(i, section) {
			if ($.inArray(section['key'], toRemove) >= 0) {
				// Remove a section, put it in the list of removed section
				var node = $('#' + section['key']);
				var nodes = node
					.add( node.prev() )
					.add( node.nextUntil('h2') )
					.detach();
				if (nodes.length) {
					removedSections[ section['key'] ] = nodes;
				}
			} else if ($.inArray(section['key'], toAdd) >= 0) {
				// Add a new section
				if (typeof removedSections[ section['key'] ] == 'undefined') {
					var defaultContent = section['key'] != 'relatedwikihows' ? '<ul><li>' + wfMsg('h5e-new-section') + '</li></ul>' : '<ul></ul>';
					var addSection = $('<h2><span>' + section['name'] + '</span></h2><div id="' + section['key'] + '" class="article_inner editable" contenteditable="true">' + defaultContent + '</div>');
				} else {
					var addSection = removedSections[ section['key'] ];
				}
				var foundKey = false, foundSibling = false;
				$(sections).each(function(j, sectionj) {
					if (sectionj['key'] == section['key']) {
						foundKey = true;
					} else if (foundKey && !foundSibling) {
						var node = $('#' + sectionj['key']);
						if (node.length) {
							foundSibling = true;
							if (node.prev().is('h2')) {
								node = node.prev();
							}
							node.before(addSection);
						}
					}
				});
				if (!foundSibling) {
					$('#bodycontents').append(addSection);
				}
			}
		});
	}

	/**
	 * Re-draw the alternate methods for the section change dialog, based on
	 * which alternate methods are in the DOM.
	 */
	function drawAlternateMethodsHTML() {
		var div = $('#h5e-sections-am');
		div.html('');

		// get list of alternate methods
		var methods = $('#steps h3');

		// do html for alternate methods 
		methods.each(function(i, method) {
			var id = 'h5e-am-' + i;
			var methodName = $(method).children().first().html();
			var checked = ' checked="checked"';
			var input = $('<input type="checkbox" id="' + id + '" name="sections" value="' + i + '"' + checked + ' /> <label for="' + id + '">' + methodName + '</label><br/>');
			input.appendTo(div);
		});

		var id = 'h5e-sections-add-method';
		var link = $('<a href="#" id="' + id + '">' + wfMsg('h5e-new-alternate-method') + '</a><br/>');
		link.appendTo(div);

		$('#' + id).click(function () {
			$('#h5e-am-name').val('');
			$('#h5e-am-dialog').dialog({
				width: 300,
				minWidth: 300,
				modal: true
			});
			$('#h5e-am-name').focus();
			return false;
		});
	}

	/**
	 * Delete all the alternate methods that were unselected in the Section
	 * change dialog.
	 */
	function deleteUnselectedAlternateMethods() {
		var methods = $('#steps h3');
		methods.each(function(i, method) {
			var id = 'h5e-am-' + i;
			var checked = $('#' + id + ':checked').val();
			if (!checked) {
				// Remove this alternate method
				var jmethod = $(method);
				jmethod.nextUntil('#steps h3').remove();
				jmethod.remove();
			}
		});
	}

	/**
	 * Add a new blank alternate method to the DOM
	 */
	function addAlternateMethod(name) {
		var methods = $('#steps h3');
		var escName = name.replace(/ /g, '_');
		var html = $('<p><a name="' + escName + '" id="' + escName + '"></a></p><h3><span>' + name + '</span></h3><ol class="steps_list_2"><li class="steps_li final_li"><div contenteditable="false" class="step_num">1</div><b class="whb">' + wfMsg('h5e-new-method') + '</b><div class="clearall"></div></li></ol>');
		$('#steps').append(html);
	}


	function getEditSummary(selector) {
		var editSummary = $(selector).val();
		editSummary = $.trim(editSummary);
		if (editSummary == wfMsg('h5e-enter-edit-summary') ||
			editSummary == wfMsg('h5e-edit-summary-examples'))
		{
			editSummary = '';
		}
		return editSummary;
	}

	/**
	 * Call to the server to save the html we've edited.  Call the method
	 * onFinish after save is complete.
	 */
	function saveArticle(onFinish) {
		var contents = $('#bodycontents');
		var editSummary = getEditSummary('#h5e-edit-summary-pre');
		var data = contents.html();
		contents.load('/Special:Html5editor',
			{ action: 'publish-html',
			  target: wgPageName,
			  summary: editSummary,
			  html: data },
			function () {
				if (onFinish) onFinish();
			}
		);

		return false;
	}

	var TOOLBAR_HEIGHT_PIXELS = 76;

	function addPageTopMargin(pixels) {
		var topMargin = $('#header').css('margin-top');
		topMargin = parseInt(topMargin.replace(/px/), 10);
		if (pixels > 0 ||
			pixels < 0 && topMargin > -pixels)
		{
			$('#header').css('margin-top', (topMargin + pixels) + 'px');
		}
	}

	function hideTemplates() {
		var templates = $('.template');

		var vids = templates.filter(function() {
			return $('object', this).length > 0;
		});
		var nonVids = templates.not(vids);

		var tmpl = '<div class="h5e-hidden-video"><span>' + wfMsg('h5e-hidden-video') + '</span></div>';
		vids
			.fadeOut()
			.after(tmpl);

		var tmpl = '<div class="h5e-hidden-template"><span>' + wfMsg('h5e-hidden-template') + '</span></div>';
		nonVids
			.fadeOut()
			.after(tmpl);

		$('.h5e-hidden-video span')
			.attr('contenteditable', false);
	}

	function showTemplates() {
		$('.h5e-hidden-video')
			.add('.h5e-hidden-template')
			.remove();
		$('.template').fadeIn();
	}

	/**
	 * Method called when we enter HTML5 editing mode.
	 */
	function startEditing(section, postEditFunc) {

		// make all of the editable sections contenteditable=true, hide 
		// templates, and set up key handler for the steps function
		$('.editable').attr('contenteditable', true);

		$('img')
			.add('.mwimg')
			.add('#video')
			.attr('contenteditable', false);
		$('.wh_ad').fadeOut();
		$('.caption').attr('contenteditable', true);

		$('#st_wrap').fadeOut();
		hideTemplates();
		$('#toc').fadeOut();
		$("h2").each(function() {
			if ($(this).html() == "Contents") $(this).fadeOut();
		});

		$('.editsectionbutton').fadeOut();
		$('.edit_article_button').fadeOut();

		$('.h5e-tb-save-wrapper').css('display', 'none');
		$('.h5e-tb-function-wrapper').css('display', 'block');

		// TODO: Bebeth, instead of slideDown() here I should be using 
		// animate(). The problem with slideDown is the button squish as 
		// they're sliding in.
		$('#h5e-editing-toolbar').slideDown();

		$('#h5e-edit-summary-pre')
			.add('#h5e-edit-summary-post')
			.blur();

		// add pixels to the top of the first div on the page
		// so that it doesn't feel like the edit bar is covering anything
		// that can't be found any longer
		addPageTopMargin(TOOLBAR_HEIGHT_PIXELS);

		// listen to keystrokes in the html5 editing areas
		$('#bodycontents').keypress(onKeystroke);

		$('#tab_edit')
			.unbind('click')
			.addClass('on')
			.click(function() {
				return false;
			});
		$('#tab_article').removeClass('on');

		$('.rounders').mouseenter(imageHoverIn);
		$('a.internal').click(function() { return false; });

		// Stop the RC widget from updating and scrolling
		rcStop();

		$('#relatedwikihows').attr('contenteditable', false);

		// Add a new related wikihows section if it didn't exist before
		if (!$('#relatedwikihows').length) {
			addOrRemoveSections(['relatedwikihows'], []);
		}

		var relatedWikihows = loadRelatedWikihows();
		saveRelatedWikihowsInactive(relatedWikihows);

		// replace all references with an "edit reference" link
		$('sup.reference').each(function(i, refnode) {
			var reftext = getOrigRefText(refnode);
			var tmpl = '<a id="h5e-new-ref" class="h5e-button edit-reference" href="#">' + wfMsg('h5e-edit-ref') + '</a>';
			var newref = $(tmpl);
			$(refnode).replaceWith(newref);
			prepEditRefNode(newref, reftext);
		});

		var msg = wfMsg('h5e-references-removed');
		var html = $('<div class="sources-removed" contenteditable="false">' + msg + '</div>');
		$('ol.references').replaceWith(html);

		// just in case the user navigates away from the page
		setPageClean();

		// in case we are doing manual revert
		if (window.location.search.indexOf("oldid") >= 0) 
			setPageDirty();

		if (section && section != 'relatedwikihows') {
			focusSection(section);
		}

		if (postEditFunc) {
			postEditFunc();
		}
	}

	/**
	 * Unhide a fixed position div and display it centered vertically and
	 * horizontally in the browser.
	 */
	function displayCenterFixedDiv(jqDiv) {
		if (jqDiv.length) {
			var w = jqDiv.width();
			var h = jqDiv.height();
			var winw = window.innerWidth;
			var winh = window.innerHeight;
			var nleft = Math.round(winw / 2 - w / 2);
			var ntop = Math.round(winh / 2 - h / 2);
			jqDiv
				.css({
					'display': 'block',
					'top': ntop,
					'left': nleft
				});
		}
	}

	/**
	 * Method called when we either exit HTML5 editing mode or when we
	 * first go to the page (while not in editing mode).
	 */
	function stopEditing(saveIt) {
		if (saveIt) {
			var savingNotice = $('.h5e-saving-notice');
			displayCenterFixedDiv(savingNotice);
		}

		$('#tab_edit')
			.unbind('click')
			.removeClass('on')
			.click(function () {
				startEditing('intro');
				return false;
			});
		$('#tab_article').addClass('on');

		// remove extra pixels from top of page since edit bar is gone
		addPageTopMargin(-TOOLBAR_HEIGHT_PIXELS);

		$('#bodycontents')
			.attr('contenteditable', 'false')
			.unbind('keypress');
		$('.editable').attr('contenteditable', false);

		showTemplates();
		$('#st_wrap').fadeIn();

		$('.wh_ad').fadeIn();
		$('.editsectionbutton').fadeIn();
		$('.edit_article_button').fadeIn();

		var relatedWikihows = loadRelatedWikihows();
		saveRelatedWikihowsActive(relatedWikihows);

		// remove related wh section if there aren't any related wikihows
		if (!$(relatedWikihows).length) {
			addOrRemoveSections([], ['relatedwikihows']);
		}

		removeImageHoverListeners();
		$('#h5e-mwimg-mouseover').hide();

		// replace all "edit reference" links with real references
		$('.edit-reference').each(function(i, ref) {
			var reftext = $(ref).data('editref');
			var refhtml = $('<sup><a href="#" onclick="return false;">[ref]</a></sup><input type="hidden" id="h5e-ref-' + i + '" value="' + reftext + '"/>');
			$(ref).replaceWith(refhtml);
		});

		if (saveIt) {
			saveArticle(function () {
				setPageClean();
				$('.h5e-saving-notice').css('display', 'none');
			});

			var editSummary = getEditSummary('#h5e-edit-summary-pre');
			if (!editSummary) {
				$('.h5e-tb-function-wrapper').fadeOut('fast', function() {
					$('.h5e-tb-save-wrapper').fadeIn();
				});
			} else {
				$('#h5e-editing-toolbar').slideUp('fast', function() {
					$('#h5e-edit-summary-pre')
						.add('#h5e-edit-summary-post')
						.val('');
				});
			}
		}

	}

	/**
	 * Get DOM node where the cursor currently lies in the html5-edited 
	 * document.
	 */
	function getCursorNode() {
		if (window.getSelection) { // should work in webkit/ff
			var node = window.getSelection().anchorNode;
			var startNode = (node && node.nodeName == "#text" ? node.parentNode : node);
			return startNode;
		} else {
			return null;
		}
	}

	/**
	 * Change a site link like "/Article-Name" to an article name like
	 * "Article Name"
	 */
	function getArticleFromLink(url) {
		return url
			.replace(/^http:\/\/([^\/]*)\//i, '')
			.replace(/^\//, '')
			.replace(/-/g, ' ');
	}

	/**
	 * Change an article name like "Article Name" to a site link like
	 * "/Article-Name"
	 */
	function getLinkFromArticle(article) {
		return '/' + article
			.replace(/ /g, '-');
	}

	/**
	 * Shorten the display of a link from something like this:
	 *
	 * This is a really really really really really really long link name
	 *
	 * to this:
	 *
	 * This is a really real...ng link name
	 */
	function getArticleDisplay(articleName, numChars) {
		if (typeof numChars == 'undefined') numChars = 45;
		if (articleName.length > numChars) {
			var start = Math.round(2*numChars / 3);
			var end = Math.round(1*numChars / 3);
			var re = new RegExp('^(.{' + start + '}).*(.{' + end + '})$');
			var m = articleName.match(re);
			return m[1] + '...' + m[2];
		} else {
			return articleName;
		}
	}

	/**
	 * Add click listeners to the editing toolbar elements.
	 */
	function tbClickListeners() {

		$('#h5e-toolbar-a').click(tbAddLink);
		$('#h5e-toolbar-img').click(tbAddImage);
		$('#h5e-toolbar-italics').click(tbAddItalics);
		$('#h5e-toolbar-indent').click(tbIndent);
		$('#h5e-toolbar-outdent').click(tbOutdent);
		$('#h5e-toolbar-section').click(tbAddSection);
		$('#h5e-toolbar-ref').click(tbAddReference);
		$('#h5e-toolbar-related').click(tbRelatedWikihows);

		$('#h5e-toolbar-publish').click(function() {
			focusCurrentSection();
			stopEditing(true);
			return false;
		});

		$('.h5e-toolbar-cancel').click(function() {
			if (isPageDirty()) {
				// force a refresh
				window.location.href = window.location.href;
			} else {
				stopEditing(false);
				$('#h5e-editing-toolbar').slideUp();
			}
			return false;
		});

		$('#h5e-edit-summary-pre').blur(function() {
			if ($('#h5e-edit-summary-pre').val() == '') {
				$('#h5e-edit-summary-pre')
					.val( wfMsg('h5e-enter-edit-summary') )
					.addClass('h5e-example-text');
			}
		});
		$('#h5e-edit-summary-pre').focus(function() {
			if ($('#h5e-edit-summary-pre').val() == wfMsg('h5e-enter-edit-summary')) {
				$('#h5e-edit-summary-pre')
					.removeClass('h5e-example-text')
					.val('');
			}
		});
		$('#h5e-edit-summary-pre').keypress(function(evt) {
			if (evt.which == 13) { // 'Enter' pressed
				$('#h5e-toolbar-publish').click();
				return false;
			}
		});

		$('#h5e-edit-summary-post').blur(function() {
			if ($('#h5e-edit-summary-post').val() == '') {
				$('#h5e-edit-summary-post')
					.val( wfMsg('h5e-edit-summary-examples') )
					.addClass('h5e-example-text');
			}
		});
		$('#h5e-edit-summary-post').focus(function() {
			if ($('#h5e-edit-summary-post').val() == wfMsg('h5e-edit-summary-examples')) {
				$('#h5e-edit-summary-post')
					.removeClass('h5e-example-text')
					.val('');
			}
		});

		$('#h5e-edit-summary-save').click(function() {
			var postSaveSummaryFunc = function() {
				$('#h5e-editing-toolbar').slideUp('fast', function() {
					$('#h5e-edit-summary-pre')
						.add('#h5e-edit-summary-post')
						.val('');
				});
			};

			var editSummary = getEditSummary('#h5e-edit-summary-post');
			if (editSummary)
			{
				$.post('/Special:Html5editor',
					{ eaction: 'save-summary',
					  target: wgPageName,
					  summary: editSummary 
					},
					postSaveSummaryFunc
				);
			} else {
				postSaveSummaryFunc();
			}
		});
		$('#h5e-edit-summary-post').keypress(function(evt) {
			if (evt.which == 13) { // 'Enter' pressed
				$('#h5e-edit-summary-save').click();
				return false;
			}
		});
	}

	/**
	 * Store the last image div we hovered over, to be used by the
	 * imageHoverIn() and imageHoverOut() methods.
	 */
	var mouseHoverDiv = null;

	/**
	 * Called when we mouse over an image in editing mode.
	 */
	function imageHoverIn() {
		// Unbind this event since our mouse will be over the new opaque
		// div instead of the img one (causes flickering otherwise)
		$(this).unbind('mouseenter');
		mouseHoverDiv = this;

		// Add the opaque div over top of the image our mouse is over
		var h = $(this).height();
		var w = $(this).width();
		var offset = $(this).offset();
		$('#h5e-mwimg-mouseover')
			.css('display', 'block')
			.height(h)
			.width(w)
			.offset(offset)
			.mouseleave(imageHoverOut);

		$('#h5e-mwimg-mouseover div')
			.css('background-color', 'white')
			.css('opacity', '0.6')
			.css('display', 'block')
			.height(h)
			.width(w);

		// Bind to the "Remove Image" link or icon in the opaque div
		$('#h5e-mwimg-mouseover a')
			.css('margin-top', (h/3)*-2)
			.css('margin-left', w/3)
			.css('display', 'block')
			.click(showRemoveImageConfirm);

		// Bind to the remove confirmation div
		$('#h5e-mwimg-mouseover-confirm')
			.css('margin-top', (h/3)*-2)
			.css('margin-left', (w-178)/2)
			.css('display','none');
	}

	/**
	 * Called when a mouse is no longer over the image or opaque div that's
	 * over top the image.
	 */
	function imageHoverOut() {
		$(this).unbind('mouseleave');
		$(mouseHoverDiv).mouseenter(imageHoverIn);
		$('#h5e-mwimg-mouseover').fadeOut('fast');
	}

	/**
	 * Show "remove image" confirmation: YES | NO
	 */
	function showRemoveImageConfirm() {
		$('#h5e-mwimg-mouseover a').fadeOut('fast');
		$('#h5e-mwimg-mouseover-confirm').fadeIn('fast');
		return false;
	}

	/**
	 * Show remove image link instead of confirmation
	 */
	function showRemoveImageLink() {
		$('#h5e-mwimg-mouseover-confirm').fadeOut('fast');
		$('#h5e-mwimg-mouseover a').fadeIn('fast');
		return false;
	}

	/**
	 * Remove an image from the DOM
	 */
	function removeImage() {
		setPageDirty();

		var rmDiv = $(mouseHoverDiv);
		var chain = rmDiv.parentsUntil('.mwimg');
		// make sure there is actually a parent with class mwimg
		if (chain.length < 3) {
			rmDiv = chain.last().parent();
		}
		rmDiv.remove();
		$('#h5e-mwimg-mouseover').css('display', 'none');
		return false;
	}

	/**
	 * Remove image editing listeners
	 */
	function removeImageHoverListeners() {
		$('.rounders').unbind('mouseenter');
	}

	function clearAutocompleteResults() {
		$('#completeDiv').remove();
		$('#completionFrame').remove();
	}

	function loadRelatedWikihows() {
		var related = [];
		var selection = $('#relatedwikihows ul li a');
		if (selection.length == 0) {
			selection = $('#relatedwikihows ul li');
		}
		selection.each(function() {
			var title = $(this).data('title');
			if (!title) {
				var title = $(this).attr('title');
			}
			related.push(title);
		});
		return related;
	}

	function saveRelatedWikihowsActive(articles) {
		// Clear out whatever is currently there
		$('#relatedwikihows ul li').remove();
		$('#relatedwikihows .h5e-rel-wh-edit').remove();

		var ul = $('#relatedwikihows ul');
		$(articles).each(function() {
			var title = this;
			var howto = wfMsg('howto', title);
			var href = getLinkFromArticle(title);
			var tmpl = '<li><a title="$1" href="$2">$3</a></li>';
			var html = wfTemplate(tmpl, title, href, howto);
			ul.append( $(html) );
		});
	}

	function saveRelatedWikihowsInactive(articles) {
		// Clear out whatever is currently there
		$('#relatedwikihows ul li').remove();
		$('#relatedwikihows .h5e-rel-wh-edit').remove();

		var ul = $('#relatedwikihows ul');
		$(articles).each(function() {
			var title = this;
			var howto = wfMsg('howto', title);
			var tmpl = '<li><span class="h5e-rel-wh-disabled">$1</span></li>';
			var html = wfTemplate(tmpl, howto);
			var node = $(html);
			node.data('title', title);
			ul.append( node );
		});

		var tmpl = '<div class="h5e-rel-wh-edit"><a href="#" class="h5e-no-edit-tooltip">$1</a></div>';
		var html = wfTemplate(tmpl, wfMsg('h5e-rel-wh-edit'));
		ul.after(html);

		$('.h5e-rel-wh-edit a').click(function() {
			tbRelatedWikihows();
			return false;
		});
	}

	var saveLink = false;

	/**
	 * Show edit/add link dialog
	 */
	function showEditLinkDialog(action, linkText, href, onSaveFunc) {
		saveLink = false;
		var pos = saveCursorPos();
		if (action == 'change') {
			var title = wfMsg('edit-link');
		} else if (action == 'add') {
			var title = wfMsg('add-link');
		}

		$('#h5e-link-dialog').dialog({
			width: 400,
			minWidth: 400,
			modal: true,
			open: function() {
				// remove any previous completedivs
				clearAutocompleteResults();

				// google-style auto-complete for links
				InstallAC(
					document['h5e-ac-link'],
					document['h5e-ac-link']['h5e-link-article'],
					"",
					"/Special:TitleSearch?lim=10",
					"en");

				// customize ac results
				$('#completeDiv')
					.css('z-index', '10000')
					.css('border', '1px solid #aaaaaa');

				$('#h5e-link-dialog').dialog('option', 'title', title);
			},
			close: function(evt, ui) {
				focusCurrentSection(pos['node']);
				loadCursorPos(pos);

				if (saveLink) {
					var text = $('#h5e-link-text').val();
					var article = $('#h5e-link-article').val();
					var link = getLinkFromArticle(article);

					if (onSaveFunc) onSaveFunc(text, link);
				}

				clearAutocompleteResults();
			}
		});

		$('#h5e-link-text').val(linkText);
		var article = getArticleFromLink(href);
		$('#h5e-link-article').val(article);
		if (linkText == '') {
			$('#h5e-link-text').focus();
		} else {
			$('#h5e-link-article').focus();
		}
	}

	/**
	 * Given a DOM node, returns the href of it if it's an <a> tag or has any
	 * immediate parents that are an <a> tag.
	 *
	 * @return the href value or '' if none exists
	 */
	function getAnchorNode(node) {
		var anchor = node;
		if (!node.is('a')) {
			anchor = node.parents('a').last();
		}
		if (anchor.length && !anchor.hasClass('h5e-no-edit-tooltip')) {
			return anchor;
		} else {
			return null;
		}
	}

	/**
	 * Add all the listeners to buttons in our editing dialogs.
	 */
	function dialogClickListeners() {
		var editLink = $('.h5e-edit-link-options-over');
		var currentLinkNode;

		// Show change link bar when key or mouse onto a link
		var oldAnchorEditLink = '';
		editLink.hide();
		$('#bodycontents').bind('keypress click', function(e) {
			var startNode = $(getCursorNode());
			currentLinkNode = getAnchorNode(startNode);
			var newShowLink = currentLinkNode ? currentLinkNode.attr('href') : '';

			// check to see if oldAnchorEditLink has changed, modify the
			// hrefs and css only if it has
			if (newShowLink !== oldAnchorEditLink) {
				oldAnchorEditLink = newShowLink;
				if (newShowLink) {
					editLink
						.css({
							top: startNode.offset().top - editLink.height() - 25,
							left: startNode.offset().left
						})
						.show()
						.data('node', currentLinkNode);
					var href = newShowLink;
					var article = getArticleFromLink(href);
					var linkDisplay = $('#h5e-editlink-display');
					linkDisplay.text( getArticleDisplay(article) );
					linkDisplay.attr('title', article);
					linkDisplay.attr('target', '_blank');
					linkDisplay.attr('href', href);
					var innerWidth = $('.h5e-edit-link-inner').width() + 31;
					editLink.width(innerWidth + 5);
				} else {
					editLink.fadeOut('fast');
				}
			}
		});

		// Edit link pop-in, called when a user changes a link in the text 
		// that's selected
		$('#h5e-editlink-change').click(function() {
			oldAnchorEditLink = '';
			editLink.hide();

			var href = currentLinkNode.attr('href');
			var text = currentLinkNode.text();
			showEditLinkDialog('change', text, href,
				function(text, link) { // when 'Change' button is clicked
					// replace current link and text
					currentLinkNode.attr('href', link);
					currentLinkNode.attr('title', getArticleFromLink(text));
					var startNode = editLink.data('node');
					startNode.text(text);
				}
			);
			return false;
		});

		// Edit link pop-in, when the user chooses to remove the link 
		// from some text
		$('#h5e-editlink-remove').click(function() {
			setPageDirty();

			var text = currentLinkNode.text();
			currentLinkNode.replaceWith('<span>'+text+'</span>');

			oldAnchorEditLink = '';
			editLink.fadeOut('fast');
			return false;
		});

		// Edit link pop-in, when the user clicks cancel (to close pop-in)
		$('#h5e-editlink-cancel').click(function() {
			oldAnchorEditLink = '';
			editLink.fadeOut('fast');
			return false;
		});

		// Change link dialog, when user clicks Change button
		var defaultBorder = $('#h5e-link-article').css('border');
		$('#h5e-link-change').click(function() {
			setPageDirty();

			$('#h5e-link-article').css('border', defaultBorder);
			var article = $.trim( $('#h5e-link-article').val() );
			if (article.indexOf('/') >= 0 && article.indexOf(' ') < 0) {
				$('#h5e-link-article')
					.css('border', '2px solid #aa0000')
					.focus();

				return false;
			} else {
				saveLink = true;
				$('#h5e-link-dialog').dialog('close');
			}
		});

		// Change link dialog, when user clicks Cancel button
		$('#h5e-link-cancel').click(function() {
			$('#h5e-link-dialog').dialog('close');
			return false;
		});

		$('#h5e-link-preview').click(function() {
			var article = $('#h5e-link-article').val();
			var link = getLinkFromArticle(article);
			$('#h5e-link-preview').attr('href', link);
		});

		$('#h5e-link-article').keypress(function(evt) {
			if (evt.which == 13) { // 'Enter' key pressed
				$('#h5e-link-change').click();
				return false;
			}
		});

		// Section dialog, user clicks change
		$('#h5e-sections-change').click(function () {
			setPageDirty();
			var sectionsDiff = getSectionsToAddOrRemove();
			var firstNewSection = sectionsDiff['first'];
			addOrRemoveSections(sectionsDiff['add'], sectionsDiff['remove']);
			deleteUnselectedAlternateMethods();
			if (firstNewSection) {
				focusSection(firstNewSection);
			}
			$('#h5e-sections-dialog').dialog('close');
		});

		// Section dialog, user clicks cancel
		$('#h5e-sections-cancel').click(function() {
			$('#h5e-sections-dialog').dialog('close');
			return false;
		});

		// Section dialog -> Alternate method add, user clicks add
		$('#h5e-am-add').click(function() {
			addAlternateMethod($('#h5e-am-name').val());
			drawAlternateMethodsHTML();
			$('#h5e-am-dialog').dialog('close');
			return false;
		});

		$('#h5e-am-name').keypress(function(evt) {
			if (evt.which == 13) { // 'Enter' key pressed
				$('#h5e-am-add').click();
				return false;
			}
		});

		// Section dialog -> Alternate method add, user clicks cancel
		$('#h5e-am-cancel').click(function() {
			$('#h5e-am-dialog').dialog('close');
			return false;
		});

		// Edit related wikihows dialog, Done button
		$('#h5e-related-done').click(function() {
			setPageDirty();

			// save links to related wikihows section after dialog Done
			var related = [];
			$('.h5e-related-sortable li').each(function() {
				var title = $(this).data('title');
				related.push(title);
			});
			saveRelatedWikihowsInactive(related);
			$('#related-wh-dialog').dialog('close');
			return false;
		});
	
		$('#h5e-related-cancel').click(function() {
			$('#related-wh-dialog').dialog('close');
			return false;
		});

		$('#h5e-related-add').click(function() {
			var title = $('#h5e-related-new').val();
			title = $.trim(title);
			if (title != '') {
				var node = createRelatedWikihowSortableNode(title);
				$('.h5e-related-sortable').append(node);
			}
			$('#h5e-related-new')
				.val('')
				.focus();
			return false;
		});

		$('#h5e-related-new').keypress(function(evt) {
			if (evt.which == 13) { // 'Enter' key pressed
				$('#h5e-related-add').click();
				return false;
			}
		});

		$('.related-wh-overlay-edit button').click(function() {
			tbRelatedWikihows();
			return false;
		});

		$('.h5e-mwimg-confirm-no').click(function() {
			showRemoveImageLink();
			return false;
		});

		$('.h5e-mwimg-confirm-yes').click(function() {
			removeImage();
			return false;
		});

	}

	/**
	 * Add click handlers to the DOM relating to HTML5 editing.
	 */
	function attachClickListeners() {
		stopEditing(false);

		tbClickListeners();
		dialogClickListeners();

		$('.edit_article_button').click(function() {
			startEditing('intro');
			return false;
		});

		$('.editsectionbutton').live('click', function() {
			var id = $(this).parent().next().attr('id');
			startEditing(id, function() {
				if (id == 'relatedwikihows') {
					tbRelatedWikihows();
				}
			});
			return false;
		});
	}

	/**
	 * Return the background pixel offset in a sprite, given the id
	 * of the element.
	 */
	function spriteLocator(id) {
		var locations = {
			'h5e-toolbar-img': '0',
			'h5e-toolbar-a': '-70px',
			'h5e-toolbar-italics': '-131px',
			'h5e-toolbar-indent': '-161px',
			'h5e-toolbar-outdent': '-191px',
			'h5e-toolbar-ref': '-221px',
			'h5e-toolbar-section': '-251px',
			'h5e-toolbar-related': '-281px',
			'h5e-discard-changes': '-311px',
			'h5e-toolbar-publish': '-341px',
			'h5e-related-done': '-447px'
		};
		if (typeof locations[id] != 'undefined') {
			var bg_pos = locations[id];
		} else {
			var bg_pos = '';
		}
		return bg_pos;
	}

	/**
	 * Add hover and mousedown handlers to the DOM relating to HTML5 editing.
	 */
	function addButtonListeners() {
		$('.h5e-button').hover(
			function () {
				$(this).data('bg_og',$(this).css('background-position'));

				bg_pos = spriteLocator( $(this).attr('id') );
				if (bg_pos == '') return;

				// set the bg position
				$(this).css('background-position',bg_pos + ' -38px');

			},
			function () {
				$(this).css('background-position',$(this).data('bg_og'));
			}
		);

		$('.h5e-button').click(
			function (event) {
				event.preventDefault();
			}
		);
		$('.h5e-button').mousedown(
			function () {

				bg_pos = spriteLocator( $(this).attr('id') );
				if (bg_pos == '') return;

				// set the bg position
				$(this).css('background-position',bg_pos + ' -76px');
			}
		);
		$('.h5e-button').mouseup( 
			function () {
				$(this).css('background-position',$(this).data('bg_og'));
			}
		);
	}

	/**
	 * Check if the browser can handle html5 editing on wikihow.
	 */
	function isHtml5EditingCompatible() {
		// We determine this by browser rather than by feature because we use
		// so many features that this is easier.
		var webkit = isHtml5EditWebkitReady();
		var firefox = isHtml5EditFirefoxReady();
		return webkit || firefox;
	}

	/**
	 * Check if the browser is WebKit and can handle html5 editing on wikihow.
	 */
	function isHtml5EditWebkitReady() {
		var webkit = typeof $.browser['webkit'] != 'undefined' && $.browser['webkit'] && parseInt($.browser['version'], 10) >= 500; // Safari or Chrome
		return webkit;
	}

	/**
	 * Check if the browser is Firefox and can handle html5 editing on wikihow.
	 */
	function isHtml5EditFirefoxReady() {
		var firefox = typeof $.browser['mozilla'] != 'undefined' && $.browser['mozilla'] && $.browser['version'].substr(0,3) == '1.9'; // FF3
		return firefox;
	}

	/**
	 * The settings returned by getEditSettingsForBrowser()
	 */
	var settings = {};

	/**
	 * Determine the browser settings/configuration that will customize how 
	 * certain actions are performed in Javascript.
	 */
	function getEditSettingsForBrowser() {
		var config = {};
		var isWebkit = isHtml5EditWebkitReady();
		var isFirefox = isHtml5EditFirefoxReady();
		config['needs-section-focus'] = isFirefox;
		return config;
	}

	// returns the public interface singleton
	return {

		/**
		 * Initialize the html5 editor.  Called when page is loaded.  
		 * startEditing() is the method called when editing actually starts.
		 *
		 * @public
		 */
		init: function() {
            // kill the temporary listeners added in edit-bootstrap.tmpl.php
            $('#tab_edit').die('click');
            $('.editsectionbutton').die('click');
            $('.edit_article_button').die('click');

			if (isHtml5EditingCompatible() && wgNamespaceNumber == 0) {

				settings = getEditSettingsForBrowser();

				makeStepNumbersNonEditable();

				attachClickListeners();

				addButtonListeners();

				if (whH5EClickedEditButton) {
					var button = whH5EClickedEditButton;
					whH5EClickedEditButton = null;
					$(button).click();
				}
				// temporary: if e=true param is in url, throw us into edit 
				// right away, for speedy testing
				if (document.location.href.indexOf('e=true') >= 0) {
					startEditing('intro');
				}
			} else {
				// insert an ad for firefox or chrome here
			}
		},

	};


	

})(jQuery); // exec anonymous function and return resulting class

// HTML5 init is run on the DOM ready event
jQuery(WH.h5e.init);

