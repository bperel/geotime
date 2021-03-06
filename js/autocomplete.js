/**
 Copyright (c) 2014 BrightPoint Consulting, Inc.

 Permission is hereby granted, free of charge, to any person
 obtaining a copy of this software and associated documentation
 files (the "Software"), to deal in the Software without
 restriction, including without limitation the rights to use,
 copy, modify, merge, publish, distribute, sublicense, and/or sell
 copies of the Software, and to permit persons to whom the
 Software is furnished to do so, subject to the following
 conditions:

 The above copyright notice and this permission notice shall be
 included in all copies or substantial portions of the Software.

 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 OTHER DEALINGS IN THE SOFTWARE.
 */

function autocomplete(parent) {
    var _results=null,
        _selection,
        _margin = {top: 30, right: 10, bottom: 50, left: 80},
        __width = 420,
        __height = 420,
        _width,
        _height,
        _searchTerm,
        _lastSearchTerm,
        _currentIndex,
        _selectedFunction=defaultSelected,
        _minLength = 1,
        _dataField = "dataField";

    _selection=d3.select(parent);

    function component() {
        _selection.each(function () {

            // Select the svg element, if it exists.
            var container = d3.select(this.parentNode)
                .append("div")
                .attr("id","bp-ac")
                .attr("class","bp-ac")
                .append("div")
                .attr("class","padded-row")
                .attr("class","padded-row")
                .append("div")
                .attr("style","bp-autocomplete-holder");

            container
                .attr("width", __width)
                .attr("height", __height);

            var input = d3.select(this)
                .on("keyup",onKeyUp)
                .on("blur", hideDropDown);

            var dropDown=container.append("div").attr("class","bp-autocomplete-dropdown");

            var searching=dropDown.append("div").attr("class","bp-autocomplete-searching").text("Searching ...");

            hideSearching();
            hideDropDown();


            function onKeyUp() {
                _searchTerm=input.node().value;
                var e=d3.event;

                if (!e || !(e.which == 38 || e.which == 40 || e.which == 13)) {
                    if (!_searchTerm || _searchTerm == "") {
                        showSearching("No results");
                    }
                    else if (isNewSearchNeeded(_searchTerm,_lastSearchTerm)) {
                        _lastSearchTerm=_searchTerm;
                        _currentIndex=-1;
                        _results=[];
                        showSearching();
                        search(function(error, _matches) {
                            processResults(_matches);
                            if (_matches.length == 0) {
                                showSearching("No results");
                            }
                            else {
                                hideSearching();
                                showDropDown();
                            }
                        });
                    }

                }
                else if (e) {
                    e.preventDefault();
                }
            }

            function processResults(_matches) {
                var results=dropDown.selectAll(".bp-autocomplete-row").data(_matches, function (d) {
                    return d[_dataField];});
                results.enter()
                    .append("div").attr("class","bp-autocomplete-row")
                    .on("mousedown",function (d) { row_onClick(d); })
                    .append("div").attr("class","bp-autocomplete-title")
                    .html(function (d) {
                        var re = new RegExp(_searchTerm, 'i');
                        var strPart = d[_dataField].match(re)[0];
                        return d[_dataField].replace(re, "<span class='bp-autocomplete-highlight'>" + strPart + "</span>");
                    });

                results.exit().remove();

                //Update results

                results.select(".bp-autocomplete-title")
                    .html(function (d,i) {
                        var re = new RegExp(_searchTerm, 'i');
                        var strPart = _matches[i][_dataField].match(re);
                        if (strPart) {
                            strPart = strPart[0];
                            return _matches[i][_dataField].replace(re, "<span class='bp-autocomplete-highlight'>" + strPart + "</span>");
                        }
                    });
            }

            function search(callback) {
                ajaxPost(
                    {getTerritories: 1, like: _searchTerm},
                    callback
                );
            }

            function row_onClick(rowData) {
                hideDropDown();
                input
                    .datum(function() { return {territoryId: rowData.id, territoryName: rowData.name }; })
                    .node().value= rowData[_dataField];
                _selectedFunction(rowData);
            }

            function isNewSearchNeeded(newTerm, oldTerm) {
                return newTerm.length >= _minLength && newTerm != oldTerm;
            }

            function hideSearching() {
                searching.style("display","none");
            }

            function hideDropDown() {
                dropDown.style("display","none");
            }

            function showSearching(value) {
                searching.style("display","block");
                searching.text(value);
            }

            function showDropDown() {
                dropDown.style("display","block");
            }

        });
    }


    function measure() {
        _width=__width - _margin.right - _margin.left;
        _height=__height - _margin.top - _margin.bottom;
    }

    function defaultSelected(d) {
    }


    component.render = function() {
        measure();
        component();
        return component;
    };

    component.dataField = function (_) {
        if (!arguments.length) return _dataField;
        _dataField = _;
        return component;
    };

    component.width = function(_) {
        if (!arguments.length) return __width;
        __width = _;
        measure();
        return component;
    };

    component.height = function(_) {
        if (!arguments.length) return __height;
        __height = _;
        measure();
        return component;
    };



    return component;

}
