# WordfulSolver
WordfulSolver is a webapp that can solve most levels of the popular Android and iOS game [Wordful](https://play.google.com/store/apps/details?id=com.smartupinc.games.wordful).

## Demo
To see the site and do a little solving on the side go to [wordfulsolver.com](http://wordfulsolver.com).

## Development
The WordfullSolver backend was developed using PHP. A dataset of 10,000 common words is stored in a MySQL database for fast word retrieval. 

The frontend was developed using HTML, CSS and JQuery. All post requests to the sever are done asynchronously using AJAX and return data in JSON format.

### Bugs
* Timeout issue with grids sizes of 5x5 or larger.
* Post issue using Chrome on mobile devices (possible caching issue).
