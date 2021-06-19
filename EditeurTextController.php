<?php

namespace App\Controller;

use DOMXPath;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Version;
use Convertio\Convertio;
use GrabzIt\GrabzItClient;
use Mnvx\Lowrapper\Format;
use App\Form\EditeurTextType;
use Mnvx\Lowrapper\Converter;
use PhpOffice\PhpWord\PhpWord;
use GrabzIt\GrabzItDOCXOptions;
use App\Entity\DocumentOriginal;
use PhpOffice\PhpWord\Shared\Html;
use App\Service\EditeurTextService;
use Symfony\Component\Finder\Finder;
use Mnvx\Lowrapper\LowrapperParameters;
use Doctrine\ORM\EntityManagerInterface;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\Session;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class EditeurTextController extends AbstractController
{
  


  public function document_existe($nomDocument){

    $repo = $this->getDoctrine()->getRepository(Version::class);
   $documentExistants = $repo->findBy([
            'nom_document'=> $nomDocument,       
        ]);
        return sizeof($documentExistants);
     
  }


    /**
     * @Route("/editeur/text/id_document/{id_document}", name="editeur_text")
     * @IsGranted("ROLE_USER")

     */
    public function Editeur($id_document,Request $request,EntityManagerInterface $manager, EditeurTextService $service): Response
    {
  
     $content="";
      $nom="";
              $session=new Session();  
            $data= $session->get('data');
              $nomDocument= $data['nomDocument'];

         
           
             $id_document;
            $formulaire = $this->createForm(EditeurTextType::class,null,array('text'=>$nomDocument));
              $formulaire->handleRequest($request);
                      $utilisateur = $this->getUser();

       $repo = $this->getDoctrine()->getRepository(DocumentOriginal::class);
       $documentOriginal = $repo->findOneBy([
            'id'=> $id_document,
            'id_utilisateur' => $utilisateur->getId(),
           
        ]);
        $contenu =file_get_contents($documentOriginal->getUrlContenuConverti());

       
         $finder = new Finder();
         $version = new Version();

    
       
        if ($formulaire->get('PDF')->isClicked()){
           $nom=$formulaire->getData('nom_document')["nom_document"];
         $this->documentExistant('PDF',$formulaire,$nom
        ,$documentOriginal,$version,$service,$id_document);

       return $this->redirectToRoute('mes_documents');

      } 
        
       else  if ($formulaire->get('DOC')->isClicked()) {
  

   
      $nomDocument=$formulaire->getData('nom_document')["nom_document"];

     
     
     $nombreoccurencedocument= $this->document_existe($nomDocument);

     if($nombreoccurencedocument>0){

   $nomDocument .= '('.$nombreoccurencedocument.')';

   }
       $this->documentExistant('DOC',$formulaire,$nomDocument
        ,$documentOriginal,$version,$service,$id_document);

   
         return $this->redirectToRoute('mes_documents');
       
        }
            
      
    
        return $this->render('editeur_text/editeurText.html.twig', [
          'formulaire'=> $formulaire->createView(),
          'text' =>$contenu,
          'nomDocument' => $nomDocument,
          'id_document' => $id_document,
        ]);
    
 
}




  /**
  * @Route("/editeur/text/modifier/{id}/index/{page}", name="editeur_text_modifier",options={"expose"=true})
  *@Route("editeur", name="editer")
  * @IsGranted("ROLE_USER") 
  * @Method({"POST"})
  */
  public function modifierContenu($id,$page,Request $request,EntityManagerInterface $manager, EditeurTextService $service): Response{
       
    
    
    
    
    $id_document= $id;
    $versions = null;
    $contents="";
        $session=new Session();  
         $version = new Version();
          $repo = $this->getDoctrine()->getRepository(DocumentOriginal::class);
         $document = $repo->findOneBy(array(
            'id' => $id
        ));

        
                       
        if (!($document ==null)){
         $utilisateur = $this->getUser();
          $contenu=file_get_contents($document->getUrlContenuConverti());
          $nomDocumentDocJs=$document->getNomDocument();
        $formulaire=$this ->createForm(EditeurTextType::class,null,array('text'=>$nomDocumentDocJs));
        $formulaire->handleRequest($request);
         
       if ($formulaire->get('PDF')->isClicked()){
          
        $contenu=file_get_contents($document->getUrlContenuConverti());
$nomDocument=$formulaire->getData('nom_document')["nom_document"];
          $this ->documentExistant(
            "PDF",$formulaire,$nomDocument
            ,$document,$version,$service,$id_document,$nomDocumentDocJs);

            $version->setDateModification(date("Y-m-d H:i:s"));
            $manager->flush($version);
              return $this->redirectToRoute('mes_documents', array('page' => $page));

      } 
        
          if ($formulaire->get('DOC')->isClicked()) {
     
       
$nomDocument=$formulaire->getData('nom_document')["nom_document"];
             $this ->documentExistant(
            "DOC",$formulaire,$nomDocument
            ,$document,$version,$service,$id_document,$nomDocumentDocJs);
             $version->setDateModification(date("Y-m-d H:i:s"));
            $manager->flush($version);
              return $this->redirectToRoute('mes_documents', array('page' => $page));
      }
      
      }else{
          
        $repo = $this->getDoctrine()->getRepository(Version::class);
      $versions = $repo->findOneBy(array(
            'id' => $id,
        ));

        $nomDocumentDocJs=$versions->getNomDocument();

        $utilisateur = $this->getUser();
        $formulaire = $this->createForm(EditeurTextType::class,null,array('text'=>$nomDocumentDocJs));
        $formulaire->handleRequest($request);
        $contenu=file_get_contents($versions->getUrlVersionConverti());
           $document = $versions->getDocumentOriginal();
      
        if ($formulaire->get('PDF')->isClicked()){
          
        $nomDocument=$formulaire->getData('nom_document')["nom_document"];

          $this ->documentNouveauFormat("PDF",$versions,
          $manager,$formulaire,$nomDocument
              ,$document,$version,$service,$id_document,$nomDocumentDocJs);
               $version->setDateModification(date("Y-m-d H:i:s"));
               $manager->flush();
              return $this->redirectToRoute('mes_documents', array('page' => $page));

      } 
          if ($formulaire->get('DOC')->isClicked()) {
          dd  ($request->request->get('ListID'));

            
   /*  $doc =    $formulaire->getData('docbase64')["docbase64"];
             $ta= base64_decode($doc);
          $file = fopen('test220dadaa0d27fe.doc', 'w');    
          fwrite($file, $ta);*/
      
$nomDocument=$formulaire->getData('nom_document')["nom_document"];

             $this ->documentNouveauFormat("DOC",$versions,
          $manager,$formulaire,$nomDocument
              ,$document,$versions,$service,$id_document,$nomDocumentDocJs);

              $versions->setDateModification(date("Y-m-d H:i:s"));
              $manager->persist($versions);
            $manager->flush();
            
              return $this->redirectToRoute('mes_documents', array('page' => $page));
      }

      
    }
      return $this->render('editeur_text/editeurText.html.twig', [
          'formulaire'=> $formulaire->createView(),
          'text' =>$contenu,
          'nomDocument' => $nomDocumentDocJs,
          "id_document" => $id,
          "page" => $page

      ]);
  }



  public function documentNouveauFormat($format,$versions,$manager
  ,$formulaire,$nomDocument
  ,$document,$version,$service,$id_document,$nomDocumentDocJs){

    if ($versions->getVersion() == $format){
              
                $this->documentExistant($format,$formulaire,$nomDocument
              ,$document,$versions,$service,$id_document,$nomDocumentDocJs);
              
        
            } else{

          $this->documentExistant($format,$formulaire,$nomDocument
        ,$document,$version,$service,$id_document,$nomDocumentDocJs);
   }


  }


  public function documentExistant($format,$formulaire,$nomDocument,
  $document,$version,$service,$id_document,$nomDocumentDocJs){

    if (($format == "DOC")) {
            $content =  $formulaire->getData('content')["content"];
   
    $url_contenu_convert = "documentOriginals\documentscontents\\". $nomDocument.'_'.$id_document.".txt";

            file_put_contents($url_contenu_convert,$content);
            $conversion=$service->EnregistrerDOC( $formulaire,$url_contenu_convert,$nomDocument
        ,$document,$version,$id_document,$nomDocumentDocJs);}

          else if (($format == "PDF")){

        $content =  $formulaire->getData('content')["content"];
        $nomDocument =  $formulaire->getData('nom_document')["nom_document"];
  dd  (file_exists ("documentOriginals/documentscontents/". $nomDocument.'_'.$id_document.".txt"));
          file_put_contents($url_contenu_convert,$content);
        $conversion=$service->EnregisterPDF( $formulaire,$content,$nomDocument
        ,$document,$version,$url_contenu_convert
        ); 
    }
  }

}

