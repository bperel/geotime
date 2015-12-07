d3.selection.prototype.xpath = function(path) {
    var elt = this.node();

    if (path) {
        var result = document.evaluate(
            "//svg:svg[@id='externalSvg'] " + path,
            document,
            function lookupNamespaceURI (prefix) {
                switch (prefix) {
                    case "svg": return "http://www.w3.org/2000/svg";
                    default: return ""
                }
            },
            XPathResult.FIRST_ORDERED_NODE_TYPE,
            null
        );
        return (result && d3.select(result.singleNodeValue)) || d3.select("nothing");
    }
    else {
        if (elt && elt.id) {
            path = "//svg:" + elt.tagName.toLowerCase() + "[@id='" + elt.id + "']";
        } else {
            var tpath = "";
            for (; elt && elt.nodeType == 1; elt = elt.parentNode) {
                var idx = 1;
                for (var sib = elt.previousSibling; sib; sib = sib.previousSibling) {
                    if (sib.nodeType == 1 && sib.tagName == elt.tagName) {
                        idx++;
                    }
                }

                var xname = "svg:" + elt.tagName.toLowerCase();

                if (xname === 'svg:svg' && elt.id) {
                    break;
                }

                if (idx > 1)
                    xname += "[" + idx + "]";

                tpath = "/" + xname + tpath;
            }
            if (tpath.length) {
                path = tpath;
            }
        }
        return path;
    }
};