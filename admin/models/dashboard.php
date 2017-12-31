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
defined('_JEXEC') or die;

jimport('joomla.application.component.modellist');

class SecretaryModelDashboard extends JModelList
{
    
    /**
     * Constructor
     */
    public function __construct($config = array())
	{
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'id', 'a.id',
                'created', 'a.created',
                'extension', 'a.extension',
            );
        }
        parent::__construct($config);
    }
	
    /**
     * Override method session state 
     */
    protected function populateState($ordering = null, $direction = null)
	{
        parent::populateState('a.created', 'desc');
    }
	
    /**
     * @Override
     * Method to get activities
     * 
     * @return array activities
     */
    protected function getListQuery()
	{
        $db		= $this->getDbo();
        $query	= $db->getQuery(true);
		
        $query->select($this->getState('list.select','a.*'));
        $query->from($db->qn('#__secretary_activities','a'));
				
        // Add the list ordering clause.
        $orderCol = $this->state->get('list.ordering');
        $orderDirn = $this->state->get('list.direction');
        if ($orderCol && $orderDirn) {
        	$query->order($db->escape($orderCol . ' ' . $orderDirn));
        }
        
        return $query;
    }
	
    /**
     * Method to prepare activity items
     * 
     * {@inheritDoc}
     * @see \Joomla\CMS\MVC\Model\ListModel::getItems()
     */
    public function getItems()
	{
        $items	= parent::getItems();
        $user	= \Secretary\Joomla::getUser();
		
		if(!empty($items)) {
				
			foreach($items as $x => $activity)
			{
				$extension = Secretary\Application::getSingularSection($activity->extension);
				// Permission Document
				$canSee = false;
				if(((int) $user->id == (int) $activity->created_by) || $user->authorise('core.show.other', 'com_secretary.'.$extension)) {
					$canSee = true;
				}
				
				if(!$canSee) $canSee = $user->authorise('core.show.other','com_secretary.'.$extension.'.'.$activity->id);
				if(!$canSee) {
					unset($items[$x]);
					continue;
				}
			}
		}
		
		return $items;
	}
	
	/**
	 * Method to delete a single activity
	 */
	public function delete(&$pks)
	{ 
		$dispatcher = JEventDispatcher::getInstance();
		$pks = (array) $pks;
		$table = JTable::getInstance('Activities', 'SecretaryTable');
		$user	= \Secretary\Joomla::getUser();
		
		JPluginHelper::importPlugin('content');
		
		foreach ($pks as $i => $pk)
		{
			if ($table->load($pk))
			{
				if ($user->authorise('core.delete','com_secretary'))
				{
					$context = 'com_secretary.' . $this->name;
					$result = $dispatcher->trigger($this->event_before_delete, array($context, $table));
					
					if (in_array(false, $result, true))
					{
						$this->setError($table->getError());
						return false;
					}
					
					if (!$table->delete($pk))
					{
						$this->setError($table->getError());
						return false;
					}
					
					// Trigger the onContentAfterDelete event.
					$dispatcher->trigger($this->event_after_delete, array($context, $table));
				}
				else
				{
					// Prune items that you can't change.
					unset($pks[$i]);
					$error = $this->getError();
					if ($error)
					{
						JLog::add($error, JLog::WARNING, 'jerror');
						return false;
					}
					else
					{
						JLog::add(JText::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'), JLog::WARNING, 'jerror');
						return false;
					}
				}
			}
			else
			{
				$this->setError($table->getError());
				return false;
			}
		}
		
		$this->cleanCache();
		return true;
	}
}