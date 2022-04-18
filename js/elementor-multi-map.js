// Keep retrying until Google Maps loads
function elementorMultiMapInit (instanceData) {
  if (!window.google || !window.google.maps || !window.google.maps.Map) {
    window.setTimeout(elementorMultiMapInit, 100, instanceData)
  } else {
    _elementorMultiMapInit(instanceData)
  }
}


// Initialise the map
function _elementorMultiMapInit (instanceData) {
  var instanceId = instanceData.instanceId
  var map = new google.maps.Map(
    document.getElementById("elementorMultiMap" + instanceId),
    {
      zoom: 3,
      center: {
        lat: 0,
        lng: 0,
      },
      zoomControl: true,
      mapTypeControl: false,
      scaleControl: false,
      streetViewControl: false,
      rotateControl: false,
      fullscreenControl: true,
    }
  );
  if (instanceData.pins && instanceData.pins.length > 0) {
    var bounds = new google.maps.LatLngBounds();
    var infowindow = new google.maps.InfoWindow({
    });
    instanceData.pins.forEach(function(pin) {

      var marker = new google.maps.Marker({
        position: {lat: pin.lat, lng: pin.lng},
        map,
        title: pin.name,
      });

      bounds.extend( marker.getPosition() );

      marker.addListener("click", () => {
        infowindow.setContent(pin.html)
        infowindow.open({
          anchor: marker,
          map,
          shouldFocus: false,
        });
      });

    })
    if (instanceData.pins.length > 1) {
      map.fitBounds(bounds, 41);
    }
  }
}

// If any instances have loaded before this script, then call the init function for each
if (window.elementorMultiMapCallbackData && window.elementorMultiMapCallbackData.length) {
  window.elementorMultiMapCallbackData.forEach(elementorMultiMapInit);
}
