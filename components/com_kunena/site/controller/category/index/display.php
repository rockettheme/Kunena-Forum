<?php
/**
 * Kunena Component
 * @package Kunena.Site
 * @subpackage Controllers.Category
 *
 * @copyright (C) 2008 - 2013 Kunena Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.kunena.org
 **/
defined ( '_JEXEC' ) or die ();

/**
 * Class ComponentKunenaControllerApplicationMiscDisplay
 */
class ComponentKunenaControllerCategoryIndexDisplay extends KunenaControllerDisplay
{
	protected $name = 'Category/Index';

	/** @var KunenaUser */
	public $me;
	public $sections = array();
	public $categories = array();
	public $pending = array();

	protected function before()
	{
		parent::before();

		$this->me = KunenaUserHelper::getMyself();

		// Get sections to display.
		$catid = $this->input->getInt('catid', 0);
		if ($catid) {
			$sections = KunenaForumCategoryHelper::getCategories($catid);
		} else {
			$sections = KunenaForumCategoryHelper::getChildren();
		}

		// Get categories and subcategories.
		if (empty($sections)) return;
		$this->sections = $sections;

		$categories = KunenaForumCategoryHelper::getChildren(array_keys($sections));
		if (empty($categories)) return;
		$subcategories = KunenaForumCategoryHelper::getChildren(array_keys($categories));

		$topicIds = array();
		$userIds = array();
		$postIds = array();
		foreach ($categories as $category) {
			// Get list of topics.
			$last = $category->getLastCategory();
			if ($last->last_topic_id) {
				$topicIds[$last->last_topic_id] = $last->last_topic_id;
			}

			$this->categories[$category->parent_id][] = $category;
		}
		foreach ($subcategories as $category) {
			$this->categories[$category->parent_id][] = $category;
		}

		// Pre-fetch topics (also display unauthorized topics as they are in allowed categories).
		$topics = KunenaForumTopicHelper::getTopics($topicIds, 'none');

		// Pre-fetch users (and get last post ids for moderators).
		foreach ($topics as $topic) {
			$userIds[$topic->last_post_userid] = $topic->last_post_userid;
			$postIds[$topic->id] = $topic->last_post_id;
		}
		KunenaUserHelper::loadUsers($userIds);
		KunenaForumMessageHelper::getMessages($postIds);

		// Pre-fetch user related stuff.
		$this->pending = array();
		if ($this->me->exists() && !$this->me->isBanned()) {

			// Load new topic counts.
			KunenaForumCategoryHelper::getNewTopics(array_keys($categories + $subcategories));

			// Get categories which are moderated by current user.
			$access = KunenaAccess::getInstance();
			$moderate = $access->getAdminStatus($this->me) + $access->getModeratorStatus($this->me);
			if (!empty($moderate[0])) {
				// Global moderators.
				$moderate = $categories;
			} else {
				// Category moderators.
				$moderate = array_intersect_key($categories, $moderate);
			}

			if (!empty($moderate)) {
				// Get pending messages.
				$catlist = implode(',', array_keys($moderate));
				$db = JFactory::getDbo();
				$db->setQuery("SELECT catid, COUNT(*) AS count
				FROM #__kunena_messages
				WHERE catid IN ({$catlist}) AND hold=1
				GROUP BY catid");
				$pending = $db->loadAssocList();
				KunenaError::checkDatabaseError();

				foreach ($pending as $item) {
					if ($item['count'])
						$this->pending[$item['catid']] = $item['count'];
				}

				if ($this->me->ordering != 0) {
					$topic_ordering = $this->me->ordering == 1 ? true : false;
				} else {
					$topic_ordering = $this->config->default_sort == 'asc' ? false : true;
				}

				// Fix last post position when user can see unapproved or deleted posts.
				if (!$topic_ordering) {
					KunenaForumMessageHelper::loadLocation($postIds);
				}
			}
		}
	}

	protected function prepareDocument()
	{
		$title = JText::_('COM_KUNENA_VIEW_CATEGORIES_DEFAULT');
		$this->setTitle($title);

		$keywords = JText::_('COM_KUNENA_CATEGORIES');
		$this->setKeywords($keywords);

		$description = JText::_('COM_KUNENA_CATEGORIES') . ' - ' . $this->config->board_title;
		$this->setDescription($description);
	}
}
