
// 	animateMessage.js
//
// 	adapted from animatePunctuation.js
//
//   	source : {id}
//   	display : {id}
//		animate : {true, false}	
//		delay : ## [50]

// 	globals	
var timeout;
var pointer;

function initMessage(sourceId, displayId, animate, delay) 
{
    var source = document.getElementById(sourceId); 
    var display = document.getElementById(displayId);
    var message = buildMessage(source);
    pointer = 0;                      

    if (animate) 
    {
        clearTimeout(timeout);
        timeout = null;
        if (delay === null)
            delay = 50;
        animateMessage(source, display, message, delay);
    } 
    else 
    {
        display.appendChild(message);
    }
    // hideShowMessage('displayWrapper','displayControl','show');
}


function buildMessage(root) 
{
    var next;
    var node = root.firstChild;
    var message = document.createDocumentFragment();

    do 
    {      
        next = node.nextSibling;

        if (node.nodeType === 1) 
        {
            message.appendChild(node.cloneNode(true));
        } 
        else if (node.nodeType === 3) 
        {
            var text = node.textContent;
            for (i = 0; i < text.length; i++) 
            {
                var temp = document.createElement("span");
                temp.textContent = text[i];
                message.appendChild(temp);
            }
        }
    } 
    while (node = next);
    return message;
}

function animateMessage(source,display,message,delay)
{
    if (pointer < message.childNodes.length)
    {
        display.appendChild(message.childNodes[pointer].cloneNode(true));
        pointer++;
        timeout = setTimeout(function(){
            animateMessage(source, display, message, delay);
        }, delay);
    } 
    else 
    {
        console.log("stop");
        startStopAnimateMessage();
    }
}

function startStopAnimateMessage()
{
    if (timeout == null)
    {            
        initMessage("animateMessage","target",true,delay);			
        return true;
    } 
    else 
    {
        clearTimeout(timeout);
        timeout = null;
        return false;
    }
}


function hideShowMessage(displayId,controlId,forceAction)
{
    var display = document.getElementById(displayId);
    var control = document.getElementById(controlId);

    if ((display.style.overflow != "hidden") || forceAction == "hide") 
    {
        display.style.overflow = "hidden";
        display.style.height = "20px";
        control.textContent = "+";
    } 
    else if ((display.style.overflow == "hidden") || forceAction == "show") 
    {
        display.style.overflow = "auto";
        display.style.height = "auto";
        control.textContent = "×";
    }
}


function setCookie(name)
{
    if (getCookie(name) == "") 
    {
        document.cookie=name+"=true";
        return true;
    } 
    else
        return false;
}


function expireCookie(name) 
{
    if (getCookie(name) != "") 
    {
        document.cookie = name+"=; expires=Thu, 01 Jan 1970 00:00:00 GMT";
        return true;
    } 
    else
        return false;
}


function getCookie(name)
{
    var cname = name + "=";
    var ca = document.cookie.split(';');

    for(var i = 0; i < ca.length; i++) 
    {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1);
        if (c.indexOf(cname) != -1) return c.substring(cname.length,c.length);
    }
    
    return "";
}


function checkCookie(name)
{
    if (getCookie(name) != "")
        return true;
    else
        return false;
}

/* 
*/

