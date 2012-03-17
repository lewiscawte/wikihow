		<div class="search_static"></div>
        <div class="search">
			<!--
			<form>
				<input type="text" name="search" class="search_box"/>
				<input type="submit" class="search_button" value="HOW"  />
			</form>
			-->
			<? // below is the modified output of: GoogSearch::getSearchBox("cse-search-box") ?>
			<? //<form action="/Special:GoogSearch" id="cse-search-box"> ?>
			<form action="http://www.google.com/cse/m" id="cse-search-box">
				<div>
					<input type="hidden" name="cx" value="008953293426798287586:mr-gwotjmbs" />
					<!--<input type="hidden" name="cof" value="FORID:10" />-->
					<input type="hidden" name="ie" value="UTF-8" />
					<input type="text" id="cse_q" name="q" value="" class="search_box" />
					<input type="submit" id="cse_sa" value="HOW" class="search_button" />
				</div>
			</form>
		</div><!--end search-->
