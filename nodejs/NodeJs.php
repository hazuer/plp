<?php

/**
 * @author Isidoro Cornelio
 *
 */
class NodeJs {

    private $nameFile;
    private $pathFile;
    private $fullPathFile;

    /**
    * Constructor de la clase
    */
    public function __construct($init){
        $this->setNameFile($init['nameFile']);
    }

    public function setNameFile($nameFile){
        $uniqueID  = rand(11111, 99999);
        $now       = new DateTime();
        $timeStamp = $now->getTimestamp();
        #$this->nameFile = $nameFile."_".$uniqueID."_".$timeStamp.".js";
        $this->nameFile = $nameFile.".js";
    }

    public function getFullPathFile(){
        return $this->fullPathFile;
    }

    public function createContentFileJs($pathFile,$jsContent){
        $this->pathFile = $pathFile;
        $this->fullPathFile = $this->pathFile.$this->nameFile;
        $file=fopen($this->fullPathFile,"w");
        fwrite($file,$jsContent);
        fclose($file);
       }

    /**
     * Read and print all content file created
     * false:break, true:continue
     */
    public function getContentFile($break=false){
        $contenido_js = file_get_contents($this->fullPathFile);
        echo "<pre>" . htmlspecialchars($contenido_js) . "</pre>";
        if(!$break){
            die();
        }
    }
}