
(function() {

    // serve local file through pluginfile for standards
    var pluginJSURL = function(path) {
        return M.cfg.wwwroot + "/pluginfile.php/" + M.cfg.contextid + "/mod_sigoff/" + path;
    };

    require.config({
        enforceDefine: false,
        paths: {
            "signature_pad_4": [
                "https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min",
                pluginJSURL('signature_pad.umd.min')
            ],
            // todo
            // "trim_canvas_012": [
            //     "https://unpkg.com/trim-canvas@0.1.2/build/index.js",
            //     pluginJSURL('trimcanvas.min')
            // ]
        }
    });

})();

// use our private namespace to avoid conflicts
define(['signature_pad_4'], function(SignaturePad) {

    // extend the base class to add our own functionality
    // source: https://github.com/szimek/signature_pad/issues/49#issuecomment-475130906
    // mod: change routine to crop against white background rather than transparent
    SignaturePad.prototype.removeBlanks = function () {
        var imgWidth = this._ctx.canvas.width;
        var imgHeight = this._ctx.canvas.height;
        var imageData = this._ctx.getImageData(0, 0, imgWidth, imgHeight),
        data = imageData.data,
        getAlpha = function(x, y) {
            var r = (imgWidth*y + x) * 4,
                g = (imgWidth*y + x) * 4 + 1,
                b = (imgWidth*y + x) * 4 + 2,
                a = (imgWidth*y + x) * 4 + 3;
            return data[r] < 254 && data[g] < 254 && data[b] < 254;
            // return data[(imgWidth*y + x) * 4 + 3]
        },
        scanY = function (fromTop) {
            var offset = fromTop ? 1 : -1;

            // loop through each row
            for(var y = fromTop ? 0 : imgHeight - 1; fromTop ? (y < imgHeight) : (y > -1); y += offset) {

                // loop through each column
                for(var x = 0; x < imgWidth; x++) {
                    if (getAlpha(x, y)) {
                        return y;                        
                    }      
                }
            }
            return null; // all image is white
        },
        scanX = function (fromLeft) {
            var offset = fromLeft? 1 : -1;

            // loop through each column
            for(var x = fromLeft ? 0 : imgWidth - 1; fromLeft ? (x < imgWidth) : (x > -1); x += offset) {

                // loop through each row
                for(var y = 0; y < imgHeight; y++) {
                    if (getAlpha(x, y)) {
                        return x;                        
                    }      
                }
            }
            return null; // all image is white
        };

        console.log(imgWidth, imgHeight);

        var cropTop = scanY(true),
        cropBottom = scanY(false),
        cropLeft = scanX(true),
        cropRight = scanX(false);

        var relevantData = this._ctx.getImageData(cropLeft, cropTop, cropRight-cropLeft, cropBottom-cropTop);
        this.canvas.width = cropRight-cropLeft;
        this.canvas.height = cropBottom-cropTop;
        this._ctx.clearRect(0, 0, cropRight-cropLeft, cropBottom-cropTop);
        this._ctx.putImageData(relevantData, 0, 0);
    };

    return {
        init: function(canvas) {
            return new SignaturePad(canvas);
        }
    };

});