<?php
/**
 * Kunena Component
 * @package     Kunena.Site
 * @subpackage  Controller.User
 *
 * @copyright   (C) 2008 - 2013 Kunena Team. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link        http://www.kunena.org
 **/
defined('_JEXEC') or die;

/**
 * Class ComponentKunenaControllerUserAttachmentsDisplay
 *
 * @since  3.1
 */
class ComponentKunenaControllerUserAttachmentsDisplay extends KunenaControllerDisplay
{
	protected $name = 'User/Attachments';

	/**
	 * @var KunenaUser
	 */
	public $me;

	/**
	 * @var KunenaUser
	 */
	public $profile;

	/**
	 * @var array|KunenaAttachments[]
	 */
	public $attachments;

	public $headerText;

	/**
	 * Prepare user attachments list.
	 *
	 * @return void
	 */
	protected function before()
	{
		parent::before();

		$userid = $this->input->getInt('userid');
		$start = $this->input->getInt('limitstart', 0);
		$limit = $this->input->getInt('limit', 30);

		$this->template = KunenaFactory::getTemplate();
		$this->me = KunenaUserHelper::getMyself();
		$this->profile = KunenaUserHelper::get($userid);
		$this->moreUri = null;

		$this->embedded = $this->getOptions()->get('embedded', false);

		if ($this->embedded)
		{
			$this->moreUri = new JUri('index.php?option=com_kunena&view=user&layout=attachments&userid=' . $userid . '&limit=' . $limit);
			$this->moreUri->setVar('Itemid', KunenaRoute::getItemID($this->moreUri));
		}

		$finder = new KunenaAttachmentFinder;
		$finder->where('userid', '=', $userid);

		$this->total = $finder->count();
		$this->pagination = new KunenaPagination($this->total, $start, $limit);

		if ($this->moreUri)
		{
			$this->pagination->setUri($this->moreUri);
		}

		$this->attachments = $finder
			->order('id', -1)
			->start($this->pagination->limitstart)
			->limit($this->pagination->limit)
			->find();

		// Pre-load messages.
		$messageIds = array();

		foreach ($this->attachments as $attachment)
		{
			$messageIds[] = (int) $attachment->mesid;
		}

		$messages = KunenaForumMessageHelper::getMessages($messageIds, 'none');

		// Pre-load topics.
		$topicIds = array();

		foreach ($messages as $message)
		{
			$topicIds[] = $message->thread;
		}

		KunenaForumTopicHelper::getTopics($topicIds, 'none');

		$this->headerText = JText::_('COM_KUNENA_MANAGE_ATTACHMENTS');
	}

	/**
	 * Prepare document.
	 *
	 * @return void
	 */
	protected function prepareDocument()
	{
		$this->setTitle($this->headerText);
	}
}
