<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//include "chech_restricted.php";

require "settings/database.class.php";

if(isset($_POST['form_id'])){
    $form_id = $_POST["form_id"];
    $uid = $_POST["form_uid"];
    $echo_data = "new";
    $db = new Database("formbuilder");
    $conn = $db->getConnection();
    
    $sql = "SELECT * FROM form_content WHERE form_id='$form_id'";
    if($result = $conn->query($sql)) {
        $count = mysqli_num_rows($result);
        if($count > 0){
            while($row = mysqli_fetch_assoc($result)){
               $echo_data =  $row['form_form'] ; 
            }
        }
    }
    if($echo_data != "new" && $echo_data != ""){
        $submitBtnObj = new stdClass();
        $submitBtnObj->type = "button";
        $submitBtnObj->subtype = "submit";
        $submitBtnObj->label = "Submit";
        $submitBtnObj->className = "btn-primary btn";
        $submitBtnObj->name = "button-submit-form";
        $submitBtnObj->id = "button-submit-form";
        $submitBtnObj->style = "primary";

        
        $hiddenObj = new stdClass();
        $hiddenObj->type = "hidden";
        $hiddenObj->name = "hidden-form-id";
        $hiddenObj->id = "hidden-form-id";
        $hiddenObj->value = $form_id;

        $form = json_decode($echo_data);
        $frm_ary = array();
        foreach($form as $fild){
            //remove header and paragraph if exists
            if($fild->type != "file" && $fild->type != "header" && $fild->type != "paragraph"){ // 
                $frm_ary[] = setFormValues($conn, $form_id, $uid, $fild);
            }
            //if file
            if($fild->type == "file"){
                $fileTbl = getFilesListTable($conn, $form_id, $uid);
                $filesTblObj = new stdClass();
                $filesTblObj->type = "paragraph";
                $filesTblObj->subtype = "div";
                $filesTblObj->label = $fileTbl;
                $filesTblObj->className = "ui-widget-content file-list-table";
                $frm_ary[] = $filesTblObj;
            }

        }
        //array_push($frm_ary,$hiddenObj);
        //array_push($frm_ary,$submitBtnObj);
        $echo_data = json_encode($frm_ary);
    }
    echo $echo_data;
}else{
    echo 'Error: missing form_id';
}
function setFormValues($conn, $form_id, $uid, $field){
    $fType = $field->type;
    $fname = $field->name;
    $fval = getFieldValue($conn, $form_id, $uid,$fname);
    if($fType == "checkbox-group"){
        if(isset($field->other) && $field->other == true){
            $field->other = false;
            
            //$fvalAry = explode(",",$fval);
            $fvalAry = json_decode($fval);
            $selectedAry = array();
            foreach($field->values as $val){
                if(in_array($val->value,$fvalAry)){
                    $val->selected = true;
                    $selectedAry[] = $val->value;
                }else{
                    $val->selected = false;
                }
                $val->disabled = true;
            }
            $dif = array_diff($fvalAry,$selectedAry);
            $otherSelected = "";
            if(!empty($dif)){
                $otherSelected = implode(",",$dif);
            }
            $otherOptObj = new stdClass();
            $otherOptObj->label = (($otherSelected != "")?"Other: ".$otherSelected:"Other");
            $otherOptObj->value = (($otherSelected != "")?$otherSelected:"other");
            if($otherSelected != "")
                $otherOptObj->selected = true;
            $otherOptObj->disabled = true;
            $field->values[] = $otherOptObj;
        }else{
            //$fvalAry = explode(",",$fval);
            if($fval != ""){
                $fvalAry = json_decode($fval);
                foreach($field->values as $val){
                    if(in_array($val->value,$fvalAry)){
                        $val->selected = true;
                    }else{
                        $val->selected = false;
                    }
                    $val->disabled = true;
                }
            }
        }
    }else if($fType == "radio-group"){
        if(isset($field->other) && $field->other == true){
            $field->other = false;
            $otherOptObj = new stdClass();
            $isOtherSelected = true;
            foreach($field->values as $val){
                if($val->value == $fval){
                    $isOtherSelected = false;
                    break;
                }
            }
            $otherOptObj->label = (($isOtherSelected)?"Other: ".$fval:"Other");
            $otherOptObj->value = (($isOtherSelected)?$fval:"other");
            $field->values[] = $otherOptObj;
        }
        $field->value = $fval;
        $field->disabled = true;
    }else{
        $field->value = $fval;
        $field->disabled = true;
    }

    return $field;
}
function getFieldValue($conn, $form_id, $uid,$fname){
    $filds_str = "";
    $sql = "SELECT field_value FROM form_data WHERE form_id='$form_id' AND UID='$uid' AND field_name='$fname'";
    if($result = $conn->query($sql)) {
        $count = mysqli_num_rows($result);
        if($count > 0){
            while($row = mysqli_fetch_assoc($result)){
                $filds_str = $row['field_value'];
            }
        }
    }
    return $filds_str;
}

function getFormDatetime($conn, $formId, $uid){
    $datetime = "";
    $sql = "SELECT datetimes FROM form_data_datetimes WHERE UID='$uid' AND form_id='$formId'";
    if($result = $conn->query($sql)) {
        $count = mysqli_num_rows($result);
        if($count > 0){
            while($row = mysqli_fetch_assoc($result)){
                $datetime = $row['datetimes'];
            }
        }
    }
    return $datetime;
}
function getFilesListTable($conn, $form_id, $uid){
    //$tbl = "<table class='table'><tr><td>#</td><td>File name</td><td>Link</td></tr><td>1</td><td>test.png</td><td><a href='#'>open</a></td><tr></tr></table>";
    
    $tble = "<table class='table table-sm'><thead><tr class='table-primary'><td>#</td><td>File name</td><td>Link</td></tr></thead>";
    $sql = "SELECT * FROM form_files WHERE UID='$uid' AND form_id='$form_id'";
    if($result = $conn->query($sql)) {
        $count = mysqli_num_rows($result);
        if($count > 0){
            $row_indx = 1;
            while($row = mysqli_fetch_assoc($result)){
                $tble .= "<tr><td>$row_indx</td><td>" .$row['file_name']."</td><td><a href='".$row['file_path']."' target='_blank' class='btn btn-outline-primary' role='button'>open</a></td></tr>";
            }
        }else{
            $tble .= "<table class='table'><tr><td colspan='3'>No file</td></tr>";
        }
    }else{
        $err =  mysqli_error($conn);
        $tble .= "<table class='table'><tr><td colspan='3'>sql error:  $err</td></tr>";
    }
    return $tble;
}
?>