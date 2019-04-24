<?php

class HtmlGenerator {

    const cssRegExpStr = '/(\w|\d|\_)(\w|\d|\-|\_|\.)*\.(?i)(css)/';// '(\w|\d|\_)(\w|\d|\-|\_|\.)*\.(?i)(css)(\?[\d\w\-\_\=\&\%]*)?\/?$';
    const jsRegExpStr = '/(\w|\d|\_)(\w|\d|\-|\_|\.)*\.(?i)(js)/'; //'(\w|\d|\_)(\w|\d|\-|\_|\.)*\.(?i)(js)(\?[\d\w\-\_\=\&\%]*)?\/?$'
    const imagesRegExpStr = '/(\w|\d|\_)(\w|\d|\-|\_|\.)*\.(?i)(jpeg|jpg|gif|png|svg|bmp)/';
    const videoRegExpStr = '/(\w|\d|\_)(\w|\d|\-|\_|\.)*\.(?i)(mpg|avi|wmv|mov|ogg|webm|mp4)/';
    const audioRegExpStr = '/(\w|\d|\_)(\w|\d|\-|\_|\.)*\.(?i)(mid|midi|wma|aac|wav|ogg|mp3)/';

    private $baseSourceUrls = array(
        'site_url' => 'file_name', //example
    );
    private $projectFolderPath;
    private $htmlFilesDomRepresent = array();
    private $linkStorage = array(
        'css' => array(),
        'js' => array(),
        'images' => array(),
        'video' => array(),
        'audio' => array(),
    );
    private $isFullMediaUrl; //For some CMS, this field help replace incorrect media url with correct.

    public static $curlSettings = array(
        'CURLOPT_SSL_VERIFYPEER' => false,
        'CURLOPT_HEADER' => false,
        'CURLOPT_FOLLOWLOCATION' => false,
        'CURLOPT_RETURNTRANSFER' => true,
    );

    public function __construct( $baseSourceUrls, $projectFolderPath, $isFullMediaUrl = true ) {
        $this->baseSourceUrls = $baseSourceUrls;
        $this->projectFolderPath = $projectFolderPath;
        $this->isFullMediaUrl = $isFullMediaUrl;
    }

