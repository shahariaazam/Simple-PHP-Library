/**
 * Countdown timer
 * 
 * @author Brian
 * @link http://brian.serveblog.net
 * @copyright 2011
 * @license Creative Commons Attribution-ShareAlike 3.0
 *
 * @usage: var eta = "21-09-2011 00:00:00"; display(eta, 'elementID');
 *
 * @name Countdown
 * @version 1.0
 */

// ==== Count down from date function ==== //
function countDown(eta)
{
	// == Getting date pieces === //
    var pieces = eta.split(' ');
    
    // == Getting date part == //
    var date = pieces[0].split('-');
    
    // == Getting time part == //
    var time = pieces[1].split(':');
    	
	// ==== Getting start date (today) ==== //
    var start = new Date();
	
	// ==== Getting end date ==== //
	var stop = new Date(date[2], date[1]-1, date[0], time[0], time[1], time[2], 0);
    
    // ==== Difference between the times ==== //
	var diff = stop.getTime()-start.getTime();
	
	// ==== Making sure diff is not < 0 ==== //
	if(diff < 0)
	{
		diff = 0;
	}
    
    // == Milliseconds in == //
    var s = 1000; 
    var m = s*60;
    var h = m*60;
    var d = h*24;
    var y = d*365;
    
    // ==== Getting counter data ==== //    
    var days    = Math.floor(diff/d);      // Days
    var hours   = Math.floor((diff%d)/h);  // Hours
    var minutes = Math.floor((diff%h)/m);  // Minutes
    var seconds = Math.round((diff%m)/s);  // Seconds

    // ==== Reset the seconds if reached 60 ==== //
    if(seconds == 60)
    {
    	seconds = 0;
    }
    
    // ==== Building string ==== //
    var string = days    + " days " +
                 hours   + " hours " +
                 minutes + " minutes " +
                 seconds + " seconds ";
    
    // ==== Returning result ==== //
    return string;
}

// ==== Display countdown timer ==== //
function display(ETA, elementId)
{
	var element = document.getElementById(elementId);
	
	element.innerHTML = countDown(ETA);
	
	setTimeout("display('"+ETA+"', '"+elementId+"')", 1000);
}