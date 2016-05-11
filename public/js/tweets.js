const LETTERS = "abcdefghijklmnopqrstuvwxyz";
var ti = 0; // DO SOMETHING WITH THIS VARIABLE

function init_animation()
{
    show_tweet(index);
    tweet_interval = setInterval(show_next_tweet, tweet_duration);
}

function show_next_tweet()
{
    // is it bad to increment index here?
    // probs too easy to miss?
    hide(tweets[index++]);
      
    // reset to the first tweet
    if (index == tweets.length)
        index = 0;

    show_tweet(index);
}

function show_prev_tweet()
{
    // is it bad to decrement index here?
    // probs too easy to miss?
    hide(tweets[index--]);
    
    // reset to the last tweet
    if (index < 0)
        index = tweets.length-1;

    show_tweet(index);
}

// given an index and an animation info array
// show the tweet at the given index
// requires: info, animation_style to be defined
function show_tweet(index)
{
    var tweet, text, anim;
    anim = info[animation_style];
    
    show(tweets[index]);
    if (anim.use_spans)
    {
        tweet = tweets[index];
        text = tweet.getElementsByClassName("text")[0];
    
        add_spans(text, anim.class_func);
        ti = 0;
        anim.animate_func(text, anim.delay);
    }
    // draw_clock(dts[index]);
}

// this should really be renamed
function toggle_rotation()
{
    if(tweet_interval)
    {
        clearInterval(tweet_interval);
        tweet_interval = null;
    }
    else
    {
        show_next_tweet();
        tweet_interval = setInterval(show_next_tweet, tweet_duration);
    }
}

function add_spans(el, class_func)
{
    var cns;
    
    // check inputs
    if (el === undefined)
        return;
    
    cns = el.childNodes;
    
    // base case
    // TODO: make sure this isn't a "SCRIPT" tag
    // (or style tag or. . . )
    if (cns.length == 1 && cns[0].nodeType == 3)
    {
        var text = el.textContent;
        
        // i suppose this while loop is unnecessary
        // as we know that el only has one child? 
        while (el.firstChild)
            el.removeChild(el.firstChild);
        
        text = split_by_symbol(text);
        for (var i = 0; i < text.length; i++)
        {
            var s = document.createElement("span");
            s.innerHTML = text[i];
            class_func(s);
            el.appendChild(s);
        }
    }
    else
    {
        for (var i = 0; i < cns.length; i++)
        {
            if(cns[i].nodeType == 1 && cns[i].tagName != "SCRIPT")
                add_spans(cns[i], class_func);
        }
    }
}

function remove_spans(el)
{

}

// --------------------------------------------------------
// random animation
// --------------------------------------------------------

function random_classes(s)
{
    s.classList.add("invisible");
}

function random_animate(el, delay)
{
    var random_index, els;
    
    // check inputs
    if (el === undefined)
        return; 
    if (delay === undefined)
        delay = 50;

    els = el.getElementsByClassName("invisible");
    random_index = Math.floor(Math.random() * els.length);
    setTimeout(function() {
        if (els[random_index] !== undefined)
        {
            els[random_index].classList.remove("invisible");
            if (els.length > 0)
                random_animate(el, delay);
        }
    }, delay); 
}

// --------------------------------------------------------
// in-order animation
// --------------------------------------------------------

function in_order_classes(s)
{
    s.classList.add("hidden");
}

function in_order_animate(el, delay)
{
    if (delay === undefined)
        delay = 50;
    var els = el.getElementsByClassName("hidden");
    setTimeout(function() {
        if (els[0] !== undefined)
        {
            els[0].classList.remove("hidden");
            if(els.length > 0)
                in_order_animate(el, delay);
        }
    }, delay);
}

// --------------------------------------------------------
// alphabetical animation
// --------------------------------------------------------

function alphabetical_classes(s)
{
    c = s.textContent.toLowerCase();
    if (LETTERS.indexOf(c) >= 0)
        s.classList.add(c);
    else
        s.classList.add("punct");
    s.classList.add("invisible");
}

function alphabetical_animate(el, delay)
{
    if (delay === undefined)
        delay = 50;
    var els;
    
    setTimeout(function() {
        if (ti < LETTERS.length)
            els = el.getElementsByClassName(LETTERS[ti++]);
        else {
            els = el.getElementsByClassName("punct");
            ti++;
        }
        show_invisibles(els);
        if (ti <= LETTERS.length)
            alphabetical_animate(el, delay);
    }, delay);
}

function show_invisibles(elements)
{
    for (var i = 0; i < elements.length; i++)
    {
        elements[i].classList.add("animated", "fadeIn");
        elements[i].classList.remove("invisible");
    }
}

// --------------------------------------------------------
// utilities
// --------------------------------------------------------

// this function takes a string and returns an array of unicode symbols
// because javascript + unicode = sadness
// https://mathiasbynens.be/notes/javascript-unicode
function split_by_symbol(str)
{
    var i, length, output, charCode;
    i = 0;
    length = str.length;
	output = [];
	
	for (; i < length - 1; ++i) 
	{
		charCode = str.charCodeAt(i);
		if (charCode >= 0xD800 && charCode <= 0xDBFF)
		{
			charCode = str.charCodeAt(i + 1);
			if (charCode >= 0xDC00 && charCode <= 0xDFFF)
			{
				output.push(str.slice(i, i + 2));
				i++;
				continue;
			}
		}
		output.push(str.charAt(i));
	}
	output.push(str.charAt(i));
	return output;   
}

// take an array and shuffle its values
// returns the shuffled array
function shuffle(array)
{
    var current_index, random_index, temp; 
    curren_index = array.length;
    
    while (0 !== current_index)
    {
        // pick a remaining element. . . 
        random_index = Math.floor(Math.random() * current_index);
        current_index--;
        
        // . . . and swap it with the current element.
        temp = array[current_index];
        array[current_index] = array[random_index];
        array[random_index] = temp;
    }
    return array;
}

// maybe this should be in its own script?
// how to deal with the fact that it can only be declared once?
document.onkeydown = function(e) {
    e = e || window.event;
    switch(e.which || e.keyCode) {
        case 32: // spacebar
            toggle_rotation();
            break;
        case 37: // left
            show_prev_tweet();
            break;
        case 39: // right
            show_next_tweet();
            break;
        case 83: // S
            strike();
            break;
        case 85:
            unstrike();
            break;
        default: return; // exit this handler for other keys
    }
    e.preventDefault();
}

// given an element, adds classes necessary to hide it
function hide(el)
{
    el.classList.add('fadeOut');
    el.classList.add('hidden');
}

// given an element, removes classes necessary to hide it
function show(el)
{
    el.classList.remove('hidden');
}