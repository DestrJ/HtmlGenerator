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