<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Jens Eipel <j.eipel@connecta.ag>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */


$LANG->includeLLFile('EXT:cag_importcsv/mod1/locallang.xml');
require_once(PATH_t3lib . 'class.t3lib_scbase.php');
$BE_USER->modAccess($MCONF, 1); // This checks permissions and exits if the users has no permission for entry.
// DEFAULT initialization of a module [END]



/**
 * Module 'Import CSV' for the 'cag_importcsv' extension.
 *
 * @author  Jens Eipel <j.eipel@connecta.ag>
 * @package TYPO3
 * @subpackage  tx_cagimportcsv
 */
class tx_cagimportcsv_module1 extends t3lib_SCbase
{
    public $pageinfo;

    public $verbose = false;
    public $dryRun = false;
    public $debug = "";
    public $enableDebug = false;
    public $formData = array();
    public $conf = array();

    /**
     * Initializes the Module
     * @return  void
     */
    function init()
    {
        global $BE_USER, $LANG, $BACK_PATH, $TCA_DESCR, $TCA, $CLIENT, $TYPO3_CONF_VARS;

        parent::init();

        /*
        if (t3lib_div::_GP('clear_all_cache'))  {
        $this->include_once[] = PATH_t3lib.'class.t3lib_tcemain.php';
        }
        */
    }

    /**
     * Adds items to the ->MOD_MENU array. Used for the function menu selector.
     *
     * @return  void
     */
    function menuConfig()
    {
        global $LANG;
        /*
        $this->MOD_MENU = Array (
        'function' => Array (
        '1' => $LANG->getLL('function1'),
        '2' => $LANG->getLL('function2'),
        '3' => $LANG->getLL('function3'),
        )
        );
        */
        parent::menuConfig();
    }

    /**
     * Main function of the module. Write the content to $this->content
     * If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
     *
     * @return  [type]    ...
     */
    public function main()
    {
        global $BE_USER, $LANG, $BACK_PATH, $TCA_DESCR, $TCA, $CLIENT, $TYPO3_CONF_VARS;

        $this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['cag_importcsv']);

        // Access check!
        // The page will show only if there is a valid page and if this page may be viewed by the user
        $this->pageinfo = t3lib_BEfunc::readPageAccess($this->id, $this->perms_clause);
        $access         = is_array($this->pageinfo) ? 1 : 0;

