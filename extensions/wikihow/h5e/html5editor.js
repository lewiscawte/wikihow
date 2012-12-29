
if (typeof WH == 'undefined') {
	var WH = {};
}

// wikiHow's HTML 5 editor
WH.h5e = (function ($) {

	/**
	 * Re-numbers steps after a new step has been inserted, etc.
	 * @private
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

		var newstep_tmpl = '<li class="steps_li"><div class="step_num" contenteditable="false">1</div><span></span><div class="clearall"></div>';
		li.after(newstep_tmpl);
		var newli = li.next();

		if (li.hasClass('final_li')) {
			li.removeClass('final_li');
			newli.addClass('final_li');
		}

		var first = $('span', newli).first();
		if (first.length) {
			var node = first[0];
			setCursorNode(node);
		}

		renumberSteps();

		// if bold isn't currently on, enable it for start of next step
		if (!document.queryCommandState('bold')) {
			document.execCommand('bold', false, '');
		}

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
	 * Get the step where the keyboard cursor is currently placed.
	 */
	function getCursorLi() {
		var select = window.getSelection();
		var anchor = $(select.anchorNode);
		var currentli = anchor.parentsUntil('#steps ol').last();
		if (!currentli.length && anchor.is('li')) {
			currentli = anchor;
		}
		return currentli;
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
		if (!isCursorAtListItemStart()) {
			// if we're in the middle of the step, don't remove step, just 
			// backspace
			return true;
		}

		// get current li under cursor
		var currentli = getCursorLi();
		var select = window.getSelection();

		var prevli = currentli.prev();
		if (!prevli.length) {
			// if we're at the start of the first li, don't do anything
			return false;
		}

		// position cursor at preview li
		var lastText = prevli.textNodes(true).last();
		if (lastText.length) {
			// position cursor at end of last text node of previous li
			var node = lastText[0];
			var position = node.length;
			setCursorNode(node, position);
		} else {
			// do something if the li has no text nodes
		}

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
	 * Check to see whether a keyboard event was an arrow key keypress.
	 */
	function isArrowKeyEvent(evt) {
		var ret = false;
		if (typeof evt.keyCode != 'undefined') {
			if (evt.keyCode == 37 ||
				evt.keyCode == 38 ||
				evt.keyCode == 39 ||
				evt.keyCode == 40)
			{
				ret = true;
			}
		}
		return ret;
	}

	/**
	 * Tracks keystrokes and inserts new step numbers when a user presses enter,
	 * or deletes them on backspace.  This method is only called for certain 
	 * keystrokes for efficiency.
	 *
	 * @private
	 */
	function onKeystroke(evt) {
		var key = evt.which;
		var propagate = true;

		if (!isArrowKeyEvent(evt)) {
			// if array key was not hit, mark document changed so user
			// gets a warning when leaving page without saving
			setPageDirty();
		}

//console.log('ch:'+key);
		if (key == 13 && typeof evt.isSimulated == 'undefined') { // 'Enter' key
			propagate = createNewStepOnEnter(evt);
		} else if (key == 8) { // 'Backspace' key
			propagate = removeStepOnBackspace(evt);
		} else if (String.fromCharCode(key) == '.') { // '.' key
			if (document.queryCommandState('bold')) {
			//if (isCursorBold()) {
				document.execCommand('bold', false, '');
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
	 * Returns true if and only if the text beneath the keyboard cursor is 
	 * bold.
	 */
	/* not used
	function isCursorBold() {
		var cursor = window.getSelection();
		var anchor = $(cursor.anchorNode);
		if (anchor[0].nodeName == '#text') {
			anchor = $(anchor).parent();
		}
		var isBold = 
			anchor.is('b') || anchor.is('strong') ||
			anchor.is('span') && anchor.css('font-weight') == 'bold';
		return isBold;
	} */

	/**
	 * Returns true if and only if the keyboard cursor is at the start of a 
	 * top level li in a step.  Used so that we can collapse 2 steps into one
	 * when user hits backspace at the start of a step.
	 */
	function isCursorAtListItemStart() {
		var select = window.getSelection();
		var li = getCursorLi().clone();
		$('div.step_num', li).remove();
		var firstTextNodes = li.textNodes(true).first();
		if (firstTextNodes.length) {
			var firstTextNode = firstTextNodes[0];
			var cursorTextNode = getCursorTextNode();
			if (textNodesEqual(firstTextNode, cursorTextNode)) {
				return select.anchorOffset == 0;
			}
		} else {
			return true;
		}

		return false;
	}

	/**
	 * Brings the cursor back to the section we're editing after a toolbar
	 * button was pressed.
	 */
	function focusCurrentSection(currentNode) {
		var node = !currentNode ? getCursorNode() : currentNode;
		var parents = $(node).parentsUntil('#bodycontents');
		parents.last().focus();
	}

	/**
	 * Move the keyboard cursor to the start of a section specified.  Make
	 * sure the browser view scrolls to that section if it's not there.
	 */
	function focusSection(section) {
		var sectionDiv, firstText;
		if (section == 'intro') {
			sectionDiv = $('#bodycontents .article_inner:first');
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
			sectionDiv.focus();
			if (firstText.length) {
				setCursorNode(firstText[0], 0);
			}
		}
	}

	function saveCursorPos() {
		var sel = window.getSelection();
		var savedNode = sel.anchorNode;
		var savedOffset = sel.anchorOffset;
		return { 'node': savedNode, 'offset' : savedOffset };
	}

	function loadCursorPos(pos) {
		if (pos && pos['node']) {
			setCursorNode(pos['node'], pos['offset']);
		}
	}


	/**
	 * Toolbar add link button clicked.  Converts selected text into a link,
	 * or inserts a new link with default text if no text is selected.
	 */
	function tbAddLinkToSection() {
		focusCurrentSection();
		setPageDirty();

		// TODO: internationalize article choice
		var link = '/Import-Content-Into-wikiHow';
		var title = getArticleFromLink(link);

		// if there's no selection, add a new link; if there is a text
		// selection, turn the selection into a link
		var cursor = document.getSelection();
		if (cursor === '') {
			var html = '<a href="' + link + '" title="' + title + '">' + wfMsg('new-link') + '</a> ';
			document.execCommand('inserthtml', false, html);
		} else {
			document.execCommand('createlink', false, link);
		}
	}

	/**
	 * Stores the position of the cursor before entering the Image Upload
	 * dialog.
	 */
	var preEIUCursorLi = null;

	/**
	 * Store the position of the cursor and call the Image Upload dialog.
	 */
	function tbAddImageToSection() {
		focusCurrentSection();
		preEIUCursorLi = getCursorLi();
		easyImageUpload.doEIUModal('html5');
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
	 */
	function insertImageHtml(details, html) {
		//focusCurrentSection();
		setPageDirty();

		var currentli = preEIUCursorLi;
		var newHtml = $(html);
		if (details['layout'] == 'center') {
			var lastdiv = $('div.clearall', currentli).last();
			if (lastdiv.length) {
				lastdiv.before(newHtml);
			} else {
				currentli.append(newHtml);
			}
		} else {
			var thisStep = $('div.step_num', currentli).first();
			thisStep.after(newHtml);
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

		var currentLi = getCursorLi();
		var existingList = $('ul', currentLi).first();

		var newItem = $('<li></li>');
		var newList = $('<ul></ul>');
		newList.append(newItem);
		if (existingList.length) {
			var node = getCursorNode();
			var li, parentLi = $(node).parentsUntil('ul').last();
			if (parentLi.length) {
				li = parentLi[0];
			} else {
				li = node;
			}
			// are we already inside a list?
			if ($(li).is('li')) {
				// if so, add bullet point to that list
				$(li).append(newList);
			} else {
				// if not, append to top level bullets
				existingList.append(newItem);
			}
		} else {
			$(currentLi).append(newList);
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
				.removeAttr('disabled');
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
			.attr('disabled', 'disabled');
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

		var tmpl = '<button id="h5e-new-ref" class="edit-reference">$1</button> ';
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

            $('#ref-edit-remove')
                .unbind('click')
                .click(function() {
                    setPageDirty();
                    $(button).remove();
                    $('#edit-ref-dialog').dialog('close');
                });

			$('#ref-edit-cancel')
				.unbind('click')
				.click(function() {
					$('#edit-ref-dialog').dialog('close');
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
					} else {
						var title = wfMsg('h5e-edit-reference');
					}
					$('#edit-ref-dialog').dialog('option', 'title', title);

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
		});
	}

	/**
	 * Toolbar button to show Related wikiHows dialog.
	 */
	function tbRelatedWikihows() {
		var related = [];
		$('.h5e-related-sortable li').remove();
		$('#relatedwikihows ul li a').each(function(i, link) {
			var title = $(link).attr('title');
			related.push(title);
		});
		$(related).each(function(i,title) {
			var node = createRelatedWikihowSortableNode(title);
			$('.h5e-related-sortable').append(node);
		});
		$('.h5e-related-sortable')
			.sortable()
			.disableSelection();
		$('#related-wh-dialog').dialog({
			width: 600,
			modal: true,
			close: function(evt, ui) { 
				focusCurrentSection();
			}
		});
	}

	/**
	 * In the Related wikiHows dialog, we use a jQuery UI sortable element
	 * to be able to add new related wikihows.  This method creates a list
	 * item for that sortable list.
	 */
	function createRelatedWikihowSortableNode(title) {
		var tmpl = '<li class="ui-state-default"><span class="move-icon ui-icon ui-icon-arrowthick-2-n-s"></span><span class="related-wh-title">$1</span><div class="trash-icon"><button>trashicon</button></div></li>';
		var howto = wfMsg('howto', title);
		var shortened = getArticleDisplay(howto, 30);
		var node = $( wfTemplate(tmpl, shortened) );
		$('button', node).click(function() {
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
	 * @private
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
	 * @private
	 */
	var removedSections = {};

	/**
	 * Inserts the new section into the HTML, deletes removed ones
	 * @private
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
	 * @private
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
	 * @private
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
	 * @private
	 */
	function addAlternateMethod(name) {
		var methods = $('#steps h3');
		var escName = name.replace(/ /g, '_');
		var html = $('<p><a name="' + escName + '" id="' + escName + '"></a></p><h3><span>' + name + '</span></h3><ol class="steps_list_2"><li class="steps_li final_li"><div contenteditable="false" class="step_num">1</div><b class="whb">' + wfMsg('h5e-new-method') + '</b><div class="clearall"></div></li></ol>');
		$('#steps').append(html);
	}

	/**
	 * Call to the server to save the html we've edited.  Call the method
	 * onFinish after save is complete.
	 */
	function saveArticle(onFinish) {
		var contents = $('#bodycontents');
		var data = contents.html();
		contents.load('/Special:Html5editor',
			{ action: 'publish-html',
			  target: wgPageName, 
			  html: data },
			function () {
				if (onFinish) onFinish();
			}
		);

		return false;
	}

	/**
	 * Method called when we enter HTML5 editing mode.
	 *
	 * @private
	 */
	function startEditing(section, postEditFunc) {

		// make all of the editable sections contenteditable=true, hide 
		// templates, and set up key handler for the steps function
		$('.editable').attr('contenteditable', true);

		$('img').attr('contenteditable', false);
		$('#video').attr('contenteditable', false);
		$('#st_wrap').fadeOut();
		$('.template').fadeOut();
		$('.wh_ad').fadeOut();
		$('#toc').fadeOut();
		$("h2").each (function(i, tag) {
				if ($(tag).html() == "Contents") $(tag).fadeOut();
			}
		);

		$('.editsectionbutton').fadeOut();
		$('.edit_article_button').fadeOut();

		$('.h5e-tb-save-wrapper').css('display', 'none');
		$('.h5e-tb-function-wrapper').css('display', 'block');

		// TODO: Bebeth, instead of slideDown() here I should be using 
		// animate(). The problem with slideDown is the button squish as 
		// they're sliding in.
		$('#h5e-editing-toolbar').slideDown();
		$('#relatedwikihows').attr('contenteditable', false);

		$('#steps').keypress(onKeystroke);

		//var node = getEditSectionLink();
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

		// Add a new related wikihows section if it didn't exist before
		if (!$('#relatedwikihows').length) {
			addOrRemoveSections(['relatedwikihows'], []);
		}

		// replace all references with an "edit reference" link
		$('sup.reference').each(function(i, refnode) {
			var reftext = getOrigRefText(refnode);
			var newref = $('<button class="edit-reference">' + wfMsg('h5e-ref') + '</button>');
			$(refnode).replaceWith(newref);
			prepEditRefNode(newref, reftext);
		});

		var msg = wfMsg('h5e-references-removed');
		var html = $('<div class="sources-removed" contenteditable="false">' + msg + '</div>');
		$('ol.references').replaceWith(html);

		// Add an overlay to the related wikihows section to make editing
		// of that section clearer
		var reldiv = $('#relatedwikihows');
        var h = reldiv.outerHeight();
        var w = reldiv.outerWidth();
        var offset = reldiv.offset();
		$('.related-wh-overlay')
            .css('display', 'block')
            .height(h)
            .width(w)
            .offset(offset);
		offset['left'] += w - 150;
		// note: to vertically center this button, uncomment this
		//offset['top'] += h/2 - $('.related-wh-overlay-edit').height()/2 - 30;
		/// change the text to "Add" if there are no related wikihows
		if ($('#relatedwikihows li').size() == 0) {
			$('#related-wh-button').html(wfMsg('h5e-add'));
		}

		$('.related-wh-overlay-edit')
			.css('display', 'block')
			.offset(offset);
		
		// just in case the user navigates away from the page
		setPageClean();

		// in case we are doing manual revert
		if(window.location.search.indexOf("oldid") >= 0) 
			setPageDirty();

		if (section != 'relatedwikihows') {
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
	 *
	 * @private
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

		$('#bodycontents')
			.attr('contenteditable', 'false');
			//.unbind('keypress');
			//.attr('onKeyPress', '');
		$('#steps').unbind('keypress');
		$('.editable').attr('contenteditable', false);

		$('.template').fadeIn();
		$('#st_wrap').fadeIn();
		$('.wh_ad').fadeIn();
		$('.editsectionbutton').fadeIn();
		$('.edit_article_button').fadeIn();

		// remove the opaque cover over top of the related wh section
		$('.related-wh-overlay')
			.add('.related-wh-overlay-edit')
			.hide();

		// remove related wh section if there aren't any related wikihows
		if (!$('#relatedwikihows ul li a').length) {
			addOrRemoveSections([], ['relatedwikihows']);
		}

		removeImageHoverListeners();

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

			$('.h5e-tb-function-wrapper').fadeOut('fast', function() {
				$('.h5e-tb-save-wrapper').fadeIn();
			});
		}

	}

	/**
	 * Get DOM node where the cursor currently lies in the html5-edited 
	 * document.
	 *
	 * @private
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

		$('#h5e-toolbar-a').click(tbAddLinkToSection);
		$('#h5e-toolbar-img').click(tbAddImageToSection);
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

		$('#h5e-edit-description').blur(function() {
			if ($('#h5e-edit-description').val() == '') {
				$('#h5e-edit-description')
					.val( wfMsg('h5e-edit-description-examples') )
					.css('color', '#999');
			}
		});
		$('#h5e-edit-description').focus(function() {
			if ($('#h5e-edit-description').val() == wfMsg('h5e-edit-description-examples')) {
				$('#h5e-edit-description')
					.css('color', '#222')
					.val('');
			}
		});
		$('#h5e-edit-description').blur();

		var postSaveSummaryFunc = function() {
			$('#h5e-editing-toolbar').slideUp('fast', function() {
				$('.h5e-tb-save-wrapper').css('display', 'none');
				$('.h5e-tb-function-wrapper').css('display', 'block');
				$('#h5e-edit-description').val('');
			});
		};

		$('#h5e-edit-description-save').click(function() {
			var editSummary = $('#h5e-edit-description').val();
			if (editSummary != wfMsg('h5e-edit-description-examples') &&
				$.trim(editSummary) != '')
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
		$('#h5e-edit-description').keypress(function(evt) {
			if (evt.which == 13) { // 'Enter' pressed
				$('#h5e-edit-description-save').click();
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
			.css('background-color', 'white')
			.css('opacity', '0.6')
			.css('display', 'block')
			.height(h)
			.width(w)
			.offset(offset)
			.mouseleave(imageHoverOut);

		// Bind to the "REMOVE IMAGE" link or icon in the opaque div
		$('#h5e-mwimg-mouseover a')
			.css('color', 'black')
			.css('opacity', '1.0')
			.css('font-size', '24px')
			.click(removeImage);
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

	/**
	 * Add all the listeners to buttons in our editing dialogs.
	 */
	function dialogClickListeners() {
		var editLink = $('.h5e-edit-link-options-over');
		var linkPreview = $('#h5e-link-preview');
		var currentLinkNode;

		// Show change link bar when key or mouse onto a link
		var oldAnchorEditLink = '';
		editLink.hide();
		$('#bodycontents').keypress(function(e) {
			setPageDirty();
		});
		$('#bodycontents').bind('keypress click', function(e) {
			currentLinkNode = $(getCursorNode());
			var startNode = currentLinkNode;
			var newShowLink = startNode.is('a') ? startNode.attr('href') : '';

			// check to see if oldAnchorEditLink has changed, modify the
			// hrefs and css only if it has
			if (newShowLink !== oldAnchorEditLink) {
				oldAnchorEditLink = newShowLink;
				if (newShowLink) {
					editLink
						.css({
							top: startNode.offset().top - editLink.height() - 5,
							left: startNode.offset().left
						})
						.show()
						.data('node', startNode);
					var href = startNode.attr('href');
					var article = getArticleFromLink(href);
					var linkDisplay = $('#h5e-editlink-display');
					linkDisplay.text( getArticleDisplay(article) );
					linkDisplay.attr('title', article);
					linkDisplay.attr('target', '_blank');
					linkDisplay.attr('href', href);
					var innerWidth = $('.h5e-edit-link-inner').width();
					editLink.width(innerWidth + 5);
				} else {
					editLink.fadeOut('fast');
				}
			}
		});

		// Edit link pop-in, called when a user changes a link in the text 
		// that's selected
		$('#h5e-editlink-change').click(function() {
			var pos = saveCursorPos();
			setPageDirty();

			oldAnchorEditLink = '';
			editLink.hide();

			$('#h5e-link-dialog').dialog({
				width: 400,
				minWidth: 400,
				modal: true,
				close: function(evt, ui) { 
					focusCurrentSection(pos['node']);
					loadCursorPos(pos);
				}
			});
			$('#h5e-link-text').val(currentLinkNode.text());
			var href = currentLinkNode.attr('href');
			var article = getArticleFromLink(href);
			$('#h5e-link-article').val(article);
			linkPreview.attr('href', href);
			$('#h5e-link-query').val(currentLinkNode.text());
			$('#h5e-link-query').focus();
			$('#h5e-link-search-button').click();
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

		// searchHandler() is a callback to show search results for
		// the link change/add dialog
		var searchPage;
		var searchHandler = function() {
			var MAX_RESULTS = 5;
			var offset = searchPage * MAX_RESULTS;
			var query = $('#h5e-link-query').val();
			var url = '/api.php?action=query&list=search&srsearch=' + encodeURIComponent(query) + '&sroffset=' + offset + '&srlimit=' + MAX_RESULTS + '&format=json';

			$('#h5e-link-query-results').html('<i><span style="color: #aaaaaa;">' + wfMsg('h5e-loading') + '</span></i>');
			$.get(url, function(results) {
				var html = '';
				if (results['query'].search.length) {
					$(results['query'].search).each( function(i, result) {
						var title = result['title'];
						var link = getLinkFromArticle(title);
						html += '<a href="#" class="h5e-search-result">' + title + '</a><br/>';
					});
					if (results['query'].search.length == MAX_RESULTS) {
						html += '<br/>';
						html += '<a href="#" id="h5e-search-more">' + wfMsg('h5e-more-results') + '</a><br/>';
					}
				} else {
					html = 'no results';
				}

				$('#h5e-link-query-results').html(html);
				$('.h5e-search-result').click(function() {
					var text = $(this).text();
					$('#h5e-link-article').val(text);
					currentLinkNode.attr('title', text);
					currentLinkNode.attr('href', getLinkFromArticle(text));
					$('#h5e-link-dialog').dialog('close');
					return false;
				});
				$('#h5e-search-more').click(function() {
					searchPage++;
					searchHandler();
				});
			});
			return false;
		};

		// Change link dialog, when the user clicks the Search button
		$('#h5e-link-search-button').click(function() {
			searchPage = 0;
			return searchHandler();
		});

		// Change link dialog, when the user hits Enter at the query input field
		$('#h5e-link-query').keypress(function(evt) {
			if (evt.which == 13) { // 'Enter' key pressed
				$('#h5e-link-search-button').click();
				return false;
			}
		});

		// Change link dialog, when the user edits the text, this callback 
		// lets you see the changes in the dialog immediately
		$('#h5e-link-text').bind('keyup change', function() {
			var startNode = editLink.data('node');
			startNode.text($(this).val());
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

		// Edit related wikihows dialog
		$('#h5e-related-done').click(function() {
			setPageDirty();

			// save links to related wikihows section after dialog Done
			$('#relatedwikihows ul li').remove();
			var ul = $('#relatedwikihows ul');
			$('.h5e-related-sortable li').each(function(i,li) {
				var title = $(li).data('title');
				var howto = wfMsg('howto', title);
				var href = getLinkFromArticle(title);
				var tmpl = '<li><a title="$1" href="$2">$3</a></li>';
				var html = wfTemplate(tmpl, title, href, howto);
				ul.append( $(html) );
			});
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

		// google-style auto-complete
		InstallAC(document['h5e-ac'],document['h5e-ac']['h5e-related-new'],document['h5e-ac']['h5e-related-add'],"./Special:TitleSearch?lim=10","en");
		// customize ac results
		$('#completeDiv')
			.css('z-index', '10000')
			.css('border', '1px solid #aaaaaa');

		$('.related-wh-overlay-edit button').click(function() {
			tbRelatedWikihows();
			return false;
		});
	}

	/**
	 * Add click handlers to the DOM relating to HTML5 editing.
	 * @private
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

	function isHtml5EditingCompatible() {
		// We determine this by browser rather than by feature because we use
		// so many features that this is easier.
		var webkit = typeof $.browser['webkit'] != 'undefined' && $.browser['webkit'] && parseInt($.browser['version'], 10) >= 500; // Safari or Chrome
		var firefox = typeof $.browser['mozilla'] != 'undefined' && $.browser['mozilla'] && $.browser['version'].substr(0,3) == '1.9'; // FF3
		return webkit || firefox;
	}

	return {

		/**
		 * Initialize the html5 editor.  Called when page is loaded.  
		 * startEditing() is the method called when editing actually starts.
		 *
		 * @public
		 */
		init: function() {
			if (isHtml5EditingCompatible() && wgNamespaceNumber == 0) {
				makeStepNumbersNonEditable();

				attachClickListeners();

				// temporary: if e=true param is in url, throw us into edit 
				// right away, for speedy testing
				if (document.location.href.indexOf('e=true') >= 0) {
					startEditing('intro');
				}
			} else {
				// insert an ad for firefox or chrome here
			}
		},

		/**
		 * Callback is called by the image upload dialog finishes.
		 *
		 * @public
		 */
		postImageCallback: function(details) {
			var html = generateImageHtml(details);
			insertImageHtml(details, html);
		}

	};

})(jQuery); // exec anonymous function and return resulting class

// HTML5 init is run on the DOM ready event
jQuery(WH.h5e.init);

