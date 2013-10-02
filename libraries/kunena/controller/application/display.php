<?php
/**
 * Kunena Component
 * @package Kunena.Site
 * @subpackage Controllers.Misc
 *
 * @copyright (C) 2008 - 2013 Kunena Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.kunena.org
 **/
defined ( '_JEXEC' ) or die ();

class KunenaControllerApplicationDisplay extends KunenaControllerDisplay
{
	/**
	 * @var KunenaLayout
	 */
	protected $page;
	/**
	 * @var KunenaLayout
	 */
	protected $content;
	/**
	 * @var JPathway
	 */
	protected $breadcrumb;
	/**
	 * @var KunenaUser
	 */
	protected $me;
	/**
	 * @var KunenaConfig
	 */
	public $config;
	/**
	 * @var KunenaTemplate
	 */
	protected $template;
	/**
	 * @var JDocument
	 */
	protected $document;

	public function exists() {
		$this->page = KunenaLayoutPage::factory("{$this->input->getCmd('view')}/{$this->input->getCmd('layout', 'default')}");
		return (bool) $this->page->getPath();
	}

	protected function display() {
		// Display layout with given parameters.
		$this->page->set('input', $this->input);

		return $this->page;
	}

	public function execute() {
		KUNENA_PROFILER ? KunenaProfiler::instance()->start('function '.get_class($this).'::'.__FUNCTION__.'()') : null;

		// Run before executing action.
		$result = $this->before();
		if ($result === false) {
			throw new KunenaExceptionAuthorise(JText::_('COM_KUNENA_NO_ACCESS'), 404);
		}

		// Wrapper layout.
		$this->output = KunenaLayout::factory('Page')->set('me', $this->me);

		if ($this->config->board_offline && !$this->me->isAdmin ()) {
			// Forum is offline.
			$this->setResponseStatus(503);
			$this->output->setLayout('offline');

			$this->content = KunenaLayout::factory('Page/Custom')
				->set('header', JText::_('COM_KUNENA_FORUM_IS_OFFLINE'))
				->set('body', $this->config->offline_message);

		} elseif ($this->config->regonly && !$this->me->exists()) {
			// Forum is for registered users only.
			$this->setResponseStatus(403);
			$this->output->setLayout('offline');

			$this->content = KunenaLayout::factory('Page/Custom')
				->set('header', JText::_('COM_KUNENA_LOGIN_NOTIFICATION'))
				->set('body', JText::_('COM_KUNENA_LOGIN_FORUM'));

		} else {
			// Display real content.
			try {
				$content = $this->display()->set('breadcrumb', $this->breadcrumb);
				$this->content = $content->render();

			} catch (KunenaExceptionAuthorise $e) {
				$this->setResponseStatus($e->getCode());
				$this->output->setLayout('unauthorized');
				$this->document->setTitle($e->getResponseStatus());

				$this->content = KunenaLayout::factory('Page/Custom')
					->set('header', $e->getResponseStatus())
					->set('body', $e->getMessage());

			} catch (Exception $e) {
				$this->setResponseStatus($e->getCode());
				$this->output->setLayout('unauthorized');
				$this->document->setTitle($e->getMessage());

				$this->content = KunenaLayout::factory('Page/Custom')
					->set('header', 'Error while rendering layout')
					->set('body', isset($content) ? $content->renderError($e) : $this->content->renderError($e));
			}
		}

		// Display wrapper layout with given parameters.
		$this->output
			->set('content', $this->content)
			->set('breadcrumb', $this->breadcrumb);

		// Run after executing action.
		$this->after();

		KUNENA_PROFILER ? KunenaProfiler::instance()->stop('function '.get_class($this).'::'.__FUNCTION__.'()') : null;
		return $this->output;
	}

