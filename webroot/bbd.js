var bbd = bbd || {};
bbd.host = null;
bbd.base = null;

function statLive()
{
	$('#meeting-live-stat').load(bbd.base + '/ajax/live', function() {
		niceTime();
	});
}

function niceTime()
{
	$('.time-nice').each(function(i, node) {
		var t = $(node).text();
		$(node).text( moment().from(t) );
	});
}