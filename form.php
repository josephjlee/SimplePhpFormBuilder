<?php

require 'settings/tracy-2.6.2/src/tracy.php';
use Tracy\Debugger;

$formId = "";
if(isset($_GET["id"])){
    $formId = $_GET["id"];
}
//if($formId == ""){
 //   die("No form to Render!!!");
//}
if (!preg_match('/^[0-9]+$/', $formId) || $formId == "") {
    die("No form to Render!!!");
} 

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['rederect_url'] = "form";
$_SESSION['form_id'] = $formId;
$_SESSION['is_form_reload'] = "0";

////////get settings ///////
if(!isset($isGetSetting)){
    require 'get_setting_data.php';
}
$appMode = getSetting("", "appMode");
if($appMode == "0"){
    //Debug mode
    Debugger::enable();
}
require 'settings/database.login.php';

$formDataAry = getFormData($conn, $formId);
$formType = $formDataAry[0];
$formStatus = $formDataAry[1];
$formTitle = $formDataAry[2];
$formStyle = $formDataAry[3];
$rstrcSubmit = $formDataAry[4];


//is set cookie protection
$isCookieSet = getSetting("", "enableUsingCookies");
if($isCookieSet == "1" && $rstrcSubmit != "-1"){
    $cookieName = md5("form_$formId");
    $cookieLifetime = getSetting("", "cookiesLifeTime");
    //$cookieValue = "1";
    if(!isset($_COOKIE[$cookieName]) ) {
        setcookie($cookieName, "1", time() + (86400 * (int)$cookieLifetime), "/"); //cookie will expire in 30 days
    }else{
        $cookieValue = (int)$_COOKIE[$cookieName];
        if($cookieValue > (int)$rstrcSubmit ){
            if($rstrcSubmit == "1"){
                die("You can not fill out the form more than once!");
            }else{
                die("You can not fill out the form more than $rstrcSubmit times!");
            }
        }else{
            $cookieValue++;
            setcookie($cookieName, $cookieValue, time() + (86400 * (int)$cookieLifetime), "/");
        }
    }
}

//echo "Typr: ".$formDataAry[0] . ", Status: " .$formDataAry[1]."<br>";

if($formStatus == "2"){
     die("This form is unpublished!!!");
}
$userId = '';
$email = '';
$userName = '';
if($formType == "2" || $formType == "4"){ //2-groups not Anonymously, 4-groups Anonymously
    if(isset($_SESSION['user_id'])){
        if($formType == "2"){ //groups not Anonymously
            $userId = $_SESSION['user_id'];
        }
        $records = $conn->prepare('SELECT * FROM users WHERE status="1" AND id = :userid');
        $records->bindParam(':userid', $_SESSION['user_id']);
        $records->execute();
        $results = $records->fetch(PDO::FETCH_ASSOC);
        $message = '';
        if($results != "" && count($results) > 0){
            $user = $_SESSION['user_id'];
            //$email = $results['email'];
            //$userName = $results['username'];
        }else{
            $message = '<label class="text-danger">Sorry, Username does not exist or is suspended</label>';
        }
    }
}else if($formType == "1"){ //public not Anonymously
    $user = getUserIp();
    $userId = getUserIp();
}else{
    $userId = "";
    $user = "Anonymously";
}
function getFormData($conn, $formId){
    $records = $conn->prepare('SELECT * FROM form_list WHERE indx = :formid');
	$records->bindParam(':formid',$formId);
	$records->execute();
    $results = $records->fetch(PDO::FETCH_ASSOC);
    return [
        $results['publish_type'],
        $results['publish_status'],
        $results['form_title'],
        $results['form_genral_style'],
        $results['amount_form_submission']
    ];
}
function getUserIp(){
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    if($ip == "::1"){
        $ip = "127.0.0.1";
    }
    return $ip;
}

