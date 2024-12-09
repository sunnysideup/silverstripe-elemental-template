<?php

namespace Sunnysideup\ElementalTemplate\Extensions;

use SilverStripe\CMS\Model\SiteTreeExtension;
use SilverStripe\Core\Config\Config;
use SilverStripe\Versioned\Versioned;

/**
 * Class \Sunnysideup\ElementalTemplate\Extensions\PageExtension
 *
 * @property Page|PageExtension $owner
 */
class PageExtension extends SiteTreeExtension
{
    /**
     * little helper to make sure we dont loop forever.
     *
     * @var bool
     */
    private $elementalTemplateInfiniteLoopCheck = false;

    public function onAfterWrite()
    {
        $owner = $this->getOwner();
        if (false === $this->elementalTemplateInfiniteLoopCheck) {
            $this->elementalTemplateInfiniteLoopCheck = true;
            $owner->findOrMakeDefaultElements();
        }
    }

    public function findOrMakeDefaultElements(): void
    {
        $owner = $this->getOwner();
        $write = false;
        if ($owner->hasMethod('ElementalArea') && ! $owner->ElementalArea()->Elements()->exists()) {
            foreach (
                array_keys([
                    'inherited' => null,
                    'uninherited' => Config::UNINHERITED,
                ])
                as $topVarNameAppendix
            ) {
                $className = $owner->ClassName;
                $list = (array) array_filter($className::config()->get('elemental_template_' . $topVarNameAppendix, Config::UNINHERITED) ?: []);
                foreach ($list as $areaName => $items) {
                    $area = $owner->{$areaName}();
                    foreach (['_top', '', '_bottom'] as $innerVarNameAppendix) {
                        $elems = (array) array_filter($items['elements' . $innerVarNameAppendix] ?? []);
                        if (! empty($elems)) {
                            if ($this->findOrMakeDefaultElementsInner($area, $elems)) {
                                $write = true;
                            }
                        }
                    }
                }
            }
        }

        if ($write) {
            $owner->writeToStage(Versioned::DRAFT);
        }
    }

    private function findOrMakeDefaultElementsInner($area, $elems): bool
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
