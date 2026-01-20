# nfo2php
php rendering engine for ascii art nfo docs<br>
<a href="https://mobcat.zip/BadRepack/">Example Demo</a>

```
   ____       __     ______________     ____            ________         ____________      __          __    ____________
  /\\\\\     /\\\   /\\\\\\\\\\\\\\\   /\\\\\_         /\\\\\\\\\_      /\\\\\\\\\\\\\__  /\\\        /\\\  /\\\\\\\\\\\\\__           
  \/\\\\\\   \/\\\  \/\\\///////////  /\\\///\\\_     /\\\///////\\\    \/\\\/////////\\\ \/\\\       \/\\\ \/\\\/////////\\\        
   \/\\\/\\\  \/\\\  \/\\\________   /\\\/  \///\\\   \///      \//\\\   \/\\\_______\/\\\ \/\\\_______\/\\\ \/\\\_______\/\\\       
    \/\\\//\\\ \/\\\  \/\\\\\\\\\\\  /\\\      \//\\\            /\\\/    \/\\\\\\\\\\\\\/  \/\\\\\\\\\\\\\\\ \/\\\\\\\\\\\\\/       
     \/\\\\//\\\\/\\\  \/\\\///////  \/\\\       \/\\\         /\\\//      \/\\\/////////    \/\\\/////////\\\ \/\\\/////////        
      \/\\\ \//\\\/\\\  \/\\\         \//\\\      /\\\       /\\\//         \/\\\             \/\\\       \/\\\ \/\\\                
       \/\\\  \//\\\\\\  \/\\\          \///\\\__/\\\       /\\\/__________  \/\\\             \/\\\       \/\\\ \/\\\               
        \/\\\   \//\\\\\  \/\\\            \///\\\\\/       /\\\\\\\\\\\\\\\  \/\\\             \/\\\       \/\\\ \/\\\              
         \///     \/////   \///               \/////        \///////////////   \///              \///        \///  \///              
nfo2php 20250917
By MobCat

nfo2php is a simple rendering "engine" that will convert your nfo files and display them as text for a web page.
This conversion maintains the ansi formatting and spacing for the original file.
So all special chars get rendered correctly, for eg block letters like █ 
This conversion is done in place and on the fly, so the original nfo file is not edited or modified in any way.

[simple index.php mode]
├───In this mode if you download and place this index.php into your website then load it
│   you will get an error message "Config json 'index.json' not found." and a button to make a new config json
│   Click the button the and you will get a new error telling you that nfo2php cant find your nfo file.
│   Rename your nfo to index.nfo and upload it to the same folder as index.php and reload the page.
│   Your nfo file should now render as a normal web page.
│
[custom filename.php mode]
├───Same idea as above however, if you say rename this index.php to something like razor.php and likewise rename your
│   nfo to razor.nfo you can place and load more then one nfo file in a folder on your website.
│   Yes, this is not a really efficient way of using nfo2php, having a bunch of copies of it all over the place.
│   but its easy to setup and use this way. You can have a main index.php that links to all your nfos being rendered by nfo2php.
│
[editing the config.json]
├───From the above 2 modes, both times you have needed a config json, what does that do?
│   well this will let you custom define some more parameters of nfo2php like links and screen colors 
│   lets look at the default config
{
    "nfoPath": "razor1911.nfo",
    "textColor": "FFDF00",
    "screenColor": "222",
    "title": "nfo2php example",
    "description": "Your description for META tag goes here",
    "icon": "https://mobcat.zip/BadRepack/icon.png",
    "customLinks": {
        "MobCat.zip": ["https://mobcat.zip/map"," target=\'_blank\'"],
        "Example 2":  ["http://example.com/file.zip"," download"],
        "Example 3":  ["http://example.com",""],
    },
    "downloadEnabled": true,
    "watermark": true
}
│    "nfoPath": At the top we have the nfo file that will be loaded.
│    If you don't want to rename your nfo to the same name as nfo2php.
│
│   "textColor": The default text color in hex you want the nfo file to be rendered as
│    (Sadly right now nfo2php only supports nfo files and not multi color ansi files.)
│
│    "screenColor": Sets the background hex code color
│
│    "title": and "description:": Are sort of self explanatory.
│    They are the title that will appear in the title bar and in the META embed tag
│    The description is just for the META tag. Description can be left blank ""
│
│    "icon": this is the image that will show for the favicon and the META embed tag
│    You could leave this blank "" but it's not recommended.
│    The icon does have to be an absolute url path otherwise the website embedding your tag
│    wont know where to fetch the image from aka they dont know where /img/icon.png is on your site.
│
│    "customLinks": This is where it gets interesting. By default any links in your nfo like
│    https://github.com/MobCat will get automatically converted to a link that can be clicked
│    but if you want to custom define a link like see more info H͟e͟r͟e͟ or d͟o͟w͟n͟l͟o͟a͟d͟ ͟f͟i͟l͟e͟.͟z͟i͟p͟
│    You can add the text you want nfo2php to find and replace with a link.
│    The link is broken into 3 parts. the key, then in the array the link and the attribute.
│    The key is the text we want to find and modify. Try and keep it short and concise but unique though
│    so that nfo2php doesn't just modify every instance of cat in your nfo with a link.
│    the first part of the array is where you want your link to point to, it can be anywhere
│    if its to another website use https:// or http://. if its internal like another page on your website
│    use /folder/newPage.html
│    The final part of our array is the link attributes. This can be anything you want and is just appended
│    into the <a href="" attributesGoHere> part of the converted link. 2 common attributes are _blank
│    and download. the former will open the link in a new tab. the later will not open the link and will
│    attempt to download something. stops the page from flashing and reloading or going blank.
│    as this is just append into the href, you need to keep the space at the start, but it can also be
│    anything you can cram into an attribute. you wanna do inline styles, or add like 10 attributes?
│    Sure go nuts.
│    Ok last 2 configs, real quick now.
│    "downloadEnabled": if set to true will append a D͟o͟w͟n͟l͟o͟a͟d͟ ͟t͟h͟i͟s͟ ͟N͟F͟O͟ link to the bottom of the page
│    so the user can download the original nfo file to view with there own software.
│    "watermark": if set to true will place a little credit to myself at the bottom of the page.
│    This NFO file was rendered with n͟f͟o͟2͟p͟h͟p͟ by MobCat
│    I'd like it if you left this enabled, but it's your website not mine. So if you want to disable it
└────that's fine to.

[Cheat sheat:]
├────A cheat cheat for the advanced formating nfo2php can do
│    /*Italics a word*/ 
│    \*Italics the other way word*\
│    /^mirror a word^/
│    /+Bold a word +/ 
│    /-Strikeout a word-/ 
│    /_underline a word_/
│    /~corrupt a word~/ (click to view) It's like a spoiler tag from other platforms.  
│    /!Invert the colors of a word!/ 
│    
│    You can even use more then one format code at a time
│    /*/_/-ITALIC UNDERLINE SCRATCH*/_/-/
│    /^/+MIRROR BOLD+/^/ 
└────Don't forget you have some extra settings for links and embed meta tags in the config.json

[TODO:]
├────This was meant as a fun weekend project but as all my projects do, it got out of hand.
│    I'd like to add image rendering next, and find a way to decode and support colored ansi art
└────But I think for now, its "done". If you want more nfo art, see https://mobcat.zip/NFO_Browser/
```
