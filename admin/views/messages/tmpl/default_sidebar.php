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

?>

<h3 class="documents-sidebar-title"><?php echo JText::_('COM_SECRETARY_MESSAGE_RECENT_TALKS');?></h3>

<div class="secretary-other-talks">
<?php foreach ($this->otherTalks as $i => $item) { ?>

<?php 


$canSee = false;
if(($item->created_by == $this->user->id && $this->canDo->get('core.show.other')) || $item->created_by == $this->userContactId || $this->canDo->get('core.edit'))
    $canSee = true;
    
if(!$canSee) continue;


$type = ''; $icon = '';
if($item->contact_to > 0 || !empty($item->contact_to_alias))
{
    // Contact 2 Contact
    $cba =  (!empty($item->created_by_alias)) ? ("&cba=".$item->created_by_alias) : "";
    $catid = (!empty($item->catid)) ? ("&catid=".$item->catid) : "";
    $link = '<a href="index.php?option=com_secretary&view=messages&layout=talk'.$catid.'&rid='.$item->refer_to.'contact_to='. $item->contact_to . $cba .'">'.
                JText::sprintf('COM_SECRETARY_MESSAGES_CONTACTTOCONTACT', $item->contact_to_alias) .'</a>';
    $type = 'c2contact';
    $icon = '<i class="fa fa-envelope-o"></i>&nbsp;';
    
}
elseif($item->catid > 0)
{
    // Contact 2 Category
    $link = '<a href="index.php?option=com_secretary&view=messages&layout=talk&catid='. (int) $item->catid .'">'. 
                JText::sprintf('COM_SECRETARY_MESSAGES_CONTACTTOCATEGORY', $item->title) .'</a>';
    $type = 'c2category';
    $icon = '<i class="fa fa-comments-o"></i>&nbsp;';
} else {
    
    $title = JText::_('COM_SECRETARY_CORRESPONDENCE') . ' - #'. $item->id;
    if(!empty($item->subject)) $title .= ' - '.$item->subject;
    $link = '<a href="index.php?option=com_secretary&view=message&id='. (int) $item->id.'">'. 
                $title .'</a>';
    $type = 'correspondence';
    $icon = '<i class="fa fa-file-o"></i>&nbsp;';
    
}

if(is_numeric($item->created_by)) {
    $from = Secretary\Database::getJDataResult('users',(int) $item->created_by,'name');
} else {
    $from = $item->created_by;
}
$alias = (!empty($item->created_by_alias)) ? (' hasTooltip" title="'. $item->created_by_alias .'') : "";
?>
<div class="secretary-other-talks-item">
    <div class="list-talks-date"><?php echo JHtml::_('date', $item->created, JText::_('DATE_FORMAT_LC2')); ?></div>
    <div class="list-talks-mid clearfix">
        <div class="list-talks-icon"><?php echo $icon; ?></div>
        <div class="list-talks-from <?php echo $alias; ?>"><?php echo $from; ?></div>
        <div class="list-talks-title"><?php echo $link; ?></div>
    </div>    
</div>            
<?php } ?>
</div>
    