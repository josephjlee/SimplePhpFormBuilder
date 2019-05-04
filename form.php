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
//is set cookie protection
$isCookieSet = getSetting("", "enableUsingCookies");
if($isCookieSet == "1"){
    $cookieName = md5("form_$formId");
    $cookieValue = "1";
    if(!isset($_COOKIE[$cookieName])) {
        $cookieLifetime = getSetting("", "cookiesLifeTime");
        setcookie($cookieName, $cookieValue, time() + (86400 * (int)$cookieLifetime), "/"); //cookie will expire in 30 days
    }else{
        if($_COOKIE[$cookieName] == "1"){
            die("You can not fill out the form more than once!");
        }
    }
}

require 'settings/database.login.php';

$formDataAry = getFormType($conn, $formId);
$formType = $formDataAry[0];
$formStatus = $formDataAry[1];
$formTitle = $formDataAry[2];
//echo "Typr: ".$formDataAry[0] . ", Status: " .$formDataAry[1]."<br>";

if($formStatus == "2"){
     die("This form is unpublished!!!");
}
$userId = '';
$email = '';
$userName = '';
if($formType != "1" ){
    if(isset($_SESSION['user_id'])){
        $user = $_SESSION['user_id'];
        $userId = $_SESSION['user_id'];
        $records = $conn->prepare('SELECT * FROM users WHERE status="1" AND id = :userid');
        $records->bindParam(':userid', $userId);
        $records->execute();
        $results = $records->fetch(PDO::FETCH_ASSOC);
        $message = '';
        if(count($results) > 0){
            $email = $results['email'];
            $userName = $results['username'];
        }else{
            $message = '<label class="text-danger">Sorry, Username does not exist or is suspended</label>';
        }
    }/*else{
        if(!empty($_POST['username']) && !empty($_POST['password'])){
            
            $records = $conn->prepare('SELECT * FROM users WHERE status="1" AND username = :username');
            $records->bindParam(':username', $_POST['username']);
            $records->execute();
            $results = $records->fetch(PDO::FETCH_ASSOC);

            $message = '';
            if(count($results) > 0){
                if(password_verify($_POST['password'], $results['password']) ){

                    $_SESSION['user_id'] = $results['id'];
                    $user = $results['id'];
                    $userId = $results['id'];
                    $email = $results['email'];
                    $userName = $results['username'];
                    //header("Location: form.php?id=$formId");
                } else {
                    $message = '<label class="text-danger">Sorry, those credentials do not match</label>';
                }
            }else{
                $message = '<label class="text-danger">Sorry, Username does not exist or is suspended</label>';
            }
        }
    }*/
}else{
    $user = "public";
}
function getFormType($conn, $formId){
    $records = $conn->prepare('SELECT form_title,publish_type,publish_status FROM form_list WHERE indx = :formid');
	$records->bindParam(':formid',$formId);
	$records->execute();
    $results = $records->fetch(PDO::FETCH_ASSOC);
    return [$results['publish_type'],$results['publish_status'],$results['form_title']];
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

?>
<!DOCTYPE html>
<html>
<head>
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
    <link rel="stylesheet" href="./css/form_main.css">
    <script type="text/javascript">
        //var formbuilder_dialog,formbuilder_content_dialog, add_file_dialog;
        //$(function () {
        //    
        //});
        //prevent a resubmit on refresh and back button
        if ( window.history.replaceState ) {
            window.history.replaceState( null, null, window.location.href );
        }
    </script>
</head>
<body>
    
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