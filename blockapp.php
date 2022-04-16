<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="common.css">
    <link rel="stylesheet" type="text/css" href="app_content.css">

    <script src='https://ajax.aspnetcdn.com/ajax/jQuery/jquery-3.2.1.js'></script>
    <script type="text/javascript">

    var nb_emplacement = 3;

    function addBlock(myId) {
        $(document).ready(function() {
            $('#trame').append('<div class="bloc" id=' + myId + ' ondragenter="return dragEnter(event)" ondrop="return dragDrop(event)" ondragover="return dragOver(event)"><button class="delete" onclick="reset(' + myId + ')"><img src="https://cdn-icons-png.flaticon.com/512/64/64022.png" /></button></div>');
        });
    }
    </script>

</head>

<?php
// Définition des paramètres PHP 

$actions = [    // val min, val max, unité, type d'action, action
    "Démarrer" => array(null,null,null,"setting",""),
    "Avancer" => array(1,9,"s","movement","car.throttle = -0.5<br/>time.sleep(VAR)"),
    "Reculer" => array(1,9,"s","movement","car.throttle = 0.5<br/>time.sleep(VAR)"),
    "S'arrêter" => array(null,null,null,"movement","car.throttle = 0.001<br/>car.throttle = 0"),
    "Tourner à gauche" => array(null, null, null,"movement","car.steering = 1"),
    "Tourner à droite" => array(null, null, null,"movement","car.steering = -1"),
    "Reset direction" => array(null, null, null,"movement","car.steering = 0.001<br/>car.steering = 0"),
    "Tourner" => array(-35,35,"°","movement","car.steering = VAR/35"),
    "Attendre" => array(1,9,"s","setting","time.sleep(VAR)"),
    "Fin" => array(null,null,null,"setting",""),
    "Si" => array("test1","test2",null,"control","if VAR == true:"),
    "Sinon" => array(null,null,null,"control","else:"),
    "Fin du Si" => array(null,null,null,"control"," "),
];

$nb_emplacements = 5;

//$file = '/home/jetson/Desktop/KDesir_Tests/projet.py';
$file = '/KDesir_Tests/projet.py';
$client = '/KDesir_Tests/client-script.py';
//$file = 'C:\wamp\www\BlockApp\projet.py';
?>

<body>
    <h2 class="maintitle">Application de développement Python en programmation par bloc</h2>

    <!-- Affichage des blocks à drag -->
    <div class="emplacement" id="origin">
        <?php 
        $i = 100;
        foreach($actions as $key => $value){
            $i++;
            ?>
        <div id=<?=$i?> class=<?= $value[3] ?> draggable="true" ondragstart="return dragStart(event)">
            <p class="action"><?= $key ?>
            <?php
            if($value[0] != null){
                if($value[3] != "control"){
                echo '<input type="number" min="'.$value[0].'" max="'.$value[1].'" value="1">'.$value[2];
                }else{
                 echo "<select><option value=$value[0]>$value[0]</option><option value=$value[1]>$value[1]</option></select>";
                }
            }
            ?>
            </p>
        </div>
        <?php } ?>
    </div>

    <!-- Affichage des emplacements de drop -->
    <div class="emplacement" id="trame">
        <!--
        <div class="bloc" id=2 ondragenter="return dragEnter(event)" ondrop="return dragDrop(event)" ondragover="return dragOver(event)">
            <button class="delete" onclick="reset(2)">X</button>
        </div>-->
        <script type="text/javascript">
            for (i = 1; i <= nb_emplacement; i++) {
                addBlock(i);
            }
        </script>
    </div>

    <div class="resultat">
    <button class="submit" onclick="generate()">Valider</button>

    <form method="POST" > <!-- action="/\\n" pour empêcher de re-exécuter lorsqu'on rafraîchit -->
        <br/><input type="submit" name="sauvegarder" value="Exécuter" >
    </form>

	<?php 
		if(isset($_POST['sauvegarder'])) {
			$output = $_COOKIE['output'];
			$output = str_replace("<br/>","\n",$output);
			//$output = str_replace("\\n","\n",$output);
			$myfile = fopen($file, "w");
			fwrite($myfile, $output);
			fclose($myfile);

			//shell_exec('sudo python3 /KDesir_Tests/projet.py');
			echo shell_exec('sudo python3 /KDesir_Tests/client-script.py 2>&1'); //for debug
			//echo '<meta http-equiv="refresh" content="1; URL=blockapp.php" />';
		}
	?>
    <div class="section">
        <div class="big">
            <p class="title">Résultats :</p>
            <p><span id="result">. . .</span></p>
        </div>
    </div>
    
    </div>
</body>


