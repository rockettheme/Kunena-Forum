<?php
/**
 * Kunena Component
 * @package Kunena.Site
 * @subpackage Layout.Elasticsearch
 *
 * @copyright (C) 2008 - 2013 Kunena Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.kunena.org
 **/
defined ( '_JEXEC' ) or die ();

class KunenaLayoutElasticsearchResultsRow extends KunenaLayout
{

	public function getTopicUrl($topic) {
		return $topic->getUrl($topic->getCategory(), true, null);
	}

}
