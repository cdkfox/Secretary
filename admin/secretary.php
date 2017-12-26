<?php
/**
 * @version     3.0.0
 * @package     com_secretary
 *
 * @author       Fjodor Schaefer (schefa.com)
 * @copyright    Copyright (C) 2015-2017 Fjodor Schaefer. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */
 
// No direct access
defined('_JEXEC') or die;
if(!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
if(!defined('SECRETARY_ADMIN_PATH')) define('SECRETARY_ADMIN_PATH', JPATH_ADMINISTRATOR .'/components/com_secretary');
define('SECRETARY_MEDIA_PATH', JURI::root() .'media/secretary');

// Access 
$app	= JFactory::getApplication();
$view	= $app->input->getVar('view');
$format	= $app->input->getVar('format');
$layout	= $app->input->getVar('layout');
$task	= $app->input->getVar('task');

if ($app->isClient('administrator') && !JFactory::getUser()->authorise('core.manage', 'com_secretary'))  {
	throw new Exception(JText::_('JERROR_ALERTNOAUTHOR'));
	return false;
}

// Dependancies
jimport('joomla.application.component.controller');
require_once SECRETARY_ADMIN_PATH .'/application/Secretary.php';
JTable::addIncludePath( SECRETARY_ADMIN_PATH .'/models/tables');
JForm::addFormPath( SECRETARY_ADMIN_PATH .'/models/forms');

$pdf = Secretary\PDF::getInstance();
define('SECRETARY_VERSION', Secretary\Application::getVersion());
define('COM_SECRETARY_PDF', (null !== $pdf->getStrategy()));

// Head
$document = JFactory::getDocument();
if($format != 'raw') { 
    
	JHtml::_('jquery.framework');
	
	$timestamp = '?'.strtotime(date('Y-m-d'));
	$document->addScript(SECRETARY_MEDIA_PATH.'/assets/jquery/jquery-ui.min.js');
	$document->addScript(SECRETARY_MEDIA_PATH.'/js/secretary.js?v='.SECRETARY_VERSION);
	if( $view == 'document' && $layout == 'edit' ) {
	    $document->addScript(SECRETARY_MEDIA_PATH.'/js/secretary.accounting.js?v='.SECRETARY_VERSION);
	    $document->addScript(SECRETARY_MEDIA_PATH.'/js/secretary.document.js?v='.SECRETARY_VERSION);
		$document->addScript(SECRETARY_MEDIA_PATH.'/assets/jquery/jquery.nestable.js');
	}

	if($layout == 'edit') {
		JHtml::_('behavior.formvalidation');
		JHtml::_('behavior.keepalive');
	}
	
	JHTML::_('behavior.modal');
	
	$document->addStyleSheet(SECRETARY_MEDIA_PATH.'/assets/jquery/jquery-ui.css?v='.SECRETARY_VERSION);
	$document->addStyleSheet(SECRETARY_MEDIA_PATH.'/css/secretary.css?v='.SECRETARY_VERSION);
	$document->addStyleSheet(SECRETARY_MEDIA_PATH.'/css/custom.css?v='.SECRETARY_VERSION);
	$document->addStyleSheet(SECRETARY_MEDIA_PATH.'/assets/fontawesome/css/font-awesome.min.css');
	\Secretary\Html::_('layout.templateCssStyle'); 
}

$title = 'Secretary';
if(!empty($view)) $title .= ' - '. JText::_('COM_SECRETARY_'.$view);
$document->setTitle($title);
			
// Display
$controller	= JControllerLegacy::getInstance('Secretary',array('base_path'=> SECRETARY_ADMIN_PATH));
$controller->execute($app->input->get('task'));
$controller->redirect();