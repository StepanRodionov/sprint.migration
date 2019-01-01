<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Helper;

class IblockHelper extends Helper
{

    public function __construct() {
        $this->checkModules(array('iblock'));
    }

    public function getIblockTypeIfExists($typeId) {
        $item = $this->getIblockType($typeId);
        if ($item && isset($item['ID'])) {
            return $item;
        }

        $this->throwException(__METHOD__, "iblock type not found");
    }


    public function getIblockTypeIdIfExists($typeId) {
        $item = $this->getIblockType($typeId);
        if ($item && isset($item['ID'])) {
            return $item['ID'];
        }

        $this->throwException(__METHOD__, "iblock type id not found");
    }


    public function getIblockIfExists($code, $typeId = '') {
        $item = $this->getIblock($code, $typeId);
        if ($item && isset($item['ID'])) {
            return $item;
        }

        $this->throwException(__METHOD__, "iblock not found");
    }

    public function getIblockIdIfExists($code, $typeId = '') {
        $item = $this->getIblock($code, $typeId);
        if ($item && isset($item['ID'])) {
            return $item['ID'];
        }

        $this->throwException(__METHOD__, "iblock id not found");
    }

    public function getIblockType($typeId) {
        /** @compatibility filter or $typeId */
        $filter = is_array($typeId) ? $typeId : array(
            '=ID' => $typeId
        );

        $filter['CHECK_PERMISSIONS'] = 'N';
        $item = \CIBlockType::GetList(array('SORT' => 'ASC'), $filter)->Fetch();

        if ($item) {
            $item['LANG'] = $this->getIblockTypeLangs($item['ID']);
        }

        return $item;
    }

    public function getIblockTypeId($typeId) {
        $iblockType = $this->getIblockType($typeId);
        return ($iblockType && isset($iblockType['ID'])) ? $iblockType['ID'] : 0;
    }

    public function getIblockTypes($filter = array()) {
        $filter['CHECK_PERMISSIONS'] = 'N';
        $dbres = \CIBlockType::GetList(array('SORT' => 'ASC'), $filter);

        $list = array();
        while ($item = $dbres->Fetch()) {
            $item['LANG'] = $this->getIblockTypeLangs($item['ID']);
            $list[] = $item;
        }
        return $list;
    }

