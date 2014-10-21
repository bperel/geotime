function showMapData() {
    $.ajax('../gateway.php', {
        dataType: 'json',
        data: { getMaps: true },
        success: function(data) {
            var gallery = $('#lightGallery');
            var thumbTemplate = gallery.find('li.template');
            $.each(data, function(mapFileName, territoriesData) {
                var thumb = thumbTemplate.clone(true).removeClass('template')
                    .attr({'data-src': '../cache/svg/'+mapFileName, 'data-html': mapFileName});
                thumb.find('img').attr({src: '../cache/thumbnails/'+mapFileName+'.png'});
                thumbTemplate.after(thumb);
            });
            gallery.lightGallery();
            $('.loading').addClass('hidden');
        }
    })
}