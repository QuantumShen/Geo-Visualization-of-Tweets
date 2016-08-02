/* ===========================================
1st layer: The tweet points */

var tweetSource = new ol.source.Vector(); //empty now. add feature later

function tweetPointStyleFunc(feature){

	function getStyleImage(){
		var temp = mapView.getZoom();
		var iconstyle = {
			anchor: [0.5, 0.5], // size and offset needn't set since using the whole image
			opacity: 1,
			scale: 0.5,
			src: feature.get('image_url')
		};

		var circlestyle = {
			radius : 3,
			stroke: new ol.style.Stroke({
				color: 'white',
				width: 1
			}),
			fill: new ol.style.Fill({
				color: 'red'
			})
		};

		if(temp < 4){
			return new ol.style.Circle(circlestyle);
		}
		else if (temp > 6){
			iconstyle.scale = 1;
			return new ol.style.Icon(iconstyle);
		}
		else{
			iconstyle.scale = 0.5;
			return new ol.style.Icon(iconstyle);
		}		
	}


	var markerStyle = new ol.style.Style({
		image: getStyleImage()
	});

	return [markerStyle];
}



var dataLayer = new ol.layer.Vector({
	source : tweetSource,
	style: tweetPointStyleFunc
});


/* ===========================================
2nd layer: The popup text box */

function wordwrap( str, width, brk, cut ) { 
//copy from http://james.padolsey.com/snippets/wordwrap-for-javascript/
 
    brk = brk || '\n';
    width = width || 26;
    cut = cut || true;  // false means long words may exceed width
 
    if (!str) { return str; }
 
    var regex = '.{1,' +width+ '}(\s|$)' + (cut ? '|.{' +width+ '}|.+$' : '|\S+?(\s|$)');
 
    return str.match( RegExp(regex, 'g') ).join( brk );
 
}

// First Edition: popup func is implemented using a common layer with text and image.
// function popupStyleFunc(feature, resolutiion){

// 	var Style = new ol.style.Style({
// 		text: new ol.style.Text({
// 			font: "12px Verdana",
// 			textAlign: 'start',
// 			textBaseline: 'bottom',
// 			offsetX: 3,
// 			offsetY: -90,
// 			fill: new ol.style.Fill({
// 				color: "black"
// 			}),
// 			stroe: new ol.style.Stroke({
// 				color: "white",
// 				width: 4
// 			}),

// 			text: feature.get('screen_name')+':\n '+ wordwrap(feature.get('text'))

// 		}),
// 		image: new ol.style.Icon({
// 			anchor: [10, 10], // size and offset needn't set since using the whole image
// 			anchorOrigin: 'bottom-left',
// 			anchorXUnits: 'pixels',
// 			anchorYUnits: 'pixels',

// 			opacity: 0.9,
// 			scale: 0.8,

// 			src: './img/balloon.png'

// 		}),
// 		zIndex: 2
// 	});

// 	return [Style];

// }


// var popupSource = new ol.source.Vector(); //empty now. add feature later

// var popupLayer = new ol.layer.Vector({
// 	source: popupSource,
// 	style: popupStyleFunc
// });



var container = document.getElementById('popup');
var content = document.getElementById('popup-content');
var closer = document.getElementById('popup-closer');


var overlay = new ol.Overlay({
	element: container,
	autoPan: true,
	autoPanAnimation: {
		duration: 250
	}
});

function clearPopup(){
	overlay.setPosition(undefined);
	closer.blur();
}

closer.onclick = function() {
	clearPopup();
	return false; // not to follow the link
};


/* ===========================================
3rd Layer: the map tiles */

// Three kind's of layers: Tile for map, Vector for data, Image for photo

var tileLayer = new ol.layer.Tile({

	source: new ol.source.OSM()

	// satallite map:
	// source: new ol.source.MapQuest({layer:'sat'})

	//bing map:
	// source: new ol.source.BingMaps({
	// 	key: 'Aif0OZRT6dJazSUgXJD3pTt3G_CRKuiKTm4mAaodxgXs_8e4atSYbKwNUafrRtr6',
	// 	imagerySet: 'AerialWithLabels'
	// })
});



/* ===========================================
4th Layer: The heatmap */

var blur = document.getElementById('blur');
var radius = document.getElementById('radius');

var heatmapSource = new ol.source.Vector(); //empty now. add feature later

