<?php
/**
 * Kunena Component
 * @package Kunena.Site
 * @subpackage Models
 *
 * @copyright (C) 2008 - 2013 Kunena Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.kunena.org
 **/
defined ( '_JEXEC' ) or die ();

// Load Elastica library
jimport('elastica.autoload');

// Load helpers
require_once(JPATH_SITE.'/components/com_elasticsearch/helpers/elasticsearch.php');

/**
 * Search Model for Kunena
 *
 * @since		2.0
 */
class KunenaModelElasticsearch extends KunenaModel {
	protected $error = null;
	protected $total = false;
	protected $data = null;
	protected $filters = false;

	protected function populateState() {
		// Get search word list
		$value = JString::trim ( JRequest::getString ( 'q', '' ) );

		if ($value == JText::_('COM_KUNENA_GEN_SEARCH_BOX')) {
			$value = '';
		}
		$this->setState ( 'searchwords', $value );

		$value = JRequest::getInt ( 'searchtype', 0 );
		$this->setState ( 'query.searchtype', $value );

		$value = JRequest::getString ( 'searchuser', '' );
		$this->setState ( 'query.searchuser', $value );

		$value = JRequest::getInt ( 'starteronly', 0 );
		$this->setState ( 'query.starteronly', $value );

		$value = JRequest::getInt ( 'exactname', 0 );
		$this->setState ( 'query.exactname', $value );

		$value = JRequest::getInt ( 'replyless', 0 );
		$this->setState ( 'query.replyless', $value );

		$value = JRequest::getInt ( 'replylimit', 0 );
		$this->setState ( 'query.replylimit', $value );

		$value = JRequest::getString ( 'searchdate', 0 );
		$this->setState ( 'query.searchdate', $value );

		$value = JRequest::getWord ( 'beforeafter', 'after' );
		$this->setState ( 'query.beforeafter', $value );

		$value = JRequest::getWord ( 'sortby', 0 );
		$this->setState ( 'query.sortby', $value );

		$value = JRequest::getWord ( 'order', 'dec' );
		$this->setState ( 'query.order', $value );

		$value = JRequest::getInt ( 'childforums', 1 );
		$this->setState ( 'query.childforums', $value );

		$value = JRequest::getInt ( 'topic_id', 0 );
		$this->setState ( 'query.topic_id', $value );

		if (isset ( $_POST ['q'] ) || isset ( $_POST ['searchword'] )) {
			$value = JRequest::getVar ( 'catids', array (0), 'post', 'array' );
			JArrayHelper::toInteger($value);
		} else {
			$value = JRequest::getString ( 'catids', '0', 'get' );
			$value = explode ( ' ', $value );
			JArrayHelper::toInteger($value);
		}
		$this->setState ( 'query.catids', $value );

		// if (isset ( $_POST ['q'] ) || isset ( $_POST ['searchword'] )) {
		// 	$value = JRequest::getVar ( 'ids', array (0), 'post', 'array' );
		// 	JArrayHelper::toInteger($value);
		// } else {
		// 	$value = JRequest::getString ( 'ids', '0', 'get' );
		// 	$value = explode ( ' ', $value );
		// 	JArrayHelper::toInteger($value);
		// }
		// $this->setState ('query.ids', $value );

		$value = JRequest::getInt ( 'show', 0 );
		$this->setState ( 'query.show', $value );

		$value = $this->getInt ( 'limitstart', 0 );
		if ($value < 0) $value = 0;
		$this->setState ( 'list.start', $value );

		$value = $this->getInt ( 'limit', 0 );
		if ($value < 1 || $value > 100) $value = $this->config->messages_per_page_search;
		$this->setState ( 'list.limit', $value );
	}

	public function getSearchwords() {
		return $this->getState('searchwords');
	}

	public function flatten(array $array) {
		$return = array();
		array_walk_recursive($array, function($a) use (&$return) { $return[] = $a; });
		return $return;
	}

	public function getTotal() {
		$this->getResults();
		return $this->total;
	}