<script type="text/javascript">
    // Reset du contenu des emplacements
    function reset(id) {
        //console.log(document.getElementById(id));
        node = document.getElementById(id);
        //console.log(node.childNodes[1]);
        if (node.childNodes[2]) {
            node.removeChild(node.childNodes[2]);
            node.removeChild(node.childNodes[2]);
        } else if (node.childNodes[1]) {
            node.removeChild(node.childNodes[1]);
        } else if (id > nb_emplacement) { // Si aucun élément n'existe dans la case (sauf la croix) et que la case a été créé par l'utilisateur
            document.getElementById(id).parentNode.removeChild(document.getElementById(id));
        }
    }

    // Drag & Drop

    function dragStart(ev) {
        ev.dataTransfer.effectAllowed = 'move';
        ev.dataTransfer.setData("Text", ev.target.getAttribute('id'));
        ev.dataTransfer.setData("Origin", ev.target.parentNode.getAttribute('id'))
        ev.dataTransfer.setDragImage(ev.target, 0, 0);
        return true;
    }

    function dragEnter(ev) {
        event.preventDefault();
        return true;
    }

    function dragOver(ev) {
        return false;
    }

    function dragDrop(ev) {

        event.preventDefault();

        var src = ev.dataTransfer.getData("Text");
        var tempo = document.createElement('span');
        tempo.className = 'hide';

        var origin_id = ev.dataTransfer.getData("Origin")
    
        if (!ev.target.id || ev.target.childNodes[0].nodeName == "\\ntext") {
            // Si on drop pas dans une case prévue à cet effet...
            console.log("Oups, vous n'avez pas posé le block dans un emplacement valide !");
            // Exemple : La croix, ou une case avec un block, ou un block
            return false;
        }

        //console.log(ev.target.parentNode.childElementCount)
        //console.log(src)
        //console.log(origin_id)
        //console.log(ev.target.id)
        //console.log(ev.target.childElementCount)
        if (ev.target.childElementCount == 1) {
            var copy = document.getElementById(src).cloneNode(true);
            copy.id = copy.id + "-copy";
            // On ne peut pas déplacer un élément copié
            // Pour cela, on rend la copie impossible à drag
            copy.draggable = false;
            copy.ondragstart = false;
            ev.target.appendChild(copy);
        }
        ev.stopPropagation();

        // Ajout d'un nouveau block
        if (document.getElementById(parseInt(ev.target.id) + 1) == null) {
            addBlock(parseInt(ev.target.id) + 1);
        }

        return false;
    }

    // Génération du code Python selon les actions choisies

    function generate() {
        actions_list = [] // liste des actions voulues
        var actions = <?php echo json_encode($actions); ?>; // Récupération de la liste des actions possibles en PHP

        indent = 0; // Défini le nombre d'espaces à indenter pour chaque ligne

        element = document.getElementsByClassName('bloc');
        //console.log(element)
        //console.log("element.length" + element.length)
        output = ""; // Code Python à ressortir


        for (var i = 0; i < element.length; i++) {
            //console.log(element[i]) // Liste des actions prises

            children = element[i].childNodes;
            //console.log(children)

            type_list = ["setting","movement","control"]

            for (var j = 0; j < children.length; j++) {
                if (type_list.includes(children[j].className) ) {

                    action = children[j].textContent.trim();
                    /*console.log("Tout: "+action);
                    console.log("Début: "+action.split(" ").slice(-1)[0]);*/
                    if(action.split(" ").slice(-1)[0].length == 1 || action.split(" ")[0] == "Si"){ // Si on a un espace 

                        if(typeof children[j].childNodes[1].childNodes[1] != "undefined"){
                        value = children[j].childNodes[1].childNodes[1].value; // La valeur dans l'input
                        } else{ value = null;}
                        //console.log("Valeur:",value);

                        //actions_list.push(children[j].id)
                        
                        action = action.split(" ")[0];
                        //console.log(action);
                    }
                    else{ 
                        value = null;
                    }
                    
                    console.log("action: " + action)
                    PythonScript = actions[action][4]; // Index du tableau

                    PythonScript = PythonScript.replace("VAR",value); // attribution de la variable

                    if(action == "Sinon"){ indent -= 1;}
                    

                    PythonScript = PythonScript.replaceAll("<br/>","<br/>"+"---".repeat(indent)); // Ajout des espaces

                    output += "---".repeat(indent) + PythonScript;            
                    output += "<br/>";

                    if(action == "Si" || action == "Sinon"){
                        indent += 1;
                    }else if(action == "Fin du Si"){
                        indent -= 1;
                    }

                    console.log("indent: "+indent)

                    //actions_list.push(text);
                }
            }
        }
        
        console.log(output.toString())
        output = output.replaceAll("---","\u0020\u0020\u0020");

        document.getElementById("result").innerHTML = output;
        document.cookie="output="+output.toString();
        console.log(output.toString())

    }

</script>

</html>
