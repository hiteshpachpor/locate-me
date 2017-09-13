<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">

    <title>Locate Me</title>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/underscore.js/1.8.3/underscore-min.js"></script>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Lato', sans-serif;
        }
        pre {
            border: 1px solid #ccc;
            padding: 16px;
            margin: 0;
            border-radius: 4px;
            background: #FAFAFA;
            box-shadow: inset -1px 2px 6px -2px #CCC;
            max-width: 800px;
        }
        #map {
            height: 500px;
        }
        .string { color: green; }
        .number { color: darkorange; }
        .boolean { color: blue; }
        .null { color: magenta; }
        .key { color: #C00; }
        .capitalize { text-transform: capitalize; }
        .badge { background-color: #3b91ce; }
    </style>

    <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <div class="h3">Coordinates</div>
                <p id="latitudeAndLongitude">Select one of the options below:</p>
                <hr>

                <div>
                    <button class="btn btn-primary pull-right" onclick="locateMe()">Locate Me</button>
                    <div class="h3">1. Current Location</div>
                </div>
                <hr>

                <div>
                    <div class="h3">2. Specify Location</div>
                    <div class="input-group">
                        <input type="text" class="form-control" name="latitude" id="latitude" placeholder="Latitude">
                        <span class="input-group-addon">-</span>
                        <input type="text" class="form-control" name="longitude" id="longitude" placeholder="Longitude">
                        <span class="input-group-btn">
                            <button class="btn btn-primary" type="button" onclick="locateFromLatLang()">Get Address</button>
                        </span>
                    </div>
                </div>
                <hr>

                <div>
                    <div class="h3">3. Search By Pincode</div>
                    <div class="input-group">
                        <input type="text" class="form-control" name="pincode" id="pincode" placeholder="Enter pincode">
                        <span class="input-group-btn">
                            <button class="btn btn-primary" type="button" onclick="searchByPincode()">Get Address</button>
                        </span>
                    </div>
                </div>
                <hr>

                <div>
                    <div class="h3">4. Choose From Map</div>
                    <div id="map"></div>
                </div>
                <hr class="scroll-to">

                <div class="h3">Primary Address</div>
                <p id="address"></p>
                <hr>

                <div class="h3">All Addresses</div>
                <div id="all_addresses">
                    <div class="list-group"></div>
                </div>
                <hr>
            </div>

            <div class="col-sm-6">
                <div class="h3">Complete Response</div>
                <pre id="raw">Waiting...</pre>
            </div>
        </div>
    </div>

    <script>
        var syntaxHighlight = function(json) {
            json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
                var cls = 'number';
                if (/^"/.test(match)) {
                    if (/:$/.test(match)) {
                        cls = 'key';
                    } else {
                        cls = 'string';
                    }
                } else if (/true|false/.test(match)) {
                    cls = 'boolean';
                } else if (/null/.test(match)) {
                    cls = 'null';
                }
                return '<span class="' + cls + '">' + match + '</span>';
            });
        };

        var latitudeAndLongitude = document.getElementById("latitudeAndLongitude");
        var _location = {
            latitude  : '',
            longitude : ''
        };

        var errorCodes = {
            0: "unknown error",
            1: "permission denied",
            2: "position unavailable (error response from location provider)",
            3: "timed out",
        };

        var initMap = function() {
            if (!navigator.geolocation) {
                latitudeAndLongitude.innerHTML = "Geolocation is not supported by this browser.";
            }

            var uluru = { lat: 20.5937, lng: 78.9629 };
            var map = new google.maps.Map(document.getElementById('map'), {
                zoom: 4,
                center: uluru
            });
            var marker = new google.maps.Marker({
                position: uluru,
                map: map
            });

            google.maps.event.addListener(map, 'click', function(event) {
                placeMarker(event.latLng);
            });

            function placeMarker(_location) {
                marker.setPosition(_location);
                fetchAddress({
                    latitude  : _location.lat(),
                    longitude : _location.lng()
                });
            }
        };

        var fetchAddress = function(_location) {
            latitudeAndLongitude.innerHTML = "Latitude: " + _location.latitude + "<br>Longitude: " + _location.longitude;

            var geocoder = new google.maps.Geocoder();
            var latLng = new google.maps.LatLng(_location.latitude, _location.longitude);

            if (geocoder) {
                geocoder.geocode({ 'latLng': latLng }, function (results, status) {
                    if (status == google.maps.GeocoderStatus.OK) {
                        $('#address').html(results[0].formatted_address);

                        $("#all_addresses .list-group").empty();
                        _.forEach(results, function(address) {
                            var addressTypes = "";

                            _.forEach(address.types, function(type) {
                                addressTypes += '<span class="badge">' + type + '</span>';
                            });

                            $("#all_addresses .list-group").append('<a href="#" class="list-group-item">' + addressTypes + address.formatted_address + '</a>');
                        });

                        $('#raw').html(syntaxHighlight(JSON.stringify(results, null, 4)));

                        setTimeout(function() {
                            $("html, body").animate({
                                scrollTop: $(".scroll-to").offset().top
                            }, 800);
                        }, 400);
                    } else {
                        $('#address').html('Geocoding failed: '+ status);
                        alert("Geocoding failed: " + status);
                    }
                });
            }
        };

        var locateMe = function() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    _location.latitude  = position.coords.latitude;
                    _location.longitude = position.coords.longitude;

                    fetchAddress(_location);
                }, function(error) {
                    alert('Error occurred. Error code: ' + errorCodes[error.code]);
                });
            }
        };

        var locateFromLatLang = function() {
            if ($("#latitude").val().length > 0 && $("#longitude").val().length > 0) {
                fetchAddress({
                    latitude  : $("#latitude").val(),
                    longitude : $("#longitude").val()
                });
            } else {
                alert("Please enter latitude and longitude.");
            }
        };

        var searchByPincode = function() {
            var pincode = $("#pincode").val();
            var apiKey = "579b464db66ec23bdd000001427ce7496ef74b7a622e95878fdfebb3";

            if (!pincode || isNaN(pincode)) {
                alert("Please enter a valid pincode.");
                return;
            }

            $.get("https://api.data.gov.in/resource/7eca2fa3-d6f5-444e-b3d6-faa441e35294?format=json&api-key=" + apiKey + "&limit=100&filters[pincode]=" + pincode, function(response) {
                var addressList = response.records;

                $("#all_addresses .list-group").empty();
                _.forEach(addressList, function(address) {
                    var addressTypes = '<span class="badge">' + address.districtname + '</span>';
                    var addressLine = address.locality_detail3 == "NA" ? '' : address.locality_detail3.toLowerCase() + "<br>";
                    if (address.locality_detail2 != "NA") {
                        addressLine += address.locality_detail2.toLowerCase() + "<br>";
                    }
                    addressLine += address.locality_detail1.toLowerCase();

                    $("#all_addresses .list-group").append('<a href="#" class="list-group-item capitalize">' + addressLine + '</a>');
                });

                $('#raw').html(syntaxHighlight(JSON.stringify(response, null, 4)));

                setTimeout(function() {
                    $("html, body").animate({
                        scrollTop: $(".scroll-to").offset().top
                    }, 800);
                }, 400);
            });
        };
    </script>
    <script async defer src="https://maps.google.com/maps/api/js?key=AIzaSyAwmC-3j1x_4vaTFffypmB_2bXC2Hs4ZCQ&callback=initMap"></script>
</body>
</html>
