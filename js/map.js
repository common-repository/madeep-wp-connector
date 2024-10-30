var map = {};
function madeepMap() {
    var mapn = 1;
    $('.madeepHotelMap').each(function () {
        var mapId = 'madeepmap_' + mapn;
        $(this).attr({id: mapId});
        var latlon = {lat: $(this).attr('data-lat')*1, lng: $(this).attr('data-lon')*1};
        map[mapId] = new google.maps.Map(document.getElementById(mapId), {
            center: latlon,
            zoom: 8
        });
        var marker = new google.maps.Marker({position: latlon, map: map[mapId]});
        mapn++;
        
        if($(this).attr('data-text').length > 0){
            var infowindow = new google.maps.InfoWindow({
                content: atob($(this).attr('data-text'))
            });
            infowindow.open(map[mapId],marker);
            marker.addListener('click',function(){
                infowindow.open(map[mapId],marker);
            });
        }
    });


}