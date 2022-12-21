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
    // "Démarrer" => array(null,null,null,"setting",""),
    "Avancer" => array(null,null,null,"movement","car.throttle = -0.5"),
    "Reculer" => array(null,null,null,"movement","car.throttle = 0.5"),
    "S'arrêter" => array(null,null,null,"movement","car.throttle = 0.001<br/>car.throttle = 0"),
    "Tourner à gauche" => array(null, null, null,"movement","car.steering = 1"),
    "Tourner à droite" => array(null, null, null,"movement","car.steering = -1"),
    "Aller tout droit" => array(null, null, null,"movement","car.steering = 0.001<br/>car.steering = 0"),
    "Tourner" => array(-35,35,"°","movement","car.steering = VAR/35"),
    "Pendant" => array(1,9,"s","setting","time.sleep(VAR)"),
    // "Fin" => array(null,null,null,"setting",""),
    // "Si" => array("test1","test2",null,"control","if VAR == true :"),
    // "Sinon" => array(null,null,null,"control","else :"),
    // "Fin du Si" => array(null,null,null,"control"," "),
    "Faire" => array(2,9,"fois","control","for i in range(VAR) :"),
    "Fin du Faire" => array(null,null,null,"control"," "),
];

$nb_emplacements = 5;

//$file = '/home/jetson/Desktop/KDesir_Tests/projet.py';
$arretUrgence = "/KDesir_Tests/client-arret-urgence.py";
$client = '/KDesir_Tests/client-script.py';
// $file = 'C:\wamp\www\BlockApp\projet.py';
$file = '/KDesir_Tests/projet.py';

?>

<body>
    <h2 class="maintitle">Application de développement Python en programmation par bloc</h2>

    <div class='container'>
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
                    if(is_int($value[0]) && is_int($value[1])){
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
            <br/><input type="submit" name="arreter" style="background-color: rgba(118, 50, 60);" value="Arrêt d'urgence" >
            </form>

            <?php 
                if(isset($_POST['arreter'])) { 
                    echo shell_exec("sudo python3 "+$arretUrgence); //for debug
                }
                if(isset($_POST['sauvegarder'])) {
                    $output = $_COOKIE['output'];
                    $output = str_replace("<br/>","\n",$output);
                    //$output = str_replace("\\n","\n",$output);
                    $myfile = fopen($file, "w");
                    fwrite($myfile, $output);
                    fclose($myfile);

                    //shell_exec('sudo python3 /KDesir_Tests/projet.py');
                    echo shell_exec("sudo python3 $client 2>&1"); //for debug
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
    </div>
</body>


<script type="text/javascript">

    
    const indented = ['Si','Sinon','Faire']

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

        blocsAction = [...document.querySelectorAll('.bloc')];

        output = ""; // Code Python à ressortir

        type_list = ["setting","movement","control"]

        for (var i = 0; i < blocsAction.length; i++) {

            actionElement = blocsAction[i].querySelector('.action');

            if(!actionElement) continue

            // if the action is in a bloc without knew action type
            if(!type_list.includes(actionElement.parentNode.className) ) continue            

            action = actionElement.childNodes[0].textContent.trim();

            if(actionElement.childNodes[1]){
                actionVar = actionElement.childNodes[1].value; // La valeur dans l'input
            }else{ actionVar = null; }
            
            console.log("action : " + action)
            console.log("actionVar : " + actionVar)
            
            // Ecriture du script 
        
            PythonScript = actions[action][4]; // Index du tableau
            PythonScript = PythonScript.replace("VAR",actionVar); // attribution de la variable

            if(action == "Sinon"){ indent -= 1;}
            
            PythonScript = PythonScript.replaceAll("<br/>","<br/>"+"---".repeat(indent)); // Ajout des espaces

            output += "---".repeat(indent) + PythonScript;    
            output += "<br/>";

            if(indented.includes(action)){
                indent += 1;
            }else if(action == "Fin du Si" || action == "Fin du Faire"){
                indent -= 1;
            }
            // console.log("indent: "+indent)
        }
        
        output = output.replaceAll("---","\u0020\u0020\u0020");

        document.getElementById("result").innerHTML = output;
        document.cookie="output="+output.toString();
        // console.log(output.toString())

    }

</script>

</html>