var heatmapLayer = new ol.layer.Heatmap({
  source: heatmapSource,
  blur: parseInt(blur.value, 10),
  radius: parseInt(radius.value, 10),
  zIndex: 3,
  weight: function(feature){
	  return 0.5; // may set weight according to user total tweets.
  },
  opacity: 0.8
});


/* ===========================================
map create */

var mapView = new ol.View({
	center: ol.proj.fromLonLat([-96,42]),  // default : EPSG3857, needs to proj lon/lat to meters
	zoom: 2
});

var map = new ol.Map({
	layers: [tileLayer,dataLayer, heatmapLayer],
	view: mapView,
	target: 'map',
	overlays: [overlay]
});

/* ===========================================
interactions */

// add handler to default interactions 
map.on('singleclick', function(e) {
	
	if(heatmapSource.getFeatures().length>0) {  // when heat map is shown
		return;
	}

	clearPopup(); //popupSource.clear();
	

	var features = [];
	map.forEachFeatureAtPixel(e.pixel, function(feature, layer) { // what if many features overlap together?
		if(layer == dataLayer)
		{
			features.push(feature);
		}
	});

	if (features.length > 0){
		//popupSource.addFeature(features[0]); 
		
		//overlay is not based on features
		//overlay is based on position and DOM contents

		var tempfeature = features[0];
		//var name_text = tempfeature.get('screen_name')+':<br />'+ wordwrap(tempfeature.get('text'));
		var name_text = tempfeature.get('screen_name')+':<br />'+ tempfeature.get('text');

		// var text = tempfeature.get('text');

		// text.replace(/((https?|s?ftp|ssh)\:\/\/[^"\s\<\>]*[^.,;'">\:\s\<\>\)\]\!])/g, function(url) {
		// 	return '<a href="'+url+'">'+url+'</a>';
		// }).replace(/\B@([_a-z0-9]+)/ig, function(reply) {
		// 	return  reply.charAt(0)+'<a href="http://twitter.com/'+reply.substring(1)+'">'+reply.substring(1)+'</a>';
		// });
		
		// var name_text = tempfeature.get('screen_name') + ':<br />' + text +'<a href="http://twitter.com/'+
		// 	tempfeature.get('screen_name')+'/statuses/'+tempfeature.get('tweet_id')+ ">; &nbsp Created at:"+
		// 	tempfeature.get('created_at')+ '</a>' ;

		$("#popup-content").html(name_text);

		var coord = tempfeature.getGeometry().getCoordinates(); // [lon, lat]

		overlay.setPosition(coord);


	}
	else { //click at empty space
		dataLayer.setSource(tweetSource);
	}

	// $("#print1").html(output);
});

// add a DragBox interaction to select by drawing box

var dragBox = new ol.interaction.DragBox({
	condition: ol.events.condition.platformModifierKeyOnly
});

map.addInteraction(dragBox);

// add handler to dragBox interactions

dragBox.on('boxend', function() {
	
	//store selected features to a temp source, substitute the orginial tweetSource

	var selectedFeatures = [];
	var extent = dragBox.getGeometry().getExtent();
	tweetSource.forEachFeatureInExtent(extent, function(feature) {
		selectedFeatures.push(feature);
	});

	var tempSource = new ol.source.Vector({
		features: selectedFeatures
	});

	heatmapSource.clear();
	clearPopup();
	dataLayer.setSource(tempSource); // always show tweets after select
	

});

// dragBox.on('boxstart', function() {  // clear selection when drawing a new box 
// 	dataLayer.setSource(tweetSource);
// });


/*===========================================
Button click handlers
*/

//Search tweets with query and geo

$("#clear2").click(function(){ 
	dataLayer.getSource().clear();
	heatmapLayer.getSource().clear();
	clearPopup();
	//popupSource.clear();
	
	$("#search_result").html("");
	$("#search_time").html("");
});


function json2Feature(data) {

	if(!data.success){
		if(data.found === 0) {
			return;
		}
	}

	data.tweets.forEach(function(item){
		var coordinate = ol.proj.fromLonLat([parseFloat(item.lon), parseFloat(item.lat)]);
		var feature = new ol.Feature({
			screen_name: item.screen_name,
			geometry: new ol.geom.Point(coordinate),
			tweet_id: item.tweet_id,
			image_url: item.profile_image_url,
			time_stamp: item.created_at,
			text: item.tweet_text
			// text: item.tweet_text.replace(/((https?|s?ftp|ssh)\:\/\/[^"\s\<\>]*[^.,;'">\:\s\<\>\)\]\!])/g, function(url) {
			// 	return '<a href="'+url+'">'+url+'</a>';
			// }).replace(/\B@([_a-z0-9]+)/ig, function(reply) {
			// 	return  reply.charAt(0)+'<a href="http://twitter.com/'+reply.substring(1)+'">'+reply.substring(1)+'</a>';
			// })
		});	//besides the system definded attributes: id,geometry, style, name, any other attributes can be added!
		tweetSource.addFeature(feature);

	});
	dataLayer.setSource(tweetSource);
}

