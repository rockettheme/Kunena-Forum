<?php
/**
 * Kunena Component
 * @package Kunena.Site
 * @subpackage Lib
 *
 * @copyright (C) 2008 - 2013 Kunena Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.kunena.org
 **/

defined( '_JEXEC' ) or die();

/**
 * Deprecated. This file is keep for legacy users which still use blue eagle template.
 *
 * @since 2.0.0
 * @deprecated 3.0.0
 */

ob_start();
if (!empty($this->poll)) :
	jimport('rokcommonjs.rokcommonjs');
	RokCommonJS::load(array('core.core', 'forum.poll'));
?>
var KUNENA_POLL_CATS_NOT_ALLOWED = "<?php echo JText::_('COM_KUNENA_POLL_CATS_NOT_ALLOWED', true) ?>";
var KUNENA_EDITOR_HELPLINE_OPTION = "<?php echo JText::_('COM_KUNENA_EDITOR_HELPLINE_OPTION', true) ?>";
var KUNENA_POLL_OPTION_NAME = "<?php echo JText::_('COM_KUNENA_POLL_OPTION_NAME', true) ?>";
var KUNENA_POLL_NUMBER_OPTIONS_MAX_NOW = "<?php echo JText::_('COM_KUNENA_POLL_NUMBER_OPTIONS_MAX_NOW', true) ?>";
var KUNENA_ICON_ERROR = <?php echo json_encode(KunenaFactory::getTemplate()->getImagePath('publish_x.png')) ?>;
<?php endif ?>
<?php if ($this->me->userid) : ?>
var kunena_anonymous_name = "<?php echo JText::_('COM_KUNENA_USERNAME_ANONYMOUS', true) ?>";
<?php endif ?>

rokcommonjs.ready(function(){

	function kunenaSelectUsername(obj, kuser) {
		if (obj.attribute('checked')) {
			rokcommonjs.$('#kauthorname').value(kunena_anonymous_name).attribute('disabled', null);
			rokcommonjs.$('#kanynomous-check-name')[0].style.display = 'block';
		} else {
			rokcommonjs.$('#kanynomous-check-name')[0].style.display = 'none';
			rokcommonjs.$('#kauthorname').value(kuser).attribute('disabled', 'disabled');
		}
	}

	function kunenaCheckPollallowed(catid) {
		if ( pollcategoriesid[catid] !== undefined ) {
			rokcommonjs.$('#kbbcode-poll-button')[0].style.display = 'block';
		} else {
			rokcommonjs.$('#kbbcode-poll-button')[0].style.display = 'none';
		}
	}

	function kunenaCheckAnonymousAllowed(catid) {
		if(rokcommonjs.$('#kanynomous-check') !== null && rokcommonjs.$('#kanonymous') !== null) {
			if ( arrayanynomousbox[catid] !== undefined ) {
				rokcommonjs.$('#kanynomous-check')[0].style.display = 'block';
				rokcommonjs.$('#kanonymous').attribute('checked','checked');
			} else {
				rokcommonjs.$('#kanynomous-check')[0].style.display = 'none';
				kbutton.attribute('checked', null);
			}
		}
		<?php if ($this->me->userid != 0) : ?>
		kunenaSelectUsername(kbutton,kuser);
		<?php endif ?>
	}
	//	for hide or show polls if category is allowed
	if(rokcommonjs.$('#postcatid') !== null) {
		rokcommonjs.$('#postcatid').on('change', function(e) {
			kunenaCheckPollallowed(this.value);
		});
	}

	if(rokcommonjs.$('#kauthorname') !== undefined) {
		var kuser = rokcommonjs.$('#kauthorname').value();
		var kbutton = rokcommonjs.$('#kanonymous');
		<?php if ($this->me->userid != 0) : ?>
		kunenaSelectUsername(kbutton, kuser);
		kbutton.on('click', function(e) {
			kunenaSelectUsername(this, kuser);
		});
		<?php endif ?>
	}
	//	to select if anynomous option is allowed on new topic tab
	if(rokcommonjs.$('#postcatid') !== null) {
		rokcommonjs.$('#postcatid').on('change', function(e) {
			var postcatid = rokcommonjs.$('#postcatid').value();
			kunenaCheckAnonymousAllowed(postcatid);
		});
	}

	if(rokcommonjs.$('#postcatid') !== null) {
		kunenaCheckPollallowed(rokcommonjs.$('#postcatid').value());
		kunenaCheckAnonymousAllowed(rokcommonjs.$('#postcatid').value());
	}
});

<?php
$script = ob_get_contents();
ob_end_clean();

$document = JFactory::getDocument();
$document->addScriptDeclaration( "// <![CDATA[
{$script}
// ]]>");
