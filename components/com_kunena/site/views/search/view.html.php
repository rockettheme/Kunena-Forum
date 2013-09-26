<?php
/**
 * Kunena Component
 * @package Kunena.Site
 * @subpackage Views
 *
 * @copyright (C) 2008 - 2013 Kunena Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.kunena.org
 **/
defined ( '_JEXEC' ) or die ();

/**
 * Search View
 */
class KunenaViewSearch extends KunenaView {
	function displayDefault($tpl = null) {

		$this->message_ordering = $this->me->getMessageOrdering();
		$this->searchwords = $this->get('SearchWords');
		$this->isModerator = ($this->me->isAdmin() || KunenaAccess::getInstance()->getModeratorStatus($this->me));

		
		if ($this->get('ShowResults')) {
			$this->data = $this->get('SearchResults');	
		}

		$this->selected=' selected="selected"';
		$this->checked=' checked="checked"';
		$this->error = $this->get('Error');

		$this->_prepareDocument();

		$this->render('Search', $tpl);
	}

	function displayRows() {

		$this->row(true);


		foreach ($this->data->results as $result) {

			$this->message = KunenaForumMessageHelper::get($result->msgid);
			$this->score = sprintf("%.1f", $result->getScore() * 10);

			if ($this->message->subject == null) {
				$this->empty = true;
				$this->subjectHtml = $result->subject;
				if ($result->parent) {
	            	$this->subjectHtml = 'RE: '.$this->subjectHtml;
	            }
				
				$this->messageHtml = ElasticSearchHelper::truncateText($result->message, 300);

			} else {
				$this->empty = false;
				$highlights = $result->getHighlights();

	            $this->subjectHtml = isset($highlights['subject']) ? $highlights['subject'][0] : $this->message->subject;
	            if ($this->message->getParent()->id) {
	            	$this->subjectHtml = 'RE: '.$this->subjectHtml;
	            }
	            if (isset($highlights['message'])) {
	                $this->messageHtml = implode('... ', $highlights['message']);
	            } else {
	                $this->messageHtml = ElasticSearchHelper::truncateText($result->message, 300);
	            }

	            

	            $this->parent = $this->message->getParent()->id;
				$this->topic = $this->message->getTopic();
				$this->category = $this->message->getCategory();
				$this->categoryLink = $this->getCategoryLink($this->category->getParent()) . ' / ' . $this->getCategoryLink($this->category);

				$profile = KunenaFactory::getUser((int)$this->message->userid);
				$this->useravatar = $profile->getAvatarImage('kavatar', 'post');

				$this->author = $this->message->getAuthor();
				$this->topicAuthor = $this->topic->getAuthor();
				$this->topicTime = $this->topic->first_post_time;	
			}

			

			$contents = $this->loadTemplateFile('row');

			$contents = preg_replace_callback('|\[K=(\w+)(?:\:([\w-_]+))?\]|', array($this, 'fillTopicInfo'), $contents);

			echo $contents;
		}
	}

	function displayPagination() {
		if ($this->data->pages > 1) {

			$uri = JFactory::getURI();
		    $query_string = $uri->getQuery();

		    // remove the page element of the query if it is set
		    parse_str($query_string, $query_array);
		    unset($query_array['page']);

		    $this->pageurl = ElasticSearchHelper::generateUrl(JURI::current(),$query_array);

			echo $this->loadTemplateFile('pagination');	
		}
	}

	function getSuggestions($suggestion = 'simple_phrase') {

        if (isset($this->data)) {
        	$results = $this->data->results;
        	$response = $results->getResponse();
            $datas = $response->getData();
            if (isset($datas['suggest'][$suggestion][0]['options'])) {
                $suggest_data = $datas['suggest'][$suggestion][0]['options'];

				$suggestions = array();
				foreach ($suggest_data as $suggestion) {
					$suggestions[] = ' <a href="'.$this->getSuggestUrl($suggestion['text']).'">'.$suggestion['text'].'</a>';
				}
				return $suggestions;	
			}
        }

        return false;
    }

    function getSuggestUrl($suggestion) {

    	$uri = JFactory::getURI();
	    $query_string = $uri->getQuery();

	    // remove the page element of the query if it is set
	    parse_str($query_string,$query_array);
	    $query_array['q'] = $suggestion;

    	return ElasticSearchHelper::generateUrl(JURI::current(),$query_array);
    }

	function displaySearchResults() {
		if(isset($this->data)) {
			echo $this->loadTemplateFile('results');
		} else {
			echo "enter keywords and/or a username...";
		}
	}

