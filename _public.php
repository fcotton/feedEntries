<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of feedEntries, a plugin for Dotclear 2.
#
# Copyright (c) 2008-2010 Pep and contributors
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------
if (!defined('DC_RC_PATH')) return;

$core->tpl->addBlock('Feed', 				array('feedEntriesTemplates','Feed'));
$core->tpl->addValue('FeedTitle', 			array('feedEntriesTemplates','FeedTitle'));
$core->tpl->addValue('FeedURL', 			array('feedEntriesTemplates','FeedURL'));
$core->tpl->addValue('FeedDescription',		array('feedEntriesTemplates','FeedDescription'));
$core->tpl->addBlock('FeedEntries', 		array('feedEntriesTemplates','FeedEntries'));
$core->tpl->addBlock('FeedEntriesHeader', 	array('feedEntriesTemplates','FeedEntriesHeader'));
$core->tpl->addBlock('FeedEntriesFooter', 	array('feedEntriesTemplates','FeedEntriesFooter'));
$core->tpl->addBlock('FeedEntryIf', 		array('feedEntriesTemplates','FeedEntryIf'));
$core->tpl->addValue('FeedEntryIfFirst', 	array('feedEntriesTemplates','FeedEntryIfFirst'));
$core->tpl->addValue('FeedEntryIfOdd', 		array('feedEntriesTemplates','FeedEntryIfOdd'));
$core->tpl->addValue('FeedEntryTitle', 		array('feedEntriesTemplates','FeedEntryTitle'));
$core->tpl->addValue('FeedEntryURL', 		array('feedEntriesTemplates','FeedEntryURL'));
$core->tpl->addValue('FeedEntryAuthor', 	array('feedEntriesTemplates','FeedEntryAuthor'));
$core->tpl->addValue('FeedEntrySummary', 	array('feedEntriesTemplates','FeedEntrySummary'));
$core->tpl->addValue('FeedEntryExcerpt', 	array('feedEntriesTemplates','FeedEntryExcerpt'));
$core->tpl->addValue('FeedEntryContent', 	array('feedEntriesTemplates','FeedEntryContent'));
$core->tpl->addValue('FeedEntryPubdate', 	array('feedEntriesTemplates','FeedEntryPubdate'));

class feedEntriesTemplates
{
	/**
	 * Start a feed block
	 * <tpl:Feed source="url"></tpl:Feed>
	 *
	 * Attribute(s) :
	 * - source = URL of the feed to fetch and render (required)
	 *
	 */
	public static function Feed($attr,$content)
	{
		if (empty($attr['source'])) {
			return;
		}
		
		if (strpos($attr['source'],'/') === 0) {
			$attr['source'] = http::getHost().$attr['source'];
		}
		
		return
			'<?php'."\n".
			'$_ctx->feed = feedReader::quickParse("'.$attr['source'].'",DC_TPL_CACHE); '."\n".
			'if ($_ctx->feed !== null) : ?>'."\n".
			$content."\n".
			'<?php unset($_ctx->feed); '."\n".
			'endif; ?>'."\n";
	}
	
	/**
	 * Display the title of the current feed
	 * {{tpl:FeedTitle}}
	 *
	 */
	public static function FeedTitle($attr)
	{
		$f = $GLOBALS['core']->tpl->getFilters($attr);
		return '<?php echo '.sprintf($f,'$_ctx->feed->title').'; ?>';
	}
	
	/**
	 * Display the source URL of the current feed
	 * {{tpl:FeedURL}}
	 *
	 */
	public static function FeedURL($attr)
	{
		$f = $GLOBALS['core']->tpl->getFilters($attr);
		return '<?php echo '.sprintf($f,'$_ctx->feed->link').'; ?>';
	}
	
	/**
	 * Display the description of the current feed
	 * {{tpl:FeedDescription}}
	 * 
	 */
	public static function FeedDescription($attr)
	{
		$f = $GLOBALS['core']->tpl->getFilters($attr);
		return '<?php echo '.sprintf($f,'$_ctx->feed->description').'; ?>';
	}
	