	public function getResults() {

		// Don't process if already set
		if ($this->data) return $this->data;

		// Don't process if not needed
		if (!($this->getState('searchwords') || $this->getState('query.searchuser'))) {
			$this->total = -1;
			$this->data = new stdClass();
			$this->data->count = 0;
			$this->data->hits = 0;
			return $this->data;
		}

		$search = ElasticsearchHelper::getSearch();

		// setup some paging information
		$limit = $this->getState('list.limit');
		$from = $this->getState('list.start');



		//$query_dsl = new Elastica\QueryBuilder\DSL\Filter();
		//$this->filters = $query_dsl->bool_and();

		$query_dsl = [
			'body' => [
				'query' => [
					'bool' => [
					],
				],
			],
		];

		$q = strip_tags($this->getState('searchwords'));

		// Keyword searching
		if ($q) {
			// $query = new Elastica\Query\FunctionScore();

			//$query->setScoreMode('sum');

			$dateScale = '365d';
			$dateOffset = '15d';
			$dateDecay = '0.1';

			/* $query->setParam('score_mode', 'sum');
			$query->setParam('boost_mode', 'sum');
			$query->setParam('max_boost', '5');

			// Add boosting algorithms
			$query->setParam('functions', [
				[
					'boost_factor' => 2,
					'filter' => [
						'term' => [
							'parent' => 0
						]
					]
				],
				[
					'boost_factor' => 2,
					'linear' => [
						'created' => [
							'scale' => $dateScale,
							'offset' => $dateOffset,
							'decay' => $dateDecay
						]
					]
				],
				[
					'boost_factor' => 2,
					'script_score' => [
						'script' => "_score * doc['thankyous'].value / 2"
					]
				]
			]); */

			$query = [
					"query" => [
						"function_score" => [
							'functions' => [
								[
									'weight' => 2,
									'filter' => [
										'term' => [
											'parent' => 0
										]
									]
								],
								[
									'weight' => 2,
									'linear' => [
										'created' => [
											'scale' => $dateScale,
											'offset' => $dateOffset,
											'decay' => $dateDecay
										]
									]
								],
								[
									'weight' => 2,
									'script_score' => [
										'script' => "_score * doc['thankyous'].value / 2"
									]
								]
							],
							'score_mode' => 'sum',
							'boost_mode' => 'sum',
							'max_boost' => '5',
				
						],
					],
			];

			//$childQuery = new Elastica\Query\QueryString();
			//$childQuery->setQuery($q);

			$childQuery = [
				'query' => [
					'query_string' => [
						'query' => $q,
					],
				],
			];

			$searchtype = $this->getState('query.searchtype');

			switch ($searchtype) {
				case 1:		// Titles only
					$childQuery['query']['query_string']['fields'] = array('subject');
					break;
				case 2:		// Messages only
					$childQuery['query']['query_string']['fields'] = array('message');
					break;
				case 3:		// First topic only
					//$topicFilter = $query_dsl->bool_term();
					//$topicFilter->setTerm('parent',0);
					//$this->filters->addFilter($topicFilter);
					$query_dsl['query']['bool']['filter'][] = [
						'term' => ['parent' => 0]
					];
				// $childQuery->setFields(array('subject','message'));
					$childQuery['query']['query_string']['fields'] = array('subject','message');
				
					break;
				default:	// Title + Message
					//$childQuery->setFields(array('subject','message'));
					$childQuery['query']['query_string']['fields'] = array('subject','message');
					break;
			}

			$query['query']['function_score']['query'] = $childQuery['query'];

		} else {
			$query = [
				'query' => [
					'match_all' => (object)[],
				],];
		}

		// put it all together
		// $queryObj = new Elastica\Query($query);
		//$queryObj->setSize($limit)->setFrom($from);
		// $queryObj->setPostFilter($this->getFilters());
		//$queryObj->setparam('track_scores', true);
		//$queryObj->setSort($this->getSortOrder());
		/* 		$queryObj->setHighlight(array(
			'pre_tags' => array('<em class="highlight">'),
			'post_tags' => array('</em>'),
			'require_field_match' => false,
			'fields' => array(
				'subject' => array(
					'number_of_fragments' => 0,
				),
				'message' => array(
					'fragment_size' => 300,
					'number_of_fragments' => 1,
				)
			),
		)); */
		//$suggest = new Elastica\Suggest();
		//$term1 = new Elastica\Suggest\Term('simple_phrase', 'content');
		//$term1->setText($q)->setSize(3);
		//$suggest->addSuggestion($term1);
		//$queryObj->setSuggest($suggest);
		//        $queryObj->setParam('suggest', array(
		//            'text' => $q,
		//            "simple_phrase" => array(
		//                "phrase" => array(
		//                    "field" => "subject",
		//                    "size" => 5,
		//                    "real_word_error_likelihood" => 0.95,
		//                    "confidence" => 2.0,
		//                    "max_errors" => 0.5,
		//                    "gram_size" => 3
		//                )
		//            )
		//        ));

		$queryObj = [
			'index' => 'kunena',
			'body' => [
				'query' => $query['query'],
				'highlight' => [
					'pre_tags' => array('<em class="highlight">'),
					'post_tags' => array('</em>'),
					'require_field_match' => false,
					'fields' => array(
						'subject' => array(
							'number_of_fragments' => 0,
						),
						'message' => array(
							'fragment_size' => 300,
							'number_of_fragments' => 1,
						)
					),
				],
				'suggest' => [
					'simple_phrase' => [
						'text' => $q,
					
						'term' => [
							'field' => 'subject',
							'size' => 5,
						]
					],
				],
				"post_filter" => $this->getFilters(),
				'track_scores' => true,
				'sort' => $this->getSortOrder(),
			],
			'from' => $from,
			'size' => $limit,
		];
		
		// $search->addIndex('kunena');

		try {
			if (ElasticsearchHelper::getDebuggable()) {
				JLog::add('Forum Query: '.json_encode($queryObj), JLog::INFO,'elasticsearch');
			}
			$resultSet = $search->search($queryObj);
		} catch (Exception $e) {
			JError::raiseWarning(500, $e->getMessage());
			throw new JException("Search Engine Failure",503);
		}

		// Load the messages
		$msg_ids = array();
		foreach($resultSet['hits']['hits'] as $result) {
			$msg_ids[] = $result['_source']['msgid'];
		}
		$this->messages = KunenaForumMessageHelper::getMessages($msg_ids);

		// Load some schnizzle
		$topicids = array();
		$userids = array();
		foreach ($this->messages as $message) {
			$topicids[$message->thread] = $message->thread;
			$userids[$message->userid] = $message->userid;
		}
		if ($topicids) {
			$topics = KunenaForumTopicHelper::getTopics($topicids);
			foreach ($topics as $topic) {
				$userids[$topic->first_post_userid] = $topic->first_post_userid;
			}
		}
		KunenaUserHelper::loadUsers($userids);
		KunenaForumMessageHelper::loadLocation($this->messages);

		$data = new JObject;
		$data->total = $resultSet['hits']['total']['value'];
		$data->hits = $resultSet['hits']['total']['value'] > ES_MAX_RESULTS ? ES_MAX_RESULTS : $resultSet['hits']['total']['value'];
		$data->page = ceil(($from+1) / $limit);
		$data->pages = intval(ceil($data->hits / $limit));
		$data->from = $from;
		$data->size = $limit;
		$data->query = $q;
		$data->count = count($resultSet['hits']['hits']);
		$data->time = 0.001 * $resultSet['took'];
		//$data->suggestions = $this->getSuggestions($resultSet);
		$data->results = $resultSet;
		$data->messages = $this->messages;

		$this->data = $data;
		$this->total = $data->hits;

		return $data;
	}