	function displayModeList($id, $attributes = '') {
		$options	= array();
		$options[]	= JHtml::_('select.option',  '0', 'Search title and message content' );
		$options[]	= JHtml::_('select.option',  '1', JText::_('COM_KUNENA_SEARCH_SEARCH_TITLES') );
		$options[]	= JHtml::_('select.option',  '2', 'Search messages only' );
		$options[]	= JHtml::_('select.option',  '3', 'Search first post of topics only' );
		echo JHtml::_('select.genericlist',  $options, 'searchtype', $attributes, 'value', 'text', $this->state->get('query.searchtype'), $id );
	}
	function displayDateList($id, $attributes = '') {
		$options	= array();
		$options[]	= JHtml::_('select.option',  '', JText::_('COM_KUNENA_SEARCH_DATE_ANY') );
		$options[]	= JHtml::_('select.option',  'lastvisit', JText::_('COM_KUNENA_SEARCH_DATE_LASTVISIT') );
		$options[]	= JHtml::_('select.option',  '-1d', JText::_('COM_KUNENA_SEARCH_DATE_YESTERDAY') );
		$options[]	= JHtml::_('select.option',  '-1w', JText::_('COM_KUNENA_SEARCH_DATE_WEEK') );
		$options[]	= JHtml::_('select.option',  '-2w',  JText::_('COM_KUNENA_SEARCH_DATE_2WEEKS') );
		$options[]	= JHtml::_('select.option',  '-1M', JText::_('COM_KUNENA_SEARCH_DATE_MONTH') );
		$options[]	= JHtml::_('select.option',  '-3M', JText::_('COM_KUNENA_SEARCH_DATE_3MONTHS') );
		$options[]	= JHtml::_('select.option',  '-6M', JText::_('COM_KUNENA_SEARCH_DATE_6MONTHS') );
		$options[]	= JHtml::_('select.option',  '-12M', JText::_('COM_KUNENA_SEARCH_DATE_YEAR') );
		
		echo JHtml::_('select.genericlist',  $options, 'searchdate', $attributes, 'value', 'text', $this->state->get('query.searchdate'), $id );
	}
	function displayBeforeAfterList($id, $attributes = '') {
		$options	= array();
		$options[]	= JHtml::_('select.option',  'after', JText::_('COM_KUNENA_SEARCH_DATE_NEWER') );
		$options[]	= JHtml::_('select.option',  'before', JText::_('COM_KUNENA_SEARCH_DATE_OLDER') );
		echo JHtml::_('select.genericlist',  $options, 'beforeafter', $attributes, 'value', 'text', $this->state->get('query.beforeafter'), $id );
	}
	function displaySortByList($id, $attributes = '') {
		$options	= array();
		$options[]  = JHtml::_('select.option',	 '', JText::_('Score, Date') );
		$options[]	= JHtml::_('select.option',  'lastpost', JText::_('COM_KUNENA_SEARCH_SORTBY_POST') );
		$options[]	= JHtml::_('select.option',  'title', JText::_('COM_KUNENA_SEARCH_SORTBY_TITLE') );
//		$options[]	= JHtml::_('select.option',  'replycount', JText::_('COM_KUNENA_SEARCH_SORTBY_POSTS') );
		$options[]	= JHtml::_('select.option',  'views', JText::_('COM_KUNENA_SEARCH_SORTBY_VIEWS') );
//		$options[]	= JHtml::_('select.option',  'threadstart', JText::_('COM_KUNENA_SEARCH_SORTBY_START') );
		$options[]	= JHtml::_('select.option',  'postusername', JText::_('COM_KUNENA_SEARCH_SORTBY_USER') );
		$options[]	= JHtml::_('select.option',  'forum', JText::_('COM_KUNENA_CATEGORY') );
		echo JHtml::_('select.genericlist',  $options, 'sortby', $attributes, 'value', 'text', $this->state->get('query.sortby'), $id );
	}
	function displayOrderList($id, $attributes = '') {
		$options	= array();
		$options[]	= JHtml::_('select.option',  'inc', JText::_('COM_KUNENA_SEARCH_SORTBY_INC') );
		$options[]	= JHtml::_('select.option',  'dec', JText::_('COM_KUNENA_SEARCH_SORTBY_DEC') );
		echo JHtml::_('select.genericlist',  $options, 'order', $attributes, 'value', 'text', $this->state->get('query.order'), $id );
	}
	function displayLimitList($id, $attributes = '') {
		// Limit value list
		$options	= array();
		$options[]	= JHtml::_('select.option',  '5', JText::_('COM_KUNENA_SEARCH_LIMIT5') );
		$options[]	= JHtml::_('select.option',  '10', JText::_('COM_KUNENA_SEARCH_LIMIT10') );
		$options[]	= JHtml::_('select.option',  '15', JText::_('COM_KUNENA_SEARCH_LIMIT15') );
		$options[]	= JHtml::_('select.option',  '20', JText::_('COM_KUNENA_SEARCH_LIMIT20') );
		echo JHtml::_('select.genericlist',  $options, 'limit', $attributes, 'value', 'text',$this->state->get('list.limit'), $id );
	}
	function displayCategoryList($id, $attributes = '') {
		//category select list
		$options	= array ();
		$options[]	= JHtml::_ ( 'select.option', '0', JText::_('COM_KUNENA_SEARCH_SEARCHIN_ALLCATS') );

		$cat_params = array ('sections'=>true);
		echo JHtml::_('kunenaforum.categorylist', 'catids[]', 0, $options, $cat_params, $attributes, 'value', 'text', $this->state->get('query.catids'), $id);
	}

	// TODO: What does this do? is it needed?
	function fillTopicInfo($matches) {
		switch ($matches[1]) {
			case 'ROW':
				return $matches[2].$this->row().($this->topic->ordering ? " {$matches[2]}sticky" : '');
			case 'TOPIC_ICON':
				return $this->topic->getIcon();
			case 'DATE':
				$date = new KunenaDate($matches[2]);
				return $date->toSpan('config_post_dateformat', 'config_post_dateformat_hover');
		}
	}

	protected function _prepareDocument(){
		$this->setTitle(JText::_('COM_KUNENA_SEARCH_ADVSEARCH'));

		// TODO: set keywords and description
	}
}
