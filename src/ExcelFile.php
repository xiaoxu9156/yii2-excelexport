<?php
namespace codemix\excelexport;

use Yii;
use yii\base\Object;
use mikehaertl\tmp\File;

/**
 * This class represents an excel file.
 */
class ExcelFile extends Object
{
    /**
     * @var string the writer class to use. Default is `\PHPExcel_Writer_Excel2007`.
     */
    public $writerClass = '\PHPExcel_Writer_Excel2007';

    /**
     * @var array options to pass to the constructor of \mikehaertl\tmp\File,
     * indexed by option name. Available keys are 'suffix', 'prefix' and 'directory'.
     * This is only useful if creation of the temporary file fails for some reason.
     */
    public $fileOptions = [];

    protected $_writer;
    protected $_workbook;
    protected $_sheets;
    protected $_tmpFile;
    protected $_fileCreated = false;
    protected $_sheetsCreated = false;

    /**
     * @return PHPExcel_Writer_Abstract the writer instance
     */
    public function getWriter()
    {
        if ($this->_writer===null) {
            $class = $this->writerClass;
            $this->_writer = new $class($this->getWorkbook());
        }
        return $this->_writer;
    }

    /**
     * @return PHPExcel the workbook instance
     */
    public function getWorkbook()
    {
        if ($this->_workbook===null) {
            $this->_workbook = new \PHPExcel();
        }
        return $this->_workbook;
    }

    /**
     * @return mikehaertl\tmp\File the instance of the temporary excel file
     */
    public function getTmpFile()
    {
        if ($this->_tmpFile===null) {
            $suffix = isset($this->fileOptions['suffix']) ? $this->fileOptions['suffix'] : null;
            $prefix = isset($this->fileOptions['prefix']) ? $this->fileOptions['prefix'] : null;
            $directory = isset($this->fileOptions['directory']) ? $this->fileOptions['directory'] : null;
            $this->_tmpFile = new File('', $suffix, $prefix, $directory);
        }
        return $this->_tmpFile;
    }

    /**
     * @return array the sheet configuration
     */
    public function getSheets()
    {
        return $this->_sheets;
    }

    /**
     * @param array $value the sheet configuration. This must be an array where keys
     * are sheet names and values are arrays with the configuration options for an
     * instance if `ExcelSheet`.
     */
    public function setSheets($value)
    {
        $this->_sheets = $value;
    }

    /**
     * Save the file under the given name
     *
     * @param string $filename
     * @return bool whether the file was saved successfully
     */
    public function saveAs($filename)
    {
        $this->createFile();
        return $this->getTmpFile()->saveAs($filename);
    }

    /**
     * Send the Excel file for download
     *
     * @param string|null $filename the filename to send. If empty, the file is streamed inline.
     * @param bool $inline whether to force inline display of the file, even if filename is present.
     */
    public function send($filename = null, $inline = false)
    {
        $this->createFile();
        $this->getTmpFile()->send($filename, 'application/vnd.ms-excel', $inline);
    }

    /**
     * Create the Excel sheets if they were not created yet
     */
    public function createSheets()
    {
        if (!$this->_sheetsCreated) {
            $workbook = $this->getWorkbook();
            $i = 0;
            foreach ($this->sheets as $title => $config) {
                if (is_string($config)) {
                    $config = ['class' => $config];
                } elseif (is_array($config)) {
                    if (!isset($config['class'])) {
                        $config['class'] = ExcelSheet::className();
                    }
                } elseif (!is_object($config)) {
                    throw new \Exception('Invalid sheet configuration');
                }
                $sheet = (0===$i++) ? $workbook->getActiveSheet() : $workbook->createSheet();
                if (is_string($title)) {
                    $sheet->setTitle($title);
                }
                Yii::createObject($config, [$sheet])->render();
            }
            $this->_sheetsCreated = true;
        }
    }

    /**
     * Create the Excel file and save it to the temp file
     */
    protected function createFile()
    {
        if (!$this->_fileCreated) {
            $this->createSheets();
            $this->getWriter()->save((string) $this->getTmpFile());
            $this->_fileCreated = true;
        }
    }
}
