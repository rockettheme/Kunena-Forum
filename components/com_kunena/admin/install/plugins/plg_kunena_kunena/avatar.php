<?php
/**
 * Kunena Plugin
 * @package Kunena.Plugins
 * @subpackage Kunena
 *
 * @copyright (C) 2008 - 2013 Kunena Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.kunena.org
 **/
defined ( '_JEXEC' ) or die ();

class KunenaAvatarKunena extends KunenaAvatar {
	protected $params = null;

	public function __construct($params) {
		$this->params = $params;
		$this->resize = true;
	}

	public function load($userlist)
	{
		if (!class_exists('RokClubAccess')) return;

		// TODO: Move to RokClub plugin
		// Set ranks for RocketTheme team.
		static $team;
		static $devclub;

		if ($team === null) {
			$team = KunenaUserHelper::getGroupsForUsers(array(11, 12, 13));
		}

		if ($devclub === null) {
			$access = RokClubAccess::getInstance();
			$devclub = $access->getAuthorisedUsers(array(5, 6, 9, 13));
		}

		if (KunenaUserHelper::getMyself()->isModerator()) {
			RokClubSubscription::loadUserStatuses($userlist);
		}

		foreach ($userlist as $userid) {
			$user = KunenaUserHelper::get($userid);
			if (isset($team[$user->userid][11])) {
				// Kahuna
				$user->rank = 16;
			} elseif (isset($team[$user->userid][12])) {
				// Core Team
				$user->rank = 11;
			} elseif (isset($team[$user->userid][13])) {
				// Mod Squad
				$user->rank = 12;
			} elseif (in_array($user->userid, $devclub)) {
				// Developer Club member
				$user->rank = 13;
			}
		}
	}

	public function getEditURL()
	{
		return KunenaRoute::_('index.php?option=com_kunena&view=user&layout=edit');
	}

	protected function _getURL($user, $sizex, $sizey)
	{
		$user = KunenaFactory::getUser($user);
		$avatar = $user->avatar;
		$config = KunenaFactory::getConfig();

		$timestamp = '';
		$path = KPATH_MEDIA ."/avatars";
		$origPath = "{$path}/{$avatar}";
		if ( !is_file($origPath)) {
			// If avatar does not exist use default image.
			if ($sizex <= 90) $avatar = 's_nophoto.jpg';
			else $avatar = 'nophoto.jpg';

			// Search from the template.
			$template = KunenaFactory::getTemplate();
			$origPath = JPATH_SITE . '/' . $template->getAvatarPath($avatar);
			$avatar = $template->name .'/'. $avatar;
		}
		$dir = dirname($avatar);
		$file = basename($avatar);
		if ($sizex == $sizey) {
			$resized = "resized/size{$sizex}/{$dir}";
		} else {
			$resized = "resized/size{$sizex}x{$sizey}/{$dir}";
		}

		if ( !is_file( "{$path}/{$resized}/{$file}" ) ) {
			KunenaImageHelper::version($origPath, "{$path}/{$resized}", $file, $sizex, $sizey, intval($config->avatarquality), KunenaImage::SCALE_INSIDE, intval($config->avatarcrop));
			$timestamp = '?'.round(microtime(true));
		}
		return KURL_MEDIA . "avatars/{$resized}/{$file}{$timestamp}";
	}
}