////////////////////////Form Style params/////////////
//echo "Style: $formStyle";
if($formStyle != null && $formStyle != ""){
    $formStyleObj = json_decode($formStyle);

    $formDirection = $formStyleObj->form_body_direction;//ltr,rtl
    if($formDirection == "rtl"){
        $bodyDirection = "right";
    }else{
        $bodyDirection = "left";
    }

    //form background style settings
    if(isset($formStyleObj->form_body_bgImage)){
        //echo "<script>console.log('{$formStyleObj->form_body_bgImage}')</script>";
        $formBgImagePath = $formStyleObj->form_body_bgImage;
    }else{
        $formBgImagePath = "";
    }
    $gradientAngle = $formStyleObj->form_body_bgcoloe_angle . "deg";//"0deg"; //45deg
    $gradRGBColor1 = fixRGBtoRGBA($formStyleObj->form_body_bgcolor_1);//"rgba(0,0,255,0.5)";
    $gradRGBColor2 = fixRGBtoRGBA($formStyleObj->form_body_bgcolor_2);//"rgba(0,0,255,0.5)";
    $formBgImage = "linear-gradient($gradientAngle,$gradRGBColor2,$gradRGBColor1),
                    url($formBgImagePath) no-repeat center center;";////"url(images/bg05.jpg)";
    $bgImgAttachment = $formStyleObj->form_body_bgImage_attach;
    $bgImgPosition = $formStyleObj->form_body_bgImage_position;
    $bgImgRepet = $formStyleObj->form_body_bgImage_repet;
    $bgImgSize = $formStyleObj->form_body_bgImage_size;
    //form style settings
    $formWidth = $formStyleObj->form_width . "%";//"60%";
    $formVertical = $formStyleObj->form_vertical_margin . "%";//"10%";
    $formOpacity = (((int)$formStyleObj->form_opacity) / 100);//"0.8";
    $formBgColor = fixRGBtoRGBA($formStyleObj->form_Background_color);//"red";
    $formBorderSize = $formStyleObj->form_border_size . "px";//"5px";
    $formBorderType = $formStyleObj->form_border_type;//"solid";
    $formBorderColor = fixRGBtoRGBA($formStyleObj->form_border_color);//"black";
    $formBorderRaduse =  $formStyleObj->form_border_radius . "px";//"20px";
    $formBorder = "$formBorderSize $formBorderType $formBorderColor"; //1px solid black
}else{
    $formDirection = "ltr"; //ltr,rtl
    $bodyDirection = "left";
    //form background style settings
    $formBgImagePath = "";
    $gradientAngle = "0deg"; 
    $gradRGBColor1 = "rgba(222, 222, 222, 1)";
    $gradRGBColor2 = "rgba(222, 222, 222, 0.8)";
    $formBgImage = "linear-gradient($gradientAngle,$gradRGBColor2,$gradRGBColor1), 
                    url($formBgImagePath) no-repeat center center;";
    $bgImgAttachment = "scroll";
    $bgImgPosition = "center center";
    $bgImgRepet = "repeat";
    $bgImgSize = "auto";
    //form style settings
    $formWidth = "80%";
    $formVertical = "5%";
    $formOpacity = "1";
    $formBgColor = "rgba(255, 255, 255, 1)";
    $formBorderSize = "1px";
    $formBorderType = "solid";
    $formBorderColor = "black";
    $formBorderRaduse = "5px";
    $formBorder = "$formBorderSize $formBorderType $formBorderColor"; //1px solid black
}
function fixRGBtoRGBA($rgb){
    if(strpos($rgb,"rgba") === false){
        return str_replace("rgb","rgba",$rgb);
    }else{
        return $rgb;
    }
}
//////////////////////////////////////////////////////

?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=Edge" />
    <meta charset="UTF-8">
    <title><?= $formTitle ?></title>

    <?php if(!empty($user) ): ?>

    <link rel="stylesheet" href="./include/fonts/fontawesome/css/fontawesome-all.min.css">
    <link rel="stylesheet" href="./include/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="./include/jquery_ui/themes/start/jquery-ui.min.css">
    
    <script src="./include/jquery/jquery-1.12.4.min.js"></script>
    <script src="./include/jquery_ui/jquery-ui.min.js"></script>
    <!--///////////// For Internet Explorer 11 polyfill ///////////////-->
    <script type="text/javascript">
    if(/MSIE \d|Trident.*rv:/.test(navigator.userAgent))
        document.write('<script src="./include/formbuilder/polyfill-4ie11.js"><\/script>');
    </script>
    <!--///////////////////////////////////////////////////////-->
    <script src="./include/formbuilder/form-render.min.js"></script>
    <script src="./include/formbuilder/control_plugins/table.js"></script>
    <!--<script src="./include/formbuilder/control_plugins/starRating.min.js"></script>-->

        <!-- Number fields handler-->
    <link rel="stylesheet" href="./include/Formstone-1.4.13.1/css/number.css">
    <link href="./include/Formstone-1.4.13.1/css/themes/light.css" rel="stylesheet">
    <script src="./include/Formstone-1.4.13.1/js/core.js"></script>
    <script src="./include/Formstone-1.4.13.1/js/number.js"></script>

    <style>
    html, body {
        height: 100%;
        min-height: 100%;
        text-align: <?=$bodyDirection ?>;
    }
    body{
        margin:0 auto;
        width: <?=$formWidth ?>;
        background: <?=$formBgImage ?>;
        background-size: <?=$bgImgSize ?>; /*contain,cover,auto - PHP-TODO*/ 
        background-repeat: <?=$bgImgRepet ?>; /*repeat,repeat-x,repeat-y,no-repeat - PHP-TODO*/
        background-attachment: <?=$bgImgAttachment ?>; /*fixed,scroll - PHP-TODO*/
        background-position: <?=$bgImgPosition ?>; /* - PHP-TODO*/
    }
    .form-render-warper{
        border: <?=$formBorder ?>;
        border-radius:<?=$formBorderRaduse ?>;
        transform: translateY(<?=$formVertical ?>);
        background-color: <?=$formBgColor ?>;
        opacity:  <?=$formOpacity ?>;
    }
    .rendered-form .form-group{
        direction: <?=$formDirection ?>;
    }
    </style>
    <link rel="stylesheet" href="./css/form_main.css">
    <script type="text/javascript">
        //var formbuilder_dialog,formbuilder_content_dialog, add_file_dialog;

        $(function () {
            $("input[type='number']").number();
        });

        //prevent a resubmit on refresh and back button
        if ( window.history.replaceState ) {
            window.history.replaceState( null, null, window.location.href );
        }
    </script>
