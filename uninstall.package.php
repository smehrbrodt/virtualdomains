<?php
/**
 * This is the special installer addon created by Andrew Eddie and the team of jXtended.
 * We thank for this cool idea of extending the installation process easily
 * @copyright 2005-2008 New Life in IT Pty Ltd.  All rights reserved.
 */

/**
 * @version $Id$
 * @package    virtualdomains
 * @subpackage Base
 * @author     EasyJoomla {@link http://www.easy-joomla.org Easy-Joomla.org}
 * @author     Michael Liebler {@link http://www.janguo.de}
 * @author     Created on 14-Aug-09
 */

//--No direct access
defined('_JEXEC') or die('=;)');

jimport('joomla.application.helper');

$status = new JObject();
$status->modules = array();
$status->plugins = array();

/***********************************************************************************************
 * ---------------------------------------------------------------------------------------------
 * MODULE REMOVAL SECTION
 * ---------------------------------------------------------------------------------------------
 ***********************************************************************************************/

$modules = &$this->manifest->getElementByPath('modules');
if (is_a($modules, 'JSimpleXMLElement') && count($modules->children()))
{

	foreach ($modules->children() as $module)
	{
		$mname		= $module->attributes('module');
		$mclient	= JApplicationHelper::getClientInfo($module->attributes('client'), true);
		$mposition	= $module->attributes('position');

		//--Set the installation path
		if( ! empty ($mname))
		{
			$this->parent->setPath('extension_root', $mclient->path.'/modules/'.$mname);
		}
		else
		{
			$this->parent->abort(JText::_('Module').' '.JText::_('Install').': '.JText::_('No module file specified'));
			return false;
		}

		/**
		 * ---------------------------------------------------------------------------------------------
		 * Database Processing Section
		 * ---------------------------------------------------------------------------------------------
		 */
		$db = &JFactory::getDBO();

		//--Lets delete all the module copies for the type we are uninstalling
		$query = 'SELECT `id`' .
				' FROM `#__modules`' .
				' WHERE module = '.$db->Quote($mname) .
				' AND client_id = '.(int)$mclient->id;
		$db->setQuery($query);
		$modules = $db->loadResultArray();

		//--Do we have any module copies?
		if (count($modules))
		{
			JArrayHelper::toInteger($modules);
			$modID = implode(',', $modules);
			$query = 'DELETE' .
					' FROM #__modules_menu' .
					' WHERE moduleid IN ('.$modID.')';
			$db->setQuery($query);
			if( ! $db->query())
			{
				JError::raiseWarning(100, JText::_('Module').' '.JText::_('Uninstall').': '.$db->stderr(true));
				$retval = false;
			}
		}

		//--Delete the modules in the #__modules table
		$query = 'DELETE FROM #__modules WHERE module = '.$db->Quote($mname);
		$db->setQuery($query);
		if( ! $db->query())
		{
			JError::raiseWarning(100, JText::_('Plugin').' '.JText::_('Uninstall').': '.$db->stderr(true));
			$retval = false;
		}

		/**
		 * ---------------------------------------------------------------------------------------------
		 * Filesystem Processing Section
		 * ---------------------------------------------------------------------------------------------
		 */

		//--Remove all necessary files
		$element = &$module->getElementByPath('files');
		if (is_a($element, 'JSimpleXMLElement') && count($element->children()))
		{
			$this->parent->removeFiles($element, -1);
		}

		//--Remove all necessary files
		$element = &$module->getElementByPath('media');
		if (is_a($element, 'JSimpleXMLElement') && count($element->children()))
		{
			$this->parent->removeFiles($element, -1);
		}

		$element = &$module->getElementByPath('languages');
		if (is_a($element, 'JSimpleXMLElement') && count($element->children()))
		{
			$this->parent->removeFiles($element, $mclient->id);
		}

		//--Remove the installation folder
		if ( ! JFolder::delete($this->parent->getPath('extension_root')))
		{
		}

		$status->modules[] = array('name'=>$mname,'client'=>$mclient->name);
	}
}

/***********************************************************************************************
 * ---------------------------------------------------------------------------------------------
 * PLUGIN REMOVAL SECTION
 * ---------------------------------------------------------------------------------------------
 ***********************************************************************************************/

