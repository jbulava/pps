var wsvg;
var digits;
var dots;
var numbers;

var segmentCounter = 0;
var scrollback = 10;
var prevDigit;
var prevSegment;

var wyoscanTimer = null;

var w;

function wyoscan_init(svgid)
{
    w = document.getElementById(svgid);
    
    // must wait for the svg to load to start animating
    w.addEventListener("load", wyoscan_listener);
    
    // numbers[][] holds which segments to show for each # at each position
    // why are there spaces for eight segments?
    numbers = new Array(10);
    for(var i = 0; i < numbers.length; i++)
        numbers[i] = new Array(8);

    numbers[0][0] = true;
    numbers[0][1] = true;
    numbers[0][2] = true;
    numbers[0][3] = false;
    numbers[0][4] = true;
    numbers[0][5] = true;
    numbers[0][6] = true;

    numbers[1][0] = false;
    numbers[1][1] = false;
    numbers[1][2] = true;
    numbers[1][3] = false;
    numbers[1][4] = false;
    numbers[1][5] = true;
    numbers[1][6] = false;

    numbers[2][0] = true;
    numbers[2][1] = false;
    numbers[2][2] = true;
    numbers[2][3] = true;
    numbers[2][4] = true;
    numbers[2][5] = false;
    numbers[2][6] = true;

    numbers[3][0] = true;
    numbers[3][1] = false;
    numbers[3][2] = true;
    numbers[3][3] = true;
    numbers[3][4] = false;
    numbers[3][5] = true;
    numbers[3][6] = true;  

    numbers[4][0] = false;
    numbers[4][1] = true;
    numbers[4][2] = true;
    numbers[4][3] = true;
    numbers[4][4] = false;
    numbers[4][5] = true;
    numbers[4][6] = false; 

    numbers[5][0] = true;
    numbers[5][1] = true;
    numbers[5][2] = false;
    numbers[5][3] = true;
    numbers[5][4] = false;
    numbers[5][5] = true;
    numbers[5][6] = true; 

    numbers[6][0] = true;
    numbers[6][1] = true;
    numbers[6][2] = false;
    numbers[6][3] = true;
    numbers[6][4] = true;
    numbers[6][5] = true;
    numbers[6][6] = true; 

    numbers[7][0] = true;
    numbers[7][1] = false;
    numbers[7][2] = true;
    numbers[7][3] = false;
    numbers[7][4] = false;
    numbers[7][5] = true;
    numbers[7][6] = false;  

    numbers[8][0] = true;
    numbers[8][1] = true;
    numbers[8][2] = true;
    numbers[8][3] = true;
    numbers[8][4] = true;
    numbers[8][5] = true;
    numbers[8][6] = true;   

    numbers[9][0] = true;
    numbers[9][1] = true;
    numbers[9][2] = true;
    numbers[9][3] = true;
    numbers[9][4] = false;
    numbers[9][5] = true;
    numbers[9][6] = true;
    
    /*
    // init animate scrollback arrays
    prevDigit = new Array(scrollback);
    prevSegment = new Array(scrollback);
    for(var i = 0; i < prevDigit.length; i++)
        prevDigit[i] = 0;
    for(var i = 0; i < prevSegment.length; i++)
        prevSegment[i] = 0;
    */
}

function wyoscan_listener()
{
    wsvg = w.contentDocument;
    
    // digits[][] holds the svg shapes that turn on & off
    digits = new Array(6);
    for (var i = 0; i < digits.length; i++)
    {
        digits[i] = new Array(7);
        for (var j = 0; j < digits[i].length; j++)
        {
            var s = i.toString() + '-' + j.toString();
            digits[i][j] = wsvg.getElementById(s);
        }
    }
    
    // hh:mm separators
    dots = wsvg.getElementById('dots');
    var all = wsvg.getElementsByTagName('g');
    for (var i = 0; i < all.length; i++)
    {
        all[i].setAttribute('fill', 'white');
    }
    
    wyoscanTimer = setInterval(animate, 50);
}

function stop_wyoscan()
{
    window.clearInterval(wyoscanTimer);
    w.removeEventListener("load", wyoscan_listener);
}

function animate()
{
    var d = new Date();
    var h = d.getHours();
    var m = d.getMinutes();
    var s = d.getSeconds();
    var hr = ("00" + h).slice(-2);
    var mn = ("00" + m).slice(-2);
    var sc = ("00" + s).slice(-2);
    var timeString = hr + mn + sc;
    
    // blink dots
    if(s % 2 == 0)
        dots.setAttribute('fill-opacity', '1.0');
    else
        dots.setAttribute('fill-opacity', '0.0');
    
    
    // animate
    var thisDigit = parseInt(segmentCounter % 42 / 7);
    var thisSegment = parseInt(segmentCounter % 7);
    var thisDigitValue = parseInt((timeString.charAt(thisDigit)));
    
    for (var digit = 0; digit < 6; digit++)
    {
        for (var seg = 0; seg < 7; seg++)
        {
            var dv = parseInt(timeString.charAt(digit));
            if (numbers[dv][seg])
                digits[digit][seg].setAttribute('fill-opacity', '1.0');
            else
                digits[digit][seg].setAttribute('fill-opacity', '0.0');
        }
    }
    
    /*
    if(numbers[thisDigitValue][thisSegment+1])
        digits[thisDigit][thisSegment].setAttribute('fill-opacity', '1.0');
    else
        digits[thisDigit][thisSegment].setAttribute('fill-opacity', '0.0');
    
    // clear previous segment
    digits[prevDigit[0]][prevSegment[0]].setAttribute('fill-opacity', '0.0');
    segmentCounter++;
    prevDigit.shift();
    prevDigit.push(thisDigit);
    prevSegment.shift();
    prevSegment.push(thisSegment);  
    */  
}