</head>
<body >
    
	<?php if(!empty($message)): ?>
		<p><?= $message ?></p>
    <?php endif; ?>
    <div class="form-render-warper">
        <form method="POST" action="form_process.php" enctype="multipart/form-data">
            <input type="hidden" name="user_id" value="<?=$userId; ?>" />
            <input type="hidden" name="user_name" value="<?=$userName; ?>" />
            <input type="hidden" name="user_email" value="<?=$email; ?>" />
            <div id="form-render-content"></div>
        </form>
    </div>
    <script>
       var form_id = "<?php echo $formId ?>";
       if(form_id != ""){
           var form_content = get_form_content(form_id);
           //console.log(form_content);
           if(form_content != "new" && form_content != "" && form_content != null && form_content !== undefined){
               var form_content_obj = JSON.parse(form_content);
                if(form_content_obj.length > 0){
                    form_content_obj.forEach(function(item, index){
                        if(item.label !== undefined){ //item.type == "paragraph" || item.type == "header" //
                            item.label = item.label.replace(/&quot;/g,"\"");
                            item.label = item.label.replace(/&apos;/g, "'"); 
                        }
                        if(item.description !== undefined){ 
                            item.description = item.description.replace(/&quot;/g,"\""); 
                            item.description = item.description.replace(/&apos;/g, "'"); 
                        }
                        if(item.type != "table" && item.placeholder !== undefined){ 
                            item.placeholder = item.placeholder.replace(/&quot;/g,"\""); 
                            item.placeholder = item.placeholder.replace(/&apos;/g, "'"); 
                        }
                        if(item.type != "hidden" && item.value !== undefined){
                            item.value = item.value.replace(/&quot;/g,"\"");
                            item.value = item.value.replace(/&apos;/g, "'"); 
                        }

                        if(item.type == "select" || item.type == "checkbox-group" || item.type == "radio-group"){
                            if(item.values !== undefined && item.values.length > 0){
                                item.values.forEach(function(item2, index){
                                    item2.label = item2.label.replace(/&quot;/g,"\""); 
                                    item2.label = item2.label.replace(/&apos;/g, "'");
                                });
                            }
                        }

                    });
                    form_content = JSON.stringify(form_content_obj);
                }
                $('#form-render-content').formRender({
                    dataType: 'json',
                    formData: form_content,
                    notify: {
                        error: function(message) {
                            return console.error(message);
                        },
                        success: function(message) {
                            if(/MSIE \d|Trident.*rv:/.test(navigator.userAgent)){
                                $('input[type="date"]').datepicker({
                                    dateFormat: "yy-mm-dd"
                                });
                            }
                            return console.log("success: " , message);
                        },
                        warning: function(message) {
                            return console.warn(message);
                        }
                    }
                });
            }else{
                alert("No form to Render!!!");
            }
       }
        function get_form_content(form_id){
            var rt_data = "";
            if(form_id != ""){
                //ajax
                $.ajax({
                    type: "POST",
                    url: "get_form_content.php",
                    async:false,
                    data: {form_id : form_id},
                    success: function (response) {
                        rt_data = response;
                        //console.log(response);
                    },
                    error:function (response) {
                        console.log("Error:",response.responseText);
                    },
                    failure: function (response) {
                        console.log("Error:" , JSON.stringify(response));
                    }
                });
            }
            return rt_data;
        }
    </script>

    <?php else: ?>

	<link rel="stylesheet" type="text/css" href="css/main.css">
	<div class="container-login100" style="background-image: url('images/bg05.jpg');">
		<div>
			<h1>Login</h1>
            <?php if(!empty($message)): ?>
                <br><p class="ui-widget-content" style='text-align:center; padding:3px;'><?= $message ?></p><br>
            <?php endif; ?>
			<div class="container-login100-form-btn p-t-10">
			<a class="login100-form-btn" href="login.php">Login</a> </div>
		</div>
	</div>
<?php endif; ?>

</body>
</html>