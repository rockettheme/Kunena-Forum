<?php
/**
 * Kunena Component
 * @package     Kunena.Site
 * @subpackage  Controller.Elasticsearch
 *
 * @copyright   (C) 2008 - 2013 Kunena Team. All rights reserved.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link        http://www.kunena.org
 **/
defined('_JEXEC') or die;

/**
 * Class ComponentKunenaControllerElasticsearchFormDisplay
 *
 * @since  3.1
 */
class ComponentKunenaControllerElasticsearchFormDisplay extends KunenaControllerDisplay
{
	protected $name = 'Elasticsearch/Form';

	/**
	 * @var KunenaModelSearch
	 */
	public $model;

	/**
	 * Prepare search form display.
	 *
	 * @return void
	 */
	protected function before()
	{
		parent::before();

		require_once KPATH_SITE . '/models/elasticsearch.php';
		$this->model = new KunenaModelElasticsearch(array(), $this->input);
		$this->model->initialize($this->getOptions(), $this->getOptions()->get('embedded', false));
		$this->state = $this->model->getState();

		$this->me = KunenaUserHelper::getMyself();

		$this->isModerator = ($this->me->isAdmin() || KunenaAccess::getInstance()->getModeratorStatus());
		$this->error = $this->model->getError();
	}

	/**
	 * Prepare document.
	 *
	 * @return void
	 */
	protected function prepareDocument()
	{
		$this->setTitle(JText::_('COM_KUNENA_SEARCH_ADVSEARCH'));
	}
}