        //if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id))
        {
            // Draw the header.
            $this->doc           = t3lib_div::makeInstance('mediumDoc');
            $this->doc->backPath = $BACK_PATH;
            $this->content .= $this->doc->startPage($LANG->getLL('title'));
            $this->content .= $this->doc->header($LANG->getLL('title'));
            // Render content:
            $this->moduleContent();
            // ShortCut
            if ($BE_USER->mayMakeShortcut()) {
                $this->content .= $this->doc->spacer(20) . $this->doc->section('', $this->doc->makeShortcutIcon('id', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name']));
            }
            $this->content .= $this->doc->spacer(10);
        }
        /*
        else {
            // If no access or if ID == zero
            $this->doc           = t3lib_div::makeInstance('mediumDoc');
            $this->doc->backPath = $BACK_PATH;
            $this->content .= $this->doc->startPage($LANG->getLL('title'));
            $this->content .= $this->doc->header($LANG->getLL('title'));
            $this->content .= $this->doc->spacer(5);
            $this->content .= "<pre>".htmlentities($this->pageinfo)."</pre>";
            $this->content .= "HALLLLOOOO ID: ".$this->id."  DUUU ".$this->perms_clause;
            $this->content .= $this->doc->spacer(10);
        }
        */
    }

    /**
     * Prints out the module HTML
     *
     * @return  void
     */
    public function printContent()
    {
        $this->content .= $this->doc->endPage();
        echo $this->content;
    }

    /**
     * Generates the module content
     *
     * @return  void
     */
    protected function moduleContent()
    {
        global $BE_USER, $LANG, $BACK_PATH, $TCA_DESCR, $TCA, $CLIENT, $TYPO3_CONF_VARS;
        $this->updateFormData();

        //$this->debug("<b>\$this->formData</b><br/><pre>" . print_r($this->formData, true) . "</pre>");

        $content = "<div id='typo3-docbody'>\n  <div id='typo3-docheader' />\n    <div id='typo3-docbody'>\n";

        $content .= $this->createFormular();
        if ( !isset($_REQUEST['formTransmitted']) ) $content .= $this->createRecommendComment();

        if ($_REQUEST['doImport'] == "import") $this->handleImport();
        if ($_REQUEST['createMappingFile'] == "create mapping file") $this->handleCreateMappingFile();

        $content .= (strlen($this->debug) > 0) ? ("<pre>" . $this->debug . "</pre>") : "";
        $content .= "</div></div>";
        $this->content .= $this->doc->section('', $content, 0, 1);
    }

    protected function handleCreateMappingFile()
    {

      $this->debug("<font color=\"blue\">handleCreateMappingFile()</font>");

      $file  = PATH_site . $this->formData['csvFilePath'];
      $files = array();
      $mappingFilePath = PATH_site.$this->formData['mappingFilePath'];

      if (is_dir($file)) {
        if (!is_dir($mappingFilePath)) {
            $this->debug("<b>Path to Mappingfile must be a path</b>");
            return;
        }
        $this->debug("<b>Searching for CSV-Files (.csv): " . $file . "</b>");
        $files = $this->dirList($file, true);
        $isDir = true;
      } else {
        $pathinfo = pathinfo($file);
        if (
              !file_exists(PATH_site . $this->fromData['mappingFilePath'])
          || is_dir(PATH_site . $this->fromData['mappingFilePath']) ) {
          $mappingFilePath = PATH_site .  $this->fromData['mappingFilePath'] . "/" . $pathinfo['filename'] . ".map";
        }
        $files[] = $file;
        $isDir   = false;
      }

      foreach ($files as $file) {
        $pathinfo = pathinfo($file);
        if (strcasecmp($pathinfo['extension'], "csv") != 0){
          continue;
        }
        $this->debug("processing file ${pathinfo['basename']}<br/>");
        $currentMappingFilePath = $mappingFilePath.'/'.$pathinfo['filename'].".map";
          $this->hint("Create a Mapping file named $currentMappingFilePath<br />".substr($currentMappingFilePath, strlen(PATH_site . "/<br/><a href='../" . substr($currentMappingFilePath, strlen(PATH_site . "/")) . "' target='_new'><font color='blue'>Click here to open Mapping-File</font></a>")));

        $this->readCsvFile($file, $colNames);
        $this->createMappingFile($currentMappingFilePath, $colNames, self::FORCE_CREATION);
      }
    }


    protected function doMap($csvFile, $mappingFile) {

      $this->hint("<hr size='1' style='border: 1px dotted black;'><b>Processing File: " . $csvFile . "</b><br><a href='../" . substr($csvFile, strlen(PATH_site)) . "' target='_new'><font color='blue'>Click here to open CSV-File</font></a>");

      if (file_exists($mappingFile)) {
        $this->hint("Looking for Mapping-File " . $mappingFile . "<br /><a href='../" . substr($mappingFile, strlen(PATH_site)) . "' target='_new'><font color='blue'>Click here to open Mapping-File</font></a>");
      } elseif ($this->formData['autoMap']) {
          $this->debug("Use &quot;Auto Map unmapped fields&quot; Option");
      } else {
          $this->debug("No Mapping File Found. Use the &quot;Auto Map unmapped fields&quot; Option above");
          return;
      }

      $map = $this->readMappingFile($mappingFile);
      // if (is_numeric($pid)) $map[':set pid'] = $pid;
      if (!is_array($map)) {
        $this->debug("<h3>No Map found at</h3> " . $mappingFile);
      } else {
        $this->debug("<b>\$map in handleImport():</b><br/><pre>".htmlentities(print_r($map, true))."</pre><br/>\n");
        $functionColumns  = array();
        $subselectColumns = array();
        $updateWhere      = "";
        foreach ($map as $key => $value) {
            if ($key == ":table") {
                $targetTable = $value;
                unset($map[$key]);
            } else if (substr($key, 0, 5) == ":set ") {
                $fKey                   = trim(substr($key, 5));
                $functionColumns[$fKey] = $value;
                unset($map[$key]);
                $map[$fKey] = str_replace("'", "", $fKey);
            } else if (substr($key, 0, 11) == ":subselect ") {
                $fKey                    = trim(substr($key, 11));
                $subselectColumns[$fKey] = $value;
                unset($map[$key]);
                $map[$fKey] = str_replace("'", "", $fKey);
            } else if (substr($key, 0, 11) == ":clearTable") {
                $clearTable = $value;
                $clearTable = (trim($value) == "true" || trim($value) == "1") ? true : false;
                unset($map[$key]);
            } else if (substr($key, 0, 7) == ":update") {
                $updateWhere = $value;
                unset($map[$key]);
            } else if (substr($key, 0, 8) == ":automap") {
                $autoMap = (trim($value) == "true" || trim($value) == "1") ? true : false;
                unset($map[$key]);
            } else if (substr($key, 0, 10) == ":mapUpdate") {
                $autoMapUpdate = (trim($value) == "true" || trim($value) == "1") ? true : false;
                unset($map[$key]);
            } else if (substr($key, 0, 10) == ":separator") {
                $separator = $value;
                unset($map[$key]);
            } else if (substr($key, 0, 1) == ":" || substr($key, 0, 1) == "#") {
                unset($map[$key]);
            }
        }

        $data = array();
        if (!$this->readCsvFile($csvFile, $colNames, $data, self::CSV_HAS_HEAD, $separator, $map)) {
          $this->hint("<b><font color=\"red\">error reading $csvFile</font></b><br/>\n");
          continue;
        }

        $this->debug("<b>\$data nach readCsvFile:</><br/><pre>".htmlentities(print_r($data, true))."</pre></br/>");
        $this->debug("<b>\$colNames nach readCsvFile:</><br/><pre>".htmlentities(print_r($colNames, true))."</pre></br/>");


        if (!is_array($data) || !count($data)) {
            $this->hint("<h3>No valid CSV-File found at " . $csvFile . "</h3>");
        } else {
          $this->debug("\$map:<pre>".htmlentities(print_r($map, true))."</pre><br/>\n");
          $result =  $this->mapAndWriteToDB($data, $map, $functionColumns, $subselectColumns, $targetTable, $updateWhere, $clearTable, $functionColumns['pid']);
        }
      }
      return true;
    }

    protected function handleImport()
    {
      $this->debug("<font color=\"blue\">handleImport()</font>");

      $file  = PATH_site.$this->formData['csvFilePath'];
      $files = array();
      $mappingFilePath = PATH_site.$this->formData['mappingFilePath'];
      $isDir = true;
      $singleMappingFile = true;

      if ($this->formData['dryRun']) $this->hint("<font color='red'><b>Notice: This is a dry run. No database statements will be executed.</b></font>");
      $this->debug("<b>\$mappingFilePath:</b>".htmlentities($mappingFilePath)."<br/>");


      if (is_dir($file)) {
        $this->debug("<b>Searching for CSV-Files (.csv) in directory: " . $file . "</b>");
        $files = $this->dirList($file, FULL_PATH);
        $isDir = true;
      } else {
        $files[] = $file;
        $isDir = false;
      }

      if (is_dir($mappingFilePath)) {
        $this->debug("<b>Searching for Mapping-Files (.map) in directory: " . $mappingFilePath . "</b>");
        $singleMappingFile  = false;
      } else {
        $mappingFile = $mappingFilePath;
        $singleMappingFile = true;
      }



/*
      if (is_dir($file) && is_dir($mappingFilePath) ) {
        $this->debug("<b>Searching for CSV-Files (.csv) in directory: " . $file . "</b>");
        $this->debug("<b>Searching for Mapping-Files (.map) in directory: " . $mappingFilePath . "</b>");

        $files = $this->dirList($file, FULL_PATH);
        $isDir = true;
      } else
*/

/*
      if( !is_dir($file) ) {
        $pathinfo = pathinfo($file);
        if ( !file_exists($mappingFilePath) || is_dir($mappingFilePath) ) {
          $currentMappingFilePath = $mappingFilePath = PATH_site.$mappingFilePath."/".$pathinfo['filename'].".map";
          $this->debug("<b>\$mappingFilePath:</b>".htmlentities($mappingFilePath)."<br/>");
        } else {
          $currentMappingFilePath = $mappingFilePath;
        }
        $files[] = $file;
        $isDir   = false;
      } else {
        return false;
      }
*/

      $this->debug("<b>\$files:</b>".htmlentities(print_r($files,true))."<br/>");

      foreach ($files as $file) {
        $pathinfo = pathinfo($file);
        if (is_dir($file) || (strcasecmp($pathinfo['extension'], "csv") != 0) ) continue;
        if (!$singleMappingFile) {
          $mappingFile = PATH_site.$this->fromData['mappingFilePath']."/".$pathinfo['filename'].".map";
        }
        if ($isDir && !file_exists($mappingFile)) {
          $this->hint("No mapping file for ${pathinfo['basename']} ($mappingFile)<br/>\n");
          continue;
        }
        $this->doMap($file, $mappingFile);
      }
    }

    protected function autoMap(&$map, &$colNames)
    {
      if (!is_array($colNames)) {
          $this->hint("<b><font color=\"red\">Cannot Automap. No CSV-Data available</font></b><br/>\n");
          return false;
      }

      $targetTable = $map[':table'];
      if ($targetTable == "") {
        $this->hint("<b><font color=\"red\">No target Table, automapping not possible</font></b><br/>\n");
        return false;
      }

      $this->debug("Looking for Fields to Automap ...");

      $descTargetTable = $GLOBALS['TYPO3_DB']->admin_get_fields($targetTable);
      $tcaTargetTable = $GLOBALS['TCA'][$targetTable]['columns'];
      //$this->debug("admin_get_fields<br/>"."<pre>".print_r($descTargetTable, true)."</pre>");
      //$this->debug("TCA<br/>"."<pre>".print_r($tcaTargetTable, true)."</pre>");

      $sqlColumnNames = array();
      foreach($descTargetTable as $field => $value) {
          if (strpos($value['Extra'], "auto_increment") !== false)
              continue;

          $sqlColumnNames[strtolower($field)] = strtolower($field);
          $this->debug("field <font color=\"red\">$field</font><br/>"."<pre>".print_r($value, true)."</pre>");
      }
      $lookupMap = array();
      foreach ($map as $csvColumnName => $sqlColumnName) {
          $csvColumnNameTmp = strtolower(str_replace("'", "", $csvColumnName));
          if (strpos($csvColumnNameTmp, ".") > 0)
              $csvColumnNameTmp = substr($csvColumnNameTmp, strpos($csvColumnNameTmp, ".") + 1);
          $lookupMap[$csvColumnName] = strtolower($csvColumnNameTmp);
      }


      foreach ($colNames as $csvColumnName) {
          $csvColumnNameTmp = strtolower($csvColumnName);
          $map2sql          = "";
          if (!in_array($csvColumnNameTmp, $lookupMap)) {
              if (isset($sqlColumnNames[$csvColumnNameTmp])) {
                  $map2sql             = $sqlColumnNames[$csvColumnNameTmp];
                  $csvColumnName       = "'" . $csvColumnName . "'";
                  $map[$csvColumnName] = $map2sql;
                  $this->debug("Mapping " . $csvColumnName . " to " . $map2sql . " (exact match)");
              } else {
                  $maxScore = 0;
                  foreach ($sqlColumnNames as $sqlColumnName) {
                      $tmpScore = similar_text($sqlColumnName, $csvColumnNameTmp);
                      if ($tmpScore > $maxScore && $tmpScore > 2) {
                          $maxScore = $tmpScore;
                          $map2sql  = $sqlColumnName;
                      }
                  }
                  if ($maxScore > 0) {
                      $csvColumnName       = "'" . $csvColumnName . "'";
                      $map[$csvColumnName] = $map2sql;
                      $this->debug("Mapping " . $csvColumnName . " to " . $map2sql . " (fuzzy match - Score: " . $maxScore . ")");
                  } else {
                      $this->debug("No Mapping for " . $csvColumnName);
                      $csvColumnName       = "'" . $csvColumnName . "'";
                      $map[$csvColumnName] = "PLEASE_ADD_DATABASE_FIELD_NAME_HERE";
                  }
              }
              if (false || $map2sql != "" && $autoMapUpdate && $file != null) {
                  $fp = fopen($file, 'a');
                  if (!$fp)
                      $this->debug("Cannot update Maping File: " . $file);
                  else {
                      fwrite($fp, $csvColumnName . "=" . $map2sql . "\n");
                      $this->debug("Updated Mapping file. Adding Automapping: " . $csvColumnName . "=" . $map2sql);
                  }
                  fclose($fp);
              }
          }
      }
      if ($map2sql == "")  $this->debug('No additional Fields automapped');

      return $map;
    }

    const FORCE_CREATION = true;
    protected function createMappingFile($file, &$colNames, $force = false) {
      if (file_exists($file) && !$force) return false;

      $this->hint("Creating Mapping File: " . $file . "");

      $map = array();
      if ($this->formData['targetTable'] != "") $map[':table'] = $this->formData['targetTable'];
      if ($this->formData['storagePID']) $map[':set pid'] = $this->formData['storagePID'];
      if ($this->formData['separator']) $map[':separator'] = $this->formData['separator'];
      if ($this->formData['clearTable']) $map[':clearTable'] = $this->formData['clearTable'];



      if (!$this->autoMap($map, $colNames)) return false;

      $fp = fopen($file, "w"); if (!$fp) return false;
      $this->debug("<b>\$this->formData in createMappingFile():</b><br/><pre>".htmlentities(print_r($this->formData, true))."</pre><br/>\n");
      $this->debug("<b>\$map in createMappingFile():</b><br/><pre>".htmlentities(print_r($map, true))."</pre><br/>\n");
      fwrite($fp, "# Autocreated Mappingfile on " . date("Y.m.d H:i:s") . "\n");
      foreach($map as $key => $value) fwrite($fp, "$key = $value\n");
      fclose($fp);

      //$this->debug("\$map<br/>"."<pre>".print_r($map, true)."</pre>");

      return true;
    }

    protected function readMappingFile($file)
    {
        if (!file_exists($file)) return null;

        $fp = fopen($file, "r"); if (!$fp) return null;
        $data = array();

        while (!feof($fp)) {
            $columns = explode("=", trim(fgets($fp)));
            if (count($columns) >= 2) {
              $key = trim(array_shift($columns));
              $data[$key] = trim(implode("=", $columns));
            }
        }
        fclose($fp);
        $this->debug("<b>Mapping data from file:<br/></b><pre>" . print_r($data, true) . "</pre>");
        $this->debug("<b>\$data<br/><pre>".htmlentities(print_r($data, true))."</pre><br/>");
        return $data;
    }

    protected function mapAndWriteToDB(&$data, &$map, &$functionColumns, &$subselectColumns, $targetTable, $updateWhere = "", $clearTable = false, $pid = 0)
    {
        global $TCA;

        $this->debug("<b>\$functionColumns: </b></br><pre>".htmlentities(print_r($functionColumns,true))."</pre>\n");

        $tcaTableColumns = $TCA[$targetTable];
        $this->debug("<b>\$tcaTargetTable: </b></br><pre>".htmlentities(print_r($tcaTableColumns,true))."</pre>\n");
        if ($clearTable) {
            $this->debug("Clearing Table " . $targetTable . "");
            if ($pid) {
              $this->execStatement("delete from " . $targetTable . " where pid = " . $pid);
            } else {
              $this->hint("<font color=\"red\"><b>NEVER</b></font>"); //$this->execStatement("delete from " . $targetTable);
            }
        }

        $this->verboseHint("<b>Insert / Update - Statements:</b>");
        $insertArray = array();
        $this->debug("<b>\$data: </b></br><pre>".htmlentities(print_r($data,true))."</pre>\n");

        foreach ($data as $rownum => $column) {
            //$this->debug("<b>\$functionColumns: </b></br><pre>".htmlentities(print_r($functionColumns,true))."</pre>\n");
            foreach ($functionColumns as $columnName => $f) {
                eval("\$res = " . $this->parseVar($f, $column) . ";");
                $column[str_replace("'", "", $columnName)] = $res;
            }
            //$this->debug("<b>\$subselectColumns: </b></br><pre>".htmlentities(print_r($subselectColumns,true))."</pre>\n");
            foreach ($subselectColumns as $columnName => $f) {
                $result = $this->execStatement(trim($this->parseVar($f, $column)), true);
                while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
                    $column[str_replace("'", "", $columnName)] = $row[str_replace("'", "", $columnName)];
                }
            }

            $this->debug("<b>\$column: </b></br><pre>".htmlentities(print_r($column,true))."</pre>\n");

            foreach($column as $csvColumnName => &$value) {
              $tableColumnName = $map["'$csvColumnName'"];
              if ($tcaTableColumns['columns'][$tableColumnName]['config']['internal_type'] == 'file' ) {
                $this->debug("$tableColumnName is file<br/>\n");
                $pathinfo = pathinfo($this->formData['uploadDir']."/".$value);
                $basename = $pathinfo['basename'];
                $uploadFolder = PATH_site.$tcaTableColumns['columns'][$tableColumnName]['config']['uploadfolder'];
                $srcFile = PATH_site.$this->formData['uploadDir']."/".$value;
                if (is_file($srcFile)) {
                  $destFile = $uploadFolder.'/'.$basename;
                  if (!copy ($srcFile, $destFile)) {
                    $this->hint("<b> copy  ".htmlentities($srcFile)." to ".$destFile." failed</b><br/>\n");

                  }
                }
                $value = $basename;
              }
            }




            $this->debug("<b>\$map: </b></br><pre>".htmlentities(print_r($map,true))."</pre>\n");

            if ($updateWhere == "")
                $statement = $this->createInsertStatement($column, $map, $targetTable);
            else
                $statement = $this->createUpdateStatement($column, $map, $targetTable, $updateWhere);


            $this->execStatement($statement);
        }
    }

