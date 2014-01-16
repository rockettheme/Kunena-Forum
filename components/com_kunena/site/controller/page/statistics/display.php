<?php
/**
 * Kunena Component
 * @package     Kunena.Site
 * @subpackage  Controller.Page
 *
 * @copyright   (C) 2008 - 2013 Kunena Team. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link        http://www.kunena.org
 **/
defined('_JEXEC') or die;

/**
 * Class ComponentKunenaControllerPageStatisticsDisplay
 *
 * @since  3.1
 */
class ComponentKunenaControllerPageStatisticsDisplay extends KunenaControllerDisplay
{
	protected $name = 'Page/Statistics';

	public $config;

	public $latestMemberLink;

	public $statisticsUrl;

	public $userlistUrl;

	/**
	 * Prepare statistics box display.
	 *
	 * @return bool
	 */
	protected function before()
	{
		parent::before();

		$this->config = KunenaConfig::getInstance();

		if (!$this->config->get('showstats') || (!$this->config->get('showstats_to_guests') && !KunenaUserHelper::get()->exists()))
		{
			throw new KunenaExceptionAuthorise(JText::_('COM_KUNENA_NO_ACCESS'), '404');
		}

		$statistics = KunenaForumStatistics::getInstance();
		$statistics->loadGeneral();
		$this->setProperties($statistics);

		$me = KunenaUserHelper::getMyself();
		$moderator = intval($me->isModerator()) + intval($me->isAdmin());

		$this->latestMemberLink = KunenaFactory::getUser(intval($this->lastUserId))->getLink();
		$this->statisticsUrl = KunenaFactory::getProfile()->getStatisticsURL();
		$this->userlistUrl = KunenaFactory::getProfile()->getUserListUrl();

		return true;
	}
}