	/**
	 * Start the loop to process each entry in the current feed
	 * <tpl:FeedEntries lastn="nb"></tpl:FeedEntries>
	 *
	 * Attribute(s) :
	 * - lastn = Number of entries to show (optional, default to 10)
	 *
	 */
	public static function FeedEntries($attr,$content)
	{
		$lastn = 10;
		if (isset($attr['lastn'])) {
			$lastn = abs((integer) $attr['lastn'])+0;
		}
		
		return
			'<?php'."\n".
			'if (count($_ctx->feed->items)) : '."\n".
			'$nb_feed_items = min(count($_ctx->feed->items),'.$lastn.');'."\n".
			'for ($_ctx->feed_idx = 0; $_ctx->feed_idx < $nb_feed_items; $_ctx->feed_idx++) : ?>'."\n".
			$content."\n".
			'<?php endfor;'."\n".
			'unset($_ctx->feed_idx,$nb_feed_items); '."\n".
			'endif; ?>'."\n";
	}
	
	/**
	 * Display a block at the start of the entries loop
	 * <tpl:FeedEntriesHeader></tpl:FeedEntriesHeader>
	 *
	 */
	public static function FeedEntriesHeader($attr,$content)
	{
		return
		"<?php if (\$_ctx->feed_idx == 0) : ?>".
		$content.
		"<?php endif; ?>";
	}
	
	/**
	 * Display a block at the end of the entries loop
	 * <tpl:FeedEntriesFooter></tpl:FeedEntriesFooter>
	 *
	 */
	public static function FeedEntriesFooter($attr,$content)
	{
		return
		"<?php if (\$_ctx->feed_idx == ($nb_feed_items - 1)) : ?>".
		$content.
		"<?php endif; ?>";
	}

	/**
	 * Display a block only if some conditions are matched.
	 * <tpl:FeedEntryIf></tpl:FeedEntryIf>
	 *
	 * Attribute(s) :
	 * - operator (optional) = logical operator used to compute multiple conditions
	 * - first (optional) 	= test if the current entry is the first in set
	 * - odd (optional) 	= test if the current entry has an odd index in set
	 * - extended (optional) = test if the current entry has a complete (non-empty) "description" property
	 *
	 */
	public static function FeedEntryIf($attr,$content)
	{
		$if = array();
		
		$operator = isset($attr['operator']) ? $GLOBALS['core']->tpl->getOperator($attr['operator']) : '&&' ;
		
		if (isset($attr['first'])) {
			$sign = (boolean) $attr['first'] ? '=' : '!';
			$if[] = '$_ctx->feed_idx '.$sign.'= 0';
		}
		
		if (isset($attr['odd'])) {
			$sign = (boolean) $attr['odd'] ? '=' : '!';
			$if[] = '($_ctx->feed_idx+1)%2 '.$sign.'= 1';
		}
		
		if (isset($attr['extended'])) {
			$sign = (boolean) $attr['extended'] ? '' : '!';
			$if[] = $sign.'dcFeedEntries::isExtended()';
		}

		if (!empty($if)) {
			return '<?php if('.implode(' '.$operator.' ',$if).') : ?>'.$content.'<?php endif; ?>';
		} else {
			return $content;
		}		
	}

	/**
	 * Return a special class if the current entry is the first of the collection
	 * {{tpl:FeedEntryIfFirst}}
	 *
	 */
	public static function FeedEntryIfFirst($attr)
	{
		$ret = isset($attr['return']) ? $attr['return'] : 'first';
		$ret = html::escapeHTML($ret);
		
		return
		'<?php if ($_ctx->feed_idx == 0) { '.
		"echo '".addslashes($ret)."'; } ?>";
	}

	/**
	 * Return a special class if the current entry has an odd index in the collection
	 * {{tpl:FeedEntryIfOdd}}
	 *
	 */
	public static function FeedEntryIfOdd($attr)
	{
		$ret = isset($attr['return']) ? $attr['return'] : 'odd';
		$ret = html::escapeHTML($ret);
		
		return
		'<?php if (($_ctx->feed_idx+1)%2 == 1) { '.
		"echo '".addslashes($ret)."'; } ?>";
	}
	