    protected function createInsertStatement(&$row, &$map, $tableName)
    {
        $line = "insert into " . $tableName;
        $set  = "\nset\n";
        foreach ($map as $csvColumnName => $sqlColumnName) {
            if (substr($csvColumnName, 0, 1) == "'" && substr($csvColumnName, -1) == "'")
                $line .= $set . $sqlColumnName . "=" . "'" . mysql_escape_string($row[str_replace("'", "", $csvColumnName)]) . "'";
            else
                $line .= $set . $sqlColumnName . "=" . $row[$csvColumnName];
            $set = ",\n";
        }
        return $line;
    }

    protected function createUpdateStatement(&$row, &$map, $tableName, $updateWhere)
    {
        $column =& $row;
        $line = "update " . $tableName;
        $set  = "\nset\n";
        foreach ($map as $csvColumnName => $sqlColumnName) {
            if (substr($csvColumnName, 0, 1) == "'" && substr($csvColumnName, -1) == "'")
                $line .= $set . $sqlColumnName . "=" . "'" . mysql_escape_string($row[str_replace("'", "", $csvColumnName)]) . "'";
            else
                $line .= $set . $sqlColumnName . "=" . $row[$csvColumnName];
            $set = ",\n";
        }
        $line .= "\n";
        $line .= $this->parseVar(trim($updateWhere), $row);
        //eval("\$line .= \"" . $this->parseVar(trim($updateWhere), $row) . "\";");
        return $line;

    }

