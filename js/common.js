/**
 * The function relies on JQuery to determine the total amount of time the page required to load
 *
 * @param void
 * @return void
 */
function getLoadTime()
{
    // ==== Creating the Data object ==== //
    var d = new Date();

    // ==== Getting the start time ==== //
    window.start_time = d.getTime();

    // ==== Calculating ==== //
    $(document).ready(function(){

        // ==== Creating the Data object ==== //
        var d = new Date();
    
        // ==== Getting the end time ==== //
        var end_time = d.getTime();

        // ==== Calculating the difference of time ==== //
        var loadTime = end_time - window.start_time;

        // ==== Converting the loadTime in seconds ==== //
        loadTime = loadTime/1000;

        // ==== Exporting the loading time ==== //
        window.loadTime = loadTime;
    });
}

/* 
 * The function adds a new element to another container
 *
 * @param string templateID
 * @param string elementID
 * @param int start
 * @return void
 */
function addElement(templateID, elementID, start, limit)
{
    var start = start || 2;
    var limit = limit || 30;

    // ==== Counter ==== //
    if(!addElement.counter) addElement.counter = start;

    // ==== Checking if the button limit has been reached ==== //
    if(limit >= addElement.counter)
    {
        // ==== The element where we will insert the new template ==== //
        var element = document.getElementById(elementID);

        // ==== Getting template contents ==== //
        var template = document.getElementById(templateID).innerHTML;

        // ==== Building new element ==== //
        var new_element = template.replace(/{num}/g, addElement.counter);

        // ==== Creating a new element ==== //
        var newElement = document.createElement('span');

        // ==== Setting ID of element ==== //
        newElement.setAttribute('id', 'file_'+addElement.counter);

        // ==== Setting style of the new element ==== //
        newElement.setAttribute('style', 'display: none');

        // ==== Appending data to element ==== //
        newElement.innerHTML = new_element;

        // ==== Adding contents to the element ==== //
        element.appendChild(newElement);

        // ==== Reveiling the element ==== //
        $(newElement).show('drop', '', 'fast');

        // ==== Incrementing counter ==== //
        addElement.counter++;
    }
}

/**
 * The method uses JQuery to hide an element
 *
 * @param string element
 * @param string style
 * @param string speed
 * @return void
 */
function hide(element, style, speed)
{
    var style = style || 'drop';
    var speed = speed || 'fast';

    $(element).hide(style, '', speed);
}

/**
 * Trim function
 *
 * @param string
 * @return string
 */
function trim(s) { return rtrim(ltrim(s)); }

/**
 * The function does a redirect
 *
 * @param url
 * @return void
 */
function redirect(url){ location.replace(url); }

/**
 * Used to remove de content of an element
 *
 * @param string element ID
 * @return void
 */
function delCont(element) { document.getElementById(element).innerHTML = '&nbsp;'; }

/**
 * The function can be used to clear and restore the text from a field
 *
 * @param object field
 * @return void
 */
function clearText(field)
{
    if (field.defaultValue == field.value) field.value = '';
    else if (field.value == '') field.value = field.defaultValue;
}

/**
 * The function checks if the given value is numeric
 *
 * @param string n
 * @return boolean
 */
function is_numeric(n)
{
    // ==== List of valid characters for a number ==== //
    var strValidChars = "0123456789.-";

    // ==== Current character in loop ==== //
    var strChar;

    // ==== Check variable ==== //
    var blnResult = true;

    // ==== Checking the string length ==== //
    if (strString.length == 0) blnResult = false;

    // ==== Testing if the string contains valid characters ==== //
    for (var i = 0; i < strString.length && blnResult == true; i++)
    {
        // ==== Getting the current character ==== //
        strChar = strString.charAt(i);

        // ==== Checking if the character exists in the valid chars string ==== //
        if (strValidChars.indexOf(strChar) == -1)
        {
            blnResult = false;
        }
    }

    // ==== result ==== //
    return blnResult;
}
