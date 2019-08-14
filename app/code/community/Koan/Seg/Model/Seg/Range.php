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

        $categoryData = array('Categories');

        $lastCatName = $category->getName();
        $lastCategoryAdjust = 1;

        if ($path = $category->getPath()) {
            $path = explode('/', $path);

            for ($i = 2; $i < count($path) - $lastCategoryAdjust; $i++) {

                $cat = Mage::getModel('catalog/category')->load($path[$i]);
                if ($cat && $cat->getIsActive()) {

                    $categoryData['Categories'][] = $cat->getName();
                }
            }

            $categoryData['Categories'][] = $lastCatName;
        }

        $this->setData($categoryData);
        return $this->toArray(array('Categories'));
    }
}