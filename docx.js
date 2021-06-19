
document.querySelector('#editeur_text_DOC').addEventListener('click', event => {
   var data = CKEDITOR.instances.editor.getData();

var preHtml = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'><head><meta charset='utf-8'><title>Export HTML To Doc</title></head><body>";
    var postHtml = "</body></html>";
    var html = preHtml + data + postHtml ;
    var blob = new Blob(['\ufeff', html], {
        type: 'application/msword'
    });
    var url = 'data:application/vnd.ms-word;charset=utf-8,' + encodeURIComponent(html);
    var nomDocument = document.getElementById("documentNom").innerHTML;
    var idDocument = document.getElementById("documentId").innerHTML;
var url = 'https://127.0.0.1:8000/editeur/text/modifier/'+idDocument+'/index/'+page;
  $.ajax({  
  type: "POST",  
  contentType: "application/json; charset=utf-8",  
  url: url,  
  data: { 'ListID': '1' },  
  dataType: "json",  
  success: function(response) { alert("item added"); },  
  error: function(xhr, ajaxOptions, thrownError) { alert(xhr.responseText); }
});
  
    filename = nomDocument+'_'+idDocument+'.doc';
    
    // Create download link element
    var downloadLink = document.createElement("a");

    document.body.appendChild(downloadLink);
    
    if(navigator.msSaveOrOpenBlob ){
        navigator.msSaveOrOpenBlob(blob, filename);
    }else{
        // Create a link to the file
        downloadLink.href = url;
        
        // Setting the file name
        downloadLink.download = filename;
        
        //triggering the function
        downloadLink.click();
    }
    
    document.body.removeChild(downloadLink);
  //handle click
})
