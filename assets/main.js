// The intention below is to allow users to X out of events they've seen, which would be stored in a browser cookie
// Update banner display on page load
// document.addEventListener("DOMContentLoaded", function() {
// Check for gtm-consent cookie
//	cookieValue = (value_or_null = (document.cookie.match(/^(?:.*;)?\s*eos-events\s*=\s*([^;]+)(?:.*)?$/)||[,null])[1]);
//	console.log(cookieValue);
//	
//	if ( cookieValue  !== null ) {
//		
//	}else{
//		document.getElementById("gc-popup").classList.remove("d-none");
//	}
//});

// Update consent on click
//function scriptAccept(id) {
//	document.cookie = 'gtm-consent=accepted; path=/; max-age=604800';
//	document.getElementById("gc-popup").classList.add("d-none");
//}