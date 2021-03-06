<?php
// src/metadataparsers/json/Json.php

/**
 * Example metadata parser class to demonstrate how to create something other
 * than MODS or DC metadata.
 *
 * Intended for demonstration purposes only, not for production.
 */

namespace mik\metadataparsers\json;

use mik\metadataparsers\MetadataParser;

abstract class Json extends MetadataParser
{
    /**
     * Create a new metadata parser instance
     */
    public function __construct($settings)
    {
        // Call Metadata.php contructor
        parent::__construct($settings);
    }

    /**
     *  Create JSON.
     *
     *  @param array $objectInfo array of info about the object that the JSON file will be created for.
     */
    abstract public function createJson($objectInfo);

    public function outputJson($json, $outputPath = '')
    {
        /**
         * $json - serialized JSON string - required.
         * $outputPath - output path for writing to a file.
         */
        if ($outputPath !='') {
            $filecreationStatus = file_put_contents($outputPath .'/JSON.json', $json);
            if ($filecreationStatus === false) {
                echo "There was a problem writing the JSON to a file.\n";
            } else {
                echo "JSON.json file created.\n";
            }
        }
    }
}
