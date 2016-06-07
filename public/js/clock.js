const SMOOTH_SECOND_HAND = false;

// DOM elements
var canvas;     // canvas
var context;    // canvas context

// integers
var r;          // clock radius

// arrays (objects)
var center = {};
var time = {};
var hand_lengths;
var hand_widths;

// timer variables;
var hand_timer;

// booleans
var show_hands;
var reverse = false;

var fr = 100; // frame rate
var pop;
var synth, vco, vca;

// set size variables
function set_size(width, height)
{
    var min, line_width_factor;
    
    if (size == "small")
    {
        width = window.innerWidth * 0.2;
        height = width;
        line_width_factor = 2.0;
    }
    else
    {
        width = window.innerWidth;
        height = window.innerHeight;
        line_width_factor = 1.0;
    }
    min = Math.min(width, height);
    r = min * 0.8;
    
    hand_widths = 
    {
        h: 0.015,
        m: 0.015,
        s: 0.007,
        circle: 0.015
    };
    hand_lengths = 
    {
        h: 0.5,
        m: 0.8,
        s: 0.9
    };

    // set the hand lengths
    Object.keys(hand_lengths).forEach(function(key, index) {
        hand_lengths[key] *= r;
    });

    // adjust hand_widths based on size
    Object.keys(hand_widths).forEach(function(key, index) {
        hand_widths[key] *= (min * line_width_factor);
    });
    
    // set the center x and y coordinates
    center.x = width;
    center.y = height;
    
    return;
}

// canvasId: id of canvas on page
// a_pos: either "centre" or "lower-right"
function init_clock(canvas_id)
{
    canvas = document.getElementById(canvas_id);
    context = canvas.getContext('2d');
    pop = document.getElementById("ding");
    
    /*
    synth = new AudioContext();
    
    vco = synth.createOscillator();
    vco.type = vco.SINE;
    vco.frequency.value = 440;
    vco.start(0);
    
    vca = synth.createGain();
    vca.gain.value = 0;
    
    vco.connect(vca);
    vca.connect(synth.destination);
    */
    set_size();
    open_clock();
    update_time();
    
    window.onresize = function(event) 
    {
        set_size();
        draw();
    };  
}

var st, et;
var total = 2;
var del = -3600 / (fr * total);

function open_clock(starttime, endtime)
{
    show_hands = true;

    if (starttime !== undefined && endtime !== undefined)
    {
        st = starttime;
        et = endtime;
        pop.play();
        /*vca.gain.value = 1;*/
        hand_timer = window.setInterval(draw_reverse, 1000 / fr);
    }
    else
        hand_timer = window.setInterval(draw, 1000 / fr);
}

function close_clock()
{
    window.clearInterval(hand_timer);
    hand_timer = null;
    show_hands = false;
    draw();
}
var xyzed = true;
var xyned = true;

// given a date, d, draw hand_lengths at time d
// if no date given, then use now
function draw(d)
{
    var d1, d2;
    
    fill_bg();
    draw_circle();
    
    if (show_hands)
    {
        update_time(d);
        draw_hands();
        
        // update reverse frequency here!
        if (time.m == 0 && xyned)
        {
            d1 = new Date();
            d2 = new Date(d1.getTime() - 60 * 61 * 1000);
            close_clock();
            fill_bg();
            document.getElementById("clock-container").classList.add("large");
            size = "large";
            set_size();
            setTimeout(function() {
                open_clock(d1, d2);
                xyned = false;
            }, 500);
        }
        else if (time.m % 2 != 0)
            xyned = true;
    }
}



function draw_reverse()
{
    var eh, em, es;
    
    fill_bg();
    draw_circle();
    
    if (show_hands)
    {
        if (xyzed)
        {
            update_time(st);
            xyzed = false;
        }   
        d = reverse_time(time, del);
        update_time(d);
        draw_hands();
        
        eh = et.getHours();
        em = et.getMinutes();
        es = et.getSeconds();
        
        if (time.h == eh && time.m <= em)
        {
            close_clock();
            set_size();
            update_time(d);
            draw_hands();
            setTimeout(function() {
                fill_bg();
                setTimeout(function() {
                    document.getElementById("clock-container").classList.remove("large");
                    size = "small";
                    set_size();
                    open_clock();
                    st = undefined;
                    et = undefined;
                    xyzed = true;
                    }, 1500);
                }, 1500);
            /*vca.gain.value = 0;*/
        }
    }
}
function fill_bg()
{
    // make the canvas not look horrible on retina screens
    // for some reason this has to be called on every loop?
    // it needs to be called in every loop if the background is at
    // all transparent. it appears to reset the canvas.
    canvas.width = center.x * 2;
    canvas.height = center.y * 2;
    canvas.style.width = center.x.toString().concat('px');
    canvas.style.height = center.y.toString().concat('px');
    
    context.strokeStyle = colours.bg;
    context.fillStyle = colours.bg;
    context.fillRect(0, 0, center.x*2, center.y*2);
}

