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
 * Class ComponentKunenaControllerElasticsearchResultsDisplay
 *
 * @since  3.1
 */
class ComponentKunenaControllerElasticsearchResultsDisplay extends KunenaControllerDisplay
{
	protected $name = 'Elasticsearch/Results';

	/**
	 * @var KunenaModelSearch
	 */
	public $model;

	/**
	 * @var int
	 */
	public $total;

	public $data = array();

	/**
	 * Prepare search results display.
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
		$this->message_ordering = $this->me->getMessageOrdering();

		$this->searchwords = $this->model->getSearchWords();
		$this->isModerator = ($this->me->isAdmin() || KunenaAccess::getInstance()->getModeratorStatus());

		$this->results = array();
		$this->total = $this->model->getTotal();
		$this->data = $this->model->getResults();

		$start = $this->state->get('list.start');
		$total = $this->total;
		$count = $this->data->count;

		$this->pagination = new KunenaPagination($total, $start, $count);

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