    protected function parseVar($in, &$columns)
    {
        $startPos = strpos($in, "{");
        if ($startPos === FALSE) return $in;
        $endPos = strpos($in, "}");
        if ($endPos === FALSE)  return $in;
        $varName = substr($in, $startPos + 1, $endPos - $startPos - 1);
        $varVal  = $columns[$varName];

        $in  = substr($in, 0, $startPos) . $varVal . substr($in, $endPos + 1);
        $tmp = $this->parseVar($in, $columns);
        if ($tmp != $in) $in = $tmp;

        return $in;
    }

    protected function message($in)
    {
        return ($this->debug .= "\n" . $in . "\n");
    }
    protected function debug($in)
    {
      if ($this->enableDebug) return ($this->message($in));
      else return $this->debug;

    }
    protected function hint($in)
    {
        return($this->message($in));
    }
    protected function verboseHint($in)
    {
      if ($this->verbose) return ($this->message($in));
      else return $this->debug;
    }
    protected function execStatement($in, $overrideDryRun = false)
    {
        $tmpDebugState = $GLOBALS['TYPO3_DB']->debugOutput;

        $this->verboseHint($in);
        if ($this->verbose) $GLOBALS['TYPO3_DB']->debugOutput = true;

        if (!$this->formData['dryRun'] || $overrideDryRun) $result = $GLOBALS['TYPO3_DB']->sql_query($in);
        $GLOBALS['TYPO3_DB']->debugOutput = $tmpDebugState;
        return $result;
    }

