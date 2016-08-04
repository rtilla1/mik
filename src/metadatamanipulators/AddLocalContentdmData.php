<?php
// src/metadatamanipulators/AddLocalContentdmData.php

namespace mik\metadatamanipulators;
use GuzzleHttp\Client;
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
class AddLocalContentdmData extends MetadataManipulator
{
    /**
     * @var string $record_key - the unique identifier for the metadata
     *    record being manipulated.
     */
    private $record_key;

    /**
     * Create a new metadata manipulator Instance.
     */
    public function __construct($settings = null, $paramsArray, $record_key)
    {
        parent::__construct($settings, $paramsArray, $record_key);
        $this->record_key = $record_key;
        $this->alias = $this->settings['METADATA_PARSER']['alias'];

        // Set up logger.
        $this->pathToLog = $this->settings['LOGGING']['path_to_manipulator_log'];
        $this->log = new \Monolog\Logger('config');
        $this->logStreamHandler = new \Monolog\Handler\StreamHandler($this->pathToLog,
            Logger::INFO);
        $this->log->pushHandler($this->logStreamHandler);
    }

    /**
     * General manipulate wrapper method.
     *
     *  @param string $input The XML fragment to be manipulated. We are only
     *     interested in the <extension><CONTENTdmData> fragment added in the
     *     MIK mappings file.
     *
     * @return string
     *     One of the manipulated XML fragment, the original input XML if the
     *     input is not the fragment we are interested in, or an empty string,
     *     which as the effect of removing the empty <extension><CONTENTdmData>
     *     fragement from our MODS (if there was an error, for example, we don't
     *     want empty extension elements in our MODS documents).
     */
    public function manipulate($input)
    {
        $dom = new \DOMDocument();
        $dom->loadxml($input, LIBXML_NSCLEAN);
        // Test to see if the current fragment is <extension><CONTENTdmData>.
        $xpath = new \DOMXPath($dom);
        
        $cdmdatas = $xpath->query("//extension/CONTENTdmData");
        // There should only be one <CONTENTdmData> fragment in the incoming
        // XML. If there is 0 or more than 1, return the original.
        if ($cdmdatas->length === 1) {
          $contentdmdata = $cdmdatas->item(0);
          $alias = $dom->createElement('alias', $this->alias);
          $contentdmdata->appendChild($alias);
          $pointer = $dom->createElement('pointer', $this->record_key);
          $contentdmdata->appendChild($pointer);          

          
          // Add the <dmGetItemInfo> element.
          $dmGetItemInfo = $dom->createElement('dmGetItemInfo');
          $dmGetItemInfo->setAttribute('timestamp', date("Y-m-d H:i:s"));
          $dmGetItemInfo->setAttribute("mimetype", 'application/json');          
          $source_url = $this->settings['METADATA_PARSER']['ws_url'] .
              'dmGetItemInfo/' . $this->alias . '/' . $this->record_key . '/json';       
          $dmGetItemInfo->setAttribute('source', $source_url);
          $item_info = $this->getCdmData(sprintf("%s.json", $this->record_key));          
          
          if (strlen($item_info)) {
              $cdata = $dom->createCDATASection($item_info);
              $dmGetItemInfo->appendChild($cdata);
              $contentdmdata->appendChild($dmGetItemInfo);
          }
          // Add the <dmCompoundObjectInfo> element.
          $dmGetCompoundObjectInfo = $dom->createElement('dmGetCompoundObjectInfo');
          $dmGetCompoundObjectInfo->setAttribute('timestamp', date("Y-m-d H:i:s"));
          $dmGetCompoundObjectInfo->setAttribute('mimetype', 'text/xml');
          $source_url = $this->settings['METADATA_PARSER']['ws_url'] .
              'dmGetCompoundObjectInfo/' . $this->alias . '/' . $this->record_key . '/xml';
          $dmGetCompoundObjectInfo->setAttribute('source', $source_url);   
          $compound_object_info = $this->getCdmData(sprintf("%s_cpd.xml", $this->record_key));
          // Only add the <dmGetCompoundObjectInfo> element if the object is compound.
          if (strlen($compound_object_info) && preg_match('/<cpd>/', $compound_object_info)) {
              $cdata = $dom->createCDATASection($compound_object_info);
              $dmGetCompoundObjectInfo->appendChild($cdata);
              $contentdmdata->appendChild($dmGetCompoundObjectInfo);
          }
          
          // Add the <GetParent> element.
          $GetParent = $dom->createElement('GetParent');
          $GetParent->setAttribute('timestamp', date("Y-m-d H:i:s"));
          $GetParent->setAttribute('mimetype', 'text/xml');
          $source_url = $this->settings['METADATA_PARSER']['ws_url'] .
              'GetParent/' . $this->alias . '/' . $this->record_key . '/xml';
          $GetParent->setAttribute('source', $source_url);    
          $parent_info = $this->getCdmData(sprintf("%s_parent.xml", $this->record_key));
          // Only add the <GetParent> element if the object has a parent
          // pointer of not -1.
          if (strlen($parent_info) && !preg_match('/\-1/', $parent_info)) {
              $cdata = $dom->createCDATASection($parent_info);
              $GetParent->appendChild($cdata);
              $contentdmdata->appendChild($GetParent);
          }
          
          return $dom->saveXML($dom->documentElement);
        }
        else {
            // If current fragment is not <extension><CONTENTdmData>, return it
            // unmodified.
            return $input;
        }
    }

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
    protected function getCdmData($sought_file) {
        $source_dir = "Cached_Cdm_files/" . $this->settings['FETCHER']['alias'];
        $dir_iterator = new \RecursiveDirectoryIterator($source_dir);
        foreach(new \RecursiveIteratorIterator($dir_iterator) as $file) {
            $filepath = $file->getPath();
            $filename = $file->getFilename();
            if ($sought_file == $filename) {
                $cdmData = file_get_contents("$filepath/$filename");
                return $cdmData;
            }
        }
        return false;
    }    
}
