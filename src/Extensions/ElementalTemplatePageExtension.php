<?php
namespace Sunnysideup\ElementalTemplate\Extensions;
use SilverStripe\ORM\DataExtension;

use SilverStripe\Core\Config\Config;

class ElementalTemplateExtension extends DataExtension
{
    /**
     * little helper to make sure we dont loop forever
     * @var bool
     */
    private $elementalTemplateInfiniteLoopCheck = false;

    protected function onAfterWrite()
    {
        $owner = $owner->getOwner();
        if($this->elementalTemplateInfiniteLoopCheck === false) {
            $this->elementalTemplateInfiniteLoopCheck = true;
            $owner->findOrMakeDefaultElements();
        }
    }

    protected function findOrMakeDefaultElements() : void
    {
        $owner = $owner->getOwner();
        $write = false;
        if(! $owner->ElementalArea()->Elements()->exists()) {
            foreach(
                ['inherited' => 'get', 'uninherited' => 'uninherited']
                as $topVarNameAppendix => $configMethod
            ) {
                $list = (array) array_filter(Config::inst()->$configMethod('elemental_template_'.$topVarNameAppendix)?:[]);
                foreach($list as $areaName => $items) {
                    $area = $owner->$areaName();
                    foreach(['_top', '', '_bottom'] as $innerVarNameAppendix) {
                        $elems = (array) array_filter($item['elements'.$innerVarNameAppendix]?:[]);
                        if(! empty($elems)) {
                            if($this->findOrMakeDefaultElementsInner($area, $items)) {
                                $write = true;
                            }
                        }
                    }
                }
            }
        }
        if($write) {
            $owner->writeToStage(Versioned::DRAFT);
        }
    }

    private function findOrMakeDefaultElementsInner($area, $elems) : bool
    {
        $write = false;
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
        return $write;
    }

}
