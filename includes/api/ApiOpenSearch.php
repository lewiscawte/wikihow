<?php

/*
 * Created on Oct 13, 2006
 *
 * API for MediaWiki 1.8+
 *
 * Copyright (C) 2006 Yuri Astrakhan <Firstname><Lastname>@gmail.com
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

if (!defined('MEDIAWIKI')) {
	// Eclipse helper - will be ignored in production
	require_once ("ApiBase.php");
}

/**
 * @addtogroup API
 */
class ApiOpenSearch extends ApiBase {

	public function __construct($main, $action) {
		parent :: __construct($main, $action);
	}

	public function getCustomPrinter() {
		return $this->getMain()->createPrinterByName('json');
	}

	public function execute() {
		$params = $this->extractRequestParams();
		$search = $params['search'];
		$limit = $params['limit'];

		// Open search results may be stored for a very long time
		$this->getMain()->setCacheMaxAge(1200);
		
		$srchres = PrefixSearch::titleSearch( $search, $limit );

		// Set top level elements
		$result = $this->getResult();
		$result->addValue(null, 0, $search);
		$result->addValue(null, 1, $srchres);
	}

	public function getAllowedParams() {
		return array (
			'search' => null,
			'limit' => array (
				ApiBase :: PARAM_DFLT => 10,
				ApiBase :: PARAM_TYPE => 'limit',
				ApiBase :: PARAM_MIN => 1,
				ApiBase :: PARAM_MAX => 100,
				ApiBase :: PARAM_MAX2 => 100
			)
		);
	}

	public function getParamDescription() {
		return array (
			'search' => 'Search string',
			'limit' => 'Maximum amount of results to return'
		);
	}

	public function getDescription() {
		return 'This module implements OpenSearch protocol';
	}

	protected function getExamples() {
		return array (
			'api.php?action=opensearch&search=Te'
		);
	}

	public function getVersion() {
		return __CLASS__ . ': $Id: ApiOpenSearch.php,v 1.1.1.1 2009/11/04 00:04:42 vu Exp $';
	}
}

