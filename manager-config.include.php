<?php
// Configuration editor //
// This file must be included inside manager.php

global $CONFIG,$MANAGER_MODE;
if (!isset($CONFIG) || empty($MANAGER_MODE)){
    exit();
}

define('CONFIG_FN','config.json');
define('CONFIG_BACKUP_FN','config-backup.json');
define('CONFIG_OPTIONS_FN','config-options.json');

$options = json_decode(file_get_contents(CONFIG_OPTIONS_FN),true);

if (!empty($_POST['configjson'])){
    // write the new configuration to config.json
    // make a copy of $CONFIG
	$copy = json_decode(json_encode($CONFIG),true);
    // populate new values to $copy
    $new = json_decode($_POST['configjson'],true);
    $changed = false;
	foreach($new as $key=>$val){
        if ($copy[$key]!==$val){
            //echo "changed: $key to $val <Br>\n";
            $changed = true;
		    $copy[$key]=$val;
		    $CONFIG[$key]=$val;
        }
	}
    if ($changed){
        // create backup
        copy(CONFIG_FN,CONFIG_BACKUP_FN);
        // write new configuration to config.json
        file_put_contents(CONFIG_FN,json_encode($copy,JSON_PRETTY_PRINT));
    }
}

$updated = array();
foreach($options as $k=>$v){
    $updated[$k]=$CONFIG[$k];
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/codemirror.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/runmode/colorize.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/fold/xml-fold.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/hint/show-hint.js.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/hint/xml-hint.js.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/hint/html-hint.js.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/hint/javascript-hint.js.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/hint/css-hint.js.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/hint/anyword-hint.js.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/edit/matchtags.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/edit/closetag.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/search/match-highlighter.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/link/lint.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/link/javascript-lint.min.js"></script>
<script>
    var codeMirrorOptions = {
        mode: "text/html",
        inputStyle: "textarea",
        lineNumbers: false,
        indentUnit: 1,
        indentWithTabs: true,
        smartIndent: true,
        electricChars: true,
        flattenSpans: false,
        matchBrackets: true,
        matchTags: {bothTags: true},
        autoCloseTags: true,
        highlightSelectionMatches: true,
        lint:true,
    }
</script>

<h3>Configuration:</h3>
<form id='frm' method='POST'><input name='configjson' type='hidden'/></form>
<div id="form_wrapper" class="configuration form-container form-inline">
    <div id="inputs"></div>
    <div id="submits" class="form-group">
        <div id="save" class='form-control btn btn-primary'>Save configuration</div>
        &nbsp;&nbsp;
        <a id="cancel" class='form-control btn btn-default' href='?'>Exit without saving</a>
    </div>
</div>
<script>
    var $configjson = $('input[name="configjson"]');
    var $inputs = $('#inputs');
    var options = <?=json_encode($options)?>;
    var updated = <?=json_encode($updated)?>;
    var counter = 0;
    Object.keys(options).forEach(function(key){
        counter++;
        var id='id'+counter;
        var option = options[key];
        option.name = option.name || fcwords(key.replace(/_/g,' '));
        var $label = $('<label class="form-label" for="'+id+'">').html(option.name);
        var $group = $('<div class="form-group" data-key="'+key+'">');
        $group.append($label);
        var current_value = updated[key];
        if (option.options){
            // select box mode - options
            var $sel = $('<select id="'+id+'" class="form-control" size="1"/>');
            var value_exist = false;
            Object.keys(option.options).forEach(function(value_name){
                var value = option.options[value_name];
                var $opt = $('<option>').html(value_name).val(JSON.stringify(value));
                if (value == current_value){
                    value_exist = true;
                    $opt.prop('selected',true);
                }
                $sel.append($opt)
            });
            if (!value_exist){
                // if current value is missing from list - add it to top
                var $opt = $('<option selected>').html(current_value).val(JSON.stringify(current_value));
                $sel.prepend($opt);
            }
            $sel.change(function(){
                // update updated
                update(key,JSON.parse($sel.val()));
            });
            $group.append($sel);
        } else {
            // open text mode - no options
            option.type = option.type || 'text';
            if (option.type == 'text' || option.type == 'number'){
                var $input = $('<input id="'+id+'" class="form-control" type="'+option.type+'">').val(current_value).change(function(){
                    if (option.type == 'number'){
                        update(key,1*$input.val());
                    } else {
                        update(key,$input.val());
                    }
                });
                $group.append($input);
            } else if (option.type == 'html' || option.type == 'textarea') {
                var $input = $('<textarea id="'+id+'" class="form-control">').text(current_value).change(function(){
                    update(key,$input.val());
                });
                $group.append($input);
            } else {
                console.log("ERROR: Unknown type at ",option);
            }
        }
        $inputs.append($group);
    });

    // like PHP's ucwords
    function ucwords(str){
        return str.replace(/^([a-z\u00E0-\u00FC])|\s+([a-z\u00E0-\u00FC])/g, function ($1) {
            return $1.toUpperCase()
        });
    }
    // like PHP's fcwords
    function fcwords(str){
        return str.replace(/^([a-z\u00E0-\u00FC])/g, function ($1) {
            return $1.toUpperCase()
        });
    }
    
    // Use this to change a value in form and prepare it for submission
    function update(key,value){
        if (arguments.length==2){
            updated[key] = value;
        }
        $configjson.val(JSON.stringify(updated));
    }
    update();

    $('#save').click(function(){
        $('#save,#cancel').addClass('disabled');
        document.querySelector('#frm').submit();
    });

    var myCodeMirror = [];
    var html_fields = document.querySelectorAll('[data-key*="html_"] textarea');
    for (var i=0;i<html_fields.length;i++){
        myCodeMirror[i] = CodeMirror.fromTextArea(html_fields[i],codeMirrorOptions);
        myCodeMirror[i].on('change',html_fields[i].change());
    }
    
</script>
<style>
<?php
if ($CONFIG["rtl"]){
    ?>
    .form-group[data-key*="text_"] input.form-control[type="text"]{
        direction:rtl;
    }
    <?php
}
?>
</style>