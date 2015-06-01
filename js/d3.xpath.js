d3.selection.prototype.xpath = function() {
    var elt = this.node(),
        path;
    if (elt && elt.id) {
        path = '//' + elt.tagName.toLowerCase() + '[id="' + elt.id + '"]';
    } else {
        var tpath = "";
        for (; elt && elt.nodeType == 1; elt = elt.parentNode) {
            var idx = 1;
            for (var sib = elt.previousSibling; sib; sib = sib.previousSibling) {
                if (sib.nodeType == 1 && sib.tagName == elt.tagName) {
                    idx++;
                }
            }

            var xname = elt.tagName.toLowerCase();

            if (idx > 1)
                xname += "[" + idx + "]";

            tpath = "/" + xname + tpath;
        }
        if (tpath.length) {
            path = tpath;
        }
    }
    return path;
};
