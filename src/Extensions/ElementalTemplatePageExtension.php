<?php
namespace Sunnysideup\ElementalTemplate\Extensions;
use SilverStripe\ORM\DataExtension;

use SilverStripe\Core\Config\Config;

class ElementalTemplateExtension extends DataExtension
{

    private $elementalTemplateInfiniteLoopCheck = false;

    /**
     * @return
     */
    protected function onAfterWrite()
    {
        $owner = $owner->getOwner();
        if($this->elementalTemplateInfiniteLoopCheck === false) {
            $this->elementalTemplateInfiniteLoopCheck = true;
            $owner->findOrMakeDefaultElements();
        }
    }

    protected function findOrMakeDefaultElements()
    {
        $owner = $owner->getOwner();
        $write = false;
        if(! $owner->ElementalArea()->Elements()->exists()) {
            $elems = array_filter(
                array_merge(
                    (array) Config::inst()->uninherited($owner->ClassName, 'elemental_template_default_elements_top'),
                    (array) Config::inst()->get($owner->ClassName, 'elemental_template_global_elements'),
                    (array) Config::inst()->uninherited($owner->ClassName, 'elemental_template_default_elements'),
                    (array) Config::inst()->get($owner->ClassName, 'elemental_template_global_elements_bottom'),
                )
            );
            if (empty($elems)) {
                return;
            }
            $area = $owner->ElementalArea();
            if ($area && $area->ID) {
                foreach ($elems as $className) {
                    $elem = call_user_func([$className, 'create']);
                    $elem->Title = 'Default ' . strtolower(Config::inst()->get($className, 'singular_name'));
                    $elem->ParentID = $area->ID;
                    // foreach($elementValues as $field => $value) {
                    //     $elem->$field = $value;
                    // }
                    $elem->writeToStage(Versioned::DRAFT);
                    $write = true;
                }
            }
        }
        if($write) {
            $owner->writeToStage(Versioned::DRAFT);
        }
    }

}
