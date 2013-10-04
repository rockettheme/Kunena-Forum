<?php
/**
 * Kunena Component
 * @package Kunena.Site
 * @subpackage Controllers.Topic
 *
 * @copyright (C) 2008 - 2013 Kunena Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.kunena.org
 **/
defined ( '_JEXEC' ) or die ();

/**
 * Class ComponentKunenaControllerTopicPollDisplay
 */
class ComponentKunenaControllerTopicPollDisplay extends KunenaControllerDisplay
{
	public $me;
	public $category;
	/**
	 * @var KunenaForumTopic
	 */
	public $topic;
	public $poll;
	public $uri;

	protected function display()
	{
		// Display layout with given parameters.
		if ($this->voted || !$this->topic->isAuthorised('poll.vote')) {
			$content = KunenaLayout::factory('Topic/Poll/Results')->setProperties($this->getProperties());
		} else {
			$content = KunenaLayout::factory('Topic/Poll/Vote')->setProperties($this->getProperties());
		}

		return $content;
	}

	protected function before()
	{
		parent::before();

		$this->topic = KunenaForumTopicHelper::get($this->input->getInt('id'));
		$this->category = $this->topic->getCategory();
		$this->config = KunenaFactory::getConfig();
		$this->me = KunenaUserHelper::getMyself();

		// need to check if poll is allowed in this category
		$this->topic->tryAuthorise('poll.read');

		$this->poll = $this->topic->getPoll();
		$this->usercount = $this->poll->getUserCount();
		$this->usersvoted = $this->poll->getUsers();
		$this->voted = $this->poll->getMyVotes();

		$this->users_voted_list = array();
		$this->users_voted_morelist = array();
		if($this->config->pollresultsuserslist && !empty($this->usersvoted)) {
			$i = 0;
			// FIXME: too many queries...
			foreach($this->usersvoted as $userid=>$vote) {
				if ( $i <= '4' ) $this->users_voted_list[] = KunenaFactory::getUser(intval($userid))->getLink();
				else $this->users_voted_morelist[] = KunenaFactory::getUser(intval($userid))->getLink();
				$i++;
			}
		}

		$this->uri = "index.php?option=com_kunena&view=topic&layout=poll&catid={$this->category->id}&id={$this->topic->id}";

		return true;
	}

	protected function prepareDocument()
	{
		$this->setTitle(JText::_('COM_KUNENA_POLL_NAME').' '.KunenaHtmlParser::parseText ($this->poll->title));
	}
}