    private function downloadFile ( $url, $filePath, $isMedia = false ) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, self::$curlSettings['CURLOPT_SSL_VERIFYPEER']);
        curl_setopt($curl, CURLOPT_HEADER, self::$curlSettings['CURLOPT_HEADER']);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, self::$curlSettings['CURLOPT_FOLLOWLOCATION']);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_REFERER, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, self::$curlSettings['CURLOPT_RETURNTRANSFER']);
        $fileContent = curl_exec($curl);
        curl_close($curl);
        $newFile = fopen($filePath, "w+");
        fwrite($newFile, $fileContent);
        fclose( $newFile );
    }

    private function initDomDocumentHtmlFiles () {
        if ( empty( $this->baseSourceUrls ) ) {
            return;
        }
        foreach ($this->baseSourceUrls as $siteUrl => $fileName) {
            $doc = new DOMDocument();
            libxml_use_internal_errors(true);
            $doc->loadHTMLFile( $this->projectFolderPath . DIRECTORY_SEPARATOR . $fileName );
            libxml_use_internal_errors(false);
            $this->htmlFilesDomRepresent[$fileName] = $doc;
        }
    }

    private function saveDomDocumentHtmlFiles () {
        if ( empty( $this->baseSourceUrls ) ) {
            return;
        }
        foreach ($this->htmlFilesDomRepresent as $filename => $domDoc) {
            $domDoc->saveHTMLFile($this->projectFolderPath . DIRECTORY_SEPARATOR . $filename);
        }
    }

    private function downloadRelativeFiles ( $REGEX, $htmlTag, $tagAttr, $fileTypeName ) {
        if ( empty($this->htmlFilesDomRepresent) ) {
            return;
        }

        if ( !file_exists( $this->projectFolderPath . DIRECTORY_SEPARATOR . $fileTypeName ) ) {
            mkdir( $this->projectFolderPath . DIRECTORY_SEPARATOR . $fileTypeName, 0755, true );
        } 

        foreach ($this->htmlFilesDomRepresent as $fileName => $domDoc) {
            $fileLinkArray = $domDoc->getElementsByTagName($htmlTag);
            if ( empty($fileLinkArray) ) {
                continue;
            }
            foreach ($fileLinkArray as $fileLink) {
                $src = $fileLink->getAttribute($tagAttr);                
                if ( empty( $src ) ){
                    continue;
                }
                $matches = array();
                preg_match( $REGEX, $src, $matches );                
                if ( empty( $matches ) ) {
                    continue;
                }
                $fileExtension = pathinfo($matches[0], PATHINFO_EXTENSION);
                if ( array_key_exists( $src, $this->linkStorage[$fileTypeName] ) ){
                    continue;
                }
                if ( in_array( $matches[0], $this->linkStorage[$fileTypeName] ) ) {
                    $file_counter = 1;
                    $fileName = $matches[0];                    
                    $fileNumbPos = strpos($fileName, '.'. $fileExtension);    
                    do {
                        $newFileName = substr( $fileName, 0, $fileNumbPos ) . $file_counter . '.' .$fileExtension;
                        $filePath = $this->projectFolderPath . DIRECTORY_SEPARATOR . $fileTypeName . DIRECTORY_SEPARATOR . $newFileName;
                        $file_counter++;
                    } while ( file_exists( $filePath ) );
                    if ( $this->isFullMediaUrl ) {
                        $this->downloadFile($src, $filePath);
                    } else {
                        $this->downloadFile($this->fixUrlForDownload( $src ), $filePath);
                    }
                    $this->linkStorage[$fileTypeName][$src] = $fileName;
                    $fileLink->setAttribute($tagAttr, $fileTypeName . DIRECTORY_SEPARATOR . $newFileName);
                } else {
                    $this->linkStorage[$fileTypeName][$src] = $matches[0];
                    if ( $this->isFullMediaUrl ) {
                        $this->downloadFile($src, $this->projectFolderPath . DIRECTORY_SEPARATOR . $fileTypeName . DIRECTORY_SEPARATOR . $matches[0]);
                    } else {
                        $this->downloadFile($this->fixUrlForDownload( $src ), $this->projectFolderPath . DIRECTORY_SEPARATOR . $fileTypeName . DIRECTORY_SEPARATOR . $matches[0]);
                    }
                    $fileLink->setAttribute($tagAttr, $fileTypeName . DIRECTORY_SEPARATOR . $matches[0]);
                }           
            }
        }
    }

    public function doConversion() {
        $this->initDomDocumentHtmlFiles();
        $this->downloadRelativeFiles( self::cssRegExpStr, 'link', 'href', 'css' );
        // $this->downloadCssFiles();
        // $this->downloadJsFiles();
        // $this->downloadImageFiles();

        // $this->initCssFileLinksStorage();
        // $this->initJsFileLinksStorage();
        // $this->initImgFileLinksStorage();
        // $this->initMediaFileLinksStorage();
        // $this->saveDomDocumentHtmlFiles();
        // var_dump($this->linkStorage);    
    }

    public function createHtmlFiles ( ) {
        if ( !file_exists( $this->projectFolderPath ) ){
            mkdir($this->projectFolderPath, 0755, true);            
        }
        if ( empty( $this->baseSourceUrls ) ) {
            return;
        }
        foreach ($this->baseSourceUrls as $siteUrl => $fileName) {
            $this->downloadFile($siteUrl, $this->projectFolderPath . DIRECTORY_SEPARATOR . $fileName);
        }
        $this->initDomDocumentHtmlFiles();
    }
    
}