	protected function getSortOrder() {

		$sortorder = array();
		$sortby = $this->getState('query.sortby');

		if ($this->getState('query.order') == 'dec')
			$ordering = 'desc';
		else
			$ordering = 'asc';

		switch ($this->getState('query.sortby')) {
			case 'lastpost' :
				$sortorder = array(
					'created' => array('order' => $ordering )
				);
				break;
			case 'title' :
				$sortorder = array(
					'subject' => array('order' => $ordering )
				);
				break;
			case 'views' :
				$sortorder = array(
					'hits' => array('order' => $ordering )
				);
				break;
			case 'postusername' :
				$sortorder = array(
					'name' => array('order' => $ordering )
				);
				break;
			case 'forum' :
				$sortorder = array(
					'catid' => array('order' => $ordering )
				);
				break;
			default :
				$sortorder = array(
					'_score' => array('order' => $ordering ),
					'created' => array('order' => $ordering )
				);
		}

		return $sortorder;
	}

	protected function getFilters() {
		// $query_dsl = new Elastica\QueryBuilder\DSL\Filter();
		// Categories filter
		$allowedCategories = KunenaUserHelper::getMyself()->getAllowedCategories();
		$categories = $this->getState('query.catids');
		$childforums = $this->getState('query.childforums');
		if (is_array($categories) && in_array(0, $categories)) {
			$categories = false;
		}
		if ($categories) {
			if ($childforums) {
				$childcats = KunenaForumCategoryHelper::getChildren($categories, -1, array('action'=>'topic.read'));
				foreach ($childcats as $child) {
					$categories[] = $child->id;
				}
			}
			$allowedCategories = array_intersect($allowedCategories, $categories);
		}

		// Topics filter
		$topic = $this->getState('query.topic_id',null);
		if ($topic) {
			//$topicFilter = $query_dsl->bool_term();
			//$topicFilter->setTerm('thread',$topic);

			$query_dsl['query']['bool']['filter'][] = [
				'term' => ['thread' => $topic]
			];
		}

		// Access Filters
		//$accessFilter = $query_dsl->bool_terms();
		//$accessFilter->setTerms('catid', array_map('intval',$allowedCategories));

		$query_dsl['query']['bool']['filter'][] = [
			'terms' => ['catid' => $this->flatten($allowedCategories)],
		];


		// Date filters
		if ($this->getState('query.beforeafter') == 'before') {
			$prefix = 'to';
		} else {
			$prefix = 'from';
		}
		$dateQuery = $this->getState('query.searchdate');
		if ($dateQuery) {
			if ($dateQuery == 'lastvisit') {
				$time = 'now-'.intval((time() - KunenaFactory::GetSession()->lasttime) / 60).'m';
			} else {
				$time = 'now'.$dateQuery;
			}
			//$dateFilter = $query_dsl->range();
			//$dateFilter->addField('created', array($prefix => $time));

			$query_dsl['query']['bool']['filter'][] = [
				'range' => ['created' => array($prefix => $time)]
			];
		}

		// Username filter
		$username = strtolower($this->getState('query.searchuser'));
		if ($username) {
			//$userFilter = $query_dsl->bool_or();
			$username_terms = explode(' ', $username);
			if (count($username_terms) > 1) {
				//$f1 = $query_dsl->terms();
				//$f2 = $query_dsl->terms();
				// $f1->setTerms('name',$username_terms);
				$query_dsl['query']['bool']['filter'][] = [
					'terms' => ['name' => $username_terms]
				];

				//$f2->setTerms('username',$username_terms);
				$query_dsl['query']['bool']['filter'][] = [
					'terms' => ['username' => $username_terms]
				];

				//$f1->setParam('execution','and');
				//$f2->setParam('execution','and');
			} else {
				//$f1 = $query_dsl->term();
				//$f2 = $query_dsl->term();
				//$f1->setTerm('name', $username_terms[0]);
				$query_dsl['query']['bool']['filter'][] = [
					'term' => ['name' => $username_terms[0]]
				];
				//$f2->setTerm('username',$username_terms[0]);
				$query_dsl['query']['bool']['filter'][] = [
					'term' => ['username' => $username_terms[0]]
				];
			}
			//$userFilter->addFilter($f1);
			//$userFilter->addFilter($f2);
		}

		// Published filter check
		//$publishedFilter = $query_dsl->term();
		//$publishedFilter->setTerm('hold',0);
		$query_dsl['query']['bool']['filter'][] = [
			'term' => ['hold' => 0]
		];



		// Put all the filters together
		/* $this->filters->addFilter($publishedFilter);
		$this->filters->addFilter($accessFilter);
		if ($dateQuery) {
			$this->filters->addFilter($dateFilter);
		}
		if ($username) {
			$this->filters->addFilter($userFilter);
		}
		if ($topic) {
			$this->filters->addFilter($topicFilter);
		} */
		return $query_dsl['query'];
	}


