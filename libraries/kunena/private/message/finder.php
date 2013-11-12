<?php
/**
 * Kunena Component
 * @package Kunena.Framework
 * @subpackage Private
 *
 * @copyright (C) 2008 - 2013 Kunena Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.kunena.org
 **/
defined ( '_JEXEC' ) or die ();

/**
 * Private message finder.
 */
class KunenaPrivateMessageFinder extends KunenaDatabaseFinder
{
	protected $table = '#__kunena_private';

	public function filterByUser(KunenaUser $user)
	{
		$this->query->innerJoin('#__kunena_private_to_user AS tou ON a.id=tou.private_id');
		$this->query->where(
			"(tou.user_id = {$this->db->quote($user->userid)} OR a.author_id = {$this->db->quote($user->userid)})"
		);

		return $this;
	}

	public function filterByTopic(KunenaForumTopic $topic)
	{
		$this->query->innerJoin('#__kunena_private_to_forum AS tot ON a.id=tot.private_id');
		$this->query->where("tot.topic_id = {$this->db->quote($topic->id)}");

		return $this;
	}

	public function filterByMessage(KunenaForumMessage $message)
	{
		$this->query->innerJoin('#__kunena_private_to_forum AS tom ON a.id=tom.private_id');
		$this->query->where("tom.message_id = {$this->db->quote($message->id)}");

		return $this;
	}

	public function filterByMessageIds(array $ids)
	{
		if (empty($ids))
		{
			$this->query->where(0);
		}
		else
		{
			$this->query->innerJoin('#__kunena_private_to_forum AS tom ON a.id=tom.private_id');
			$this->query->where("tom.message_id IN (".implode(',', $ids).")");
		}

		return $this;
	}

	/**
	 * Get private messages.
	 *
	 * @return array|KunenaPrivateMessage[]
	 */
	public function find()
	{
		return $this->load(parent::find());
	}

	public function firstOrNew()
	{
		$results = $this->find();
		$first = array_pop($results);
		return $first ? $first : new KunenaPrivateMessage;
	}

	protected function load(array $ids)
	{
		if (empty($ids)) return array();

		$query = $this->db->getQuery(true);
		$query->select('*')->from('#__kunena_private')->where('id IN('.implode(',', $ids).')');
		$this->db->setQuery($query);
		$results = $this->db->loadObjectList('id', 'KunenaPrivateMessage');

		return $results;
	}
}
