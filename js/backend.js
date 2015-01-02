function showMapData() {
    ajaxPost(
        {getMaps: true},
        function(error, data) {
            var gallery = $('#lightGallery');
            var thumbTemplate = gallery.find('li.template');
            $.each(data, function(mapFileName, territoriesData) {
                var thumb = thumbTemplate.clone(true).removeClass('template')
                    .attr({'data-src': '../cache/svg/'+mapFileName, 'data-html': mapFileName});
                thumb.find('img').attr({src: '../cache/thumbnails/'+mapFileName+'.png'});
                thumbTemplate.after(thumb);
            });
            gallery.lightGallery();
            $('#mapNumber').text(Object.keys(data).length);
            $('#mapInfo').removeClass('hidden');
            $('.loading-maps').addClass('hidden');
        }
    );
}

function showTerritoryData() {
    ajaxPost(
        {getImportedTerritories: true},
        function(error, data) {
            $('#importedTerritoriesNumber').text(data.count);
            $('#territoryInfo').removeClass('hidden');
            $('.loading-territories').addClass('hidden');
        }
    );
}