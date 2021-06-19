<?php

namespace App\Service;

use Imagick;
use DOMXPath;
use DOMDocument;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Version;
use Spipu\Html2Pdf\Html2Pdf;
use PhpOffice\PhpWord\PhpWord;
use App\Entity\DocumentOriginal;
use PhpOffice\PhpWord\Shared\Html;
use Symfony\Component\Finder\Finder;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpWord\Shared\ZipArchive;
use Spipu\Html2Pdf\Exception\Html2PdfException;
use Spipu\Html2Pdf\Exception\ExceptionFormatter;

class EditeurTextService
{
 /**
     * @var EntityManagerInterface
     */
    private $manager;


    /**
     * DocumentService constructor.
     * @param EntityManagerInterface $manager
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }


    public function EnregisterPDF(
        $formulaire,$contenu,$nomDocument,$documentOriginal,$version
        ,$chemin_version
        ){        
                   
              $pdfOptions = new Options();
            $pdfOptions->set('defaultFont', 'Arial');

            $dompdf = new Dompdf($pdfOptions);
            $dompdf->loadHtml(mb_convert_encoding($contenu, 'HTML-ENTITIES', 'UTF-8'));
            $dompdf->setPaper('A4', 'portrait');
            // (Optional) Setup the paper size and orientation
         
            // Render the HTML as PDF
             $chemin =  "documentOriginals\\version\\".$nomDocument.".pdf";
            $dompdf->render($chemin);
             $dompdf->set_base_path($chemin);
           $output = $dompdf->output();
        
        // Write file to the desired path
           file_put_contents($chemin, $output);
         $longueur=filesize($chemin);
        
     
      $this->bdd($documentOriginal,$formulaire,$chemin,$longueur,$nomDocument,$chemin_version,$version,$format="PDF");
            

           
    }

 

    public function EnregistrerDOC($formulaire,$contenu,$nomDocument
    ,$documentOriginal,$version,$documentId,$nomDocumentDocJs

){
  
    $document_doc= "documentOriginals/".$nomDocumentDocJs.'_'.$documentId.".doc";
    $chemin="documentOriginals/version/".$nomDocument.'_'.$documentId."doc";
   file_put_contents($document_doc,$chemin);

 if (file_exists( $chemin)){
    
      unlink($document_doc);
      }
        
     $longueur=filesize($chemin);
       $this->bdd($documentOriginal,$formulaire,$chemin
            ,$longueur,$nomDocument,$contenu,$version,$format="DOC");
       
    }



    public function EnregistrerImage($formulaire,$contenu,$nomDocument
    ,$documentOriginal,$version

){
         $grabzIt = new \GrabzIt\GrabzItClient("ZjQzNzE2ODEwNmZjNGMwYWIyOTUyZjdjNTdjM2Y0MDE=",
             "Pz8/P1QdPz8/Yj94Bjk4PxZ1Xz8/IVY/PzQyPz8pPz8=");
            $grabzIt->HTMLToImage($contenu);
            //Then call the Save method
             $chemin="documentOriginals\\version\\".$nomDocument.".jpg";
            $grabzIt->SaveTo($chemin);
            $longueur=filesize($chemin);
            $this->bdd($documentOriginal,$formulaire,$chemin
            ,$longueur,$nomDocument,$contenu,$version,$format="JPG");

        
    }

       public function bdd(DocumentOriginal $documentOriginal,
        $formulaire,
        $chemin,$longueur,$nomDocument,$contenu,$version,$format){
        $version->setNomDocument($nomDocument)
            ->setVersion($format)
            ->setChemin($chemin)
            ->setTailleDocument($longueur)
            ->setDateAjout(date("Y-m-d H:i:s"))
            ->setDateModification("null")
            ->setDocumentOriginal($documentOriginal)
            ->setUrlVersionConverti($contenu);
          $this->manager->persist($version);
    return $this->manager->flush();
    }



    public function EnregisterPlanImage($nomDocument,$content,$documentOriginal
    ,$formulaire,$version){
      list($type, $content) = explode(';', $content);
            list(, $content)      = explode(',', $content);

            $content = base64_decode($content);
          $cheminComplet="documentOriginals\\version\\".$nomDocument.".png";
          file_put_contents($cheminComplet, $content);

          $this->bdd($documentOriginal,
        $formulaire,
        $cheminComplet,filesize($cheminComplet),$nomDocument,$cheminComplet,$version,"PNG");

    }


    public function EnregistrerPlanPDF($nomDocument,$content,
    $documentOriginal
    ,$formulaire,$version){

          $pdfOptions = new Options();
            $pdfOptions->set('defaultFont', 'Arial');
           
          $htmlCode ='<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Document</title>
        </head>
        <body>
        <style>
                @page {
                    size: 2480   px; 3508   px;
                    margin: 0px;
                    padding: 0px;
                }
                body {
                    margin: 0px;
                }
            </style>

            <img src="'.$content.' style="max-width: 100%;">
        </body>
        </html>';
             $dompdf = new Dompdf($pdfOptions);
            $dompdf->loadHtml(mb_convert_encoding($htmlCode, 'HTML-ENTITIES', 'UTF-8'));
            $dompdf->setPaper('A4', 'portrait');
            // (Optional) Setup the paper size and orientation

            // Render the HTML as PDF
             $chemin =  "documentOriginals\\version\\".$nomDocument.".pdf";
            $dompdf->render($chemin);
             $dompdf->set_base_path($chemin);
           $output = $dompdf->output();        
        // Write file to the desired path
            file_put_contents($chemin, $output);

           $longueur=filesize($chemin);

           list($type, $content) = explode(';', $content);
            list(, $content)      = explode(',', $content);

            $content = base64_decode($content);
          $cheminComplet="documentOriginals\\version\\".$nomDocument.".png";
          file_put_contents($cheminComplet, $content);

          

       $this->bdd($documentOriginal,$formulaire,
        $chemin,$longueur,$nomDocument,$cheminComplet,$version,"PDF");

    }
}