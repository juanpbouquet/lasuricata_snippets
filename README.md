lasuricata_snippets
===================

Some of the models, views and controllers developed for LaSuricata.com.

Please consider the files in this repo as a little showcase of what I did for this development. Neither the file structure or the PHP files themselves are complete or in working condition. They are all took from a working environment, but a lot of methods has been removed since they don't give any feedback of what I'm capable to code.

Classes
-------
They're basically Zend_Db_Table_Row extensions which is more or less the same way Doctrine works. What I left there are the methods that provided some functionalities to the site. They are the most specific part of the data layer, so I think that was the most relevant code to showcase. 

Layout
------
Basic layout file I'm using for LaSuricata, nothing fancy but since it's used in all views, I thought it was a good idea to let you see.

Models
------
I stripped out most of the content here, since functions were pretty simple in most cases, but I left some interesting ones, specially the Facebook's API integration and the resource uploader. The Facebook object you see around there is just the FB PHP SDK adapted by me for Zend Framework's file/classes structure.

Views
-----
Some of the views I use in the site, there aren't any comments, and they're not clean at all. LaSuricata.com is a work-in-progress and the views are the dirtier resource at the moment. I just wanted to showcase I can code HTML and CSS and implement jQuery and CSS3 frameworks like Twitter's Bootstrap.

All the code showcased here is up and running @ http://lasuricata.com

Hope you enjoyed, and feel free to leave any comments or suggestions.