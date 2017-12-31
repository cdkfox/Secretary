<?php
/**
 * @version     3.2.0
 * @package     com_secretary
 *
 * @author       Fjodor Schaefer (schefa.com)
 * @copyright    Copyright (C) 2015-2017 Fjodor Schaefer. All rights reserved.
 * @license      MIT License
 */
 
// No direct access
defined('JPATH_BASE') or die;


JFormHelper::loadFieldClass('list');

class JFormFieldCurrency extends JFormFieldList
{
	
	protected $type = 'currency';
	
	/**
	 * Method to return a list of all available currencies
	 * 
	 * {@inheritDoc}
	 * @see JFormFieldList::getInput()
	 */
	public function getInput( )
	{
		$options = array();
		
		$items = \Secretary\Database::getObjectList('currencies',['currency',"CONCAT(symbol,' (',title,')') as value"],[],'title ASC'); 
		foreach($items as $message) {
			$options[] = JHtml::_('select.option', $message->currency, $message->value );
		}
	
		$html = '<div class="select-arrow select-arrow-white">'
		    .'<select name="'.$this->name.'" id="'.$this->id.'" class="form-control currency-select">'
            . JHtml::_('select.options', $options, 'value', 'text', $this->value)
            . '</select></div>';
            	
		return $html;
	}
	
}