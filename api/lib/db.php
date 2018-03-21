<?php

function getDB()
{
	$dbData = getClientDBData();

	if( isset($dbData->dbname) )
	{
		$dbhost="localhost";
		$dbport= ( strpos($_SERVER['HTTP_HOST'], 'localhost') === false ? "5432" : "5432");
		$dbuser = $dbData->dbuser;
		$dbpass = $dbData->dbpass;
		$dbname = $dbData->dbname;
		$dbConnection = new PDO("pgsql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpass);
		$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $dbConnection;
	}
	else
	{
		echo "No user found!";
	}
}

function getMISDB()
{
	$dbhost="localhost";
	$dbport= ( strpos($_SERVER['HTTP_HOST'], 'localhost') === false ? "5432" : "5432");
	$dbuser="postgres";
	$dbpass="postgres";
	$dbname="eduweb_mis";
	$dbConnection = new PDO("pgsql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpass);
	$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	return $dbConnection;

}

function getClientDBData()
{
	$dbConnection = getMISDB();

	$subDomain = getSubDomain();
	$subDomain = 'eduweb';
	$sth = $dbConnection->prepare("SELECT client_id, dbusername, dbpassword FROM clients WHERE subdomain = :subDomain");
	$sth->execute(array(':subDomain' => $subDomain));
	$appData = $sth->fetch(PDO::FETCH_OBJ);

	$dbData = new stdClass();
	if( $appData )
	{
		$dbData->dbuser = $appData->dbusername;
		$dbData->dbpass = $appData->dbpassword;
		$dbData->dbname = "eduweb_" . $subDomain;
		$dbData->dbname = "eduweb_dev";
		$dbData->subdomain = $subDomain;
	}
	$dbConnection = null;
	return $dbData;
}

function getLoginDB()
{
	$dbhost="localhost";
	$dbport= ( strpos($_SERVER['HTTP_HOST'], 'localhost') === false ? "5432" : "5432");
	$dbuser = "postgres";
	$dbpass = "postgres";
	$dbname = "eduweb_mis";
	$dbConnection = new PDO("pgsql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpass);
	$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	return $dbConnection;
}


function setDBConnection($subDomain)
{
	$dbConnection = getMISDB();
	$sth = $dbConnection->prepare("SELECT client_id, dbusername, dbpassword FROM clients WHERE subdomain = :subDomain");
	$sth->execute(array(':subDomain' => $subDomain));
	$appData = $sth->fetch(PDO::FETCH_OBJ);

	$dbhost="localhost";
	$dbport= ( strpos($_SERVER['HTTP_HOST'], 'localhost') === false ? "5432" : "5432");
	$dbuser = $appData->dbusername;
	$dbpass = $appData->dbpassword;
	$dbname = "eduweb_" . $subDomain;
	$dbname = "eduweb_dev";
	$dbConnection = new PDO("pgsql:host=$dbhost;port=$dbport;dbname=$dbname", $dbuser, $dbpass);
	$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	return $dbConnection;
}

function getSubDomain()
{
	$referrer = ( isset($_SERVER['HTTP_X_SCHOOL_IDENTIFIER']) ? $_SERVER['HTTP_X_SCHOOL_IDENTIFIER'] : '');
	return $referrer;

	/*
	$referrer = ( isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'http://hog.eduweb.localhost/');
	$data = explode('.', $referrer); // Get the sub-domain here


	 Add some sanitization for $data

	if (!empty($data[0])) {
		$subdomain = substr($data[0], strpos($data[0], '//') + 2 ); // The * of *.mydummyapp.com will be now stored in $subdomain
	}
	return $subdomain;
	*/
}

?>
