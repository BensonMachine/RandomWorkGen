<script>
    function loadDoc(url, cFunction) {
        var xhttp;
        xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                cFunction(this);}};
        xhttp.open("GET", url, true);
        xhttp.send();
    }
    function loadDocPost(url, param, cFunction) {
		var xhttp;
		xhttp = new XMLHttpRequest();
		xhttp.onreadystatechange = function() {
			if (this.readyState == 4 && this.status == 200) {
				cFunction(this);}};
		xhttp.open("POST", url, true);
		xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xhttp.send(param);
	}

	function charger_contenu(xhttp) {
		document.getElementById("contenu").innerHTML = xhttp.response;
	}
	function loadapi(xhttp){
		document.getElementById("divapi").innerHTML = xhttp.response;
		$('#lcarsmodal').modal("show")
	}
	function dependantback(xhttp){
		//here
		document.getElementById("dependantback").innerHTML = ("<hr>") + xhttp.response;
	}
</script>

<?php
include_once("./DynaDB.php");
session_start();


function randomFR($num){
   $wordarray = array();
   
   for ($i = 1; $i <= $num; $i++) {
       $randnum = rand(1,23052);
       $mots = $this->Select()->Table("mots")->Where("ID = '$randnum'")->QueryOne();
       array_push($wordarray, utf8_decode($mots->Mot));
   }
}

 


   function creategooglelink($term){
		return"
		<a href='' target='popup' onclick='window.open(\"http://www.larousse.fr/dictionnaires/francais/$term\",\"name\",\"width=600,height=400\")'><i class='fas fa-atlas'></i></a>
		<a href='' target='popup' onclick='window.open(\"https://www.google.com/search?tbm=isch&q=$term\",\"name\",\"width=600,height=400\")'><i class='fas fa-camera'></i></a>
		<a href='https://www.google.com/search?tbm=isch&q=$term' target='popup'></a>
		$term";
	}

?>

<div class='col-4'>
   <h1> Francais 1</h1> <hr>
   <div id="contentfr">
         <?php foreach($DynaMan->randomFR(15) as $fr) 
         h2(creategooglelink($fr)); ?>
   </div>
   <button onclick='loadDoc("api.php?lng=fr&nmb=15",charger_contenufr)'>Random Francais</button>
</div>