	protected function sanitizeQuery($query) {
		return Elastica\Util::replaceBooleanWordsAndEscapeTerm($query);
	}

	public function getUrlParams() {
		// Turn internal state into URL, but ignore default values
		$defaults = array ('searchtype' => 0, 'searchuser' => '', 'exactname' => 0, 'childforums' => 0, 'starteronly' => 0,
			'replyless' => 0, 'replylimit' => 0, 'searchdate' => '', 'beforeafter' => 'after', 'sortby' => '',
			'order' => 'dec', 'catids' => '0', 'show' => '0', 'topic_id' => '0');

		$url_params = '';
		$state = $this->getState();
		foreach ( $state as $param => $value ) {
			$paramparts = explode('.', $param);
			if ($paramparts[0] != 'query') continue;
			$param = $paramparts[1];

			if ($param == 'catids' || $param == 'ids')
				$value = implode ( ' ', $value );
			if ($value != $defaults [$param])
				$url_params .= "&$param=" . urlencode ( $value );
		}
		return $url_params;
	}

	public function getSearchURL($view, $searchword='', $limitstart=0, $limit=0, $params = '', $xhtml=true) {
		$config = KunenaFactory::getConfig ();
		$limitstr = "";
		if ($limitstart > 0)
			$limitstr .= "&limitstart=$limitstart";
		if ($limit > 0 && $limit != $config->messages_per_page_search)
			$limitstr .= "&limit=$limit";
		if ($searchword) {
			$searchword = str_replace(array('"','*','?',')','(','[',']','&','@'), '', $searchword);
			$searchword = '&q=' . urlencode ( $searchword );
		}
		return KunenaRoute::_ ( "index.php?option=com_kunena&view={$view}{$searchword}{$params}{$limitstr}", $xhtml );
	}
}
