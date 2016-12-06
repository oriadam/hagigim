<?php
// File editor //
// This file must be included inside manager.php

global $CONFIG,$MANAGER_MODE,$FILENAME;
if (!isset($CONFIG) || empty($MANAGER_MODE)){
    exit();
}

$backup_fn = "$FILENAME.backup";
$content = file_get_contents($FILENAME);
$ext = explode('.',$FILENAME);
$ext = $ext[count($ext)-1];

if (!empty($_POST['filecontent'])){
    $updated = $_POST['filecontent'];
    // write the new content to file
    if ($content != $updated){
        $content = $updated;
        // backup when necessary
        copy($FILENAME,$backup_fn);
        // write new content to file
        file_put_contents($FILENAME,$updated);
    }
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/codemirror.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/hint/show-hint.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/runmode/colorize.min.js"></script>
<?php if ($ext=='js') { ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/edit/matchbrackets.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/lint/lint.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/lint/javascript-lint.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/hint/javascript-hint.min.js"></script>
<?php } if ($ext=='css') { ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/mode/css/css.min.js"></script>
<?php } if ($ext=='xml') { ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/hint/xml-hint.min.js"></script>
<?php } if ($ext=='html') { ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/hint/xml-hint.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/hint/html-hint.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/runmode/colorize.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/fold/xml-fold.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/edit/matchtags.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/edit/closetag.min.js"></script>
<?php } else { ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/hint/anyword-hint.min.js"></script>
<?php } ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.21.0/addon/search/match-highlighter.min.js"></script>

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
    }
</script>

<h3>Edit <?=$FILENAME?>:</h3>
<form id='frm' method='POST'>
<div id="form_wrapper" class="editfile form-container form-inline">
    <div id="inputs">
        <textarea id="editor" name='filecontent' class="editor"></textarea>
    </div>
    <div id="submits" class="form-group">
        <div id="save" class='form-control btn btn-primary'>Save file</div>
        &nbsp;&nbsp;
        <a id="cancel" class='form-control btn btn-default' href='?'>Exit without saving</a>
    </div>
</div>
<script>
    var $content = <?=json_encode($content)?>;
    $('#editor').val($content).change(function(){
        $('[name="filecontent"]').val($('#editor').val());
    }).change();

    var ext = '<?=$ext?>';
    var modes = {
        'js':'text/javascript',
        'css':'text/css',
        'html':'text/html',
        'xml':'text/xml',
    };
    if (ext in modes){
        codeMirrorOptions.mode = modes[ext];
        var myCodeMirror = CodeMirror.fromTextArea(document.querySelector('#editor'),codeMirrorOptions);
    }

    $('#save').click(function(){
        $('#save,#cancel').addClass('disabled');
        document.querySelector('#frm').submit();
    });

</script>
<style>
.editor,.CodeMirror {
    height:calc(100vh - 200px);
} 
</style>