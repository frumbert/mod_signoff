// amd built following https://brudinie.medium.com/moodle-plugins-and-3rd-party-amd-umd-91b4595fcdec
define(
    [
        'mod_signoff/main', // loads the main AMD module from either CDN or a local fallback if CDN fails
    ],
    function(main) {
        return {
            init: function(name) {
                const placeholder = document.getElementById(`tmp_${name}`);
                const formrow = placeholder.closest('div[style]');
                const hiddenField = document.querySelector(`input[name='${name}']`);
                console.log(hiddenField,`input[name='${name}']`);

                // define a new form layout based on the 'static / submit' templates.
                const html = `<div id="fitem_id_${name}" class="form-group row  fitem femptylabel mod-signoff-control  ">
    <div class="col-md-3">
        <span class="float-sm-right text-nowrap">${M.util.get_string('signature_label', 'mod_signoff')}</span>
        <label class="col-form-label d-inline "></label>
    </div>
    <div class="col-md-9 form-inline felement" data-fieldtype="hidden">
        <div class="form-control-static" id="mod-signoff-signature-${name}"><div class="signature-pad">
    <div class="signature-pad--body">
      <canvas></canvas>
    </div>
    <div class="signature-pad--footer">
      <div class="description">${M.util.get_string('instructions', 'mod_signoff')}</div>

      <div class="signature-pad--actions">
        <div>
          <button type="button" class="button clear" data-action="clear">${M.util.get_string('clear', 'mod_signoff')}</button>
          <button type="button" class="button" data-action="undo">${M.util.get_string('undo', 'mod_signoff')}</button>
        </div>
      </div>
    </div>
  </div></div>
        <div class="form-control-feedback invalid-feedback" id="id_error_${name}"></div>
    </div>
</div>`;
                // add our new form element after this one
                formrow.insertAdjacentHTML('afterend', html);

                // remove the original element
                placeholder.parentNode.removeChild(placeholder);

                // initialise the drawing surface
                const canvas = document.querySelector(`#fitem_id_${name} canvas`);
                const signaturePad = main.init(canvas);
                signaturePad.penColor = 'black';
                signaturePad.backgroundColor = 'white';

                function updateValue() {
                    if (signaturePad.isEmpty()) {
                        hiddenField.value = '';
                    } else {
                        // gotcha - this modifies the canvas size; clear and undo are affected by this
                        // todo: work from a clone instead
                        // signaturePad.removeBlanks();
                        const dataURL = signaturePad.toDataURL("image/jpeg");
                        hiddenField.value = dataURL;
                    }
                }

                signaturePad.addEventListener("endStroke", updateValue, { once: false });

                const dom = document.getElementById(`fitem_id_${name}`);
                const clearButton = dom.querySelector('[data-action=clear]');
                const undoButton = dom.querySelector('[data-action=undo]');
                // const saveJPGButton = dom.querySelector("[data-action=save-jpg]");

                function resizeCanvas() {
                    const ratio =  Math.max(window.devicePixelRatio || 1, 1);
                    canvas.width = canvas.offsetWidth * ratio;
                    canvas.height = canvas.offsetHeight * ratio;
                    canvas.getContext("2d").scale(ratio, ratio);
                    signaturePad.clear();
                    updateValue();
                }
                window.onresize = resizeCanvas;
                resizeCanvas();


                // todo 
                // cropping, maybe https://github.com/szimek/signature_pad/issues/49#issuecomment-260976909

                clearButton.addEventListener("click", function (event) {
                    signaturePad.clear();
                    updateValue();
                });

                undoButton.addEventListener("click", function (event) {
                    var data = signaturePad.toData();
                    if (data) {
                        data.pop(); // remove the last dot or line
                        signaturePad.fromData(data);
                        updateValue();
                    }
                });

            }
        };
    }
)