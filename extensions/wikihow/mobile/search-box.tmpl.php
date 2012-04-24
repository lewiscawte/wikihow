        <div class="search">
			<!--
			<form>
				<input type="text" name="search" class="search_box"/>
				<input type="submit" class="search_button" value="HOW"  />
			</form>
			-->
			<? // below is the modified output of: GoogSearch::getSearchBox("cse-search-box") ?>
			<? //<form action="/Special:GoogSearch" id="cse-search-box"> ?>
			<form action="http://www.google.<?=wfMsg('cse_domain_suffix')?>/cse/m" id="cse-search-box">
				<div>
					<input type="hidden" name="cx" value="<?=wfMsg('cse_cx')?>" />
					<!--<input type="hidden" name="cof" value="FORID:10" />-->
					<input type="hidden" name="ie" value="UTF-8" />
					<input type="text" id="cse_q" name="q" value="" class="search_box" />
					<input type="submit" id="cse_sa" value="<?=wfMsg('m_search_button_text')?>" class="search_button" />
				</div>
			</form>
		</div><!--end search-->
