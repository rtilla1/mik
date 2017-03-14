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
$config = parse_ini_file($config_path, TRUE);
$config_output_dir = $config['WRITER']['output_directory'];

// Set up logging.
$log_path = explode(DIRECTORY_SEPARATOR, $config['LOGGING']['path_to_log']);
array_pop($log_path);
$log_path = implode(DIRECTORY_SEPARATOR, $log_path) . DIRECTORY_SEPARATOR . 'saxon.log';

$info_log = new Logger('saxon_info');
$info_log_handler = new StreamHandler($log_path, Logger::INFO);
$info_log->pushHandler($info_log_handler);

$error_log = new Logger('saxon_err');
$error_log_handler = new StreamHandler($log_path, Logger::ERROR);
$error_log->pushHandler($error_log_handler);

if (!file_exists('saxon9he.jar')) {
  $error_log->addError("MIK is configured to run xslt transformations using saxon, but the "
              . "saxon jar was not found. Either remove 'apply_xslt_with_saxon.php' "
              . "from your config section [WRITER][postwritebatchhooks], or download and "
              . "extract saxon9he.jar to the top level directory next to the mik executable."
            );
  exit(1);
}

$transforms = $config['XSLT']['stylesheets'];
$xslt_input = $config_output_dir . DIRECTORY_SEPARATOR;

foreach ($transforms as $i => $transform) {

  $transform_key = explode('.', array_pop(explode(DIRECTORY_SEPARATOR, $transform)))[0];
  $xslt_output = sprintf('%s%sxslt-%d-%s%s', $config_output_dir, DIRECTORY_SEPARATOR, $i + 1, $transform_key, DIRECTORY_SEPARATOR);
  if (!is_dir($xslt_output)) {
    mkdir($xslt_output);
  }

  $info_log->addInfo("Applying stylesheet " . $transform);
  $command = "java -jar saxon9he.jar -s:$xslt_input -xsl:$transform  -o:$xslt_output";
  $info_log->addInfo("Saxon command line: $command");

  try {
    exec($command, $ret);
  }
  catch(Exception $e) {
    if (!empty($ret)) {
      $error_log->addError(sprintf("Output from saxon: %s", implode("\n", $ret)));
    }
  }

  $xslt_input = $xslt_output;
}