$("#search").click(function(){ 
	dataLayer.getSource().clear();
	clearPopup(); //popupSource.clear();
	heatmapLayer.getSource().clear();
	
	var centerPoint = new ol.geom.Point(mapView.getCenter()); // projection same as map : 3857
	centerPoint.transform("EPSG:3857", "EPSG:4326");
	var centerCoord = centerPoint.getCoordinates(); // [lon, lat]


	//$("#debug_output").html(JSON.stringify(centerCoord, null, 4));

	$.ajax({
		type: "GET",
		url: "./php/search_tweets.php",
		data:{
			q: $("#q").val(),
			geo: centerCoord[1] + "," + centerCoord[0] + "," + "24901mi",
			result_type:$("#result_type").val(),
			lang: $("#lang").val() 
		},
		dataType: "json",
		success: function(data) {
			$("#search_result").html(data.msg + "<br />" + "Found tweets: " + data.found);
			$("#search_time").html("Last search: " + Date());
			json2Feature(data);
		},
		error: function(jqXHR){
			$("#search_time").html("Last search: " + Date());
			alert("Error Calling Search API:" + jqXHR.status);
		}   //todo: better give api status number
	});

});


// Stream in 1000 tweets Function:

$("#clear1").click(function(){ 
	dataLayer.getSource().clear();
	clearPopup(); //popupSource.clear();
	heatmapLayer.getSource().clear();
	$("#update_msg").html("");
});


function update_report(data) {

	if(!data.success){
		$("#update_msg").html("Last update not finished. Started at:" + data.start_time);
		return;
	}

	$("#update_msg").html("Update starting at: " + data.start_time + " ...");

}

// read db and display tweets
$("#update").click(function(){ 
	dataLayer.getSource().clear();
	clearPopup(); //popupSource.clear();
	heatmapLayer.getSource().clear();
	
	//$("#debug_output").html(JSON.stringify(centerCoord, null, 4));

	$.ajax({
		type: "GET",
		url: "./php/update_db.php",
		dataType: "json",
		success: update_report,
		error: function(jqXHR){alert("Error in updating database:" + jqXHR.status);}  
	});

});

// read db and display tweets
$("#display").click(function(){ 
	dataLayer.getSource().clear();
	clearPopup(); //popupSource.clear();
	heatmapLayer.setSource(new ol.source.Vector()); // heatmapLayer.getSource().clear(); // must cut the reference to tweetSource
	//$("#debug_output").html(JSON.stringify(centerCoord, null, 4));

	$.ajax({
		type: "GET",
		url: "./php/read_db.php",
		dataType: "json",
		success: function(data) {
			if(!data.success){
				$("#update_msg").html("Last update not finished. Started at: " + data.start_time);
				return;
			}	
			$("#update_msg").html("Last update completed at: " + data.end_time + "<br />" + "Found tweets: " + data.found);
			json2Feature(data);
		},
		error: function(jqXHR){alert("Error in reading database:" + jqXHR.status);} 
	});

});


// heatmap function
$("#clear3").click(function(){ 

	if(heatmapSource.getFeatures().length>0){
		dataLayer.setSource(heatmapSource); // use heatmapSource back
		heatmapSource = new ol.source.Vector();
		heatmapLayer.setSource(heatmapSource);
		clearPopup();
	}
	
});

$("#heatmap").click(function(){

	if(dataLayer.getSource().getFeatures().length>0){
		heatmapSource = dataLayer.getSource();
		heatmapLayer.setSource(heatmapSource);

		//clear tweets using empty vector source
		//tweetSource.clear();
		var emptySource = new ol.source.Vector();
		dataLayer.setSource(emptySource);
		clearPopup(); //popupSource.clear();
	}
});


blur.addEventListener('input', function() {
	heatmapLayer.setBlur(parseInt(blur.value, 10));
  });

radius.addEventListener('input', function() {
	heatmapLayer.setRadius(parseInt(radius.value, 10));
});
