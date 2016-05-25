<?php
// src/metadatamanipulators/AddContentdmData.php

namespace mik\metadatamanipulators;
use \Monolog\Logger;

/**
 * AddCdmItemInfo - Adds several types of data about objects being
 * migrated from CONTENTdm, specifically the output of the following
 * CONTENTdm web API requests for the current object: dmGetItemInfo
 * (JSON format), dmGetCompoundObjectInfo (if applicable) (XML format),
 * and GetParent (if applicable) (XML format).
 *
 * Note that this manipulator doesn't add the <extension> fragment, it
 * only populates it with data from CONTENTdm. The mappings file
 * must contain a row that adds the following element to your MODS:
 * '<extension><CONTENTdmData></CONTENTdmData></extension>', e.g.,
 * null5,<extension><CONTENTdmData></CONTENTdmData></extension>.
 *
 * This metadata manipulator takes no configuration parameters.
 */
class AddLocalContentdmData extends AddContentdmData
{
    /**
     * Fetch the output of the CONTENTdm web API for the current object.
     *
     * @param string $alias
     *   The CONTENTdm alias for the current object.
     * @param string $pointer
     *   The CONTENTdm pointer for the current object.
     * @param string $cdm_api_function
     *   The name of the CONTENTdm API function.
     * @param string $format
     *   Either 'json' or 'xml'.
     *
     * @return stting
     *   The output of the CONTENTdm API request, in the format specified.
     */
    protected function getCdmData($alias, $pointer, $cdm_api_function, $format)
    {
          // Use Guzzle to fetch the output of the call to dmGetItemInfo
          // for the current object.
        if($cdm_api_function === 'dmGetItemInfo'){
            $cdmdataFile = sprintf("%s/%s/%s.json",$this->settings['FILE_GETTER']['local_dir'], $alias, $pointer);
        }elseif ($cdm_api_function === 'dmGetCompoundObjectInfo') {
            $cdmdataFile = sprintf("%s/%s/Cpd/%s_cpd.xml",$this->settings['FILE_GETTER']['local_dir'], $alias, $pointer);
        }else{
            $cdmdataFile = sprintf("%s/%s/%s_parent.xml",$this->settings['FILE_GETTER']['local_dir'], $alias, $pointer);
        }

        try {
            if(file_exists($cdmdataFile)){
                $cdmData = file_get_contents($cdmdataFile);
            }else{
                return false;
            }
        } catch (Exception $e) {
            $this->log->addInfo("AddContentdmData",
                array('Open file error' => $e->getMessage()));
            return '';
        }
        return $cdmData;
    }
    
    public function manipulate($input) {
        return parent::manipulate($input);
    }
}