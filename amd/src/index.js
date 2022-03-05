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
        <!--div>
          <button type="button" class="button save" data-action="save-jpg">Save as JPG</button>
        </div-->
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
                        const dataURL = cropSignatureCanvas(canvas,"image/jpeg");// signaturePad.toDataURL("image/jpeg");
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

                /**

                * Crop signature canvas to only contain the signature and no whitespace.
                *
                * @since 1.0.0
                * @copyright https://github.com/szimek/signature_pad/issues/49#issuecomment-260976909
                */
                function cropSignatureCanvas(canvas,type) {

                    // First duplicate the canvas to not alter the original
                    var croppedCanvas = document.createElement('canvas'),
                        croppedCtx    = croppedCanvas.getContext('2d');

                        croppedCanvas.width  = canvas.width;
                        croppedCanvas.height = canvas.height;
                        croppedCtx.drawImage(canvas, 0, 0);

                    // Next do the actual cropping
                    var w         = croppedCanvas.width,
                        h         = croppedCanvas.height,
                        pix       = {x:[], y:[]},
                        imageData = croppedCtx.getImageData(0,0,croppedCanvas.width,croppedCanvas.height),
                        x, y, index;

                    for (y = 0; y < h; y++) {
                        for (x = 0; x < w; x++) {
                            index = (y * w + x) * 4;
                            if (imageData.data[index+3] > 0) {
                                pix.x.push(x);
                                pix.y.push(y);

                            }
                        }
                    }
                    pix.x.sort(function(a,b){return a-b});
                    pix.y.sort(function(a,b){return a-b});
                    var n = pix.x.length-1;

                    w = pix.x[n] - pix.x[0];
                    h = pix.y[n] - pix.y[0];
                    var cut = croppedCtx.getImageData(pix.x[0], pix.y[0], w, h);

                    croppedCanvas.width = w;
                    croppedCanvas.height = h;
                    croppedCtx.putImageData(cut, 0, 0);

                    return croppedCanvas.toDataURL(type);
                }

                // function dataURLToBlob(dataURL) {
                //     // Code taken from https://github.com/ebidel/filer.js
                //     var parts = dataURL.split(';base64,');
                //     var contentType = parts[0].split(":")[1];
                //     var raw = window.atob(parts[1]);
                //     var rawLength = raw.length;
                //     var uInt8Array = new Uint8Array(rawLength);

                //     for (var i = 0; i < rawLength; ++i) {
                //         uInt8Array[i] = raw.charCodeAt(i);
                //     }

                //     return new Blob([uInt8Array], { type: contentType });
                // }

                // function download(dataURL, filename) {
                //     if (navigator.userAgent.indexOf("Safari") > -1 && navigator.userAgent.indexOf("Chrome") === -1) {
                //         window.open(dataURL);
                //     } else {
                //         var blob = dataURLToBlob(dataURL);
                //         var url = window.URL.createObjectURL(blob);

                //         var a = document.createElement("a");
                //         a.style = "display: none";
                //         a.href = url;
                //         a.download = filename;

                //         document.body.appendChild(a);
                //         a.click();

                //         window.URL.revokeObjectURL(url);
                //     }
                // }

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

                // saveJPGButton.addEventListener("click", function (event) {
                //     if (!signaturePad.isEmpty()) {
                //         const dataURL = signaturePad.toDataURL("image/jpeg");
                //         download(dataURL, "signature.jpg");
                //     }
                // });

            }
        };
    }
)