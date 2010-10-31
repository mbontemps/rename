<?php

/**
 * Translates a string with underscores into camel case (e.g. first_name -&gt; firstName)
 * @param    string   $str                     String in underscore format
 * @param    bool     $capitalise_first_char   If true, capitalise the first char in $str
 * @return   string                              $str translated into camel caps
 */
function to_camel_case($str, $capitalise_first_char = false) {
  if($capitalise_first_char) {
    $str[0] = strtoupper($str[0]);
  }
  $func = create_function('$c', 'return strtoupper($c[1]);');
  return preg_replace_callback('/_([a-z])/', $func, $str);
}

/**
 * Translates a camel case string into a string with underscores (e.g. firstName -&gt; first_name)
 * @param    string   $str    String in camel case format
 * @return    string            $str Translated into underscore format
 */
function from_camel_case($str) {
  $str[0] = strtolower($str[0]);
  $func = create_function('$c', 'return "_" . strtolower($c[1]);');
  return preg_replace_callback('/([A-Z])/', $func, $str);
}

$excludeFiles = array(
  '/.git',
  '.svn',
  '/lib/vendor',
  '/plugins',
  '/cache/',
);
$from = $argv[1];
$to = $argv[2];
$dir = realpath(isset($argv[3]) ? $argv[3] : '.');

$files = explode("\n", trim(shell_exec("find $dir -type f")));

$fromTo = array(
    lcfirst($from) => lcfirst($to),
    ucfirst($from) => ucfirst($to),
    from_camel_case($from) => from_camel_case($to),
    to_camel_case($from) => to_camel_case($to),
    strtoupper(from_camel_case($from)) => strtoupper(from_camel_case($to)),
);

foreach($files as $file) {
    $niceFile = str_replace($dir, '', $file);
    $skip = false;
    foreach($excludeFiles as $excludeFile) {
        if($excludeFile[0] == '/') {
            $excludeFile = $dir . $excludeFile;
        }
        if(strpos($file, $excludeFile) !== false) {
            $skip = true;
            break;
        }
    }
    if($skip) {
        continue;
    }

    foreach($fromTo as $from => $to) {
        if(strpos($file, $from) !== false) {
            $fileNew = str_replace($from, $to, $file);
            rename($file, $fileNew);
            echo "Replacing $niceFile by $fileNew\n";
            $file = $fileNew;
        }
    }
    $data = file_get_contents($file);
    // Replace inside data
    $dataHasChanged = false;
    foreach($fromTo as $from => $to) {
        if(strpos($data, $from) !== false) {
            $data = str_replace($from, $to, $data);
            $dataHasChanged = true;
        }
    }
    if($dataHasChanged) {
        file_put_contents($file, $data);
        echo "Replacing data in $niceFile\n";
    }
}