	/**
	 * Display the title of the current entry
	 * {{tpl:FeedEntryTitle}}
	 *
	 */
	public static function FeedEntryTitle($attr)
	{
		$f = $GLOBALS['core']->tpl->getFilters($attr);
		return '<?php echo '.sprintf($f,'$_ctx->feed->items[$_ctx->feed_idx]->title').'; ?>';
	}

	/**
	 * Display the source URL of the current entry
	 * {{tpl:FeedEntryURL}}
	 *
	 */
	public static function FeedEntryURL($attr)
	{
		$f = $GLOBALS['core']->tpl->getFilters($attr);
		return '<?php echo '.sprintf($f,'$_ctx->feed->items[$_ctx->feed_idx]->link').'; ?>';
	}

	/**
	 * Display the author of the current entry
	 * {{tpl:FeedEntryAuthor}}
	 *
	 */
	public static function FeedEntryAuthor($attr)
	{
		$f = $GLOBALS['core']->tpl->getFilters($attr);
		return '<?php echo '.sprintf($f,'$_ctx->feed->items[$_ctx->feed_idx]->creator').'; ?>';
	}

	/**
	 * Display the summary of the current entry.
	 * {{tpl:FeedEntrySummary}}
	 *
	 */
	public static function FeedEntrySummary($attr)
	{
		$f = $GLOBALS['core']->tpl->getFilters($attr);
		return '<?php echo '.sprintf($f,'$_ctx->feed->items[$_ctx->feed_idx]->description').'; ?>';
	}

	/**
	 * Display an excerpt of the current entry.
	 * {{tpl:FeedEntryExcerpt}}
	 *
	 */
	public static function FeedEntryExcerpt($attr)
	{
		$f = $GLOBALS['core']->tpl->getFilters($attr);
		return '<?php echo '.sprintf($f,'dcFeedEntries::getExcerpt()').'; ?>';
	}
	
	/**
	 * Display the full content of the current entry
	 * {{tpl:FeedEntryContent}}
	 *
	 */
	public static function FeedEntryContent($attr)
	{
		$f = $GLOBALS['core']->tpl->getFilters($attr);
		return '<?php echo '.sprintf($f,'$_ctx->feed->items[$_ctx->feed_idx]->content').'; ?>';
	}

	/**
	 * Display the publication date and/or time of the current entry
	 * {{tpl:FeedEntryPubdate format="strftime"}}
	 *
 	 * Attribute(s) :
 	 * - format = Format string compatible with PHP strftime()
	 *            (optional, default to the date_format setting of the running blog)
	 *
	 */
	public static function FeedEntryPubdate($attr)
	{
		$fmt = $GLOBALS['core']->blog->settings->date_format;
		if (!empty($attr['format'])) {
			$fmt = $attr['format'];
		}
		$f = $GLOBALS['core']->tpl->getFilters($attr);
		return '<?php echo '.sprintf($f,'dt::str("'.$fmt.'",$_ctx->feed->items[$_ctx->feed_idx]->TS,$core->blog->settings->blog_timezone)').'; ?>';
	}
}

class dcFeedEntries
{
	/**
	 * Get an excerpt from a feed entry.
	 * Returns the "description" property as is if available, or a filtered version of the "content" property.
	 * By "filtered" we mean clean from any HTML markup.
	 *
	 * @return	string	The text to be used as an excerpt
	 * 
	 */
	public static function getExcerpt()
	{
		global $core,$_ctx;
		
		if (!$_ctx->feed || is_null($_ctx->feed_idx)) {
			return;
		}
		
		if ($_ctx->feed->items[$_ctx->feed_idx]->description) {
			return $_ctx->feed->items[$_ctx->feed_idx]->description;
		}
		else {
			return html::clean($_ctx->feed->items[$_ctx->feed_idx]->content);
		}
	}
	
	/**
	 * Check if the current feed entry has a non-empty description property
	 *
	 * @return	boolean	True if the "description" property isn't empty, false elsewise
	 */
	public static function isExtended()
	{
		global $core,$_ctx;
		
		if (!$_ctx->feed || is_null($_ctx->feed_idx)) {
			return false;
		}
		
		return ($_ctx->feed->items[$_ctx->feed_idx]->description != '');
	}
}
?>