    const CSV_HAS_HEAD = true;
    const CSV_HAS_NO_HEAD = false;
    const NO_OUTPUT = null;
    protected function readCsvFile($filename, &$colNames = array(), &$out = self::NO_OUTPUT, $head = self::CSV_HAS_HEAD, $delimiter = ';')
    {
        ini_set("auto_detect_line_endings", true);
        if (strlen($delimiter) == 0) $delimiter = ',';
        if (!file_exists($filename)) {
          return false;
        }
        $fp = fopen($filename, "r"); if (!$fp) {
         return false;
        }

        // read first line
        if ($data = fgetcsv($fp, 0, $delimiter, "\"")) {
            $num = count($data);
            // create default column names
            for ($c = 0; $c < $num; $c++) $colNames[$c] = sprintf("COL%04u", $c);
            if ($head == self::CSV_HAS_HEAD) {
              for ($c = 0; $c < $num; $c++) {
                $currentColName = htmlentities($data[$c]);
                if (strlen($currentColName) > 0) $colNames[$c] = $currentColName;
              }
              if ($out === self::NO_OUTPUT)
                return true;
            } else {
              if ($out === self::NO_OUTPUT)
                return true;
              $row = array();
              for ($c = 0; $c < $num; $c++) $row[$colNames[$c]] = $data[$c];
              $out[] = $row;
          }
        } else {
          return false;
        }

        while ($data = fgetcsv($fp, 0, $delimiter, "\"")) {
            $row = array(); $num = count($data);
            for ($c = 0; $c < $num; $c++) {
                $row[$colNames[$c]] = $data[$c];
            }
            $out[] = $row;
        }
        fclose($fp);

        $this->debug("<b>\$colNames in readCsvFile'():</b><br/><pre>".htmlentities(print_r($colNames, true))."</pre><br/>\n");
        $this->debug("<b>\$out in readCsvFile'():</b><br/><pre>".htmlentities(print_r($out, true))."</pre><br/>\n");

        return true;
    }

