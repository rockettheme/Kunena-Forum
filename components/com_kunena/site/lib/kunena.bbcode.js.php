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

/**
 * Deprecated. This file is keep for legacy users which still use blue eagle template.
 *
 * @deprecated 3.0.0
 */
defined( '_JEXEC' ) or die();

$kunena_config = KunenaFactory::getConfig ();

ob_start();
// Now we instanciate the class in an object and implement all the buttons and functions.
?>
rokcommonjs.ready(function() {

<?php if( $this->poll ){ ?>

rokcommonjs.KBBCode.addAction('Poll', function() {
	rokcommonjs.KBBCode.toggleOrSwap("#kbbcode-poll-options");
}, {'id': 'kbbcode-poll-button',
	'class': 'kbbcode-poll-button',
<?php
if (empty($this->category->allow_polls)) {
	echo '\'style\':\'display: none;\',';
} ?>
	'title': '<?php echo JText::_('COM_KUNENA_EDITOR_POLL', true);?>',
	'alt': '<?php echo JText::_('COM_KUNENA_EDITOR_HELPLINE_POLL', true);?>'});

<?php
}
?>

});
<?php
$script = ob_get_contents();
ob_end_clean();

JFactory::getDocument()->addScriptDeclaration($script);
