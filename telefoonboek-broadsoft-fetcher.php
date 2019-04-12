#!/usr/bin/php
<?php
	error_reporting(E_ALL);
	date_default_timezone_set('Europe/Amsterdam');

/*username = // Example user10@companyname.provider.voipit.nl
/*password = "password";   // SIP password or XSI directory password
/* Get the password from the users>some-user>address>profiles>files?respository download if unsure
/*

/* Fetch phonebook from Broadsoft server and create xml file in Tiptel Format*/
/* Ronald Brakeboer 2019 */

/* Step 1
Connect to Broadsoft server and import the data
*/ 
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://xsi.voipit.nl/com.broadsoft.xsi-actions/v2.0/user/USERNAME/directories/GroupCommon?format=json'); //Change username


curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_USERPWD, 'USERNAME' . ':' . 'PASSWORD'); //Change username (Same as above) and password

$headers = array();
$headers[] = 'Content-Type: application/json';
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$result = curl_exec($ch);
//print_r($result);
 if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}

/* Debug - Write to file

 $phonebookxml = "phonebook-imported.xml";  
 $phonebook_file = fopen ($phonebookxml, "w") or die (error_get_last()); 
  fwrite ($phonebook_file, json_encode($result) );
 fclose ($phonebook_file);
 if(file_exists($phonebookxml) && filesize($phonebookxml) > 10){
  error_log("Data received from Broadsoft server",0);
  echo  "Data received from Broadsoft server"; 
}
else
{
  error_log("Does phonebook-imported.xml exist?...exiting now",0);
  error_log("No DATA received from Broadsoft server...exiting now",0);
  echo  "NO DATA received from Broadsoft server..exiting now";
  exit();
} end debug */

curl_close ($ch); //End request to Broadsoft 

/* Step 2 - Grep name and phonenumber from the json */
/* First iterate over the Json */

$phonebook=array();
$jsondata=json_decode($result, true);
//print_r($jsondata);
foreach ($jsondata as $_key){
if (is_array($_key))
{
	foreach ($_key as $value){
if (is_array($value))
{
		foreach ($value as $_final => $_final_value){
if (is_array($_final_value))
{
$name=$_final_value['name']['$'] ;
$number=$_final_value['number']['$'];
$phonebook[]=array('name'=> $name, 'number'=> $number);
						}
					}
				}
			}
		}
	}


//print_r($phonebook);

/* Finally we create the XML */

    if(count($phonebook)){

/* Debug */
//print_r($phonebook); //So far so good!
printf("Array successfully build");
/* Debug end */

        createXMLfile($phonebook);
     }



/* Function to format the XML file so that the Tiptel phones can handle it*/

function createXMLfile($phonebook){

/* Het handigste is om een leeg bestand generated.phonebook.xml aan te maken en de juiste rechten toe te kennen.    
   De onderstaande regel moet aangepast worden in bijv. $filePath = 'C://DIRECTORY/ANDERE/DIRECTORY/generated.phonebook.xml'; onder windows
*/
	$filePath = '/var/www/html/telefoonboek/generated.phonebook.xml'; 
	
	$document = new DOMDocument("1.0","UTF-8");
	$root = $document->createElement('IPPhoneDirectory');
        $parent1 = $document->createElement('Title','Phonelist');
        $parent2= $document->createElement('Prompt','Prompt');
 	$root->appendChild($parent1);
 	$root->appendChild($parent2);
	$document->appendChild($root);

	for($i=0; $i<count($phonebook); $i++){

	$_Name      	 =  $phonebook[$i]['name'];
	$_phonenumber    =  $phonebook[$i]['number']; 

	$_Directory      =  $document->createElement('DirectoryEntry');
        $name            =  $document->createElement('Name', $_Name);
        $_Directory->appendChild($name); 
        $phonenumber     =  $document->createElement('Telephone', $_phonenumber);
        $_Directory->appendChild($phonenumber);
 	
	$root->appendChild($_Directory);
	}

 $document->appendChild($root);
 
  $document->save($filePath)or die("Error saving phonebook file. Check folder or permissions?");  
  echo "Saving changes";
//  echo $document->saveXML() . "\n";
} 
?>