$plugins = &$this->manifest->getElementByPath('plugins');
if (is_a($plugins, 'JSimpleXMLElement') && count($plugins->children()))
{
	foreach ($plugins->children() as $plugin)
	{
		$pname		= $plugin->attributes('plugin');
		$pgroup		= $plugin->attributes('group');

		//--Set the installation path
		if ( ! empty($pname) && !empty($pgroup))
		{
			$this->parent->setPath('extension_root', JPATH_ROOT.'/plugins/'.$pgroup);
		}
		else
		{
			$this->parent->abort(JText::_('Plugin').' '.JText::_('Uninstall').': '.JText::_('No plugin file specified'));
			return false;
		}

		/**
		 * ---------------------------------------------------------------------------------------------
		 * Database Processing Section
		 * ---------------------------------------------------------------------------------------------
		 */
		$db = &JFactory::getDBO();

		//--Delete the plugins in the #__plugins table
		$query = 'DELETE FROM #__plugins WHERE element = '.$db->Quote($pname).' AND folder = '.$db->Quote($pgroup);
		$db->setQuery($query);
		if ( ! $db->query())
		{
			JError::raiseWarning(100, JText::_('Plugin').' '.JText::_('Uninstall').': '.$db->stderr(true));
			$retval = false;
		}

		/**
		 * ---------------------------------------------------------------------------------------------
		 * Filesystem Processing Section
		 * ---------------------------------------------------------------------------------------------
		 */

		//--Remove all necessary files
		$element = &$plugin->getElementByPath('files');
		if (is_a($element, 'JSimpleXMLElement') && count($element->children()))
		{
			$this->parent->removeFiles($element, -1);
		}

		$element = &$plugin->getElementByPath('languages');
		if (is_a($element, 'JSimpleXMLElement') && count($element->children()))
		{
			$this->parent->removeFiles($element, 1);
		}

		//--If the folder is empty, let's delete it
		$files = JFolder::files($this->parent->getPath('extension_root'));
		if ( ! count($files))
		{
			JFolder::delete($this->parent->getPath('extension_root'));
		}

		$status->plugins[] = array('name'=>$pname,'group'=>$pgroup);
	}
}

/***********************************************************************************************
 * ---------------------------------------------------------------------------------------------
 * OUTPUT TO SCREEN
 * ---------------------------------------------------------------------------------------------
 ***********************************************************************************************/
 $rows = 0;
?>

<h2>virtualdomains Removal</h2>
<table class="adminlist">
	<thead>
		<tr>
			<th class="title" colspan="2"><?php echo JText::_('Extension'); ?></th>
			<th width="30%"><?php echo JText::_('Status'); ?></th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="3"></td>
		</tr>
	</tfoot>
	<tbody>
		<tr class="row0">
			<td class="key" colspan="2"><?php echo 'virtualdomains '.JText::_('Component'); ?></td>
			<td><img src="images/publish_g.png" alt="OK" /><strong><?php echo JText::_('Removed'); ?></strong></td>
		</tr>
<?php if (count($status->modules)) : ?>
		<tr>
			<th><?php echo JText::_('Module'); ?></th>
			<th><?php echo JText::_('Client'); ?></th>
			<th></th>
		</tr>
	<?php foreach ($status->modules as $module) : ?>
		<tr class="row<?php echo (++ $rows % 2); ?>">
			<td class="key"><?php echo $module['name']; ?></td>
			<td class="key"><?php echo ucfirst($module['client']); ?></td>
			<td><img src="images/publish_g.png" alt="OK" /><strong><?php echo JText::_('Removed'); ?></strong></td>
		</tr>
	<?php endforeach;
endif;
if (count($status->plugins)) : ?>
		<tr>
			<th><?php echo JText::_('Plugin'); ?></th>
			<th><?php echo JText::_('Group'); ?></th>
			<th></th>
		</tr>
	<?php foreach ($status->plugins as $plugin) : ?>
		<tr class="row<?php echo (++ $rows % 2); ?>">
			<td class="key"><?php echo ucfirst($plugin['name']); ?></td>
			<td class="key"><?php echo ucfirst($plugin['group']); ?></td>
			<td><img src="images/publish_g.png" alt="OK" /><strong><?php echo JText::_('Removed'); ?></strong></td>
		</tr>
	<?php endforeach;
endif; ?>
	</tbody>
</table>
