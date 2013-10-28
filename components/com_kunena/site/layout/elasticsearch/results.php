<?php
/**
 * Kunena Component
 * @package Kunena.Site
 * @subpackage Layout.Elasticsearch
 *
 * @copyright (C) 2008 - 2013 Kunena Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.kunena.org
 **/
defined ( '_JEXEC' ) or die ();

class KunenaLayoutElasticsearchResults extends KunenaLayout
{
	public function processResults() {

		$this->getSuggestions();
		$this->start = $this->pagination->limitstart + 1;
		$this->end = $this->start + $this->pagination->limit - 1;
		
	}

	public function getTopicUrl($topic) {
		return $topic->getUrl($topic->getCategory(), true, null);
	}

	public function displayRows() {
		foreach ($this->data->results as $result) {

			$this->message = KunenaForumMessageHelper::get($result->msgid);
			$this->score = sprintf("%.1f", $result->getScore() * 10);

			if ($this->message->subject == null) {
				$this->empty = true;
				$this->subjectHtml = $result->subject;
				if ($result->parent) {
					$this->subjectHtml = 'Re: '.$this->subjectHtml;
				}

				$this->messageHtml = ElasticSearchHelper::truncateText($result->message, 300);

			} else {
				$this->empty = false;
				$highlights = $result->getHighlights();

				$this->subjectHtml = isset($highlights['subject']) ? $highlights['subject'][0] : $this->message->subject;
				if ($this->message->getParent()->id) {
					$this->subjectHtml = 'Re: '.$this->subjectHtml;
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

			$contents = $this->subLayout('Elasticsearch/Results/Row')->setProperties($this->getProperties());
			echo $contents;
		}
	}

	public static function getUri($params=array()) {
		$uri = JUri::getInstance();

		foreach($params as $key=>$value) {
			$uri->setVar($key, $value);
		}

		return $uri;
	}

	public function getSuggestions($suggestion = 'simple_phrase') {
		$this->suggestions = false;
		if (isset($this->data->results)) {
			$results = $this->data->results;
			$response = $results->getResponse();
			$datas = $response->getData();
			if (isset($datas['suggest'][$suggestion][0]['options'])) {
				$suggest_data = $datas['suggest'][$suggestion][0]['options'];

				$suggestions = array();
				foreach ($suggest_data as $suggestion) {
					$suggestions[] = ' <a href="'.$this->getSuggestUrl($suggestion['text']).'">'.$suggestion['text'].'</a>';
				}
				$this->suggestions = $suggestions;
			}
		}
		return;
	}

	public function getSuggestUrl($suggestion) {
		return $this->getUri(array('q'=>$suggestion));
	}
}
