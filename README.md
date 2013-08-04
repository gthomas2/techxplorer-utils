# Techxplorer's Utility Scripts #
This repository contains a number of utility scripts that I have developed in the course of my work to make things easier. They are published here in the hope that they prove useful to others.

If you find these scripts useful please [let me know](http://thoughtsbytechxplorer.com/pages/who-am-i/#contactme), if you find they don't work or there are other issues please [add an issue](https://github.com/techxplorer/techxplorer-utils/issues) to let me know.

These scripts have been developed and tested on Mac OS X and as such should work for other Unix like operating systems. If you're working on Windows they're likely to not work. 

## Installation ##
1. Clone the repository or download the ZIP file
2. Install [Composer](http://getcomposer.org/)
3. Download and install the dependencies with Composer
4. Run the scripts

## Available Scripts ##

### FileCreator.php ###
The purpose of this script is to create a file of an arbitrary size containing random data. The source of the random data is the /dev/urandom file which is available on many systems excluding Windows. You can read more [about it here](http://en.wikipedia.org/wiki//dev/random).

The script expects two command line arguments:

1. --output (-o) the path to the output file
2. --size (-s) the size of the file in human readable format e.g. 22MB

More information on the script is available on [this blog post](http://thoughtsbytechxplorer.com/thoughts/2013/07/new-utility-file-creator).

### MdlUserListCreator.php ###
The purpose of this script is to create a randomly generated list of user records that can be uploaded into a [Moodle](https://moodle.org/) website. The data source for names are the top 100 male, female and last name from [this dataset](http://www.census.gov/genealogy/www/data/1990surnames/names_files.html) available from the [US Census Bureau](http://www.census.gov/).

The script expects the following command line arguments

1. --output (-o) the path to the output file
2. --number (-n) the number of user records to create
3. --domain (-d) the domain used to build email addresses
4. --course (-c) the short code of a course to enrol the new users (optional)

More information on the script is available on [this blog post](http://thoughtsbytechxplorer.com/thoughts/2013/07/new-utility-moodle-user-list-creator).

## Dependencies ##

The utility scripts use the following libraries, installed via Composer

1. [php-cli-tools](https://github.com/jlogsdon/php-cli-tools)