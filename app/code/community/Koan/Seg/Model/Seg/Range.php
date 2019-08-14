<?php

/**
 * @category Koan
 * @package Seg
 * @author Seg <hello@getseg.com, http://getseg.com>
 * @copyright Seg <http://getseg.com>
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Koan_Seg_Model_Seg_Range extends Varien_Object
{
    public function prepare($category)
    {
        if (!$category) {
            Mage::throwException('Category is not valid!');
        }

        if (!$categoryId = $category->getId()) {
            Mage::throwException('Missing Category Id');
        }

        $tagData = array('Tags');

        $lastCatName = $category->getName();
        $lastCategoryAdjust = 1;

        if ($path = $category->getPath()) {
            $path = explode('/', $path);

            for ($i = 2; $i < count($path) - $lastCategoryAdjust; $i++) {

                $cat = Mage::getModel('catalog/category')->load($path[$i]);
                if ($cat && $cat->getIsActive()) {

                    $tagData['Tags'][] = $cat->getName();
                }
            }

            $tagData['Tags'][] = $lastCatName;
        }


        $appliedFilters = Mage::getSingleton('catalog/layer')->getState()->getFilters();

        $tagAttributeCodes = Mage::helper('koan_seg')->getTagAttributeCodes(Mage::app()->getStore()->getId());
        if ($tagAttributeCodes) {
            $tagAttributeCodes = array_map('trim', explode(',', $tagAttributeCodes));
        }

        if ($appliedFilters AND is_array($appliedFilters) AND count($appliedFilters)) {
            foreach ($appliedFilters as $item) {
                $requestVar = $item->getFilter()->getRequestVar();
                if (!in_array($requestVar, $tagAttributeCodes)) {
                    continue;
                }
                $filterValue = $item->getLabel(); // Currently selected value
                $tagData['Tags'][] = $filterValue;
            }

        }

        $this->setData($tagData);
        return $this->toArray(array('Tags'));
    }
}