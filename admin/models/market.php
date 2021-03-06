<?php
/**
 * @version     3.2.0
 * @package     com_secretary
 *
 * @author       Fjodor Schaefer (schefa.com)
 * @copyright    Copyright (C) 2015-2017 Fjodor Schaefer. All rights reserved.
 * @license      MIT License
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 * 
 */
 
// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.modeladmin');

require_once SECRETARY_ADMIN_PATH.'/application/webservice/HttpClient.php';
require_once SECRETARY_ADMIN_PATH.'/application/webservice/yahoo/YahooFinanceApi.php';

class SecretaryModelMarket extends JModelAdmin
{
	
    protected $app;
	protected $text_prefix = 'com_secretary';
	private $extension;
	private static $_item;

	/**
	 * Class constructor
	 * 
	 * @param array $config
	 */
    public function __construct($config = array())
	{
        $this->app          = \Secretary\Joomla::getApplication();
        $this->business     = \Secretary\Application::company();
        $this->extension    = $this->app->input->getCmd('extension');
        parent::__construct($config);
    }
	
    /**
     * {@inheritDoc}
     * @see \Joomla\CMS\MVC\Model\AdminModel::canDelete()
     */
	protected function canDelete($record)
	{
		return \Secretary\Helpers\Access::canDelete($record,'market');
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Joomla\CMS\MVC\Model\BaseDatabaseModel::getTable()
	 */
	public function getTable($type = 'Market', $prefix = 'SecretaryTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Joomla\CMS\MVC\Model\FormModel::getForm()
	 */
	public function getForm($data = array(), $loadData = true)
	{ 
		$form = $this->loadForm('com_secretary.market', 'market', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form)) { return false; }
		return $form;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Joomla\CMS\MVC\Model\FormModel::loadFormData()
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = $this->app->getUserState('com_secretary.edit.market.data', array());

		$catid=	$this->app->input->getInt('catid');
		if(empty($data->catid) && !empty($catid)) {
			$data->catid = $catid;
			$data->category = Secretary\Database::getQuery('folders', $data->catid );
		}
		
		return $data;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Joomla\CMS\MVC\Model\AdminModel::getItem()
	 */
	public function getItem($pk = null)
	{
		if(empty(self::$_item[$pk]) && ($item = parent::getItem($pk)))
		{  
			self::$_item[$pk] = $item;
		}
		
		return self::$_item[$pk];
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Joomla\CMS\MVC\Model\AdminModel::save()
	 */
	public function save($data)
	{
		// Initialise variables; 
	    $user	= \Secretary\Joomla::getUser();
		$table	= $this->getTable();
		$key	= $table->getKeyName();
		$pk		= (!empty($data[$key])) ? $data[$key] : (int)$this->getState($this->getName().'.id');
		
		// Access
		if(!(\Secretary\Helpers\Access::checkAdmin())) {
			if ( !$user->authorise('core.create', 'com_secretary.market') || ($pk > 0 && !$user->authorise('core.edit', 'com_secretary.market') ) )
			{
				throw new Exception(JText::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'));
				return false;
			}
		}
		
		try
		{
			if ($pk > 0) { $table->load($pk); }
 
			// Bind the data.
			if (!$table->bind($data)) { $this->setError($table->getError()); return false; }
			
			// Store the data.
			if (!$table->store()) { $this->setError($table->getError()); return false; }
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());
			return false;
		}

		$pkName = $table->getKeyName();

		if (isset($table->$pkName)) {
			$this->setState($this->getName().'.id', $table->$pkName);
		}

		$this->cleanCache();
		return true;

	}
	
	public function searchStock($symbol = NULL)
	{
	    $client = new Secretary\Webservice\Yahoo\FinanceApi(); 
	    
	    $ret = array();
	    if(!empty($symbol)) {
	        $ret = $client->search($symbol);
	    }
	    
	    return json_encode($ret);
	}


	public function getStockHistorical($symbol)
	{
	    $client = new Secretary\Webservice\Yahoo\FinanceApi();
	
	    $startDate = new DateTime("2016-01-01");
	    $endDate = new DateTime(date("Y-m-d"));
	
	    $data = $client->getHistoricalData($symbol,$startDate,$endDate);
	    
	    if(isset($data['query']['results']['quote']))
	       $items = $data['query']['results']['quote'];
	    else 
	       $items = array();
	    
		return $items;
	} 
	
	public function getStocksQuotes($symbols = array())
	{
		$result = array();
		$client = new Secretary\Webservice\Yahoo\FinanceApi();
        $stockData = $client->getQuotes($symbols);
        $stocksList = $stockData['query']['results']['quote'];
        if(!empty($stocksList)) {
        	if(isset($stocksList['symbol'])) {
        		$result = $stocksList;
        	} else {
	        	foreach($stocksList as $k => $val) { 
	        		$result[$val['symbol']] = $val;
	        	}
        	}
        	
        }
        
        return $result;
	}

	public function addStock($json = array())
	{
		// Initialise variables; 
	    $user	= \Secretary\Joomla::getUser();
		
		// Access
		if(!(\Secretary\Helpers\Access::checkAdmin())) {
			if ( !$user->authorise('core.create', 'com_secretary.market') || ($pk > 0 && !$user->authorise('core.edit', 'com_secretary.market') ) )
			{
				throw new Exception(JText::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'));
				return false;
			}
		}
		
        $db		= \Secretary\Database::getDBO();
        $query	= $db->getQuery(true);
        
        $stockData  = $this->getStocksQuotes($json['symbol']);
        $price      = (!empty($stockData)) ? $stockData['LastTradePriceOnly'] : 0;

        $columns = array('symbol','catid','name','exch','exchType','ek_price','quantity','created');
        $values = array(
            $db->quote($json['symbol']),
            intval($json['catid']),
            $db->quote($json['name']),
            $db->quote($json['exchDisp']),
            $db->quote($json['type']),
            floatval($price),
            intval($json['quantity']),
            $db->quote(date('Y-m-d'))
        );
        
        $query->insert($db->qn('#__secretary_markets'))
            ->columns($db->qn($columns))
            ->values(implode(',', $values));
        $db->setQuery($query);
        $result = $db->execute();

        return $result;
	}
	
}