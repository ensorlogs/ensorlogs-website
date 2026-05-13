/* global tinymce */
/**
 * Plugin TinyMCE: botón "Marker" que envuelve la selección en <mark>
 * para destacar palabras clave en el editor clásico de WordPress.
 *
 * Si la selección ya está dentro de <mark>, lo retira (toggle).
 */
(function () {
    if (typeof tinymce === 'undefined') {
        return;
    }
    tinymce.PluginManager.add('ensor_mark', function (editor) {
        editor.addButton('ensor_mark', {
            tooltip: 'Marker (resaltar)',
            icon: false,
            text: 'Marker',
            onclick: function () {
                var node = editor.selection.getNode();
                var $mark = editor.dom.getParent(node, 'mark');
                if ($mark) {
                    editor.dom.remove($mark, true);
                    return;
                }
                var selected = editor.selection.getContent({ format: 'html' });
                if (!selected) {
                    return;
                }
                editor.selection.setContent('<mark>' + selected + '</mark>');
            },
            onpostrender: function () {
                var btn = this;
                editor.on('NodeChange', function (e) {
                    var inMark = !!editor.dom.getParent(e.element, 'mark');
                    btn.active(inMark);
                });
            }
        });
    });
})();
