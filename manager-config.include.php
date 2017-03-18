<?php
// Configuration editor //
// This file must be included inside manager.php

global $CONFIG,$CUSTOM_CONFIG_FN,$CUSTOM_CONFIG_NAME,$MANAGER_MODE;

// allowed only on manager mode
if (!isset($CONFIG) || empty($CUSTOM_CONFIG_FN) || empty($MANAGER_MODE)){
    exit();
}

$options = json_decode(file_get_contents(CONFIG_OPTIONS_FN),true);

if (!empty($_POST['configjson'])){
    // write the new configuration to config.json
    // populate new values to $copy
    $copy = json_decode(file_get_contents($CUSTOM_CONFIG_FN),true);
    $new = json_decode($_POST['configjson'],true);
    $changed = false;
	foreach($new as $key=>$val){
        if ($CONFIG[$key]!==$val){
            //echo "changed: $key to $val <Br>\n";
            $changed = true;
	        $copy[$key]=$val;
            $CONFIG[$key]=$val; // update current session $CONFIG as well
        }
	}
    if ($changed){
        // create backup
        //copy($CUSTOM_CONFIG_FN,"$CUSTOM_CONFIG_FN.backup.json");
        // write new configuration to config.json
        file_put_contents($CUSTOM_CONFIG_FN,json_encode($copy,JSON_PRETTY_PRINT));
    }
}

$updated = array();
foreach($options as $k=>$v){
    $updated[$k]=$CONFIG[$k];
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/switchery/0.8.2/switchery.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/switchery/0.8.2/switchery.min.js"></script>
<script>
    var switchery_settings = {
        color: '#337ab7'
    };
</script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/codemirror.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/runmode/colorize.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/fold/xml-fold.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/hint/show-hint.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/hint/xml-hint.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/hint/html-hint.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/hint/javascript-hint.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/hint/css-hint.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/hint/anyword-hint.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/edit/matchtags.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/edit/closetag.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/search/match-highlighter.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/lint/lint.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/lint/javascript-lint.min.js"></script>
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
        lint: true,
    }
</script>

<h3>Select Book:</h3>
<?php
foreach (glob("custom/config-*.json") as $filename) {
    $cfg = str_replace('custom/config-','',str_replace('.json','',$filename));
    $active = $cfg == $CUSTOM_CONFIG_NAME ? 'active btn-primary':'';
    echo "<a class='btn btn-default configFilename $active' href='?f=config&cfg=$cfg'>$cfg</a>";
}
?>
<span id='cfg_add' class='btn btn-default configFilename' href='#'>+ Add</span>
<div id="cfg_add_dialog" style="display:none" title="Select a name for new book config">
    <div id="cfg_add_name_container">
        <input id="cfg_add_name" type="text" class="form-control">
    </div>
</div>
<div id="cfg_selected">
    <?=$CUSTOM_CONFIG_NAME?> <?=$CUSTOM_CONFIG_FN?> <a class="btn btn-default btn-xs" target="_blank" href="/?cfg=<?=$CUSTOM_CONFIG_NAME?>">View the book</a>
</div>


<form id='frm' method='POST'><input name='configjson' type='hidden'/></form>
<div id="form_wrapper" class="configuration form-container form-inline">
    <div id="inputs"></div>
    <div id="submits" class="form-group">
        <div id="save" class='form-control btn btn-primary'>Save configuration</div>
        &nbsp;&nbsp;
        <a id="cancel" class='form-control btn btn-default' href='?'>Exit without saving</a>
    </div>
</div>

<a class="btn btn-default btn-large" href="?f=edit&fn=style-<?=$CUSTOM_CONFIG_NAME?>.css">Edit Custom style.css</a>
<a class="btn btn-default btn-large" href="?f=edit&fn=script-<?=$CUSTOM_CONFIG_NAME?>.js">Edit Custom script.js</a>

<script>
    $('#cfg_add').click(function(ev){
        var bad = /[^a-z0-9_\-]/g;
        ev.preventDefault();
        ev.stopPropagation();
        var name = prompt('Name for the new book config');
        while (name && bad.test(name)){
            name = prompt('Use only English letters or numbers, no signs.\nName for the new book config');
        }
        if (name){
            location.href = '?f=config&addcfg=1&cfg='+name;
        }
    });
    // not in use yet:
    $('#cfg_add_name').on('keyup change click',function(ev){
        var val = this.value;
        var bad = /[^a-z0-9_\-]/g;
        if (bad.test(val)){
            val = val.replace(bad,'');
            this.value = val;
        }
    });

    var $configjson = $('input[name="configjson"]');
    var $inputs = $('#inputs');
    var options = <?=json_encode($options)?>;
    var updated = <?=json_encode($updated)?>;
    var counter = 0;
    if (options) {
        Object.keys(options).forEach(function(key){
            counter++;
            var id='id'+counter;
            var option = options[key];
            option.name = option.name || fcwords(key.replace(/_/g,' '));
            var $label = $('<label class="form-label" for="'+id+'">').html(option.name);
            var $group = $('<div class="form-group" data-key="'+key+'">');
            $group.append($label);
            if (option.cls)
                $group.addClass(option.cls);
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
                } else if (option.type == 'html' || option.type == 'textarea' || option.type.indexOf('text/')===0 ) {
                    var $input = $('<textarea id="'+id+'" class="form-control">').text(current_value).change(function(){
                        update(key,$input.val());
                    });
                    $group.append($input);
                } else if (option.type == 'boolean'){
                    var $input = $('<input id="'+id+'" type="checkbox" class="js-switch" ' + (current_value?'checked':'') + '>').change(function(){
                        update(key,$input[0].checked);
                        console.log('chg ',key,$input[0].checked);
                    });
                    $group.append($input);
                } else if (option.type == "configuration title") {
                    $group.addClass('h4 conf-title');
                    if (option.output)
                        $group.append($('<output>').html(option.output));
                } else {
                    console.log("ERROR: Unknown type at ",option);
                }
            }

            ["autocomplete","height","width","list","min","max","multiple","pattern","placeholder","required","step",].forEach(function(k){
                if (option[k]!==undefined){
                    $input.prop(k,option[k]);
                }
            })       
            
            $inputs.append($group);
        });
    }

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
        myCodeMirror[i].on('change',$(html_fields[i]).change());
    }

    $('.js-switch').each(function() {
        var switchery = new Switchery(this,switchery_settings);
    });
    
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