function draw_circle()
{
    context.strokeStyle = colours.circle;
    context.lineCap = 'round';
    context.lineWidth = hand_widths.circle;
    context.beginPath();
    context.arc(center.x, center.y, r, 0, 2* Math.PI);
    context.stroke();
}

function update_time(d)
{
    if (d === undefined)
        d = new Date();

    time.h = d.getHours();
    time.m = d.getMinutes();
    time.s = d.getSeconds();
    time.ms = d.getMilliseconds();
}

function draw_hands()
{
    var rad, k, x, y;

    // get angles for each hand (hours, minutes, seconds)
    rad = {};
    rad.h = (((time.h % 12) + time.m / 60.0) / 6.0);
    rad.m = (time.m + time.s / 60.0) / 30.0;
    // smooth second hand (uses milliseconds)
    if (SMOOTH_SECOND_HAND)
        rad.s = (time.s + time.ms / 1000.0) / 30.0;
    else
        rad.s = time.s / 30.0;

    // adjust hand angles
    Object.keys(rad).forEach(function(key, index) {
        rad[key] *= Math.PI;
        rad[key] -= Math.PI / 2.0;
    });

    // draw hands on canvas
    for(k in rad)
    {
        if (!(et !== undefined && k == "s"))
        {
            context.beginPath();
            context.strokeStyle = colours[k];
            context.lineWidth = hand_widths[k];
            context.moveTo(center.x, center.y);
            x = Math.cos(rad[k]) * hand_lengths[k] + center.x;
            y = Math.sin(rad[k]) * hand_lengths[k] + center.y;
            context.lineTo(x, y);
            context.stroke();
        }
    }
}

// reverse stuff
var now = new Date();

var speed = 1; // seconds to display one hour
var delta = -30; 
// rewind stuff

function reverse_time(time, delta)
{
    var h, m, s, d;

    time.s = (time.s + delta);
    if (time.s < 0)
    {
        time.m += Math.floor(time.s / 60);
        time.s += 60;
    }
    if (time.m < 0)
    {
        time.h += Math.floor(time.m / 60);
        time.m += 60;
        // time.m = 0;
    }
    if (time.h < 0)
        time.h += 12;
    
    return new Date(1991, 01, 25, time.h, time.m, time.s);
}

/*
function strike_hour()
{
    var tweets, header, container;
    var id, position, hand_lengths, reverse, pop;
    
    window.clearInterval(hand_timer);
    hand_timer = false;
    
    time = 
    {
        // this is kind of cheating, amiright?
        h: now.getHours() - 1,
        m: 59,
        s: 59,
        hs: now.getHours(),
        ms: 0,
        ss: 0,
        delta: - (3600 / (fr * speed))
    }
       
    container = document.getElementById("clock-container");
    container.id = "clock-container-big";
    tweets = document.getElementById("tweets");
    header = document.getElementsByTagName("HEADER")[0];
    tweets.classList.add("hidden");
    header.classList.add("hidden");
    pop = document.getElementById("pop");
    
    // this loop needs to be a timeout function
    pop.play();
    
    id = "clock-canvas";
    position = "centre";
    hand_lengths = true;
    reverse = true;
    
    init_clock(id, position, hand_lengths, reverse);
}

function unstrike_hour()
{
    var tweets, header, container;
    container = document.getElementById("clock-container-big");
    container.id = "clock-container";
    tweets = document.getElementById("tweets");
    header = document.getElementsByTagName("HEADER")[0];
    tweets.classList.remove("hidden");
    header.classList.remove("hidden");
    
    id = "clock-canvas";
    position = "lower-right";
    hand_lengths = true;
    reverse = false;
    
    init_clock(id, position, hand_lengths, reverse);
}

function strike()
{
    strike_hour();
    setTimeout(unstrike_hour, speed * 1000 + 5000);
}
*/

function strike()
{
    reverse = true;
}

function unstrike()
{
    reverse = false;
}
function set_strike()
{
    var now, mills;
    now = new Date();
    ms = new Date(  now.getFullYear(), 
                    now.getMonth(), 
                    now.getDate(), 
                    now.getHours(), 0, 0, 0) - now;
    if (ms < 0)
        ms += 3600000;
    ms = 1000*30;
    setTimeout(first_strike, 1000*10); 
}

function first_strike()
{
    strike();
    setInterval(strike, 1000*60*60);
}