    const FULL_PATH = true;
    private function dirList($directory, $fullPath = false)
    {

        // create an array to hold directory list
        $results = array();

        // create a handler for the directory
        $handler = opendir($directory);

        // keep going until all files in directory have been read
        while ($file = readdir($handler)) {

            // if $file isn't this directory or its parent,
            // add it to the results array
            if ($file != '.' && $file != '..')
                $results[] = ($fullPath) ? ($directory . ((substr($directory, -1) == "/") ? "" : "/") . $file) : $file;
        }

        // tidy up: close the handler
        closedir($handler);

        // done!
        return $results;
    }

    /**
     * returns the formular
     *
     * @return  string
     */

    private function createFormular()
    {
        return "
            <form action='#' method='POST'>
                <input type='hidden' name='formTransmitted' value='formTransmitted'  />
                <label><b>Directory where CSV-Files are located or Filename of a single CSV-File to import</b><br>Example: fileadmin/my_csvfiles/ or fileadmin/my_csv.csv</label>
                <br />
                <input type='text' name='csvFilePath' value='" . $this->formData['csvFilePath'] . "' size='60'/>
                <br />
                <br />
                <label><b>Path to Mapping File (when importing a single CSV-File.).</b><br />The Mapping-File contains instructions on how to Map the CSV-Data to Database Fields when importing.<br>If no Mapping File ist specified the system searches for a Mapping-file named as the CSV-File with the Extension .map (Example: my_csv.map) </label>
                <br />
                <input type='text' name='mappingFilePath' value='" . $this->formData['mappingFilePath'] . "' size='60' />
                <br />
                <br />
                <label><b>Path to file upload directory.</b><br /></label>
                <br />
                <input type='text' name='uploadDir' value='" . $this->formData['uploadDir'] . "' size='60' />
                <br />
                <br />
                <label><b>Data separator Character in CSV-File (default is ;)</b><br>May be overridden in .map file</label>
                <br />
                <input type='text' name='separator' value='" . $this->formData['separator'] . "' size='5'/>
                <br />
                <br />
                <label><b>Table name to insert data into</b> (if not specified in .map File)</label>
                <br />
                <input type='text' name='targetTable' value='" . $this->formData['targetTable'] . "'  size='60'/>
                <br />
                <br />
                <label><b>pid</b><br>Page ID to set for imported data (not mandetory)</label>
                <br />
                <input type='text' name='storagePID' value='" . $this->formData['storagePID'] . "'  size='4'/>
                <br />
                <br />
                <label><b>Clear Table before insert</b><br>Be carefull using this option! Checking this Option deletes all data in specified table above.  If a PID is set only records with this PID are removed.</label>
                <br />
                <input type='checkbox' name='clearTable' " . $this->checked($this->formData['clearTable']) . " value ='1' />
                <br />
                <br />
                <label><b>Auto Map Fields</b><br />If no Mapping definition (from CSV-Data-Column to Dababase-Table-Column) exists in the .map-File, the system tries to Map fields on a Basis simularity between CSV-Column-Name and Database-Column-Name when using this Option.</label>
                <br />
                <input type='checkbox' name='autoMap' " .  $this->checked($this->formData['autoMap']) . " value ='1' />
                <br />
                <br />
                <!--label><b>Auto Map unmapped Fields and write to Mapping File (automaticaly enables &quot;Auto Map Fields&quot; above)</b><br />The Automatically Maps Fields and writes a Mapping-File (.map) and places it into the folder where your CSV-File is located.<br>This Option is recomendet if you don't have a .map File yet and want to autogenerate one. After Autogeneration it is recomendet to check and adapt the Mapping-File before you do the actual import. You may want to use the Dry-Run Option below in the course of this process.</label>
                <br />
                <input type='checkbox' name='autoMapUpdate' " . $this->checked($this->formData['autoMapUpdate']) . " value ='1' / -->
                <br />
                <br />
                <label><b>Dry run. Don't write to database</b><br>Only do Mapping and write Mapping-Files (if enabled above)</label>
                <br />
                <input type='checkbox' name='dryRun' " . $this->checked($this->formData['dryRun']) . " value ='1' />
                <br />
                <br />
                <label><b>Verbose Output</b><br>Display all SQL Staments generated during the import Process<br>(also displays Statements in Dry-Run-Mode)</label>
                <br />
                <input type='checkbox' name='verbose' " . $this->checked($this->verbose) . " value ='1' />
                <br />
                <div style=\"position: fixed; top: 0; right: 10px; display: inline-block;\">
                  <br />
                  <input type='submit' name='doImport' value='import' />&nbsp;<input type='submit' name='createMappingFile' value='create mapping file' />
                </div>
            </form><br /><br />
        ";
    }
    private function checked($var)
    {
      if ($var) return  "checked=\"checked\" ";
      return "";
    }

