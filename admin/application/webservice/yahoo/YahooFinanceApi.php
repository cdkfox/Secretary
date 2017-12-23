<?php
/**
 * @version     3.0.0
 * @package     com_secretary
 *
 * @author       Fjodor Schaefer (schefa.com)
 * @copyright    Copyright (C) 2015-2017 Fjodor Schaefer. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */

namespace Secretary\Webservice\Yahoo;

use Secretary\Webservice\HttpClient;
use DateInterval;
use DateTime;
use JFactory;

// No direct access
defined('_JEXEC') or die;

class FinanceApi
{
    
    private $timeout;

    public function __construct($timeout = 30)
    {
        $this->timeout = $timeout;
    }

    public function search($searchTerm)
    {
        $url = "http://autoc.finance.yahoo.com/autoc?query=".urlencode($searchTerm)."&region=EU&lang=de-DE";
        try
        {
            $client = new HttpClient($url, $this->timeout);
            $response = $client->execute();
        }
        catch (\Exception $e)
        {
            throw new ApiException("Yahoo Search API is not available.", ApiException::UNAVIALABLE, $e);
        }

        //Remove callback function from response
        $response = preg_replace("#^YAHOO\\.Finance\\.SymbolSuggest\\.ssCallback\\((.*)\\)$#", "$1", $response);

        $decoded = json_decode($response, true);
        if (!isset($decoded['ResultSet']['Result']))
        {
            throw new ApiException("Yahoo Search API returned an invalid result.", ApiException::INVALID_RESULT);
        }
        return $decoded['ResultSet']['Result'];
    }

    public function getHistoricalData($symbol, DateTime $startDate, DateTime $endDate)
    { 
    	$result = array();

    	$newEndDate = clone $endDate;
    	$interval = $startDate->diff($endDate);
    	$totalDays = (int) $interval->format('%R%a'); 
    	
    	if($totalDays <= 250) {
    		$query = "select * from yahoo.finance.historicaldata where startDate='".$startDate->format("Y-m-d")."' and endDate='".$endDate->format("Y-m-d")."' and symbol='".$symbol."'";
    		$tmp = $this->execQuery($query);
	        if(isset($tmp['query']) && $tmp['query']['count'] > 0)
	        	$result = $tmp['query']['results']['quote'];
	        
    	} else {
	    	
    		$totalDays = 0;
	        $newStartDate = $newEndDate->sub(new DateInterval('P365D'));
	        
	        do
	        {
	    		$interval = $startDate->diff($endDate);
	    		$totalDays = (int) $interval->format('%R%a'); 
	    		
		        $query = "select * from yahoo.finance.historicaldata where startDate='".$newStartDate->format("Y-m-d")."' and endDate='".$endDate->format("Y-m-d")."' and symbol='".$symbol."'";
		        $tmp =  $this->execQuery($query) ;
		        if(isset($tmp['query']) && $tmp['query']['count'] > 0)
		        	$result = array_merge( $result, $tmp['query']['results']['quote']);
		        
	        	$endDate = $endDate->sub(new DateInterval('P366D'));
	        	if($startDate <= $newStartDate)
	        	{
		    		$interval = $newStartDate->diff($startDate);
		    		$startDifference = (int) $interval->format('%R%a'); 
		    		
	        		$newStartDate = ($startDifference >= 365) ? $endDate->sub(new DateInterval('P365D')) : $startDate;
	        	}
	        
	        } while ($totalDays > 365);
	        
    	}
    	
        return $result;
    }
    
    public function getQuotes($symbols)
    {
        if (is_string($symbols))
        {
            $symbols = array($symbols);
        }
        $query = "select * from yahoo.finance.quotes where symbol in ('".implode("','", $symbols)."')";
        
        return $this->execQuery($query);
    }
    
    public function getQuotesList($symbols)
    {
        if (is_string($symbols))
        {
            $symbols = array($symbols);
        }
        $query = "select * from yahoo.finance.quoteslist where symbol in ('".implode("','", $symbols)."')";
        return $this->execQuery($query);
    }
    
    private function execQuery($query)
    {

        $application = JFactory::getApplication();
        
        try
        {
            $url = $this->createUrl($query);
            $client = new HttpClient($url, $this->timeout);
            $response = $client->execute();
        }
        catch (\Exception $e)
        {
            $application->enqueueMessage("Yahoo Finance API is not available.". $e, 'error');
        }
         
        $decoded = json_decode($response, true);
        if (!isset($decoded['query']['results']) || count($decoded['query']['results']) === 0)
        {
            $application->enqueueMessage("Yahoo Finance API did not return a result.", 'error'); 
        }
        return $decoded;
    }
    
    private function createUrl($query)
    {
        $params = array(
            'env' => "store://datatables.org/alltableswithkeys",
            'format' => "json", 
            'q' => $query,
        );
        return "http://query.yahooapis.com/v1/public/yql?".http_build_query($params);
    }

}

class ApiException extends \Exception
{
    const UNAVIALABLE = 1;
    const EMPTY_RESULT = 2;
    const INVALID_RESULT = 3;
}

