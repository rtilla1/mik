<?php

namespace mik\filegetters;

use GuzzleHttp\Client;
use mik\exceptions\MikErrorException;
use Monolog\Logger;

class LocalCdmCompound extends FileGetter
{
    /**
     * @var array $settings - configuration settings from confugration class.
     */
    public $settings;

    /**
     * @var string $utilsUrl - CDM utils url.
     */
    public $utilsUrl;

    /**
     * @var string $alias - CDM alias
     */
    public $alias;

    /**
     * Create a new CONTENTdm Fetcher Instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        $this->settings = $settings['FILE_GETTER'];
        $this->utilsUrl = $this->settings['utils_url'];
        $this->alias = $this->settings['alias'];
        $this->temp_directory = (!isset($settings['FILE_GETTER']['temp_directory'])) ?
          '/tmp' : $settings['FILE_GETTER']['temp_directory'];

        if (!isset($this->settings['http_timeout'])) {
            // Seconds.
            $this->settings['http_timeout'] = 60;
        }

        // Set up logger.
        $this->pathToLog = $settings['LOGGING']['path_to_log'];
        $this->log = new \Monolog\Logger('CdmPhpDocuments filegetter');
        $this->logStreamHandler = new \Monolog\Handler\StreamHandler($this->pathToLog,
            Logger::ERROR);
        $this->log->pushHandler($this->logStreamHandler);

    }

    /**
     * Gets a compound item's children pointers. 
     */
    public function getChildren($pointer)
    {
        $item_structure = $this->getDocumentStructure($pointer);

        $children_pointers = array();
        if (strlen($item_structure)) {
            $structure = simplexml_load_string($item_structure);
            if ($structure->code == '-2') {
                return $children_pointers;
            }
            else {
                $pages = $structure->xpath('//page');
                foreach ($pages as $page) {
                    $children_pointers[] = (string) $page->pageptr;
                }
            }
        }

        return $children_pointers;
    }


    /**
     * Gets a compound document's structure.
     */
    public function getDocumentStructure($pointer, $format = 'xml')
    {
        $alias = $this->settings['alias'];

        if ($format == 'json') {
            $filepath = './Cached_Cdm_files/' . $alias . '/' . 'Cpd/' .  $pointer .  '_cpd.json';
        }
        if ($format == 'xml') {
            $filepath = './Cached_Cdm_files/' . $alias . '/' . 'Cpd/' .  $pointer .  '_cpd.xml';
        }

        try {
            $item_structure = file_get_contents($filepath);
        }
        catch (RequestException $e) {
            $this->log->addError("Cannot read $filepath", array('HTTP request error' => $e->getRequest()));
        }       

        if ($format == 'json') {
            return json_decode($item_structure, true);
        }
        if ($format == 'xml') {
            return $item_structure;
        }
        return false;
    }

}