    private function createRecommendComment()
    {
        return "
            <h4>Recomended Workflow for Beginners:</h4>
            <p>
            <ul>
                <li>Specify the Path to your CSV-File (&quot;Directory where CSV-Files are located&quot;)
                <li>Specify the tablename you want to import the CSV-Data into</li>
                <li>Specify the Separator / Delimiter of your CSV-File-data (if not ;)</li>
                <li>Check the Option &quot;Dry run&quot; and &quot;Verbose Output&quot;</li>
                <li>Write your own Mapping-File or if you dont know how let this Module generate one using the Option &quot;Auto Map unmapped Fields&quot;</li>
                <li>Open the Mapping File and check the Mapping. It is located in the Directory of your CSV-File.</li>
                <li>Uncheck &qout;Dry run&quot; and start importing</li>
            </ul>
            View this example Mapping File that comes with this Extension: <a href='../typo3conf/ext/cag_importcsv/example.map' target='_new'><font color='blue'>Download</font></a>
            </p>
        ";
    }

    /**
     * @brief set form data from form respectively from extension configuration
     *
     * all excpect verbose is set in member formData, verbose is member by itsself
     *
     * @return  void
     */
    protected function updateFormData()
    {
        $this->formData = array(
          'csvFilePath'     => $this->getDataFromRequestOrExtConfString('csvFilePath'),
          'mappingFilePath' => $this->getDataFromRequestOrExtConfString('mappingFilePath'),
          'uploadDir'       => $this->getDataFromRequestOrExtConfString('uploadDir'),
          'separator'       => $this->getDataFromRequestOrExtConfString('separator'),
          'storagePID'      => $this->getDataFromRequestOrExtConfString('storagePID'),
          'targetTable'     => $this->getDataFromRequestOrExtConfString('targetTable'),
          'clearTable'      => $this->getDataFromRequestOrExtConfBool('clearTable'),
          'autoMap'         => $this->getDataFromRequestOrExtConfBool('autoMap'),
          'verbose'         => $this->getDataFromRequestOrExtConfBool('verbose'),
          'dryRun'          => $this->getDataFromRequestOrExtConfBool('dryRun'),
        );
        $this->verbose = $this->getDataFromRequestOrExtConfBool('verbose');

        $mappingFilePath = $this->formData['mappingFilePath'];
        $path = $this->formData['csvFilePath'];
        if (trim($mappingFilePath) == "" && trim($path) != "" && strrpos($path, ".") !== false) $mappingFilePath = substr($path, 0, strrpos($path, ".")) . ".map";
        $this->formData['mappingFilePath'] = $mappingFilePath;
    }

    private function getDataFromRequestOrExtConfString($name)
    {
      return (strlen(trim($_REQUEST[$name])) > 0) ? trim($_REQUEST[$name]) : trim($this->extConf[$name]);
    }
    private function getDataFromRequestOrExtConfBool($name)
    {
      if (isset($_REQUEST['formTransmitted'])) return isset($_REQUEST[$name]);
      return $this->extConf[$name] == 1;
    }
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cag_importcsv/mod1/index.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cag_importcsv/mod1/index.php']);
}

// Make instance:
$SOBE = t3lib_div::makeInstance('tx_cagimportcsv_module1');
$SOBE->init();

// Include files?
foreach ($SOBE->include_once as $INC_FILE)
    include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>
