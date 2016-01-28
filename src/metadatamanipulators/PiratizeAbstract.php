<?php
// src/metadatamanipulators/PiratizeAbstract.php

namespace mik\metadatamanipulators;
use GuzzleHttp\Client;
use \Monolog\Logger;

/**
 * PiratizeAbstract - converts MODS <abstract>s into pirate talk using
 * the http://isithackday.com/arrpi.php API. Really just a late-night
 * sample thrown toghether to illustrate how MIK can use an external
 * service to modify your scurvy metadata.
 *
 * Applies to all MODS toolchains.
 */
class PiratizeAbstract extends MetadataManipulator
{
    /**
     * Create a new metadata manipulator instance.
     */
    public function __construct($settings = null, $paramsArray, $record_key)
    {
        parent::__construct($settings, $paramsArray, $record_key);
        $this->record_key = $record_key;

        $this->arrpiUrl = 'http://isithackday.com/arrpi.php';

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
     * @param string $input An XML snippet to be manipulated. We are only interested
     *    in <abstract> snippets.
     *
     * @return string
     *     Manipulated string
     */
     public function manipulate($input)
     {
        $dom = new \DomDocument();
        $dom->loadxml($input, LIBXML_NSCLEAN);

        $abstracts = $dom->getElementsByTagName('abstract');
        if ($abstracts->length) {
            foreach ($abstracts as $abstract) {
                // Use Guzzle to fetch piratized text.
                $client = new Client();
                try {
                    $original_text = urlencode($abstract->nodeValue); 
                    $query = "?text=$original_text&format=json";
                    $response = $client->get($this->arrpiUrl . $query);
                } catch (Exception $e) {
                    $this->log->addWarning("PiratizeAbstract",
                        array('HTTP request error' => $e->getMessage()));
                    return $input;
                }
                $body = $response->getBody();
                $translation = json_decode($body, true);
                $abstract->nodeValue = urldecode($translation['translation']['pirate']);

                if (urldecode($original_text) != $abstract->nodeValue) {
                    $this->log->addInfo("PiratizeAbstract",
                        array(
                            'Record key' => $this->record_key,
                            'Source abstract text' => urldecode($original_text),
                            'Piratized abstract text' => $abstract->nodeValue,
                        )
                    );
                }

                return $dom->saveXML($dom->documentElement);
            }
        }
        // If the current snippet isn't <abstract>, return the input snippet.
        else {
            return $input;
        }
     }
}