	protected function before() {
		KUNENA_PROFILER ? KunenaProfiler::instance()->start('function '.get_class($this).'::'.__FUNCTION__.'()') : null;

		if (!$this->exists()) {
			throw new RuntimeException("Layout '{$this->input->getCmd('view')}/{$this->input->getCmd('layout', 'default')}' does not exist!", 404);
		}

		// Load language files.
		KunenaFactory::loadLanguage('com_kunena.sys', 'admin');
		KunenaFactory::loadLanguage('com_kunena.templates');
		KunenaFactory::loadLanguage('com_kunena.models');
		KunenaFactory::loadLanguage('com_kunena.views');

		$this->me = KunenaUserHelper::getMyself();
		$this->config = KunenaConfig::getInstance();
		$this->document = JFactory::getDocument();
		$this->template = KunenaFactory::getTemplate();
		$this->template->initialize();

		if ($this->me->isAdmin ()) {
			// Display warnings to the administrator if forum is either offline or debug has been turned on.
			if ($this->config->board_offline) {
				$this->app->enqueueMessage(JText::_('COM_KUNENA_FORUM_IS_OFFLINE'), 'notice');
			}
			if ($this->config->debug) {
				$this->app->enqueueMessage(JText::_('COM_KUNENA_WARNING_DEBUG'), 'notice');
			}
		}
		if ($this->me->isBanned()) {
			// Display warnings to the banned users.
			$banned = KunenaUserBan::getInstanceByUserid($this->me->userid, true);
			if (!$banned->isLifetime()) {
				$this->app->enqueueMessage(JText::sprintf('COM_KUNENA_POST_ERROR_USER_BANNED_NOACCESS_EXPIRY',
					KunenaDate::getInstance($banned->expiration)->toKunena('date_today')), 'notice');
			} else {
				$this->app->enqueueMessage(JText::_ ( 'COM_KUNENA_POST_ERROR_USER_BANNED_NOACCESS'), 'notice');
			}
		}

		// Remove base and add canonical link.
		$this->document->setBase('');
		$this->document->addHeadLink( KunenaRoute::_(), 'canonical', 'rel', '' );

		// Initialize breadcrumb.
		$this->breadcrumb = $this->app->getPathway();

		KUNENA_PROFILER ? KunenaProfiler::instance()->stop('function '.get_class($this).'::'.__FUNCTION__.'()') : null;
	}

	protected function after() {
		KUNENA_PROFILER ? KunenaProfiler::instance()->start('function '.get_class($this).'::'.__FUNCTION__.'()') : null;

		// Use our own browser side cache settings.
		JResponse::allowCache(false);
		JResponse::setHeader( 'Expires', 'Mon, 1 Jan 2001 00:00:00 GMT', true );
		JResponse::setHeader( 'Last-Modified', gmdate("D, d M Y H:i:s") . ' GMT', true );
		JResponse::setHeader( 'Cache-Control', 'no-store, must-revalidate, post-check=0, pre-check=0', true );

		if ($this->config->get('credits', 1)) {
			$this->output->appendAfter($this->poweredBy());
		}
		KUNENA_PROFILER ? KunenaProfiler::instance()->stop('function '.get_class($this).'::'.__FUNCTION__.'()') : null;
	}

	public function setResponseStatus($code = 404) {
		switch ((int) $code) {
			case 400:
				JResponse::setHeader('Status', '400 Bad Request', 'true');
				break;
			case 401:
				JResponse::setHeader('Status', '401 Unauthorized', 'true');
				break;
			case 403:
				JResponse::setHeader('Status', '403 Forbidden', 'true');
				break;
			case 404:
				JResponse::setHeader('Status', '404 Not Found', 'true');
				break;
			case 410:
				JResponse::setHeader('Status', '410 Gone', 'true');
				break;
			case 503:
				JResponse::setHeader('Status', '503 Service Temporarily Unavailable', 'true');
				break;
			case 500:
			default:
				JResponse::setHeader('Status', '500 Internal Server Error', 'true');
		}
	}

	final public function poweredBy() {
		$templateText = (string) $this->template->params->get('templatebyText');
		$templateName = (string) $this->template->params->get('templatebyName');
		$templateLink = (string) $this->template->params->get('templatebyLink');
		$credits = '<div style="text-align:center">';
		$credits .= JHtml::_('kunenaforum.link', 'index.php?option=com_kunena&view=credits',
			JText::_('COM_KUNENA_POWEREDBY'), '', '', 'follow',
			array('style'=>'display: inline; visibility: visible; text-decoration: none;'));
		$credits .= ' <a href="http://www.kunena.org" rel="follow"
			target="_blank" style="display: inline; visibility: visible; text-decoration: none;">'
			. JText::_('COM_KUNENA').'</a>';
		if ($templateText) {
			$credits .= ' :: <a href ="'. $templateLink. '" rel="follow" target="_blank" style="text-decoration: none;">'
				. $templateText .' '. $templateName .'</a>';
		}
		$credits .= '</div>';
		return $credits;
	}
}
