<?php
/**
 * Kunena Component
 * @package Kunena.Framework
 * @subpackage Forum.Message
 *
 * @copyright (C) 2008 - 2013 Kunena Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.kunena.org
 **/
defined ( '_JEXEC' ) or die ();

/**
 * Class KunenaForumMessageFinder
 */
class KunenaForumMessageFinder
{
	/**
	 * @var JDatabaseQuery
	 */
	protected $query;
	/**
	 * @var JDatabase
	 */
	protected $db;
	protected $start = 0;
	protected $limit = 20;
	protected $hold = array(0);
	protected $moved = null;

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$this->limit = KunenaConfig::getInstance()->messages_per_page;
		$this->db = JFactory::getDbo();
		$this->query = $this->db->getQuery(true);
		$this->query->from('#__kunena_messages AS m');
	}

	/**
	 * Set limitstart for the query.
	 *
	 * @param int $limitstart
	 *
	 * @return $this
	 */
	public function start($limitstart = 0)
	{
		$this->start = $limitstart;

		return $this;
	}

	/**
	 * Set limit to the query.
	 *
	 * If this function isn't used, Kunena will use threads per page configuration setting.
	 *
	 * @param int $limit
	 *
	 * @return $this
	 */
	public function limit($limit = null)
	{
		if (!isset($limit)) $limit = KunenaConfig::getInstance()->messages_per_page;
		$this->limit = $limit;

		return $this;
	}

	/**
	 * Set order by field and direction.
	 *
	 * This function can be used more than once to chain order by.
	 *
	 * @param  string $by
	 * @param  int $direction
	 *
	 * @return $this
	 */
	public function order($by, $direction = 1)
	{
		$direction = $direction > 0 ? 'ASC' : 'DESC';
		$by = 'm.'.$this->db->quoteName($by);
		$this->query->order("{$by} {$direction}");

		return $this;
	}

	public function filterBy($field, $operation, $value)
	{
		$operation = strtoupper($operation);
		switch ($operation)
		{
			case '>':
			case '>=':
			case '<':
			case '<=':
			case '=':
				$this->query->where("{$this->db->quoteName($field)} {$operation} {$this->db->quote($value)}");
				break;
			case 'IN':
			case 'NOT IN':
				$value = (array) $value;
				if (empty($value)) {
					// WHERE field IN (nothing).
					$this->query->where('0');
				} else {
					$list = implode(',', $value);
					$this->query->where("{$this->db->quoteName($field)} {$operation} ({$list})");
				}
				break;
		}

		return $this;
	}

	/**
	 * Filter by user access to the categories.
	 *
	 * It is very important to use this or category filter. Otherwise messages from unauthorized categories will be
	 * included to the search results.
	 *
	 * @param KunenaUser $user
	 *
	 * @return $this
	 */
	public function filterByUserAccess(KunenaUser $user)
	{
		$categories = $user->getAllowedCategories();
		$list = implode(',', $categories);
		$this->query->where("m.catid IN ({$list})");

		return $this;
	}

	/**
	 * Filter by list of categories.
	 *
	 * It is very important to use this or user access filter. Otherwise messages from unauthorized categories will be
	 * included to the search results.
	 *
	 * $messages->filterByCategories($me->getAllowedCategories())->limit(20)->find();
	 *
	 * @param array $categories
	 *
	 * @return $this
	 */
	public function filterByCategories(array $categories)
	{
		$list = array();
		foreach ($categories as $category)
		{
			if ($category instanceof KunenaForumCategory) $list[] = (int) $category->id;
			else $list[] = (int) $category;
		}
		$list = implode(',', $list);
		$this->query->where("m.catid IN ({$list})");

		return $this;
	}

	/**
	 * Filter by time.
	 *
	 * @param JDate $starting  Starting date or null if older than ending date.
	 * @param JDate $ending    Ending date or null if newer than starting date.
	 *
	 * @return $this
	 */
	public function filterByTime(JDate $starting = null, JDate $ending = null)
	{
		if ($starting && $ending) {
			$this->query->where("m.time BETWEEN {$this->db->quote($starting->toUnix())} AND {$this->db->quote($ending->toUnix())}");
		} elseif ($starting) {
			$this->query->where("m.time > {$this->db->quote($starting->toUnix())}");
		} elseif ($ending) {
			$this->query->where("m.time <= {$this->db->quote($ending->toUnix())}");
		}

		return $this;
	}

	/**
	 * Filter by users role in the message. For now use only once.
	 *
	 * posted = User has posted the message.
	 *
	 * @param KunenaUser $user
	 * @param string     $action Action or negation of the action (!action).
	 *
	 * @return $this
	 */
	public function filterByUser(KunenaUser $user, $action = 'posted')
	{
		switch ($action)
		{
			case 'posted':
				$this->query->where('m.userid='.$user->userid);
				break;
			case '!posted':
				$this->query->where('m.userid!='.$user->userid);
				break;
			case 'edited':
				$this->query->where('m.modified_by='.$user->userid);
				break;
			case '!edited':
				$this->query->where('m.modified_by!='.$user->userid);
				break;
		}

		return $this;
	}

	/**
	 * Filter by hold (0=published, 1=unapproved, 2=deleted, 3=topic deleted).
	 *
	 * @param array $hold  List of hold states to display.
	 *
	 * @return $this
	 */
	public function filterByHold(array $hold = array(0))
	{
		$this->hold = $hold;

		return $this;
	}

	/**
	 * Get messages.
	 *
	 * @param  string  $access  Kunena action access control check.
	 * @return array|KunenaForumMessage[]
	 */
	public function find($access = 'read')
	{
		$query = clone $this->query;
		$this->build($query);
		$query->select('m.id');
		$this->db->setQuery($query, $this->start, $this->limit);
		$results = (array) $this->db->loadColumn();
		KunenaError::checkDatabaseError();

		return KunenaForumMessageHelper::getMessages($results, $access);
	}

	/**
	 * Count messages.
	 * @return int
	 */
	public function count()
	{
		$query = clone $this->query;
		$this->build($query);
		$query->select('COUNT(*)');
		$this->db->setQuery($query);
		$count = (int) $this->db->loadResult();
		KunenaError::checkDatabaseError();

		return $count;
	}

	protected function build($query)
	{
		if (!empty($this->hold)) {
			JArrayHelper::toInteger($this->hold, 0);
			$hold = implode(',', $this->hold);
			$query->where("m.hold IN ({$hold})");
		}
	}
}
