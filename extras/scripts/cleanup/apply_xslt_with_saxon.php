<?php

/**
  * Post-Write script for MIK that applies XSLTs defined in .ini file
  * to the mods output of MIK. Before transformation of original mods
  * are saved in a subdirecory of 'output_directory' named 'original_mods' 
 */

require 'vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$config_path = trim($argv[1]);
$config = parse_ini_file($config_path, true);
$config_output_dir = $config['WRITER']['output_directory'];

// Set up logging.
$path_to_success_log = $config_output_dir . DIRECTORY_SEPARATOR .
    'postwritehook_apply_xslt_success.log';
$path_to_error_log = $config_output_dir . DIRECTORY_SEPARATOR .
    'postwritehook_apply_xslt_error.log';

$info_log = new Logger('postwritehooks/apply_xslt.php');
$info_handler = new StreamHandler($path_to_success_log, Logger::INFO);
$info_log->pushHandler($info_handler);

$error_log = new Logger('postwritehooks/apply_xslt_with_saxon.php');
$error_handler = new StreamHandler($path_to_error_log, Logger::WARNING);
$error_log->pushHandler($error_handler);

$transforms = $config['XSLT']['stylesheets'];
$xslt_input = $config_output_dir . DIRECTORY_SEPARATOR;

foreach($transforms as $i => $transform) {

  $transform_key = explode('.', array_pop(explode(DIRECTORY_SEPARATOR, $transform)))[0];
  $xslt_output = sprintf('%s%sxslt-%d-%s%s', $config_output_dir, DIRECTORY_SEPARATOR, $i+1, $transform_key, DIRECTORY_SEPARATOR);
  mkdir($xslt_output);

  $info_log->addInfo("Applying stylesheet " . $transform);
  $command = "java -jar saxon9he.jar -s:$xslt_input -xsl:$transform  -o:$xslt_output";
  $info_log->addInfo("Saxon command line: $command");

  exec($command);

  //$info_log->addInfo(sprintf("Output from saxon: %s", implode("\n", $ret)));
  $xslt_input = $xslt_output;
}
