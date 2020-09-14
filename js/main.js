/**
 * Used resources:
 * - https://jquery.com/
 * - https://getbootstrap.com/docs/3.3/
 * - https://www.glyphicons.com/
 * - https://fonts.google.com/specimen/Josefin+Sans
 * - https://www.glyphrstudio.com/online/
 * - https://valentin.dasdeck.com/midi/
 * - https://jqueryvalidation.org
 * - http://jquery.malsup.com/form
 */
/**
 * show the response
 * @param resultString
 */
function showSong (resultString) {
    var res = JSON.parse(resultString);
    if (res.success) {
        if (!res.valid) {
            $("#songAlert").show();
            $("#songAlert").html(res.alert);
        }
        $("#songTitle").html(res.title);
        $("#songMeta").html(res.baseNote + "<br/>" + res.bpm);
        $("#songResult").html(res.noteString);
        $("#songTitle").show();
        $("#songMeta").show();
        $("#songresult").show();
        $("#song").show(1000);
        kern();
    } else {
        $("#songAlert").show();
        $("#songAlert").html(res.alert);
    }

}

/**
 * set the kerning of notes
 */
function kern() {
    //console.log($( "span.note" ));
    $.each($( "span.note" ), function( index, item ) {
        var text = $(item).html()
        if (text.indexOf("_") > 0 || text.indexOf(",") > 0 || text.indexOf(":") > 0 || text.indexOf(";") > 0) {

            var a = text.split('');

            var newText = "";
            if (a[0] == "#") {
                a.shift();
                newText = "#";
            }
            if (isNaN(a[0])) {
                newText += a[0];
                a.shift();
            }
            var k1 = 25,k2 = 25;

            if (a[0] == "1") {
                k1 = 10;
                k2 = 14;
                if (a[1] != ";" && a[1] != ":") {
                    k1 = 18;
                }
            } else {
                k2 = 16;
                if (a[1] == ";" || a[1] == ":") {
                    k1 = 15;
                }
            }

            $.each(a, function( i, letter ) {
                if (i == 0) {
                    newText += "<span>" + letter + "</span>";
                } else if (i == 1) {
                    newText += "<span class='kern"+k1+"'>" + letter + "</span>";
                } else if (i == 2) {
                    newText += "<span class='kern"+k2+"'>" + letter + "</span>";
                } else {
                    newText += letter;
                }
            });
            if (text.indexOf("_") < 0 && text.indexOf(",") < 0) {
                newText += "&nbsp;";
            }
            $(item).html(newText);

        }

    });
}

/**
 * change select options
 * @param flute
 */
function loadDataToSelect (flute) {
    var jsonName = flute + "BaseNotes";
    var $select = $('select[name=baseNote]');
    $select.find('option').remove();
    $.getJSON( "json/" + jsonName + ".json", function(data) {
        $.each(data.notes,function(key, value) {
            $select.append('<option value=' + value.value + '>' + value.name + '</option>');
        });
    });
}

function checkMidi (validator) {
    $( "#uploadMidi" ).ajaxSubmit({
        data: {
            check: true
        },
        success: function (responseText, statusText, xhr, $form) {
            var res = JSON.parse(responseText);
            if (res.success) {
                $("#songAlert").hide();
                $("#track").attr('max',res.tracks)
                if (parseInt(res.tracks) < parseInt($("#track").val())) {
                    $("#track").val(res.tracks);
                }
            } else {
                validator.showErrors({
                    "midi": res.alert
                });
            }
        }
    });
}

/**
 * when document is ready
 */
$(document).ready(function () {
    /**
     * on print
     */
    $('#print').on("click", function() {
        window.print();
    });

    /**
     * flute selection change
     */
    $('input[type=radio][name=flute]').change(function() {
        loadDataToSelect(this.value);
    });

    /**
     * on change the midi file
     */
    $(document).on('change', '#midi', function() {
        var input = $(this),
            files = input.get(0).files,
            text = $(this).parents('.input-group').find('#filename');
        text.val(files[0].name);
        var validator = $("#uploadMidi").validate();
        checkMidi(validator);
    });

    /**
     * number fields
     */
    $('.spinner .btn:first-of-type').on('click', function() {
        var input = $(this).parent().parent().children('input');
        var max = input.attr('max');
        var val = input.val()
        if (val == "") {
            val = 0;
        }
        var newvalue = parseInt(val, 10) + 1;
        if (newvalue <= parseInt(max, 10)) {
            input.val(newvalue);
        }
    });
    $('.spinner .btn:last-of-type').on('click', function() {
        var input = $(this).parent().parent().children('input');
        var min = input.attr('min');
        var val = input.val()
        if (val == "") {
            val = 0;
        }
        var newvalue = parseInt(val, 10) - 1;
        if (newvalue >= parseInt(min, 10)) {
            input.val(newvalue);
        }
    });
    $(document).on('change', '.number-control', function() {
        var min = $(this).attr('min');
        var max = $(this).attr('max');
        if (isNaN($(this).val())) {
            $(this).val(0);
        }
        if (parseInt($(this).val(), 10) > parseInt(max, 10)) {
            $(this).val(max);
        }
        if (parseInt($(this).val(), 10) < parseInt(min, 10)) {
            $(this).val(min);
        }
    });


    /**
     * on select a demo song
     */
    $('.demo').on("click", function() {
        var url = $( "#uploadMidi" ).serialize();
        var json = JSON.parse('{"' + url.replace(/&/g, '","').replace(/=/g,'":"') + '"}', function(key, value) { return key===""?value:decodeURIComponent(value) })
        json.demo = $(this).attr('id');
        $("#songAlert").hide();
        $("#songTitle").hide();
        $("#songMeta").hide();
        $("#songresult").hide();
        $("#song").hide(1000);
        $.ajax({
            type: "POST",
            url: "router.php",
            data: json,
            success: function (responseText) {
                showSong(responseText);
            },
        });
    });

    /**
     * validate the form
     */
    $('#uploadMidi').validate({ // initialize the plugin
        rules: {
            flute: {
                required: true
            },
            baseNote: {
                required: true
            },
            midi: {
                required: true
            }
        },
        errorPlacement: function(error, element) { // where to show error msg
            if (element.attr("name") == "midi") {
                error.insertAfter("#filenamegroup");
            } else if (element.attr("name") == "track") {
                error.insertAfter("#trackgroup");
            } else if (element.attr("name") == "transpose") {
                error.insertAfter("#transposegroup");
            } else {
                error.insertAfter(element);
            }
        },
        submitHandler: function (form) {  // sumbit form
            $("#songAlert").hide();
            $("#songTitle").hide();
            $("#songMeta").hide();
            $("#songresult").hide();
            $("#song").hide(1000);
            $(form).ajaxSubmit({
                success: function (responseText, statusText, xhr, $form) {
                    showSong(responseText); // get response
                }
            });
            return false;
        }
    });

    /**
     * set up tooltip
     */
    $('[data-toggle="tooltip"]').tooltip();

    /**
     * set up select dropdown
     */
    loadDataToSelect($("input[name=flute]:checked", '#uploadMidi').val());

});