    public function addIblockTypeIfNotExists($fields = array()) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('ID'));

        $iblockType = $this->getIblockType($fields['ID']);
        if ($iblockType) {
            return $iblockType['ID'];
        }

        return $this->addIblockType($fields);
    }

    public function addIblockType($fields = array()) {
        $default = Array(
            'ID' => '',
            'SECTIONS' => 'Y',
            'IN_RSS' => 'N',
            'SORT' => 100,
            'LANG' => Array(
                'ru' => Array(
                    'NAME' => 'Catalog',
                    'SECTION_NAME' => 'Sections',
                    'ELEMENT_NAME' => 'Elements'
                ),
                'en' => Array(
                    'NAME' => 'Catalog',
                    'SECTION_NAME' => 'Sections',
                    'ELEMENT_NAME' => 'Elements'
                ),
            )
        );

        $fields = array_replace_recursive($default, $fields);

        $ib = new \CIBlockType;
        if ($ib->Add($fields)) {
            return $fields['ID'];
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    public function updateIblockType($iblockTypeId, $fields = array()) {
        $ib = new \CIBlockType;
        if ($ib->Update($iblockTypeId, $fields)) {
            return $iblockTypeId;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    public function deleteIblockTypeIfExists($typeId) {
        $iblockType = $this->getIblockType($typeId);
        if (!$iblockType) {
            return false;
        }

        return $this->deleteIblockType($iblockType['ID']);

    }

    public function deleteIblockType($typeId) {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        if (\CIBlockType::Delete($typeId)) {
            return true;
        }

        $this->throwException(__METHOD__, 'Could not delete iblock type %s', $typeId);
    }

    public function getIblock($code, $typeId = '') {
        /** @compatibility filter or code */
        $filter = is_array($code) ? $code : array(
            '=CODE' => $code
        );

        if (!empty($typeId)) {
            $filter['=TYPE'] = $typeId;
        }

        $filter['CHECK_PERMISSIONS'] = 'N';

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */

        $item = \CIBlock::GetList(array('SORT' => 'ASC'), $filter)->Fetch();
        return $this->prepareIblock($item);
    }

    protected function prepareIblock($item) {
        if (empty($item['ID'])) {
            return $item;
        }
        $item['LID'] = $this->getIblockSites($item['ID']);
        return $item;
    }

    public function getIblockSites($iblockId) {
        $dbres = \CIBlock::GetSite($iblockId);
        return $this->fetchAll($dbres,false,'LID');
    }

    public function getIblockId($code, $typeId = '') {
        $iblock = $this->getIblock($code, $typeId);
        return ($iblock && isset($iblock['ID'])) ? $iblock['ID'] : 0;
    }

    public function getIblocks($filter = array()) {
        $filter['CHECK_PERMISSIONS'] = 'N';

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbres = \CIBlock::GetList(array('SORT' => 'ASC'), $filter);
        $list = array();
        while ($item = $dbres->Fetch()) {
            $list[] = $this->prepareIblock($item);
        }
        return $list;
    }

    public function addIblockIfNotExists($fields = array()) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('CODE', 'IBLOCK_TYPE_ID', 'LID'));

        $typeId = false;
        if (!empty($fields['IBLOCK_TYPE_ID'])) {
            $typeId = $fields['IBLOCK_TYPE_ID'];
        }

        $iblock = $this->getIblock($fields['CODE'], $typeId);
        if ($iblock) {
            return $iblock['ID'];
        }

        return $this->addIblock($fields);
    }

    public function addIblock($fields) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('CODE', 'IBLOCK_TYPE_ID', 'LID'));

        $default = array(
            'ACTIVE' => 'Y',
            'NAME' => '',
            'CODE' => '',
            'LIST_PAGE_URL' => '',
            'DETAIL_PAGE_URL' => '',
            'SECTION_PAGE_URL' => '',
            'IBLOCK_TYPE_ID' => 'main',
            'LID' => array('s1'),
            'SORT' => 500,
            'GROUP_ID' => array('2' => 'R'),
            'VERSION' => 2,
            'BIZPROC' => 'N',
            'WORKFLOW' => 'N',
            'INDEX_ELEMENT' => 'N',
            'INDEX_SECTION' => 'N'
        );

        $fields = array_replace_recursive($default, $fields);

        $ib = new \CIBlock;
        $iblockId = $ib->Add($fields);

        if ($iblockId) {
            return $iblockId;
        }
        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    public function updateIblock($iblockId, $fields = array()) {
        $ib = new \CIBlock;
        if ($ib->Update($iblockId, $fields)) {
            return $iblockId;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);

    }

    public function updateIblockIfExists($code, $fields = array()) {
        $iblock = $this->getIblock($code);
        if (!$iblock) {
            return false;
        }
        return $this->updateIblock($iblock['ID'], $fields);
    }

    public function deleteIblockIfExists($code, $typeId = '') {
        $iblock = $this->getIblock($code, $typeId);
        if (!$iblock) {
            return false;
        }
        return $this->deleteIblock($iblock['ID']);
    }

    public function deleteIblock($iblockId) {
        if (\CIBlock::Delete($iblockId)) {
            return true;
        }
        $this->throwException(__METHOD__, 'Could not delete iblock %s', $iblockId);
    }

    public function getIblockFields($iblockId) {
        return \CIBlock::GetFields($iblockId);
    }

    public function getProperty($iblockId, $code) {
        /** @compatibility filter or code */
        $filter = is_array($code) ? $code : array(
            'CODE' => $code
        );

        $filter['IBLOCK_ID'] = $iblockId;
        $filter['CHECK_PERMISSIONS'] = 'N';
        /* do not use =CODE in filter */
        $property = \CIBlockProperty::GetList(array('SORT' => 'ASC'), $filter)->Fetch();
        return $this->prepareProperty($property);
    }

    protected function prepareProperty($property) {
        if ($property && $property['PROPERTY_TYPE'] == 'L' && $property['IBLOCK_ID'] && $property['ID']) {
            $property['VALUES'] = $this->getPropertyEnums(array(
                'IBLOCK_ID' => $property['IBLOCK_ID'],
                'PROPERTY_ID' => $property['ID'],
            ));
        }
        return $property;
    }

    public function getPropertyEnums($filter = array()) {
        $result = array();

        $dbres = \CIBlockPropertyEnum::GetList(array("SORT" => "ASC", "VALUE" => "ASC"), $filter);
        while ($item = $dbres->Fetch()) {
            $result[] = $item;
        }
        return $result;
    }

    public function getPropertyEnumValues($iblockId, $propertyId) {
        return $this->getPropertyEnums(array(
            'IBLOCK_ID' => $iblockId,
            'PROPERTY_ID' => $propertyId,
        ));
    }

    public function getPropertyId($iblockId, $code) {
        $item = $this->getProperty($iblockId, $code);
        return ($item && isset($item['ID'])) ? $item['ID'] : 0;
    }

    public function getProperties($iblockId, $filter = array()) {
        $filter['IBLOCK_ID'] = $iblockId;
        $filter['CHECK_PERMISSIONS'] = 'N';

        $filterIds = false;
        if (isset($filter['ID']) && is_array($filter['ID'])) {
            $filterIds = $filter['ID'];
            unset($filter['ID']);
        }

        $dbres = \CIBlockProperty::GetList(array('SORT' => 'ASC'), $filter);

        $result = array();

        while ($property = $dbres->Fetch()) {
            if ($filterIds) {
                if (in_array($property['ID'], $filterIds)) {
                    $result[] = $this->prepareProperty($property);
                }
            } else {
                $result[] = $this->prepareProperty($property);
            }
        }
        return $result;
    }

    public function addPropertyIfNotExists($iblockId, $fields) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('CODE'));

        $property = $this->getProperty($iblockId, $fields['CODE']);
        if ($property) {
            return $property['ID'];
        }

        return $this->addProperty($iblockId, $fields);

    }

    public function addProperty($iblockId, $fields) {

        $default = array(
            'NAME' => '',
            'ACTIVE' => 'Y',
            'SORT' => '500',
            'CODE' => '',
            'PROPERTY_TYPE' => 'S',
            'USER_TYPE' => '',
            'ROW_COUNT' => '1',
            'COL_COUNT' => '30',
            'LIST_TYPE' => 'L',
            'MULTIPLE' => 'N',
            'IS_REQUIRED' => 'N',
            'FILTRABLE' => 'Y',
            'LINK_IBLOCK_ID' => 0
        );

        if (!empty($fields['VALUES'])) {
            $default['PROPERTY_TYPE'] = 'L';
        }

        if (!empty($fields['LINK_IBLOCK_ID'])) {
            $default['PROPERTY_TYPE'] = 'E';
        }

        $fields = array_replace_recursive($default, $fields);

        if (false !== strpos($fields['PROPERTY_TYPE'], ':')) {
            list($ptype, $utype) = explode(':', $fields['PROPERTY_TYPE']);
            $fields['PROPERTY_TYPE'] = $ptype;
            $fields['USER_TYPE'] = $utype;
        }

        if (false !== strpos($fields['LINK_IBLOCK_ID'], ':')) {
            list($ibtype, $ibcode) = explode(':', $fields['LINK_IBLOCK_ID']);
            $fields['LINK_IBLOCK_ID'] = $this->getIblockId($ibcode, $ibtype);
        }

        $fields['IBLOCK_ID'] = $iblockId;

        $ib = new \CIBlockProperty;
        $propertyId = $ib->Add($fields);

        if ($propertyId) {
            return $propertyId;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    public function deletePropertyIfExists($iblockId, $code) {
        $property = $this->getProperty($iblockId, $code);
        if (!$property) {
            return false;
        }

        return $this->deletePropertyById($property['ID']);

    }

    public function deletePropertyById($propertyId) {
        $ib = new \CIBlockProperty;
        if ($ib->Delete($propertyId)) {
            return true;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    public function updatePropertyIfExists($iblockId, $code, $fields) {
        $property = $this->getProperty($iblockId, $code);
        if (!$property) {
            return false;
        }
        return $this->updatePropertyById($property['ID'], $fields);
    }

    public function updatePropertyById($propertyId, $fields) {
        if (!empty($fields['VALUES']) && !isset($fields['PROPERTY_TYPE'])) {
            $fields['PROPERTY_TYPE'] = 'L';
        }

        if (!empty($fields['LINK_IBLOCK_ID']) && !isset($fields['PROPERTY_TYPE'])) {
            $fields['PROPERTY_TYPE'] = 'E';
        }

        if (false !== strpos($fields['PROPERTY_TYPE'], ':')) {
            list($ptype, $utype) = explode(':', $fields['PROPERTY_TYPE']);
            $fields['PROPERTY_TYPE'] = $ptype;
            $fields['USER_TYPE'] = $utype;
        }

        if (false !== strpos($fields['LINK_IBLOCK_ID'], ':')) {
            list($ibtype, $ibcode) = explode(':', $fields['LINK_IBLOCK_ID']);
            $fields['LINK_IBLOCK_ID'] = $this->getIblockId($ibcode, $ibtype);
        }

        if (isset($fields['VALUES']) && is_array($fields['VALUES'])) {
            $existsEnums = $this->getPropertyEnums(array(
                'PROPERTY_ID' => $propertyId,
            ));

            $newValues = array();
            foreach ($fields['VALUES'] as $index => $item) {
                foreach ($existsEnums as $existsEnum) {
                    if ($existsEnum['XML_ID'] == $item['XML_ID']) {
                        $item['ID'] = $existsEnum['ID'];
                        break;
                    }
                }

                if (!empty($item['ID'])) {
                    $newValues[$item['ID']] = $item;
                } else {
                    $newValues['n' . $index] = $item;
                }

            }

            $fields['VALUES'] = $newValues;
        }


        $ib = new \CIBlockProperty();
        if ($ib->Update($propertyId, $fields)) {
            return $propertyId;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    public function getElement($iblockId, $code) {
        /** @compatibility filter or code */
        $filter = is_array($code) ? $code : array(
            '=CODE' => $code
        );

        $filter['IBLOCK_ID'] = $iblockId;
        $filter['CHECK_PERMISSIONS'] = 'N';

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        return \CIBlockElement::GetList(array(
            'SORT' => 'ASC'
        ), $filter, false, array(
            'nTopCount' => 1
        ), array(
            'ID',
            'IBLOCK_ID',
            'NAME',
            'CODE',
        ))->Fetch();
    }

    public function getElementId($iblockId, $code) {
        $item = $this->getElement($iblockId, $code);
        return ($item && isset($item['ID'])) ? $item['ID'] : 0;
    }

    public function getElements($iblockId, $filter = array(), $select = array()) {
        $filter['IBLOCK_ID'] = $iblockId;
        $filter['CHECK_PERMISSIONS'] = 'N';

        $select = array_merge(array(
            'ID',
            'IBLOCK_ID',
            'NAME',
            'CODE',
        ), $select);

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbres = \CIBlockElement::GetList(array(
            'ID' => 'ASC'
        ), $filter, false, false, $select);

        $list = array();
        while ($item = $dbres->Fetch()) {
            $list[] = $item;
        }
        return $list;
    }

    public function addElementIfNotExists($iblockId, $fields, $props = array()) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('CODE'));

        $item = $this->getElement($iblockId, $fields['CODE']);
        if ($item) {
            return $item['ID'];
        }

        return $this->addElement($iblockId, $fields, $props);
    }

    public function addElement($iblockId, $fields = array(), $props = array()) {
        $default = array(
            "NAME" => "element",
            "IBLOCK_SECTION_ID" => false,
            "ACTIVE" => "Y",
            "PREVIEW_TEXT" => "",
            "DETAIL_TEXT" => "",
        );

        $fields = array_replace_recursive($default, $fields);
        $fields["IBLOCK_ID"] = $iblockId;

        if (!empty($props)) {
            $fields['PROPERTY_VALUES'] = $props;
        }

        $ib = new \CIBlockElement;
        $id = $ib->Add($fields);

        if ($id) {
            return $id;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    public function updateElementIfExists($iblockId, $fields = array(), $props = array()) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('CODE'));

        $item = $this->getElement($iblockId, $fields['CODE']);
        if (!$item) {
            return false;
        }

        $fields['IBLOCK_ID'] = $iblockId;
        unset($fields['CODE']);

        return $this->updateElement($item['ID'], $fields, $props);
    }

    public function updateElement($elementId, $fields = array(), $props = array()) {
        $iblockId = !empty($fields['IBLOCK_ID']) ? $fields['IBLOCK_ID'] : false;
        unset($fields['IBLOCK_ID']);

        if (!empty($fields)) {
            $ib = new \CIBlockElement;
            if (!$ib->Update($elementId, $fields)) {
                $this->throwException(__METHOD__, $ib->LAST_ERROR);
            }
        }

        if (!empty($props)) {
            \CIBlockElement::SetPropertyValuesEx($elementId, $iblockId, $props);
        }

        return $elementId;
    }

    public function deleteElementIfExists($iblockId, $code) {
        $item = $this->getElement($iblockId, $code);

        if (!$item) {
            return false;
        }

        return $this->deleteElement($item['ID']);
    }

    public function deleteElement($elementId) {
        $ib = new \CIBlockElement;
        if ($ib->Delete($elementId)) {
            return true;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    public function getSection($iblockId, $code) {
        /** @compatibility filter or code */
        $filter = is_array($code) ? $code : array(
            '=CODE' => $code
        );

        $filter['IBLOCK_ID'] = $iblockId;
        $filter['CHECK_PERMISSIONS'] = 'N';

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        return \CIBlockSection::GetList(array(
            'ID' => 'ASC'
        ), $filter, false, array(
            'ID',
            'IBLOCK_ID',
            'NAME',
            'CODE',
        ))->Fetch();
    }

    public function getSectionId($iblockId, $code) {
        $item = $this->getSection($iblockId, $code);
        return ($item && isset($item['ID'])) ? $item['ID'] : 0;
    }

    public function getSections($iblockId, $filter = array()) {
        $filter['IBLOCK_ID'] = $iblockId;
        $filter['CHECK_PERMISSIONS'] = 'N';

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbres = \CIBlockSection::GetList(array(
            'SORT' => 'ASC'
        ), $filter, false, array(
            'ID',
            'IBLOCK_ID',
            'NAME',
            'CODE',
        ));

        $list = array();
        while ($item = $dbres->Fetch()) {
            $list[] = $item;
        }
        return $list;
    }

    public function addSectionIfNotExists($iblockId, $fields) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('CODE'));

        $item = $this->getSection($iblockId, $fields['CODE']);
        if ($item) {
            return $item['ID'];
        }

        return $this->addSection($iblockId, $fields);

    }

    public function addSection($iblockId, $fields = array()) {
        $default = array(
            "ACTIVE" => "Y",
            "IBLOCK_SECTION_ID" => false,
            "NAME" => 'section',
            "CODE" => '',
            "SORT" => 100,
            "PICTURE" => false,
            "DESCRIPTION" => '',
            "DESCRIPTION_TYPE" => 'text'
        );

        $fields = array_replace_recursive($default, $fields);
        $fields["IBLOCK_ID"] = $iblockId;

        $ib = new \CIBlockSection;
        $id = $ib->Add($fields);

        if ($id) {
            return $id;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    public function updateSectionIfExists($iblockId, $fields) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('CODE'));

        $item = $this->getSection($iblockId, $fields['CODE']);
        if (!$item) {
            return false;
        }

        unset($fields['CODE']);

        return $this->updateSection($item['ID'], $fields);

    }

    public function updateSection($sectionId, $fields) {
        $ib = new \CIBlockSection;
        if ($ib->Update($sectionId, $fields)) {
            return $sectionId;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    public function deleteSectionIfExists($iblockId, $code) {
        $item = $this->getSection($iblockId, $code);
        if (!$item) {
            return false;
        }

        return $this->deleteSection($item['ID']);

    }

    public function deleteSection($sectionId) {
        $ib = new \CIBlockSection;
        if ($ib->Delete($sectionId)) {
            return true;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    public function getIblockTypeLangs($typeId) {
        $result = array();
        $dbres = \CLanguage::GetList($lby = "sort", $lorder = "asc");
        while ($item = $dbres->GetNext()) {
            $values = \CIBlockType::GetByIDLang($typeId, $item['LID'], false);
            if (!empty($values)) {
                $result[$item['LID']] = array(
                    'NAME' => $values['NAME'],
                    'SECTION_NAME' => $values['SECTION_NAME'],
                    'ELEMENT_NAME' => $values['ELEMENT_NAME']
                );
            }
        }
        return $result;
    }


    //version 2

    public function saveIblockType($fields = array()) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('ID'));

        $exists = $this->getIblockType($fields['ID']);
        $exportExists = $this->prepareExportIblockType($exists);
        $fields = $this->prepareExportIblockType($fields);

        if (empty($exists)) {
            $ok = ($this->testMode) ? true : $this->addIblockType($fields);
            $this->outNoticeIf($ok, 'Тип инфоблока %s: добавлен', $fields['ID']);
            return $ok;
        }


        if ($exportExists != $fields) {
            $ok = ($this->testMode) ? true : $this->updateIblockType($exists['ID'], $fields);
            $this->outNoticeIf($ok, 'Тип инфоблока %s: обновлен', $fields['ID']);
            return $ok;
        }

        $ok = ($this->testMode) ? true : $fields['ID'];
        $this->outIf($ok, 'Тип инфоблока %s: совпадает', $fields['ID']);
        return $ok;
    }

    public function saveIblock($fields = array()) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('CODE', 'IBLOCK_TYPE_ID', 'LID'));

        $exists = $this->getIblock($fields['CODE'], $fields['IBLOCK_TYPE_ID']);
        $exportExists = $this->prepareExportIblock($exists);
        $fields = $this->prepareExportIblock($fields);

        if (empty($exists)) {
            $ok = ($this->testMode) ? true : $this->addIblock($fields);
            $this->outNoticeIf($ok, 'Инфоблок %s: добавлен', $fields['CODE']);
            return $ok;
        }

        if ($exportExists != $fields) {
            $ok = ($this->testMode) ? true : $this->updateIblock($exists['ID'], $fields);
            $this->outNoticeIf($ok, 'Инфоблок %s: обновлен', $fields['CODE']);
            return $ok;
        }

        $ok = ($this->testMode) ? true : $exists['ID'];
        $this->outIf($ok, 'Инфоблок %s: совпадает', $fields['CODE']);
        return $ok;
    }

    public function saveIblockFields($iblockId, $fields = array()) {
        $exists = \CIBlock::GetFields($iblockId);

        $exportExists = $this->prepareExportIblockFields($exists);
        $fields = $this->prepareExportIblockFields($fields);

        $fields = array_replace_recursive($exportExists, $fields);

        if (empty($exists)) {
            $ok = ($this->testMode) ? true : $this->updateIblockFields($iblockId, $fields);
            $this->outNoticeIf($ok, 'Инфоблок %s: поля добавлены', $iblockId);
            return $ok;
        }

        if ($exportExists != $fields) {
            $ok = ($this->testMode) ? true : $this->updateIblockFields($iblockId, $fields);
            $this->outNoticeIf($ok, 'Инфоблок %s: поля обновлены', $iblockId);
            return $ok;
        }

        $this->out('Инфоблок %s: поля совпадают', $iblockId);
        return true;
    }

    public function saveProperty($iblockId, $fields) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('CODE'));

        $exists = $this->getProperty($iblockId, $fields['CODE']);
        $exportExists = $this->prepareExportProperty($exists);
        $fields = $this->prepareExportProperty($fields);

        if (empty($exists)) {
            $ok = ($this->testMode) ? true : $this->addProperty($iblockId, $fields);
            $this->outNoticeIf($ok, 'Инфоблок %s: свойство %s добавлено', $iblockId, $fields['CODE']);
            return $ok;
        }

        if ($exportExists != $fields) {
            $ok = ($this->testMode) ? true : $this->updatePropertyById($exists['ID'], $fields);
            $this->outNoticeIf($ok, 'Инфоблок %s: свойство %s обновлено', $iblockId, $fields['CODE']);
            return $ok;
        }

        $ok = ($this->testMode) ? true : $exists['ID'];
        $this->outIf($ok, 'Инфоблок %s: свойство %s совпадает', $iblockId, $fields['CODE']);
        return $ok;
    }

    //exports

    public function exportIblockType($typeId) {
        return $this->prepareExportIblockType(
            $this->getIblockType($typeId)
        );
    }

    protected function prepareExportIblockType($item) {
        if (empty($item)) {
            return $item;
        }

        return $item;
    }

    public function exportIblock($iblockId) {
        $export = $this->prepareExportIblock(
            $this->getIblock(array('ID' => $iblockId))
        );

        if (!empty($export['CODE'])) {
            return $export;
        }

        $this->throwException(__METHOD__, 'code not found');
    }

    public function exportIblocks($filter = array()) {
        $exports = array();
        $items = $this->getIblocks($filter);
        foreach ($items as $item) {
            if (!empty($item['CODE'])) {
                $exports[] = $this->prepareExportIblock($item);
            }
        }
        return $exports;
    }

    protected function prepareExportIblockFields($fields) {
        if (empty($fields)) {
            return $fields;
        }

        $exportFields = array();
        foreach ($fields as $code => $field) {
            if ($field["VISIBLE"] == "N" || preg_match("/^(SECTION_|LOG_)/", $code)) {
                continue;
            }
            $exportFields[$code] = $field;
        }

        return $exportFields;
    }

    public function exportIblockFields($iblockId) {
        return $this->prepareExportIblockFields(
            $this->getIblockFields($iblockId)
        );
    }

    protected function prepareExportIblock($iblock) {
        if (empty($iblock)) {
            return $iblock;
        }

        unset($iblock['ID']);
        unset($iblock['TIMESTAMP_X']);
        unset($iblock['TMP_ID']);

        return $iblock;
    }

    protected function prepareExportProperty($prop) {
        if (empty($prop)) {
            return $prop;
        }

        unset($prop['ID']);
        unset($prop['IBLOCK_ID']);
        unset($prop['TIMESTAMP_X']);
        unset($prop['TMP_ID']);

        if (!empty($prop['VALUES']) && is_array($prop['VALUES'])) {
            $exportValues = array();

            foreach ($prop['VALUES'] as $item) {
                unset($item['ID']);
                unset($item['TMP_ID']);
                unset($item['PROPERTY_ID']);
                unset($item['EXTERNAL_ID']);
                unset($item['PROPERTY_NAME']);
                unset($item['PROPERTY_CODE']);
                unset($item['PROPERTY_SORT']);
                $exportValues[] = $item;
            }

            $prop['VALUES'] = $exportValues;
        }

        if (!empty($prop['LINK_IBLOCK_ID'])) {
            $linked = $this->getIblock(array(
                'ID' => $prop['LINK_IBLOCK_ID']
            ));

            if (!empty($linked['CODE'])) {
                $prop['LINK_IBLOCK_ID'] = $linked['IBLOCK_TYPE_ID'] . ':' . $linked['CODE'];
            }
        }

        return $prop;
    }

    public function exportProperty($iblockId, $code = false) {
        $export = $this->prepareExportProperty(
            $this->getProperty($iblockId, $code)
        );

        if (!empty($export['CODE'])) {
            return $export;
        }

        $this->throwException(__METHOD__, 'code not found');
    }

    public function exportProperties($iblockId, $filter = array()) {
        $exports = array();
        $items = $this->getProperties($iblockId, $filter);
        foreach ($items as $item) {
            if (!empty($item['CODE'])) {
                $exports[] = $this->prepareExportProperty($item);
            }
        }
        return $exports;
    }

    /* @deprecated */
    public function deleteProperty($iblockId, $code) {
        return $this->deletePropertyIfExists($iblockId, $code);
    }

    /* @deprecated */
    public function updateProperty($iblockId, $code, $fields) {
        return $this->updatePropertyIfExists($iblockId, $code, $fields);
    }

    /** @deprecated */
    public function mergeIblockFields($iblockId, $fields) {
        $this->saveIblockFields($iblockId, $fields);
    }


    public function updateIblockFields($iblockId, $fields) {
        if ($iblockId && !empty($fields)) {
            \CIBlock::SetFields($iblockId, $fields);
            return true;
        }
        return false;
    }

    /** @deprecated */
    public function findIblockType($typeId) {
        return $this->getIblockTypeIfExists($typeId);
    }

    /** @deprecated */
    public function findIblockId($code, $typeId = '') {
        return $this->getIblockIdIfExists($code, $typeId);
    }

    /** @deprecated */
    public function findIblock($code, $typeId = '') {
        return $this->getIblockIfExists($code, $typeId);
    }

    /** @deprecated */
    public function findIblockTypeId($typeId) {
        return $this->getIblockTypeIdIfExists($typeId);
    }
}