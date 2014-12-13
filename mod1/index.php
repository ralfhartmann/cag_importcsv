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
$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.
	// DEFAULT initialization of a module [END]



/**
 * Module 'Import CSV' for the 'cag_importcsv' extension.
 *
 * @author	Jens Eipel <j.eipel@connecta.ag>
 * @package	TYPO3
 * @subpackage	tx_cagimportcsv
 */
class  tx_cagimportcsv_module1 extends t3lib_SCbase {
				var $pageinfo;

                var $verbose = false;
                var $dryRun = false;
                var $debug = "";

				/**
				 * Initializes the Module
				 * @return	void
				 */
				function init()	{
					global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

					parent::init();

					/*
					if (t3lib_div::_GP('clear_all_cache'))	{
						$this->include_once[] = PATH_t3lib.'class.t3lib_tcemain.php';
					}
					*/
				}

				/**
				 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
				 *
				 * @return	void
				 */
				function menuConfig()	{
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
				 * @return	[type]		...
				 */
				function main()	{
					global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

    
	        	    $this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['cag_importcsv']);

					// Access check!
					// The page will show only if there is a valid page and if this page may be viewed by the user
					$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
					$access = is_array($this->pageinfo) ? 1 : 0;
				
					if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id))	{

							// Draw the header.
						$this->doc = t3lib_div::makeInstance('mediumDoc');
						$this->doc->backPath = $BACK_PATH;
						$this->content.=$this->doc->startPage($LANG->getLL('title'));
						$this->content.=$this->doc->header($LANG->getLL('title'));


						// Render content:
						$this->moduleContent();


						// ShortCut
						if ($BE_USER->mayMakeShortcut())	{
							$this->content.=$this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']));
						}

						$this->content.=$this->doc->spacer(10);
					} else {
							// If no access or if ID == zero

						$this->doc = t3lib_div::makeInstance('mediumDoc');
						$this->doc->backPath = $BACK_PATH;

						$this->content.=$this->doc->startPage($LANG->getLL('title'));
						$this->content.=$this->doc->header($LANG->getLL('title'));
						$this->content.=$this->doc->spacer(5);
						$this->content.=$this->doc->spacer(10);
					}
				
				}

				/**
				 * Prints out the module HTML
				 *
				 * @return	void
				 */
				function printContent()	{

					$this->content.=$this->doc->endPage();
					echo $this->content;
				}

				/**
				 * Generates the module content
				 *
				 * @return	void
				 */
				function moduleContent()	{
					global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;
                            $path = (strlen(trim($_REQUEST['path'])) > 0) ? trim($_REQUEST['path']) : $this->extConf['csvFilePath'];
                            $pid = (isset($_REQUEST['submit'])) ? trim($_REQUEST['pid']) : $this->extConf['storagePID'];
                            $seperator = (strlen($_REQUEST['seperator']) > 0) ? $_REQUEST['seperator'] : ";";
                            $mappingFilePath = (strlen(trim($_REQUEST['mappingFilePath'])) > 0) ? trim($_REQUEST['mappingFilePath']) : trim($this->extConf['mappingFilePath']);
                            if (trim($mappingFilePath) == "" && trim($path) != "" && strrpos($path, ".") !== false)
                                $mappingFilePath = substr($path, 0, strrpos($path, ".")) . ".map";
                                
                            $targetTable = (strlen(trim($_REQUEST['targetTable'])) > 0) ? trim($_REQUEST['targetTable']) : $this->extConf['targetTable'];
                            $clearTable = ($_REQUEST['clearTable'] == "1") ? "1" : "0";
                            $this->verbose = ($_REQUEST['verbose'] == "1" || (!isset($_REQUEST['submit']))) ? true : false;
                            $autoMapUpdate = ($_REQUEST['automapupdate'] == "1") ? true : false;
                            if ($autoMapUpdate)
                                $autoMap = true;
                            else
                                $autoMap = ($_REQUEST['automap'] == "1" || (!isset($_REQUEST['submit']))) ? true : false;
                            $this->dryRun = ($_REQUEST['dryrun'] == "1" || (!isset($_REQUEST['submit']))) ? true : false;
                            $content = "<div id='typo3-docbody'><div id='typo3-docheader' /><div id='typo3-docbody'>
                                <form action='#' method='POST'>
                                    <label><b>Directory where CSV-Files are located or Filename of a single CSV-File to import</b><br>Example: fileadmin/my_csvfiles/ or fileadmin/my_csv.csv</label>
                                    <br />
                                    <input type='text' name='path' value='" . $path . "' size='60'/>
                                    <br />
                                    <br />
                                    <label><b>Path to Mapping File (when importing a single CSV-File.).</b><br />The Mapping-File contains instructions on how to Map the CSV-Data to Database Fields when importing.<br>If no Mapping File ist specified the system searches for a Mapping-file named as the CSV-File with the Extension .map (Example: my_csv.map) </label>
                                    <br />
                                    <input type='text' name='mappingFilePath' value='" . $mappingFilePath . "' size='60' />
                                    <br />
                                    <br />
                                    <label><b>Data separator Character in CSV-File (default is ;)</b><br>May be overridden in .map file</label>
                                    <br />
                                    <input type='text' name='seperator' value='" . $seperator . "' size='5'/>
                                    <br />
                                    <br />
                                    <label><b>Table name to insert data into</b> (if not specified in .map File)</label>
                                    <br />
                                    <input type='text' name='targetTable' value='" . $targetTable . "'  size='60'/>
                                    <br />
                                    <br />
                                    <label><b>pid</b><br>Page ID to set for imported data (not mandetory)</label>
                                    <br />
                                    <input type='text' name='pid' value='" . $pid . "'  size='4'/>
                                    <br />
                                    <br />
                                    <label><b>Clear Table before insert</b><br>Be carefull using this option! Checking this Option deletes all data in specified table above. </label>
                                    <br />
                                    <input type='checkbox' name='clearTable' " . (($clearTable == "1") ? " checked=checked" : "") . " value ='1' />
                                    <br />
                                    <br />
                                    <label><b>Auto Map Fields</b><br />If no Mapping definition (from CSV-Data-Column to Dababase-Table-Column) exists in the .map-File, the system tries to Map fields on a Basis simularity between CSV-Column-Name and Database-Column-Name when using this Option.</label>
                                    <br />
                                    <input type='checkbox' name='automap' " . (($autoMap) ? " checked=checked" : "") . " value ='1' />
                                    <br />
                                    <br />
                                    <label><b>Auto Map unmapped Fields and write to Mapping File (automaticaly enables &quot;Auto Map Fields&quot; above)</b><br />The Automatically Maps Fields and writes a Mapping-File (.map) and places it into the folder where your CSV-File is located.<br>This Option is recomendet if you don't have a .map File yet and want to autogenerate one. After Autogeneration it is recomendet to check and adapt the Mapping-File before you do the actual import. You may want to use the Dry-Run Option below in the course of this process.</label>
                                    <br />
                                    <input type='checkbox' name='automapupdate' " . (($autoMapUpdate) ? " checked=checked" : "") . " value ='1' />
                                    <br />
                                    <br />
                                    <label><b>Dry run. Don't write to database</b><br>Only do Mapping and write Mapping-Files (if enabled above) and print SQL-Statements if &quot;Verbose Ouput&quot; is enabled.</label>
                                    <br />
                                    <input type='checkbox' name='dryrun' " . (($this->dryRun) ? " checked=checked" : "") . " value ='1' />
                                    <br />
                                    <br />
                                    <label><b>Verbose Output</b><br>Display all SQL Staments generated during the import Process<br>(also displays Statements in Dry-Run-Mode)</label>
                                    <br />
                                    <input type='checkbox' name='verbose' " . (($this->verbose) ? " checked=checked" : "") . " value ='1' />
                                    <br />
                                    <br />
                                    <input type='submit' name='submit' value='import' />
                                </form><br /><br />
                            ";
                            if (!isset($_REQUEST['submit'])) {
                                $content .= "
                                <h4>Recomended Workflow for Beginners:</h4>
                                <p>
                                <ul>
                                    <li>Specify the Path to your CSV-File (&quot;Directory where CSV-Files are located&quot;)
                                    <li>Specify the tablename you want to import the CSV-Data into</li>
                                    <li>Specify the Seperator / Delimiter of your CSV-File-data (if not ;)</li>
                                    <li>Check the Option &quot;Dry run&quot; and &quot;Verbose Output&quot;</li>
                                    <li>Write your own Mapping-File or if you dont know how let this Module generate one using the Option &quot;Auto Map unmapped Fields&quot;</li>
                                    <li>Open the Mapping File and check the Mapping. It is located in the Directory of your CSV-File.</li>
                                    <li>Uncheck &quot;Dry run&quot; and start importing</li>
                                </ul>
                                View this example Mapping File that comes with this Extension: <a href='../typo3conf/ext/cag_importcsv/example.map' target='_new'><font color='blue'>Download</font></a>
                                </p>
                                ";
                            }


                            if ($_REQUEST['submit'] == "import") {
                                $file = t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT') . "/" . $path;
                                $files = array();
                                $maps = array();
                                if ($this->dryRun)
                                    $this->debug("<font color='red'><b>Notice: This is a dry run. No database statements will be executed.</b></font>");
                                if (is_dir($file)) {
                                    $this->debug("<b>Searching for CSV-Files (.csv) and Mapping-Files (.map) in directory: " . $file. "</b>");
                                    $files = $this->dirList($file, true, true);
                                    $isDir = true;
                                } else {
                                    if (!file_exists(t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT') . "/" . $mappingFilePath) || is_dir(t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT') . "/" . $mappingFilePath))
                                        $mappingFilePath = substr($file, 0, strlen($file) -4) . ".map";
                                    else 
                                        $mappingFilePath = t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT') . "/" . $mappingFilePath;
                                    $files[] = $file;
                                    $isDir = false;
                                }

                                $loopCount = 0;
                                foreach ($files as $file) {
                                    if (is_dir($file) || strpos($file, ".map") == strlen($file) - 4)
                                        continue;
                                    if (strtolower(substr($file, -4)) != ".csv") {
                                        // $this->debug("<hr size='1' style='border: 1px dotted black;'>Warning: Not a CSV-File. Skipping " . $file );
                                        continue;
                                    }
                                    $this->debug("<hr size='1' style='border: 1px dotted black;'><b>Processing File: " . $file . "</b><br><a href='../" . substr($file, strlen(t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT') . "/")) . "' target='_new'><font color='blue'>Click here to open CSV-File</font></a>");
                                    if ($isDir) {
                                        if (!file_exists($mappingFilePath)  || strpos($file, ".map") != strlen($file) - 4)
                                            $mappingFilePath = substr($file, 0, strlen($file) -4) . ".map";
                                    }
                                    if (file_exists($mappingFilePath))
                                        $this->debug("Looking for Mapping-File " . $mappingFilePath . "<br /><a href='../" . substr($mappingFilePath, strlen(t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT') . "/")) . "' target='_new'><font color='blue'>Click here to open Mapping-File</font></a>");
                                    else
                                        $this->debug("No Mapping File Found. Use the &quot;Auto Map unmapped fields&quot; Option above or create a Mapping file named:<br />" . substr($mappingFilePath, strlen(t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT') . "/")));
                                    $map = $this->getMap($mappingFilePath, $autoMapUpdate, trim($targetTable));
                                    if (is_numeric($pid))
                                        $map[':set pid'] = $pid; 
                                    if (!is_array($map))
                                        $this->debug("<h3>No Map found at</h3> " . $mappingFilePath);
                                    else {
                                        $functionColumns = array();
                                        $subselectColumns = array();
                                        $updateWhere = "";
                                        foreach ($map as $key => $value) {
                                            if ($key == ":table") {
                                                $targetTable = $value;
                                                unset($map[$key]);
                                            } else if (substr($key, 0, 5) == ":set ") {
                                                $fKey = trim(substr($key, 5));
                                                $functionColumns[$fKey] = $value;
                                                unset($map[$key]);
                                                $map[$fKey] = str_replace("'", "", $fKey);
                                            } else if (substr($key, 0, 11) == ":subselect ") {
                                                $fKey = trim(substr($key, 11));
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
                                                $seperator = $value;
                                                unset($map[$key]);
                                            } else if (substr($key, 0, 1) == ":" || substr($key, 0, 1) == "#") {
                                                unset($map[$key]);
                                            }
                                        }
                                        // automap the Rest of the fields if autoMap = true

                                        $data = $this->doCsvImport($file, true, $seperator, $map);
                                        if (!is_array($data) || sizeof($data) == 0) {
                                            $this->debug("<h3>No valid CSV-File found at " . $file . "</h3>");
                                        }  else {
                                            $map = $autoMap ? $this->autoMap($map, $data, $targetTable, $autoMapUpdate, $mappingFilePath) : $map;
                                            if (!is_array($data))
                                                $content .= "<h3>No CSV-File found at " . $location;
                                            else {
                                                $result = $this->mapAndWriteToDB($data, $map, $functionColumns, $subselectColumns, $targetTable, $updateWhere, $loopCount++ > 0 ? false : $clearTable);
                                            }
                                        }
                                    }
                                }
                            }
                            $content.= (strlen($this->debug) > 0) ? ("<pre>" . $this->debug . "</pre>") : "";
                            $content .= "</div></div>";
							$this->content .= $this->doc->section('',$content,0,1);
                }

                function autoMap(&$map, &$data, $targetTable, $autoMapUpdate = false, $file = null) {
                    if (!is_array($data[0])) {
                        $this->debug("Cannot Automap. No CSV-Data available");
                        return $map;
                    }
                    
                    $this->debug("Looking for Fields to Automap ...");
			        // $result = $this->execStatement("desc " . $targetTable, true);
			        $result = $GLOBALS['TYPO3_DB']->sql_query("desc " . $targetTable);
                    $sqlColumnNames = array();
			        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
                        if (strpos($row['Extra'], "auto_increment") !== false)
                            continue;
                        $sqlColumnNames[strtolower($row['Field'])] = strtolower($row['Field']);
                    }
                    $lookupMap = array();
                    foreach ($map as $csvColumnName => $sqlColumnName) {
                        $csvColumnNameTmp = strtolower(str_replace("'", "", $csvColumnName));
                        if (strpos($csvColumnNameTmp, ".") > 0)
                            $csvColumnNameTmp = substr($csvColumnNameTmp, strpos($csvColumnNameTmp, ".") + 1);
                        $lookupMap[$csvColumnName] = strtolower($csvColumnNameTmp);
                    }


                    foreach ($data[0] as $csvColumnName => $value) {
                        $csvColumnNameTmp = strtolower($csvColumnName);
                        $map2sql = "";
                        if (!in_array($csvColumnNameTmp, $lookupMap)) {
                            if (isset($sqlColumnNames[$csvColumnNameTmp])) {
                                $map2sql = $sqlColumnNames[$csvColumnNameTmp];
                                $csvColumnName = "'" . $csvColumnName . "'";
                                $map[$csvColumnName] = $map2sql;
                                $this->debug("Mapping " . $csvColumnName . " to " . $map2sql . " (exact match)"); 
                            } else {
                                $maxScore = 0;
                                foreach ($sqlColumnNames as $sqlColumnName) {
                                    $tmpScore = similar_text($sqlColumnName, $csvColumnNameTmp);
                                    if ($tmpScore > $maxScore && $tmpScore > 2) {
                                        $maxScore = $tmpScore;
                                        $map2sql = $sqlColumnName;
                                    }
                                }
                                if ($maxScore > 0) {
                                    $csvColumnName = "'" . $csvColumnName . "'";
                                    $map[$csvColumnName] = $map2sql;
                                    $this->debug("Mapping " . $csvColumnName . " to " . $map2sql . " (fuzzy match - Score: " . $maxScore . ")"); 
                                } else {
                                    $this->debug("No Mapping for " . $csvColumnName); 
                                    $csvColumnName = "'" . $csvColumnName . "'";
                                    $map[$csvColumnName] = "PLEASE_ADD_DATABASE_FIELD_NAME_HERE";
                                }

                            }
                            if ($map2sql != "" && $autoMapUpdate && $file != null) {
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
                    if ($map2sql == "")
                        $this->debug('No additional Fields automapped');

                    
                    return $map;
                }

                function getMap($file, $createFile = false, $targetTable = "") {
                    if (!file_exists($file)) {
                        if ($createFile) {
                            $this->debug("Creating Mapping File: " . $file . "");
                            $fp = fopen ($file, "a");
                            fwrite($fp, "# Autocreated Mappingfile on " . date("Y.m.d H:i:s") . "\n");
                            if ($targetTable != "")
                                fwrite($fp, ":table=" . $targetTable . "\n");
                            fclose($fp);
                        } else 
                            return false;
                    }
                    $fp = fopen ($file, "r");
                    $data = array();
                    while (!feof($fp)) {
                        $columns = explode("=", trim(fgets($fp)));
                        if (isset($columns[0]) && isset($columns[1])) {
                            $key = $columns[0];
                            unset($columns[0]);
                            $data[$key] = implode("=", $columns);
                        }
                    }
                    fclose($fp);
                    return $data;
                }

                function mapAndWriteToDB(&$data, &$map, &$functionColumns, &$subselectColumns, $targetTable, $updateWhere = "",  $clearTable = false) {
                        if ($clearTable) {
                            $this->debug("Clearing Table " . $targetTable . "");
			                $this->execStatement("delete from " . $targetTable);
                        }
                        if ($this->verbose)
                            $this->debug("<b>Insert / Update - Statements:</b>");
                        $insertArray = array();
                        foreach ($data as $rownum => $column) {
                            foreach ($functionColumns as $columnName => $f) {
                                eval("\$res = " . $this->parseVar($f, $column) . ";");
                                $column[str_replace("'", "", $columnName)] = $res;
                            }
                            foreach ($subselectColumns as $columnName => $f) {
			                    $result = $this->execStatement(trim($this->parseVar($f, $column)), true);
                                while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
                                    $column[str_replace("'", "", $columnName)] = $row[str_replace("'", "", $columnName)];
                                }
                            }
                            if ($updateWhere == "")
                                $statement = $this->createInsertStatement($column, $map, $targetTable);
                            else
                                $statement = $this->createUpdateStatement($column, $map, $targetTable, $updateWhere);
			                $this->execStatement($statement);
                        }
                }

                function createInsertStatement(&$row, &$map, $tableName) {
                    $line = "insert into " . $tableName ;
                    $set = "\nset\n";
                    foreach ($map as $csvColumnName => $sqlColumnName) {
                        if (substr($csvColumnName, 0, 1) == "'" && substr($csvColumnName, -1) == "'")
                            $line .= $set . $sqlColumnName ."=" . "'" . mysql_escape_string($row[str_replace("'", "", $csvColumnName)]) . "'";
                        else
                            $line .= $set . $sqlColumnName . "=" . $row[$csvColumnName];
                        $set = ",\n";
                    }
                    return $line;
                }

                function createUpdateStatement(&$row, &$map, $tableName, $updateWhere) {
                    $column = &$row;
                    $line = "update " . $tableName ;
                    $set = "\nset\n";
                    foreach ($map as $csvColumnName => $sqlColumnName) {
                        if (substr($csvColumnName, 0, 1) == "'" && substr($csvColumnName, -1) == "'")
                            $line .= $set . $sqlColumnName ."=" . "'" . mysql_escape_string($row[str_replace("'", "", $csvColumnName)]) . "'";
                        else
                            $line .= $set . $sqlColumnName . "=" . $row[$csvColumnName];
                        $set = ",\n";
                    }
                    $line .= "\n";
                    eval("\$line .= \"" . $this->parseVar(trim($updateWhere), $row) . "\";");
                    return $line;

                }

            function parseVar($in, &$columns) {
                $startPos = strpos($in, "{");
                if ($startPos === FALSE)
                    return $in;
                $endPos = strpos($in, "}");
                if ($endPos === FALSE)
                    return $in;
                $varName = substr($in, $startPos + 1, $endPos - $startPos - 1);
                $varVal = $columns[$varName];
                
                $in = substr($in, 0, $startPos) . $varVal . substr($in, $endPos + 1);
                $tmp = $this->parseVar($in, $columns);
                if ($tmp != $in)
                    $in = $tmp;
                return $in;
            }

                function debug($in) {
                    return ($this->debug .= "\n" . $in . "\n");
                }
            
                function execStatement($in, $overrideDryRun = false) {
                    $tmpDebugState = $GLOBALS['TYPO3_DB']->debugOutput;
                    
                    if ($this->verbose) {
                        $this->debug($in);
                        $GLOBALS['TYPO3_DB']->debugOutput = true;
                    }
                    if (!$this->dryRun || $overrideDryRun)
			            $result = $GLOBALS['TYPO3_DB']->sql_query($in);
                    $GLOBALS['TYPO3_DB']->debugOutput = $tmpDebugState;
                    return $result;
                }

                

                function doCsvImport($filename, $head = true, $delimiter = ';', $map = array()) {
                            ini_set("auto_detect_line_endings", TRUE);
                            $out = array();
                            $colNames = array();
                            if (!file_exists($filename))
                                return false;
                            $fp = fopen ($filename, "r");
                            if (strlen($delimiter) == 0)
                                $delimiter = ',';
                            while ($data = fgetcsv ($fp, 1000, $delimiter)) { 
                                $num = count ($data);
                                for ($c=0; $c < $num; $c++) {
                                    if ($head) {
                                      $colNames[$c] = htmlentities($data[$c]);
                                    
                                    } else
                                      $colNames[$c] = $c;
                                }
                                break;
                            }
                            if (!head) {
                              fclose($fp);
                              $fp = fopen ($filename, "r");
                            }


                            $i = 0;
                            while ($data = fgetcsv ($fp, 10000, $delimiter)) { 
                                $row = array();
                                $num = count ($data);
                                for ($c=0; $c < $num; $c++) {
                                    $row[$colNames[$c]] = $data[$c];
                                }
                                $out[$i++] = $row;
                            }
                            fclose ($fp);
                            return $out;
             }

            function dirList ($directory, $fullPath = false, $unzipContents = false) {
                if ($unzipContents) {
                    $handler = opendir($directory);
                    while ($file = readdir($handler)) {
                        if (strtolower(substr($file, -4)) == ".zip") {
                            $file = ($fullPath) ? ($directory . ((substr($directory, -1) == "/") ? "" : "/") . $file) : $file;
                            $cmd = "unzip -d '" . $directory . "' '" .  $file . "' ";
                            $this->debug("Extracting zip-File found in Data Directory<br>" . system($cmd));
                            // unlink($file);
                        }
                    }
                }

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



				
		}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cag_importcsv/mod1/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cag_importcsv/mod1/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_cagimportcsv_module1');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>
