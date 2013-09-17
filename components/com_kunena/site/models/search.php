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
class KunenaModelSearch extends KunenaModel {
	protected $error = null;
	protected $total = false;
	protected $messages = false;
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

		$value = JRequest::getInt ( 'show', 0 );
		$this->setState ( 'query.show', $value );

		$value = $this->getInt ( 'limitstart', 0 );
		if ($value < 0) $value = 0;
		$this->setState ( 'list.start', $value );

		$value = $this->getInt ( 'page', 1 );
		if ($value < 1) $value = 1;
		$this->setState ( 'list.page', $value );

		$value = $this->getInt ( 'limit', 0 );
		if ($value < 1 || $value > 100) $value = $this->config->messages_per_page_search;
		$this->setState ( 'list.limit', $value );
	}

	public function getShowResults() {
		if ($this->getState('searchwords') || $this->getState('query.searchuser')) {
			return true;
		} 
		return false;
	}

	public function getSearchResults() {

		$search = ElasticsearchHelper::getSearch();

		// setup some paging information
        $limit = $this->getState('list.limit');
        $page = $this->getState('list.page');
        $from = ($page-1) * $limit;

        $this->filters = new Elastica\Filter\BoolAnd();

        $q = strip_tags($this->getState('searchwords'));

        // Keyword searching
        if ($q) {

    		$query = new Elastica\Query\MultiMatch();
	        $query->setQuery($q);

	        $searchtype = $this->getState('query.searchtype');

	        switch ($searchtype) {
	        	case 1:		// Titles only
	        		$query->setFields(array('subject'));
	        		break;
	        	case 2:		// Messages only
	        		$query->setFields(array('message'));
	        		break;  
	        	case 3:		// First topic only
	        		$topicFilter = new Elastica\Filter\Term();
        			$topicFilter->setTerm('parent',0);
        			$this->filters->addFilter($topicFilter);
        			$query->setFields(array('subject','message'));
	        		break;
	        	default:	// Title + Message
	        		$query->setFields(array('subject','message'));	
	        		break;
	        }
        } else {
        	$query = new Elastica\Query\MatchAll();
        }

        // Add filtering

        // put it all together
        $queryObj = new Elastica\Query($query);
        $queryObj->setSize($limit)->setFrom($from);
        $queryObj->setFilter($this->getFilters());
        $queryObj->setparam('track_scores', true);
        $queryObj->setSort($this->getSortOrder());
        $queryObj->setHighlight(array(
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
        ));
        $queryObj->setParam('suggest', array(
            'text' => $q,
            "simple_phrase" => array(
                "phrase" => array(
                    "field" => "subject",
                    "size" => 5,
                    "real_word_error_likelihood" => 0.95,
                    "confidence" => 2.0,
                    "max_errors" => 0.5,
                    "gram_size" => 3
                )
            )
        ));

        $search->addIndex('kunena');

        try {
            $resultSet = $search->search($queryObj);
        } catch (Exception $e) {
            throw new JException("Search Engine Failure",503);
        }

        // Load the messages
        $msg_ids = array();
       	foreach($resultSet as $result) {
       		$msg_ids[] = $result->msgid;
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
        $data->total = $resultSet->getTotalHits();
        $data->hits = $resultSet->getTotalHits() > ES_MAX_RESULTS ? ES_MAX_RESULTS : $resultSet->getTotalHits();
        $data->page = $page;
        $data->pages = intval(ceil($data->hits / $limit));
        $data->from = $from;
        $data->size = $limit;
        $data->query = $q;
        $data->count = $resultSet->count();
        $data->time = 0.001 * $resultSet->getTotalTime();
        //$data->suggestions = $this->getSuggestions($resultSet);
        $data->results = $resultSet;
        $data->messages = $this->messages;

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

		// Access Filters
		$accessFilter = new Elastica\Filter\Terms();
		$accessFilter->setTerms('catid', array_map('intval',$allowedCategories));


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
			$dateFilter = new Elastica\Filter\Range();
			$dateFilter->addField('created', array($prefix => $time));
		}


		// Username filter
		$username = $this->getState('query.searchuser');
		if ($username) {
			$userFilter = new Elastica\Filter\Term();
        	$userFilter->setTerm('name', $username);	
		}

		// Published filter check
        $publishedFilter = new Elastica\Filter\Term();
        $publishedFilter->setTerm('hold',0);

        

        // Put all the filters together
        $this->filters->addFilter($publishedFilter);
        $this->filters->addFilter($accessFilter);
        if ($dateQuery) {
        	$this->filters->addFilter($dateFilter);	
        }
        if ($username) {
        	$this->filters->addFilter($userFilter);	
        }

        return $this->filters;
	}


	protected function sanitizeQuery($query) {
        return Elastica\Util::replaceBooleanWordsAndEscapeTerm($query);
    }

	protected function setSortOrder(&$query) {
		if ($this->getState('query.order') == 'dec')
			$order1 = 'desc';
		else
			$order1 = 'asc';
		switch ($this->getState('query.sortby')) {
			case 'lastpost' :
				$orderby = "m.time {$order1}";
				break;
			case 'title' :
				$orderby = "m.subject {$order1}, m.time {$order1}";
				break;
			case 'views' :
				$orderby = "m.hits {$order1}, m.time {$order1}";
				break;
			case 'forum' :
				$orderby = "m.catid {$order1}, m.time {$order1}";
				break;
			case 'score' :
			default :
				$orderby = "m.score {$order1}";
		}

		return $orderby;		
	}

	protected function getUsernameFilter() {

		$username = $this->getState('query.searchuser');
		if ($username) {
			if ($this->getState('query.exactname') == '1') {
				$querystrings [] = "m.name LIKE '" . $db->escape ( $username ) . "'";
			} else {
				$querystrings [] = "m.name LIKE '%" . $db->escape ( $username ) . "%'";
			}
		}
	}

	protected function buildWhere() {
		$db = JFactory::getDBO ();
		$querystrings = array();

		foreach ( $this->getSearchWords() as $searchword ) {
			$searchword = $db->escape ( JString::trim ( $searchword ) );
			if (empty ( $searchword ))
				continue;
			$not = '';
			$operator = ' OR ';

			if ( substr($searchword, 0, 1) == '-' && strlen($searchword) > 1 ) {
				$not = 'NOT';
				$operator = 'AND';
				$searchword = JString::substr ( $searchword, 1 );
			}

			if (!$this->getState('query.titleonly')) {
				$querystrings [] = "(t.message {$not} LIKE '%{$searchword}%' {$operator} m.subject {$not} LIKE '%{$searchword}%')";
			} else {
				$querystrings [] = "(m.subject {$not} LIKE '%{$searchword}%')";
			}
		}

		//User searching
		$username = $this->getState('query.searchuser');
		if ($username) {
			if ($this->getState('query.exactname') == '1') {
				$querystrings [] = "m.name LIKE '" . $db->escape ( $username ) . "'";
			} else {
				$querystrings [] = "m.name LIKE '%" . $db->escape ( $username ) . "%'";
			}
		}

		$time = 0;
		switch ($this->getState('query.searchdate')) {
			case 'lastvisit' :
				$time = KunenaFactory::GetSession()->lasttime;
				break;
			case 'all' :
				break;
			case '1' :
			case '7' :
			case '14' :
			case '30' :
			case '90' :
			case '180' :
			case '365' :
				$time = time () - 86400 * intval ( $this->getState('query.searchdate') ); //24*3600
				break;
			default :
				$time = time () - 86400 * 365;
		}

		if ($time) {
			if ($this->getState('query.beforeafter') == 'after') {
				$querystrings [] = "m.time > '{$time}'";
			} else {
				$querystrings [] = "m.time <= '{$time}'";
			}
		}

		$topic_id = $this->getState('query.topic_id');
		if ( $topic_id ) {
			$querystrings [] = "m.id = '{$topic_id}'";
		}

		return implode ( ' AND ', $querystrings );
	}

	protected function buildOrderBy() {
		if ($this->getState('query.order') == 'dec')
			$order1 = 'DESC';
		else
			$order1 = 'ASC';
		switch ($this->getState('query.sortby')) {
			case 'title' :
				$orderby = "m.subject {$order1}, m.time {$order1}";
				break;
			case 'views' :
				$orderby = "m.hits {$order1}, m.time {$order1}";
				break;
			case 'forum' :
				$orderby = "m.catid {$order1}, m.time {$order1}";
				break;
			case 'lastpost' :
			default :
				$orderby = "m.time {$order1}";
		}

		return $orderby;
	}

	public function getUrlParams() {
		// Turn internal state into URL, but ignore default values
		$defaults = array ('searchtype' => 0, 'searchuser' => '', 'exactname' => 0, 'childforums' => 0, 'starteronly' => 0,
			'replyless' => 0, 'replylimit' => 0, 'searchdate' => '', 'beforeafter' => 'after', 'sortby' => '',
			'order' => 'dec', 'catids' => '0', 'show' => '0', 'topic_id' => 0 );

		$url_params = '';
		$state = $this->getState();
		foreach ( $state as $param => $value ) {
			$paramparts = explode('.', $param);
			if ($paramparts[0] != 'query') continue;
			$param = $paramparts[1];

			if ($param == 'catids')
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
