<?php
// *Class to establish a connection to the database
class DatabaseConn
{
    //* Returns a connection object to a database
    public static function establishConn($param = array())
    {
        $dbConn = $param[0];
        $user = $param[1];
        $pass = $param[2];
        // *Set pdo variable as PHP Data Object with parameters passed
        $pdo = new PDO($dbConn, $user, $pass);
        // *Sets attributes on the database handles
        $pdo->setAttribute(
            // *Error reporting
            PDO::ATTR_ERRMODE,
            // *Throw exception if encountered
            PDO::ERRMODE_EXCEPTION
        );
        $pdo->setAttribute(
            // *Set default fetch mode
            PDO::ATTR_DEFAULT_FETCH_MODE,
            // *Tell PDO to return the result as an associative array.
            PDO::FETCH_ASSOC
        );
        return $pdo;
    }

    // *Runs the specified SQL query using the passed connection and the passed array of parameters (null if none)

    public static function runQuery($connection, $sql, $param = array())
    {
        // *Ensure parameters are in an array
        if (!is_array($param)) {
            $param = array($param);
        }
        // *Set null if not an array
        $statement = null;
        if (count($param) > 0) {
            // *Use a prepared statement if parameters
            $statement = $connection->prepare($sql);
            $executedOk = $statement->execute($param);
            if (!$executedOk) throw new PDOException;
        } else {
            // *Execute a normal query
            $statement = $connection->query($sql);
            if (!$statement) throw new PDOException;
        }
        return $statement;
    }
}

// *Class that processes paintings functions
class PaintingDB
{
    public function __construct($connection)
    {
        $this->pdo = $connection;
    }

    public function getPaintingforGal($galleryID)
    {
        $sql = getPaintingSQL() . " WHERE Paintings.GalleryID=?";
        $statement = DatabaseConn::runQuery($this->pdo, $sql, array($galleryID));
        return $statement->fetchAll();
    }

