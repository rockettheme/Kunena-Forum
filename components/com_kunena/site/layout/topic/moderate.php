<?php
/**
 * Kunena Component
 * @package Kunena.Site
 * @subpackage Layout.Topic
 *
 * @copyright (C) 2008 - 2013 Kunena Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.kunena.org
 **/
defined ( '_JEXEC' ) or die ();

class KunenaLayoutTopicModerate extends KunenaLayout
{
	public function getTopicOptions() {
		$options =array ();

		// Start with default options.
		if (!$this->message) {
			$options[] = JHtml::_('select.option', 0, JText::_('COM_KUNENA_MODERATION_MOVE_TOPIC'));
		} else {
			$options[] = JHtml::_('select.option', 0, JText::_('COM_KUNENA_MODERATION_CREATE_TOPIC'));
		}
		$options[] = JHtml::_('select.option', -1, JText::_('COM_KUNENA_MODERATION_ENTER_TOPIC'));

		// Then list a few topics.
		$db = JFactory::getDbo();
		$params = array(
			'orderby'=>'tt.last_post_time DESC',
			'where'=>" AND tt.id != {$db->Quote($this->topic->id)} ");
		list ($total, $topics) = KunenaForumTopicHelper::getLatestTopics($this->category->id, 0, 30, $params);
		foreach ($topics as $topic) {
			$options[] = JHtml::_('select.option', $topic->id, $this->escape($topic->subject));
		}

		return $options;
	}

	public function getCategoryList() {
		$options = array ();
		$params = array ('sections'=>0, 'catid'=>0);
		return JHtml::_('kunenaforum.categorylist', 'targetcategory', 0, $options, $params, 'class="inputbox kmove_selectbox"', 'value', 'text', $this->category->id, 'kmod_categories');
	}
}
