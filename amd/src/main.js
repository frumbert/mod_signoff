
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
            "trim_canvas_012": [
                "https://unpkg.com/trim-canvas@0.1.2/build/index.js",
                pluginJSURL('trimcanvas.min')
            ]
        }
    });

})();

// use our private namespace to avoid conflicts
define(['signature_pad_4','trim_canvas_012'], function(SignaturePad, trimCanvas) {

    return {
        init: function(canvas) {
            return new SignaturePad(canvas);
        }
    };

});