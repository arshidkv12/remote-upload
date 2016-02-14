jQuery(document).ready(function()
    {
        jQuery.ajaxSetup(
        {
            cache: false,
            async: true
 
        });
 
   console.log( php_vars.file_json_url);
        //refresh_call();
         var flag  = true;
         var interval = setInterval(function(){ 
        if( flag == true){
             flag = false;
            //console.log(flag);
           jQuery( "#file_url" ).prop( "disabled", true );
            //var $container = $("#content"); 
            //$container.load(php_vars.post_file_url);
            //$container.hide();

            jQuery.post(php_vars.post_file_url,
            {
                file_urls: php_vars.file_urls,
            },
            function(data, status){
                //alert("Data: " + data + "\nStatus: " + status);
            });
         }
 

     jQuery.ajax({
        dataType: "json",
        url: php_vars.json_data,
        success: function(data){
      
        if(data != null){   

            var numerator = (data.url_count*100)+data.percentage;
            var denominator = data.total_url_count*100;
            var percentage = numerator/denominator || 0 ;
            console.log(percentage*100+" %");

            jQuery('#loader').html(Math.round(percentage*100) +" %");


            if(data.percentage =='done..!'){
             jQuery( "#file_url" ).prop( "disabled", false );
             jQuery('#loader').html(data.percentage+'     Please go to <a href="upload.php" target="_blank">Media</a> and check files.');
               clearInterval(interval);
             }
        }
      }
    });

         
 
            
             }, 1000);
        

      

 }); 