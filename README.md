fire
====

A modular PHP framework that I use for some of my own projects. When I get time,
I may extract some useful libraries out of this. I'll add more documentation down the line.

Here are some interesting things to look at:
- The router class
Routes similar to codeigniter's routes.

- The display class
Allows you to define .tpl.php files and quickly render views. Allows you to define objects to override the behavior of the view.

- The factory class
Allows you define all of the objects for use in the app. Objects can easily be constructed with a call to build(...)
all dependencies automatically get resolved

- The indexers
The indexers go through the entire project and build an index of where all of the files are, all of the php classes, their
arguments, and their methods, and where all the static resources are located. This allows components such as the class loader
and the asset library to perform a lot of magic loading.

- The class loader
No more require_once needed in this framework. Makes use of spl_autoload_register and the indexers to transparently load
required classes on demand

- The file repository
Allows you to store files with a simple API. The implementation can be swapped by changing the driver. For example, my
localhost uses a local file system storage driver. My production websites use an Amazon S3 driver.

- The image repository
Makes use of the file repository and provides an API for easily storing images. You can store multiple variations of a
picture. For example, the user uploads a picture, you could have a thumbnail automatically generated as a variation.

- The event dispatcher
Dispatches events throughout the system. Plugins can hook into the events and alter the behavior of the system.

- Asset library
Allows you to require resources like scripts and stylesheets into the document. Automatically resolves dependencies and
uses a cache busting technique based on the timestamp so you never have to clear your cache when making changes to these
files.