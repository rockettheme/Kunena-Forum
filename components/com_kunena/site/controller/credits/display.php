<?php
/**
 * Kunena Component
 * @package Kunena.Site
 * @subpackage Controllers.Credits
 *
 * @copyright (C) 2008 - 2013 Kunena Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.kunena.org
 **/
defined ( '_JEXEC' ) or die ();

/**
 * Class ComponentKunenaControllerApplicationMiscDisplay
 */
class ComponentKunenaControllerCreditsDisplay extends KunenaControllerDisplay
{
	protected $name = 'Credits';

	public $logo;
	public $intro;
	public $memberList;
	public $thanks;

	protected function before()
	{
		parent::before();

		$this->logo = KunenaFactory::getTemplate()->getImagePath('icons/kunena-logo-48-white.png');

		$this->intro = JText::sprintf('COM_KUNENA_CREDITS_INTRO', 'http://www.kunena.org/team');

		$this->memberList = array(
			array('name'=>'Matias Griese', 'url'=>'http://www.kunena.org/forum/user/63-matias',
				'title'=>JText::_('COM_KUNENA_CREDITS_DEVELOPEMENT')),
			array('name'=>'Florian Dal Fitto', 'url'=>'http://www.kunena.org/forum/user/1288-xillibit',
				'title'=>JText::_('COM_KUNENA_CREDITS_DEVELOPEMENT')),
			array('name'=>'Joshua Weiss', 'url'=>'http://www.kunena.org/forum/user/10809-coder4life',
				'title'=>JText::sprintf('COM_KUNENA_CREDITS_X_AND_Y', JText::_('COM_KUNENA_CREDITS_DESIGN'), JText::_('COM_KUNENA_CREDITS_DEVELOPEMENT'))),
			array('name'=>'Jelle Kok', 'url'=>'http://www.kunena.org/forum/user/634-810',
				'title'=>JText::sprintf('COM_KUNENA_CREDITS_X_AND_Y', JText::_('COM_KUNENA_CREDITS_DESIGN'), JText::_('COM_KUNENA_CREDITS_TESTING'))),
			array('name'=>'Ron Severdia', 'url'=>'http://www.kunena.org/forum/user/114-severdia',
				'title'=>JText::_('COM_KUNENA_CREDITS_DESIGN')),
			array('name'=>'Janich Rasmussen', 'url'=>'http://www.kunena.org/forum/user/10133-littlejohn',
				'title'=>JText::_('COM_KUNENA_CREDITS_CONTRIBUTION')),
			array('name'=>'Sven Schultschik ', 'url'=>'http://www.kunena.org/forum/user/2171-svanschu',
				'title'=>JText::_('COM_KUNENA_CREDITS_LANGUAGES')),
			array('name'=>'Michael Russell', 'url'=>'http://www.kunena.org/forum/user/997-sozzled',
				'title'=>JText::sprintf('COM_KUNENA_CREDITS_X_AND_Y', JText::_('COM_KUNENA_CREDITS_MODERATION'), JText::_('COM_KUNENA_CREDITS_DOCUMENTATION'))),
			array('name'=>'Richard Binder', 'url'=>'http://www.kunena.org/forum/user/2198-rich',
				'title'=>JText::sprintf('COM_KUNENA_CREDITS_X_AND_Y', JText::_('COM_KUNENA_CREDITS_MODERATION'), JText::_('COM_KUNENA_CREDITS_TESTING'))),
			array('name'=>'Sami Haaranen', 'url'=>'http://www.kunena.org/forum/user/151-mortti',
				'title'=>JText::sprintf('COM_KUNENA_CREDITS_X_AND_Y', JText::_('COM_KUNENA_CREDITS_MODERATION'), JText::_('COM_KUNENA_CREDITS_TESTING'))),
			array('name'=>'Joe Collins', 'url'=>'http://www.kunena.org/forum/user/26335-jiminimonka',
				'title'=>JText::sprintf('COM_KUNENA_CREDITS_X_AND_Y', JText::_('COM_KUNENA_CREDITS_MODERATION'), JText::_('COM_KUNENA_CREDITS_TESTING'))),
			array('name'=>'Oliver Ratzesberger', 'url'=>'http://www.kunena.org/forum/user/64-fxstein',
				'title'=>JText::_('COM_KUNENA_CREDITS_FOUNDER')),

		);
		$this->thanks = JText::sprintf('COM_KUNENA_CREDITS_THANKS', 'http://www.kunena.org/team#special_thanks',
			'https://www.transifex.com/projects/p/Kunena', 'http://www.kunena.org',
			'https://github.com/Kunena/Kunena-Forum/graphs/contributors');
	}

	protected function prepareDocument()
	{
		$title = JText::_('COM_KUNENA_VIEW_CREDITS_DEFAULT');
		$this->setTitle($title, true);

		$keywords = 'kunena forum, kunena, forum, joomla, joomla extension, joomla component';
		$this->setKeywords($keywords);

		// TODO: translate at some point...
		$description = 'Kunena is the ideal forum extension for Joomla. It\'s free and fully integrated. For more information, please visit www.kunena.org.';
		$this->setDescription($description);
	}
}