    public function getTop20()
    {
        $sql = getPaintingSQL();
        $sql = addSortAndLimit($sql);
        $statement = DatabaseConn::runQuery($this->pdo, $sql, null);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    function findPainting($search)
    {
        try {
            // $pdo = new PDO(DBCONNSTRING, DBUSER, DBPASS);
            // // $pdo = new PDO(DBCONNECTION, DBUSER, DBPASS);
            // $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sql = getPaintingSQL();
            $sql .= " WHERE PaintingID LIKE ?";
            // $statement = $pdo->prepare($sql);
            // *Estalbish connection and run query to return searched data
            $statement = DatabaseConn::runQuery($this->pdo, $sql, $search);
            // *Prevents code injection to the SQL database.
            $statement->bindValue(1, '%' . $search . '%');
            $statement->execute();
            $paintings = $statement->fetch(PDO::FETCH_ASSOC);
            $pdo = null;
            return $paintings;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    
    function findFavPainting($search)
    {
        try {
            // $pdo = new PDO(DBCONNSTRING, DBUSER, DBPASS);
            // // $pdo = new PDO(DBCONNECTION, DBUSER, DBPASS);
            // $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sql = getFavPaintingSQL();
            $sql .= " WHERE PaintingID LIKE ?";
            // $statement = $pdo->prepare($sql);
            // *Estalbish connection and run query to return searched data
            $statement = DatabaseConn::runQuery($this->pdo, $sql, $search);
            // *Prevents code injection to the SQL database.
            $statement->bindValue(1, '%' . $search . '%');
            $statement->execute();
            $paintings = $statement->fetch(PDO::FETCH_ASSOC);
            $pdo = null;
            return $paintings;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }
}

// SQL Strings related to paintings
function getPaintingSQL()
{
    $sql = "SELECT PaintingID, Paintings.ArtistID AS ArtistID, FirstName, LastName, Paintings.GalleryID as GalleryID, GalleryName, 
    ImageFileName, Title, ShapeID, MuseumLink, AccessionNumber, CopyrightText, Description, 
    Excerpt, YearOfWork, Width, Height, Medium, Cost, MSRP, GoogleLink, GoogleDescription, 
    WikiLink, JsonAnnotations FROM (( Paintings 
    INNER JOIN Artists ON Paintings.ArtistID = Artists.ArtistID )
    INNER JOIN Galleries ON Galleries.GalleryID = Paintings.GalleryID)";
    return $sql;
}

function getFavPaintingSQL()
{
    $sql = "SELECT PaintingID, ImageFileName, Title, FROM (( Paintings 
    INNER JOIN Artists ON Paintings.ArtistID = Artists.ArtistID )
    INNER JOIN Galleries ON Galleries.GalleryID = Paintings.GalleryID)";
    return $sql;
}

function addSortAndLimit($sqlOld)
{
    $sqlNew = $sqlOld . " ORDER BY YearOfWork limit 20";
    return $sqlNew;
}

// Creates the DOM elements to display information of a single painting. 
function displayPainting($painting, $visibility)
{
    echo "<div class='pImg'><img src='/img/paintings/full/" . $painting['ImageFileName'] . ".jpg' alt='" . $painting['Title'] . "' /></div>";
    echo "<div class='pInfo'>";
    echo "<h2>" . $painting['Title'] . "</h2>";
    createFavBtn($painting, $visibility);
    echo "<label>" . $painting['FirstName'] . " " . $painting['LastName'] . "</label>";
    echo "<label>" . $painting['GalleryName'] . ", " . $painting['YearOfWork'] . "</label>";
    echo "</div>";
    echo "<div class='pTabs'>";
    echo "<div id='pButtons'>";
    echo "<button class='btnDesc btnSelected'>Description</button>";
    echo "<button class='btnDet '>Detail</button>";
    echo "<button class='btnColr '>Color</button>";
    echo "</div>";
    echo "<div class='pDesc dBox btnSelected'>";
    echo "<p>" . $painting['Description'] . "</p>";
    echo "</div>";
    echo "<div class='pDet dBox '>";
    echo "<label>Medium</label><span>" . $painting['Medium'] . "</span>";
    echo "<label>Width</label><span>" . $painting['Width'] . "</span>";
    echo "<label>Height</label><span>" . $painting['Height'] . "</span>";
    echo "<label>Copyright</label><span>" . $painting['CopyrightText'] . "</span>";
    echo "<label>WikiLink</label><a href='" . $painting['WikiLink'] . "'>" . $painting['WikiLink'] . "</a>";
    echo "<label>Museum Page</label><a href='" . $painting['MuseumLink'] . "'>" . $painting['MuseumLink'] . "</a>";
    echo "</div>";
    echo "<div class='pColr dBox '>";
    echo "<div id='colorContainer'>";
    $JsonAnnotations = $painting['JsonAnnotations'];
    //encodes the string as an Array
    $arrayOfAnnotations = json_decode($JsonAnnotations, true);
    $dominantColors = $arrayOfAnnotations["dominantColors"];
    foreach ($dominantColors as $detail) {
        echo "<div class='squares'>";
        echo "<span>" . $detail['name'] . "</span> <span>" . $detail['web'] . "</span>" . "<div class='colorSquare' style='background-color: " . $detail['web'] . "'> </div>";
        echo "</div>";
    }
    echo "</div>";
    echo "</div>";
    echo "</div>";
}

function displayThumbPaint($array)
{
    echo  "<div class='thumbnailDiv'>";
    echo  "<a href='./single-painting.php?id=" . $array['id'] . "'><img src='./img/paintings/square/" . $array['ImageFileName'] . ".jpg' class='paintImg'/></a>";
    echo  "<div class='paintingName'>";
    echo  "<a href='./single-painting.php?id=" . $array['id'] . "'><span>" . $array['title'] . "</span></a>";
    echo  "<input type='checkbox' class='paintCheck' name='" . $array['id'] . "'>";
    echo  "</div>";
    echo  "</div>";
}

function createFavBtn($painting, $visibility)
{
    // *Creates the button to add painting to favorites.
    echo "<button class='btnAddFav' style='visibility:" . $visibility . "'><a href='addToFavorites.php?id=" . $painting['PaintingID'] . "&img=" . $painting['ImageFileName'] . "&title=" . $painting['Title'] . "&artist=" . $painting['ArtistID'] . "&gallery=" . $painting['GalleryID'] . "'>Add to Favorites</a></button>";
}

// if Session favorites array has a matching PaintingID then return true
function isFavorite($painting)
{
    $id = $painting['PaintingID'];
    $fav = $_SESSION['favorites'];
    // *converts  $_SESSION['favorites'] to a one dimentional array
    $searchArr = array_column($fav, 'id');
    // *Check if the painting exist
    if (in_array($id, $searchArr)) {
        return true;
    } else {
        return false;
    }
}


class loginDB
{
    public function __construct($connection)
    {
        $this->pdo = $connection;
    }

    function checkUser($search)
    {
        try {
            // $pdo = new PDO(DBCONNSTRING, DBUSER, DBPASS);
            // // $pdo = new PDO(DBCONNECTION, DBUSER, DBPASS);
            // $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sql = "SELECT * FROM customerlogon";
            $sql .= " WHERE UserName LIKE '%$search%'";
            // $statement = $pdo->prepare($sql);
            // *Estalbish connection and run query to return searched data
            $statement = DatabaseConn::runQuery($this->pdo, $sql, $search);
            // *Prevents code injection to the SQL database.
            $statement->bindValue(1, '%' . $search . '%');
            $statement->execute();
            $userVal = $statement->fetch(PDO::FETCH_ASSOC);
            $pdo = null;
            return $userVal;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    function findCustomer($search)
    {
        try {
            // $pdo = new PDO(DBCONNSTRING, DBUSER, DBPASS);
            // // $pdo = new PDO(DBCONNECTION, DBUSER, DBPASS);
            // $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sql = "SELECT * FROM customers";
            $sql .= " WHERE CustomerID LIKE '%$search%'";
            // $statement = $pdo->prepare($sql);
            // *Estalbish connection and run query to return searched data
            $statement = DatabaseConn::runQuery($this->pdo, $sql, $search);
            // *Prevents code injection to the SQL database.
            $statement->bindValue(1, '%' . $search . '%');
            $statement->execute();
            $userVal = $statement->fetch(PDO::FETCH_ASSOC);
            $pdo = null;
            return $userVal;
        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

}


function displayHome($userInfo){
    echo "<main class='homeMain'>";
    echo "<div class='homeWrapper'>";
    echo "<header class='headr'>";
    echo "<h1>Home Page</h1>";
    echo "</header>";
    echo " <div class='userInfo'>";
    echo "<h1>User Information</h1>";
    displayUserInfo($userInfo);
    echo "</div>";
    echo "<div class='searchBox'><input type='text' placeholder='SEARCH BOX FOR Paintings' name='pSearch'></div>";
    echo "<div class='favPaint'>";
    echo "<h1>Favorite Paintings</h1>";
    echo "<div class='paintItems'>";
    if (!isset($_SESSION['favorites'])) {
        echo "<h1>You don't have favorites set.</h2>";
    } else {
        foreach ($_SESSION['favorites'] as $fave) {
            displayThumbPaint($fave);
        }
    }
    echo "</div>";
    echo "</div>";
    echo "<div class='likePaint'>";
    echo "<h1>Paintings You May Like</h1>";
    echo "<div class='paintItems'>";
    displayRecomPaint();
    echo "< </div>";
    echo "<  </div>";
    echo "</main>";
}

function displayLogIn(){
    echo "<main id='indexPage'>";
    echo "<div class='heroWrapper1'>";
    echo "<form class='indexForm' action='login.php' method='post'>";
    echo "<div class='frmBtn'>";
    echo "<button type='submit'>Login</button>";
    echo "<button type='submit' formaction='signup.php'>Join</button>";
    echo "</div>";
    echo "<input type='text' placeholder='SEARCH BOX FOR Paintings' name='pSearch'>";
    echo "</form>";
    echo "<span id='imgCred'>Photos by <a href='https://unsplash.com/@deuxdoom?utm_source=unsplash&amp;utm_medium=referral&amp;utm_content=creditCopyText'>Eric Park</a> on <a href='https://unsplash.com/?utm_source=unsplash&amp;utm_medium=referral&amp;utm_content=creditCopyText'>Unsplash</a></span>";
    echo "</div>";
    echo "</main>";
}

function displayUserInfo($userInfo){
    $conn = DatabaseConn::establishConn(array(DBCONNSTRING, DBUSER, DBPASS));
    $dbConnect = new loginDB($conn);
    $userCred = $dbConnect->findCustomer($userInfo);
    echo "<div class='uInfoDiv'>";
    echo "<label>Name</label><span>" . $userCred['FirstName'] . "</span> <span>" . $userCred['LastName'] . "</span>";
    echo "<label>City</label><span>" . $userCred['City'] . "</span>";    
    echo "<label>Country</label><span>" . $userCred['Country'] . "</span>";
    echo "</div>";
}

function displayRecomPaint(){
    $conn = DatabaseConn::establishConn(array(DBCONNSTRING, DBUSER, DBPASS));
    $dbConnect = new PaintingDB($conn);
    $rPainting =  $dbConnect->getTop20();
    displayThumbPaint($rPainting);
}