<?php

namespace Moo\HasOneSelector\Form;

use Exception;
use Moo\HasOneSelector\ORM\DataList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\GridField\GridFieldPageCount;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\SS_List;
use SilverStripe\View\Requirements;

/**
 * Class Field provides CMS field to manage selecting/adding/editing object within
 * has_one relation of the current object being edited
 */
class Field extends GridField
{
    /**
     * Name of the list data class
     *
     * @var string
     */
    protected $dataClass;

    /**
     * Instance of data object that contains the has one relation
     *
     * @var DataObject
     */
    protected $owner;

    /**
     * Text to display when no record selected
     *
     * @var string
     */
    protected $emptyString = 'No item selected';

    /**
     * HasOneSelectorField constructor.
     * @param string      $name
     * @param string|null $title
     * @param DataObject  $owner
     * @param string      $dataClass
     */
    public function __construct($name, $title = null, DataObject $owner, $dataClass = DataObject::class)
    {
        // Include styles
        Requirements::css('moo/hasoneselector:client/styles/hasoneselector.css');

        // Initiate grid field configuration based on relation editor
        $config = GridFieldConfig_RelationEditor::create();
        $config->removeComponentsByType(GridFieldToolbarHeader::class);
        $config->removeComponentsByType(GridFieldSortableHeader::class);
        $config->removeComponentsByType(GridFieldFilterHeader::class);
        $config->removeComponentsByType(GridFieldPageCount::class);
        $config->removeComponentsByType(GridFieldPaginator::class);

        // Set the data class of the list
        $this->setDataClass($dataClass);
        // Set the owner data object that contains the has one relation
        $this->setOwner($owner);

        // Instance of data list that manages the grid field data
        $dataList = DataList::create($this);

        // Set empty string based on the data class
        $this->setEmptyString(sprintf('No %s selected', strtolower(singleton($dataClass)->singular_name())));

        parent::__construct($name . 'ID', $title, $dataList, $config);
    }

    /**
     * Set empty string when no record selected
     *
     * @param  string $string
     * @return $this
     */
    public function setEmptyString($string)
    {
        $this->emptyString = $string;

        return $this;
    }

    /**
     * set the name of the data class for current list
     *
     * @param  string $class
     * @return $this
     */
    public function setDataClass($class)
    {
        $this->dataClass = $class;

        return $this;
    }

    /**
     * Get the name of the data class for current list
     *
     * @return string
     */
    public function getDataClass()
    {
        return $this->dataClass;
    }

    /**
     * Get the record of the has one relation for current owner object
     *
     * @return DataObject|null
     * @throws Exception
     */
    public function getRecord()
    {
        return $this->getOwner()->{rtrim($this->getName(), 'ID')}();
    }

    /**
     * Set the record of the has one relation for current owner object
     *
     * @param  DataObject|null $object
     * @return void
     * @throws Exception
     */
    public function setRecord($object)
    {
        $owner      = $this->getOwner();
        $recordName = $this->getName();

        $owner->{$recordName} = is_null($object) ? 0 : $object->ID;
        $owner->write();
    }

    /**
     * Set instance of data object that has the has one relation
     *
     * @param  DataObject $owner
     * @return $this
     */
    public function setOwner(DataObject $owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get instance of data object that has the has one relation
     *
     * @return DataObject
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Get the data source.
     *
     * @return SS_List
     */
    public function getList()
    {
        // Get current record ID
        $id = (int) $this->getOwner()->{$this->getName()};

        // Filter current list to display current record (has one) value
        return $this->list->filter('ID', $id);
    }

    /**
     * Get the data source after applying every {@link GridField_DataManipulator} to it.
     *
     * @return SS_List
     */
    public function getManipulatedList()
    {
        // Call manipulation from parent class to update current record (has one)
        parent::getManipulatedList();

        // Get list of data based on new record
        return $this->getList();
    }

    /**
     * @param  array  $content
     * @return string
     */
    protected function getOptionalTableBody(array $content)
    {
        // Text used by grid field for no items
        $noItemsText = _t('GridField.NoItemsFound', 'No items found');

        // If we have no items text in the body, then replace the text with customised string
        if (strpos($content['body'], $noItemsText) !== false) {
            $content['body'] = str_replace($noItemsText, $this->emptyString, $content['body']);
        }

        return parent::getOptionalTableBody($content);
    }
}
